<?php
/**
 * Paystack Webhook Handler
 *
 * Register this URL in your Paystack Dashboard:
 *   Dashboard → Settings → API Keys & Webhooks → Webhook URL
 *   https://yoursite.com/htdocs/custom/foodbankcrm/core/pages/webhook_paystack.php
 *
 * This endpoint is called directly by Paystack's servers after a payment.
 * It verifies the signature, then updates the database without relying on
 * the subscriber's browser — so orders/subscriptions are always marked paid
 * even if the user closes their tab right after paying.
 */

// Tell Dolibarr this is a server-to-server call — no user session needed
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOHEADER'))       define('NOHEADER', 1);
if (!defined('NOLOGIN'))        define('NOLOGIN', 1);

// Read raw body BEFORE Dolibarr or PHP modifies the input stream
$raw_body = file_get_contents('php://input');

ob_start();
require_once dirname(__DIR__, 4) . '/main.inc.php';
ob_clean();

global $db, $conf;

// Always respond with JSON
header('Content-Type: application/json');

// ---------------------------------------------------------------------------
// STEP 1: Load the Paystack secret key
// ---------------------------------------------------------------------------
$paystack_secret = getDolGlobalString('FOODBANK_PAYSTACK_SECRET_KEY');

if (empty($paystack_secret)) {
    http_response_code(500);
    dol_syslog('FoodbankCRM Webhook: Paystack secret key not configured', LOG_ERR);
    echo json_encode(['error' => 'Webhook not configured on server']);
    exit;
}

