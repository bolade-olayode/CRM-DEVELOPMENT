<?php
/**
 * Activate Subscription - SECURED
 * Verifies Paystack payment, checks auth + ownership + amount before activating
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
ob_start();

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

ob_clean();
header('Content-Type: application/json');

global $user, $db;

// --- 1. AUTH CHECK: User must be logged in ---
if (empty($user->id)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
    exit;
}

// --- 2. GET DATA ---
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['reference']) || !isset($input['tier']) || !isset($input['subscriber_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

$reference = $input['reference'];
$tier_type = $db->escape($input['tier']);
$subscriber_id = (int)$input['subscriber_id'];

// --- 3. OWNERSHIP CHECK: subscriber must belong to logged-in user ---
$sql_owner = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries
              WHERE rowid = ".$subscriber_id." AND fk_user = ".(int)$user->id;
$res_owner = $db->query($sql_owner);

if (!$res_owner || $db->num_rows($res_owner) == 0) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

// --- 4. VERIFY WITH PAYSTACK ---
$paystack_secret_key = getDolGlobalString('FOODBANK_PAYSTACK_SECRET_KEY');
if (empty($paystack_secret_key)) {
    echo json_encode(['status' => 'error', 'message' => 'Paystack is not configured. Contact admin.']);
    exit;
}

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $paystack_secret_key,
        "Cache-Control: no-cache",
    ],
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    dol_syslog("Paystack verification curl error: " . $err, LOG_ERR);
    echo json_encode(['status' => 'error', 'message' => 'Payment verification failed. Please contact support.']);
    exit;
}

$result = json_decode($response);

if (!$result || !isset($result->data) || $result->data->status !== 'success') {
    $paystack_msg = isset($result->message) ? $result->message : 'Verification failed';
    dol_syslog("Paystack verification failed for ref $reference: $paystack_msg", LOG_ERR);
    echo json_encode(['status' => 'error', 'message' => 'Payment could not be verified']);
    exit;
}

// --- 5. LOOK UP TIER & VERIFY AMOUNT ---
$sql_tier = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE tier_type = '".$tier_type."'";
$res_tier = $db->query($sql_tier);
$tier = $db->fetch_object($res_tier);

if (!$tier) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid subscription tier']);
    exit;
}

$amount_paid = $result->data->amount / 100; // Paystack returns kobo

if (abs($amount_paid - (float)$tier->price) > 1) {
    dol_syslog("Subscription amount mismatch for subscriber $subscriber_id: paid=$amount_paid expected=$tier->price", LOG_ERR);
    echo json_encode(['status' => 'error', 'message' => 'Payment amount does not match tier price']);
    exit;
}

// --- 6. ALL CHECKS PASSED: Activate Subscription ---
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+' . (int)$tier->duration_days . ' days'));

$db->begin();
try {
    $sql_update = "UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET
                   subscription_type = '".$tier_type."',
                   subscription_status = 'Active',
                   subscription_start_date = '".$db->escape($start_date)."',
                   subscription_end_date = '".$db->escape($end_date)."',
                   subscription_fee = ".(float)$tier->price.",
                   payment_method = 'Paystack',
                   last_payment_date = NOW()
                   WHERE rowid = ".$subscriber_id." AND fk_user = ".(int)$user->id;

    if (!$db->query($sql_update)) throw new Exception('Subscription update failed');

    // Prevent duplicate payment records
    $sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_payments WHERE payment_reference = '".$db->escape($reference)."'";
    $res_dup = $db->query($sql_check);
    if ($res_dup && $db->num_rows($res_dup) == 0) {
        $sql_payment = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_payments
                        (fk_subscriber, payment_type, amount, payment_method, payment_status,
                         payment_reference, payment_date, date_created)
                        VALUES (
                            ".$subscriber_id.",
                            'Subscription',
                            ".(float)$amount_paid.",
                            'Paystack',
                            'Success',
                            '".$db->escape($reference)."',
                            NOW(),
                            NOW()
                        )";
        if (!$db->query($sql_payment)) throw new Exception('Payment record failed');
    }

    $db->commit();
    echo json_encode(['status' => 'success', 'end_date' => $end_date]);

} catch (Exception $e) {
    $db->rollback();
    dol_syslog("Subscription activation failed for subscriber $subscriber_id: " . $e->getMessage(), LOG_ERR);
    echo json_encode(['status' => 'error', 'message' => 'Activation failed. Please contact support.']);
}
exit;
?>