// ---------------------------------------------------------------------------
// STEP 2: Verify the request genuinely came from Paystack
// Paystack signs every webhook with HMAC-SHA512 using your secret key.
// If the signature doesn't match, reject immediately — could be a forged request.
// ---------------------------------------------------------------------------
$sig_header = isset($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'])
    ? $_SERVER['HTTP_X_PAYSTACK_SIGNATURE']
    : '';

$expected_sig = hash_hmac('sha512', $raw_body, $paystack_secret);

if (!hash_equals($expected_sig, $sig_header)) {
    http_response_code(401);
    dol_syslog('FoodbankCRM Webhook: Invalid Paystack signature — possible forged request', LOG_WARNING);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// ---------------------------------------------------------------------------
// STEP 3: Parse the event payload
// ---------------------------------------------------------------------------
$event = json_decode($raw_body);

if (!$event || !isset($event->event) || !isset($event->data)) {
    http_response_code(400);
    dol_syslog('FoodbankCRM Webhook: Invalid JSON payload', LOG_WARNING);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

// Only act on successful charges — acknowledge everything else with 200
if ($event->event !== 'charge.success') {
    http_response_code(200);
    echo json_encode(['status' => 'ignored', 'event' => $event->event]);
    exit;
}

$data      = $event->data;
$reference = isset($data->reference) ? $data->reference : '';
$amount    = isset($data->amount)    ? ((float)$data->amount / 100) : 0; // kobo → naira
$metadata  = isset($data->metadata) ? $data->metadata : null;

if (empty($reference)) {
    http_response_code(400);
    echo json_encode(['error' => 'No reference in payload']);
    exit;
}

dol_syslog("FoodbankCRM Webhook: charge.success received — ref=$reference amount=$amount", LOG_INFO);

// ---------------------------------------------------------------------------
// STEP 4: Route by reference prefix and metadata
// Order payments:        ref starts with ORD-   metadata.order_id
// Subscription payments: ref starts with SUB-   metadata.subscriber_id + metadata.subscription_tier
// ---------------------------------------------------------------------------

// ===== ORDER PAYMENT =====
if (strpos($reference, 'ORD-') === 0 && $metadata && isset($metadata->order_id)) {

    $order_id = (int)$metadata->order_id;

    // Fetch order (ref added so we can include it in the payment receipt email)
    $sql = "SELECT rowid, ref, total_amount, payment_status
            FROM ".MAIN_DB_PREFIX."foodbank_distributions
            WHERE rowid = ".$order_id;
    $res = $db->query($sql);

    if (!$res || $db->num_rows($res) == 0) {
        http_response_code(404);
        dol_syslog("FoodbankCRM Webhook: Order $order_id not found (ref=$reference)", LOG_ERR);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    $order = $db->fetch_object($res);

    // Idempotency — already processed, just acknowledge
    if ($order->payment_status === 'Paid') {
        http_response_code(200);
        dol_syslog("FoodbankCRM Webhook: Order $order_id already marked paid — skipping (ref=$reference)", LOG_INFO);
        echo json_encode(['status' => 'already_processed']);
        exit;
    }

    // Amount integrity check
    if (abs($amount - (float)$order->total_amount) > 1) {
        http_response_code(400);
        dol_syslog("FoodbankCRM Webhook: Order $order_id amount mismatch — paid=$amount expected={$order->total_amount} (ref=$reference)", LOG_ERR);
        echo json_encode(['error' => 'Amount mismatch']);
        exit;
    }

    // Update database
    $db->begin();

    $sql_dist = "UPDATE ".MAIN_DB_PREFIX."foodbank_distributions
                 SET payment_status = 'Paid',
                     status = 'Prepared',
                     payment_reference = '".$db->escape($reference)."',
                     payment_gateway = 'Paystack',
                     payment_date = NOW()
                 WHERE rowid = ".$order_id;

    if ($db->query($sql_dist)) {
        // Keep payments table in sync too
        $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_payments
                    SET payment_status = 'Success', payment_date = NOW()
                    WHERE fk_order = ".$order_id);
        $db->commit();
        dol_syslog("FoodbankCRM Webhook: Order $order_id marked Paid via webhook (ref=$reference)", LOG_INFO);

        // Send payment receipt email to the subscriber (non-blocking)
        require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/foodbank_mailer.class.php';
        $sql_sub_wh = "SELECT b.* FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries b
                       INNER JOIN ".MAIN_DB_PREFIX."foodbank_distributions d ON d.fk_beneficiary = b.rowid
                       WHERE d.rowid = ".$order_id;
        $res_sub_wh = $db->query($sql_sub_wh);
        $sub_wh     = ($res_sub_wh && $db->num_rows($res_sub_wh) > 0) ? $db->fetch_object($res_sub_wh) : null;
        if ($sub_wh) {
            FoodbankMailer::sendPaymentReceipt($sub_wh, $order->ref, $order->total_amount, $reference);
        }

        http_response_code(200);
        echo json_encode(['status' => 'success', 'type' => 'order', 'order_id' => $order_id]);
    } else {
        $db->rollback();
        dol_syslog("FoodbankCRM Webhook: DB update failed for order $order_id — ".$db->lasterror(), LOG_ERR);
        http_response_code(500);
        echo json_encode(['error' => 'Database update failed']);
    }

// ===== SUBSCRIPTION PAYMENT =====
} elseif (strpos($reference, 'SUB-') === 0 && $metadata && isset($metadata->subscriber_id)) {

    $subscriber_id = (int)$metadata->subscriber_id;
    $tier_type     = isset($metadata->subscription_tier) ? (string)$metadata->subscription_tier : '';

    if (empty($tier_type)) {
        http_response_code(400);
        dol_syslog("FoodbankCRM Webhook: Missing subscription_tier in metadata (ref=$reference)", LOG_ERR);
        echo json_encode(['error' => 'Missing tier in metadata']);
        exit;
    }

    // Verify subscriber exists
    $sql_sub = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE rowid = ".$subscriber_id;
    if (!$db->query($sql_sub) || $db->num_rows($db->query($sql_sub)) == 0) {
        http_response_code(404);
        dol_syslog("FoodbankCRM Webhook: Subscriber $subscriber_id not found (ref=$reference)", LOG_ERR);
        echo json_encode(['error' => 'Subscriber not found']);
        exit;
    }

    // Idempotency — check if this reference was already processed
    $sql_dup = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_payments
                WHERE payment_reference = '".$db->escape($reference)."'";
    $res_dup = $db->query($sql_dup);
    if ($res_dup && $db->num_rows($res_dup) > 0) {
        http_response_code(200);
        dol_syslog("FoodbankCRM Webhook: Reference $reference already processed — skipping", LOG_INFO);
        echo json_encode(['status' => 'already_processed']);
        exit;
    }

    // Look up tier
    $sql_tier = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers
                 WHERE tier_type = '".$db->escape($tier_type)."' AND status = 'Active'";
    $res_tier = $db->query($sql_tier);
    $tier     = $db->fetch_object($res_tier);

    if (!$tier) {
        http_response_code(404);
        dol_syslog("FoodbankCRM Webhook: Tier '$tier_type' not found or not active (ref=$reference)", LOG_ERR);
        echo json_encode(['error' => 'Tier not found']);
        exit;
    }

    // Amount integrity check
    if (abs($amount - (float)$tier->price) > 1) {
        http_response_code(400);
        dol_syslog("FoodbankCRM Webhook: Sub amount mismatch for subscriber $subscriber_id — paid=$amount expected={$tier->price} (ref=$reference)", LOG_ERR);
        echo json_encode(['error' => 'Amount mismatch']);
        exit;
    }

    // Activate subscription
    $start_date = date('Y-m-d');
    $end_date   = date('Y-m-d', strtotime('+' . (int)$tier->duration_days . ' days'));

    $db->begin();
    try {
        $sql_act = "UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET
                    subscription_type = '".$db->escape($tier_type)."',
                    subscription_status = 'Active',
                    subscription_start_date = '".$start_date."',
                    subscription_end_date = '".$end_date."',
                    subscription_fee = ".(float)$tier->price.",
                    payment_method = 'Paystack',
                    last_payment_date = NOW()
                    WHERE rowid = ".$subscriber_id;

        if (!$db->query($sql_act)) {
            throw new Exception('Subscription update failed: '.$db->lasterror());
        }

        $sql_pay = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_payments
                    (fk_subscriber, payment_type, amount, payment_method, payment_status,
                     payment_reference, payment_date, date_created)
                    VALUES (
                        ".$subscriber_id.", 'Subscription', ".(float)$amount.",
                        'Paystack', 'Success',
                        '".$db->escape($reference)."', NOW(), NOW()
                    )";

        if (!$db->query($sql_pay)) {
            throw new Exception('Payment record failed: '.$db->lasterror());
        }

        $db->commit();
        dol_syslog("FoodbankCRM Webhook: Subscription activated for subscriber $subscriber_id tier=$tier_type until $end_date (ref=$reference)", LOG_INFO);

        // Send subscription activated email (non-blocking)
        require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/foodbank_mailer.class.php';
        $sql_sub_wh2 = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE rowid = ".$subscriber_id;
        $res_sub_wh2 = $db->query($sql_sub_wh2);
        $sub_wh2     = ($res_sub_wh2 && $db->num_rows($res_sub_wh2) > 0) ? $db->fetch_object($res_sub_wh2) : null;
        if ($sub_wh2) {
            FoodbankMailer::sendSubscriptionActivated($sub_wh2, $tier->tier_name, $end_date);
        }

        http_response_code(200);
        echo json_encode(['status' => 'success', 'type' => 'subscription', 'end_date' => $end_date]);

    } catch (Exception $e) {
        $db->rollback();
        dol_syslog("FoodbankCRM Webhook: Subscription activation failed for $subscriber_id — ".$e->getMessage(), LOG_ERR);
        http_response_code(500);
        echo json_encode(['error' => 'Activation failed']);
    }

// ===== MOBILE APP PAYMENT (reference starts with APP-) =====
} elseif (strpos($reference, 'APP-') === 0 && $metadata) {

    $type = isset($metadata->type) ? (string)$metadata->type : '';
    $bid  = isset($metadata->beneficiary_id) ? (int)$metadata->beneficiary_id : 0;

    // ── APP order ─────────────────────────────────────────────────────────────
    if ($type === 'order' && $bid > 0) {

        // Find the pending intent saved by payment.php
        $sql_intent = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_pending_orders
                       WHERE payment_reference = '".$db->escape($reference)."'
                         AND status = 'pending' LIMIT 1";
        $res_intent = $db->query($sql_intent);
        $intent     = ($res_intent && $db->num_rows($res_intent) > 0) ? $db->fetch_object($res_intent) : null;

        if (!$intent) {
            // Already handled or unknown
            http_response_code(200);
            echo json_encode(['status' => 'already_processed', 'type' => 'app_order']);
            exit;
        }

        $delivery_address = $intent->delivery_address;
        $notes            = $intent->notes ?? '';

        // Check if app already placed the order
        $sql_dup_ord = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_distributions
                        WHERE payment_reference = '".$db->escape($reference)."' LIMIT 1";
        $res_dup_ord = $db->query($sql_dup_ord);
        if ($res_dup_ord && $db->num_rows($res_dup_ord) > 0) {
            $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_pending_orders
                        SET status = 'completed'
                        WHERE payment_reference = '".$db->escape($reference)."'");
            http_response_code(200);
            echo json_encode(['status' => 'already_processed', 'type' => 'app_order']);
            exit;
        }

        // Get cart items
        $sql_cart = "SELECT c.fk_package, c.quantity, c.unit_price, p.name AS package_name
                     FROM ".MAIN_DB_PREFIX."foodbank_cart c
                     JOIN ".MAIN_DB_PREFIX."foodbank_packages p ON p.rowid = c.fk_package
                     WHERE c.fk_subscriber = ".$bid;
        $res_cart  = $db->query($sql_cart);

        if (!$res_cart || $db->num_rows($res_cart) == 0) {
            $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_pending_orders
                        SET status = 'completed'
                        WHERE payment_reference = '".$db->escape($reference)."'");
            http_response_code(200);
            echo json_encode(['status' => 'cart_empty']);
            exit;
        }

        $cart_items = [];
        $total      = 0;
        while ($ci = $db->fetch_object($res_cart)) {
            $cart_items[] = $ci;
            $total       += $ci->quantity * $ci->unit_price;
        }

        $ord_ref   = 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $full_note = $delivery_address . ($notes ? ' -- ' . $notes : '');

        $db->begin();
        try {
            $sql_dist = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_distributions
                         (ref, fk_beneficiary, status, total_amount, note,
                          payment_reference, payment_status, date_distribution, datec, tms)
                         VALUES (
                           '".$db->escape($ord_ref)."', ".$bid.", 'Prepared',
                           ".(float)$total.", '".$db->escape($full_note)."',
                           '".$db->escape($reference)."', 'Paid', NOW(), NOW(), NOW()
                         )";
            if (!$db->query($sql_dist)) throw new Exception($db->lasterror());
            $order_id = $db->last_insert_id(MAIN_DB_PREFIX.'foodbank_distributions', 'rowid');

            foreach ($cart_items as $ci) {
                $sql_pi = "SELECT product_name, quantity, unit
                           FROM ".MAIN_DB_PREFIX."foodbank_package_items
                           WHERE fk_package = ".(int)$ci->fk_package;
                $res_pi = $db->query($sql_pi);

                if ($res_pi && $db->num_rows($res_pi) > 0) {
                    while ($pi = $db->fetch_object($res_pi)) {
                        $line_qty = round($pi->quantity * $ci->quantity, 2);
                        $db->query("INSERT INTO ".MAIN_DB_PREFIX."foodbank_distribution_lines
                                    (fk_distribution, product_name, quantity, unit, note, datec)
                                    VALUES (".$order_id.", '".$db->escape($pi->product_name)."',
                                    ".$line_qty.", '".$db->escape($pi->unit)."',
                                    '".$db->escape($ci->package_name)."', NOW())");
                    }
                } else {
                    $db->query("INSERT INTO ".MAIN_DB_PREFIX."foodbank_distribution_lines
                                (fk_distribution, product_name, quantity, unit, datec)
                                VALUES (".$order_id.", '".$db->escape($ci->package_name)."',
                                ".(float)$ci->quantity.", 'unit', NOW())");
                }
            }

            $db->query("DELETE FROM ".MAIN_DB_PREFIX."foodbank_cart WHERE fk_subscriber = ".$bid);

            $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_pending_orders
                        SET status = 'completed'
                        WHERE payment_reference = '".$db->escape($reference)."'");

            $db->commit();
            dol_syslog("FoodbankCRM Webhook: APP order {$ord_ref} placed for beneficiary {$bid} (ref={$reference})", LOG_INFO);

            // Send receipt email
            require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/foodbank_mailer.class.php';
            $res_ben = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE rowid = ".$bid);
            $ben     = ($res_ben && $db->num_rows($res_ben) > 0) ? $db->fetch_object($res_ben) : null;
            if ($ben) {
                FoodbankMailer::sendPaymentReceipt($ben, $ord_ref, $total, $reference);
            }

            http_response_code(200);
            echo json_encode(['status' => 'success', 'type' => 'app_order', 'ref' => $ord_ref]);

        } catch (Exception $e) {
            $db->rollback();
            dol_syslog("FoodbankCRM Webhook: APP order placement failed for {$reference} — ".$e->getMessage(), LOG_ERR);
            http_response_code(500);
            echo json_encode(['error' => 'Order placement failed']);
        }

    // ── APP subscription ───────────────────────────────────────────────────────
    } elseif ($type === 'subscription' && $bid > 0) {

        $tier_id = isset($metadata->tier_id) ? (int)$metadata->tier_id : 0;
        if (!$tier_id) {
            http_response_code(200);
            echo json_encode(['status' => 'ignored', 'reason' => 'Missing tier_id']);
            exit;
        }

        // Idempotency check
        $res_dup = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_payments
                               WHERE payment_reference = '".$db->escape($reference)."'");
        if ($res_dup && $db->num_rows($res_dup) > 0) {
            http_response_code(200);
            echo json_encode(['status' => 'already_processed', 'type' => 'app_subscription']);
            exit;
        }

        $res_tier = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers
                                WHERE rowid = ".$tier_id." AND status = 'Active'");
        $tier     = ($res_tier && $db->num_rows($res_tier) > 0) ? $db->fetch_object($res_tier) : null;

        if (!$tier) {
            http_response_code(200);
            echo json_encode(['status' => 'ignored', 'reason' => 'Tier not found']);
            exit;
        }

        $start_date = date('Y-m-d');
        $end_date   = date('Y-m-d', strtotime('+' . (int)$tier->duration_days . ' days'));

        $db->begin();
        try {
            $sql_act = "UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET
                        subscription_type        = '".$db->escape($tier->tier_type)."',
                        subscription_status      = 'Active',
                        subscription_start_date  = '".$start_date."',
                        subscription_end_date    = '".$end_date."',
                        subscription_fee         = ".(float)$tier->price.",
                        max_orders_per_month     = ".($tier->max_orders_per_month !== null ? (int)$tier->max_orders_per_month : 'NULL').",
                        can_place_orders         = ".(int)($tier->can_place_orders ?? 1).",
                        payment_method           = 'Paystack',
                        last_payment_date        = NOW()
                        WHERE rowid = ".$bid;

            if (!$db->query($sql_act)) throw new Exception($db->lasterror());

            $sql_pay = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_payments
                        (fk_subscriber, payment_type, amount, payment_method, payment_status,
                         payment_reference, payment_date, date_created)
                        VALUES (
                          ".$bid.", 'Subscription', ".(float)$amount.",
                          'Paystack', 'Success',
                          '".$db->escape($reference)."', NOW(), NOW()
                        )";
            if (!$db->query($sql_pay)) throw new Exception($db->lasterror());

            $db->commit();
            dol_syslog("FoodbankCRM Webhook: APP subscription activated for beneficiary {$bid} tier={$tier->tier_type} until {$end_date} (ref={$reference})", LOG_INFO);

            // Send activation email
            require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/foodbank_mailer.class.php';
            $res_ben2 = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE rowid = ".$bid);
            $ben2     = ($res_ben2 && $db->num_rows($res_ben2) > 0) ? $db->fetch_object($res_ben2) : null;
            if ($ben2) {
                FoodbankMailer::sendSubscriptionActivated($ben2, $tier->tier_name, $end_date);
            }

            http_response_code(200);
            echo json_encode(['status' => 'success', 'type' => 'app_subscription', 'end_date' => $end_date]);

        } catch (Exception $e) {
            $db->rollback();
            dol_syslog("FoodbankCRM Webhook: APP subscription activation failed for {$bid} — ".$e->getMessage(), LOG_ERR);
            http_response_code(500);
            echo json_encode(['error' => 'Subscription activation failed']);
        }

    } else {
        http_response_code(200);
        echo json_encode(['status' => 'ignored', 'reason' => 'Unknown APP payment type']);
    }

// ===== UNRECOGNIZED =====
} else {
    http_response_code(200);
    dol_syslog("FoodbankCRM Webhook: Unrecognized reference format '$reference' — ignored", LOG_WARNING);
    echo json_encode(['status' => 'ignored', 'reason' => 'Unrecognized reference or missing metadata']);
}

exit;
?>
