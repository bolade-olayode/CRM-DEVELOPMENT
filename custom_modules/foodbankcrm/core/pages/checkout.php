<?php
/**
 * Checkout Page
 */
ob_start();

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

ob_clean();

global $user, $db, $conf;
$langs->load("admin");

// --- Auth & Subscriber ---
if (!FoodbankPermissions::isBeneficiary($user, $db)) {
    accessforbidden('You must be a subscriber to checkout.');
}

$sql_ben = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res_ben = $db->query($sql_ben);
if (!$res_ben || $db->num_rows($res_ben) == 0) {
    accessforbidden('Beneficiary profile not found.');
}
$subscriber    = $db->fetch_object($res_ben);
$subscriber_id = (int)$subscriber->rowid;

if ($subscriber->subscription_status != 'Active') {
    header('Location: renew_subscription.php'); exit;
}

// --- Fetch Cart ---
$cart_items  = [];
$grand_total = 0;

$sql_cart = "SELECT c.fk_package, c.quantity, c.unit_price,
             p.name, p.ref,
             SUM(pi.quantity * pi.unit_price) as calculated_price
             FROM ".MAIN_DB_PREFIX."foodbank_cart c
             LEFT JOIN ".MAIN_DB_PREFIX."foodbank_packages p ON c.fk_package = p.rowid
             LEFT JOIN ".MAIN_DB_PREFIX."foodbank_package_items pi ON p.rowid = pi.fk_package
             WHERE c.fk_subscriber = ".$subscriber_id."
             GROUP BY c.fk_package, c.quantity, c.unit_price, p.name, p.ref";
$res_cart = $db->query($sql_cart);

if ($res_cart) {
    while ($obj = $db->fetch_object($res_cart)) {
        $unit_price  = ($obj->calculated_price > 0) ? (float)$obj->calculated_price : (float)$obj->unit_price;
        $qty         = (int)$obj->quantity;
        $line_total  = $unit_price * $qty;
        $item        = new stdClass();
        $item->fk_package    = $obj->fk_package;
        $item->package_name  = $obj->name;
        $item->quantity      = $qty;
        $item->unit_price    = $unit_price;
        $item->line_total    = $line_total;
        $cart_items[]        = $item;
        $grand_total        += $line_total;
    }
}

// --- Determine saved delivery address for pre-fill ---
// Priority: 1) current POST (form error), 2) most recent order address, 3) profile address
$saved_address = trim($subscriber->address ?? '');
$saved_phone   = trim($subscriber->phone ?? '');
$address_source = $saved_address ? 'profile' : '';

// Check most recent order's note for a previously used delivery address
$sql_last = "SELECT note FROM ".MAIN_DB_PREFIX."foodbank_distributions
             WHERE fk_beneficiary = ".$subscriber_id."
             ORDER BY datec DESC LIMIT 1";
$res_last = $db->query($sql_last);
if ($res_last && $db->num_rows($res_last) > 0) {
    $last_order = $db->fetch_object($res_last);
    if (!empty($last_order->note)) {
        // note stores "delivery_address - delivery_notes" — extract just the address part
        $parts = explode(' -- ', $last_order->note, 2);
        if (!empty($parts[0])) {
            $saved_address  = trim($parts[0]);
            $address_source = 'last_order';
        }
    }
}

// --- Process Order Submission ---
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $error = 'Session expired. Please refresh the page and try again.';
    } else {
        $delivery_address = trim(GETPOST('delivery_address', 'restricthtml'));
        $delivery_notes   = trim(GETPOST('delivery_notes', 'restricthtml'));
        $delivery_phone   = trim(GETPOST('delivery_phone', 'alpha'));
        $payment_method   = GETPOST('payment_method', 'alpha');

        if (empty($delivery_address)) {
            $error = 'A delivery address is required.';
        } elseif (!in_array($payment_method, ['pay_now', 'pay_on_delivery'])) {
            $error = 'Please select a payment method.';
        } else {
            $db->begin();
            try {
                $ref            = 'DIS'.date('Y').'-'.str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $pay_status_txt = ($payment_method == 'pay_now') ? 'Pending' : 'Pay on Delivery';
                // Store address and notes separately with a reliable separator
                $full_note      = $delivery_address.($delivery_notes ? ' -- '.$delivery_notes : '');

                // 1. Create Distribution
                $sql_dist = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_distributions
                             (ref, fk_beneficiary, date_distribution, note, status,
                              payment_status, payment_method, total_amount, datec)
                             VALUES (
                                 '".$db->escape($ref)."',
                                 ".$subscriber_id.",
                                 NOW(),
                                 '".$db->escape($full_note)."',
                                 'Pending',
                                 '".$db->escape($pay_status_txt)."',
                                 '".$db->escape($payment_method)."',
                                 ".(float)$grand_total.",
                                 NOW()
                             )";
                if (!$db->query($sql_dist)) throw new Exception('DB Error (Dist): '.$db->lasterror());
                $dist_id = $db->last_insert_id(MAIN_DB_PREFIX."foodbank_distributions");

                // 2. Distribution Lines
                foreach ($cart_items as $item) {
                    $sql_pkg = "SELECT product_name, quantity, unit FROM ".MAIN_DB_PREFIX."foodbank_package_items WHERE fk_package = ".(int)$item->fk_package;
                    $res_pkg = $db->query($sql_pkg);
                    while ($pi = $db->fetch_object($res_pkg)) {
                        $line_qty = (float)$pi->quantity * (int)$item->quantity;
                        $sql_line = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_distribution_lines
                                    (fk_distribution, product_name, quantity, unit)
                                    VALUES (".$dist_id.", '".$db->escape($pi->product_name)."', ".$line_qty.", '".$db->escape($pi->unit)."')";
                        if (!$db->query($sql_line)) throw new Exception('DB Error (Line): '.$db->lasterror());
                    }
                }

                // 3. Payment Record
                $pay_rec_status = ($payment_method == 'pay_now') ? 'Pending' : 'Unpaid';
                $sql_pay = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_payments
                            (fk_subscriber, fk_order, payment_type, amount, payment_method, payment_status, payment_date)
                            VALUES (".$subscriber_id.", ".$dist_id.", 'Order', ".(float)$grand_total.", '".$db->escape($payment_method)."', '".$db->escape($pay_rec_status)."', NOW())";
                if (!$db->query($sql_pay)) throw new Exception('DB Error (Pay): '.$db->lasterror());

                // 4. AUTO-SAVE: Update profile with the delivery address & phone used
                //    Next checkout will pre-fill with this address automatically
                $sql_save = "UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET
                             address = '".$db->escape($delivery_address)."',
                             phone   = '".$db->escape($delivery_phone)."'
                             WHERE rowid = ".$subscriber_id;
                $db->query($sql_save); // Non-fatal if this fails

                // 5. Clear Cart
                $db->query("DELETE FROM ".MAIN_DB_PREFIX."foodbank_cart WHERE fk_subscriber = ".$subscriber_id);
                unset($_SESSION['cart']);

                $db->commit();

                // Send order confirmation email (non-blocking — never interrupts user flow)
                require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/foodbank_mailer.class.php';
                FoodbankMailer::sendOrderConfirmation(
                    $subscriber,
                    $ref,
                    $grand_total,
                    $cart_items,
                    ($payment_method === 'pay_on_delivery')
                );

                ob_end_clean();

                if ($payment_method == 'pay_now') {
                    header('Location: process_order_payment.php?order_id='.$dist_id);
                } else {
                    header('Location: order_confirmation.php?order_id='.$dist_id);
                }
                exit;

            } catch (Exception $e) {
                $db->rollback();
                $error = 'Order could not be placed: '.$e->getMessage();
            }
        }

        // On error — restore what the user typed so they don't re-enter everything
        if ($error) {
            $saved_address  = GETPOST('delivery_address', 'restricthtml');
            $saved_phone    = GETPOST('delivery_phone', 'alpha');
            $address_source = '';
        }
    }
}

ob_clean();
$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Checkout');
?>
<style>
    #id-top,.side-nav,.side-nav-vert,#id-left,.login_block,.tmenudiv,.nav-bar,header{display:none!important;width:0!important;height:0!important;pointer-events:none!important}
    html,body{background:#f0f4f8!important;margin:0!important;padding:0!important;width:100%!important;overflow-x:hidden!important;font-family:'Segoe UI',system-ui,sans-serif}
    #id-container,#id-right,.id-right{margin:0!important;padding:0!important;width:100vw!important;max-width:100vw!important;display:block!important}
    .fiche{width:100%!important;max-width:100%!important;margin:0!important}
    *{box-sizing:border-box}
    body{padding-top:64px!important}

    /* NAV */
    .fb-nav{position:fixed;top:0;left:0;right:0;height:64px;background:linear-gradient(90deg,#0f766e,#0d9488);display:flex;align-items:center;justify-content:space-between;padding:0 28px;z-index:9999;box-shadow:0 2px 16px rgba(0,0,0,.18)}
    .fb-nav-brand{font-size:17px;font-weight:800;color:#fff;text-decoration:none;display:flex;align-items:center;gap:9px}
    .fb-nav-links{display:flex;gap:2px}
    .fb-nav-link{color:rgba(255,255,255,.82);text-decoration:none;font-size:13px;font-weight:500;padding:7px 14px;border-radius:20px;transition:background .15s}
    .fb-nav-link:hover,.fb-nav-link.active{background:rgba(255,255,255,.18);color:#fff}
    .fb-nav-right{display:flex;align-items:center;gap:14px}
    .fb-logout{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.35);padding:6px 16px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600}

    /* PAGE */
    .page-wrap{max-width:1060px;margin:0 auto;padding:36px 24px 60px}

    /* HERO */
    .page-hero{background:linear-gradient(135deg,#134e4a,#0d9488);border-radius:16px;padding:26px 32px;margin-bottom:28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
    .page-hero h1{margin:0 0 4px;font-size:22px;font-weight:800;color:#fff}
    .page-hero p{margin:0;color:rgba(255,255,255,.72);font-size:13px}
    .checkout-steps{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600}
    .step{padding:5px 12px;border-radius:20px;color:rgba(255,255,255,.6)}
    .step.done{background:rgba(255,255,255,.15);color:#fff}
    .step.active{background:#fff;color:#0f766e}
    .step-sep{color:rgba(255,255,255,.4)}

    /* LAYOUT */
    .checkout-grid{display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start}
    @media(max-width:820px){.checkout-grid{grid-template-columns:1fr}}

    /* CARDS */
    .card{background:#fff;border-radius:14px;box-shadow:0 4px 16px rgba(0,0,0,.06);border:1px solid #e8edf2;overflow:hidden;margin-bottom:20px}
    .card:last-child{margin-bottom:0}
    .card-header{padding:16px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between}
    .card-header h3{margin:0;font-size:14px;font-weight:700;color:#1e293b}
    .card-body{padding:22px 24px}

    /* ORDER SUMMARY */
    .pkg-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f8fafc}
    .pkg-row:last-child{border-bottom:none}
    .pkg-icon{width:40px;height:40px;background:linear-gradient(135deg,#d1fae5,#a7f3d0);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
    .pkg-name{font-size:13px;font-weight:600;color:#1e293b;flex:1}
    .pkg-qty{font-size:12px;color:#94a3b8}
    .pkg-price{font-size:14px;font-weight:700;color:#0d9488;white-space:nowrap}

    /* FORM */
    .form-group{margin-bottom:18px}
    .form-group:last-child{margin-bottom:0}
    .form-label{display:flex;align-items:center;justify-content:space-between;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;margin-bottom:7px}
    .saved-badge{display:inline-flex;align-items:center;gap:4px;background:#d1fae5;color:#065f46;font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;text-transform:none;letter-spacing:0}
    .form-control{width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;color:#1e293b;font-family:inherit;transition:border-color .15s;resize:vertical}
    .form-control:focus{outline:none;border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.1)}
    .address-hint{font-size:11px;color:#94a3b8;margin-top:5px;display:flex;align-items:center;gap:4px}

    /* PAYMENT OPTIONS */
    .pay-option{display:flex;align-items:center;gap:14px;padding:16px 18px;border:2px solid #e2e8f0;border-radius:10px;cursor:pointer;transition:all .15s;margin-bottom:10px;background:#fff}
    .pay-option:last-of-type{margin-bottom:0}
    .pay-option:hover{border-color:#0d9488;background:#f0fdf9}
    .pay-option.selected{border-color:#0d9488;background:#f0fdf9;box-shadow:0 0 0 3px rgba(13,148,136,.1)}
    .pay-option input[type=radio]{display:none}
    .pay-radio-dot{width:20px;height:20px;border:2px solid #cbd5e1;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .15s}
    .pay-option.selected .pay-radio-dot{border-color:#0d9488;background:#0d9488}
    .pay-radio-inner{width:8px;height:8px;border-radius:50%;background:#fff;display:none}
    .pay-option.selected .pay-radio-inner{display:block}
    .pay-icon-wrap{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
    .pay-now-icon{background:linear-gradient(135deg,#dbeafe,#bfdbfe)}
    .pay-cod-icon{background:linear-gradient(135deg,#fef9c3,#fde68a)}
    .pay-title{font-size:14px;font-weight:700;color:#1e293b;margin-bottom:2px}
    .pay-sub{font-size:12px;color:#94a3b8}

    /* ORDER TOTAL (sticky right card) */
    .total-card{background:#fff;border-radius:14px;box-shadow:0 4px 16px rgba(0,0,0,.06);border:1px solid #e8edf2;overflow:hidden;position:sticky;top:80px}
    .total-header{padding:16px 22px;background:linear-gradient(135deg,#0f766e,#0d9488);color:#fff}
    .total-header h3{margin:0;font-size:14px;font-weight:700}
    .total-body{padding:20px 22px}
    .total-row{display:flex;justify-content:space-between;font-size:13px;color:#64748b;margin-bottom:10px}
    .total-divider{border:none;border-top:2px solid #f1f5f9;margin:14px 0}
    .total-grand{display:flex;justify-content:space-between;font-size:20px;font-weight:800;color:#0d9488}
    .btn-place{display:block;width:100%;padding:15px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:15px;font-weight:800;border:none;border-radius:10px;cursor:pointer;margin-top:18px;transition:all .2s;box-shadow:0 4px 14px rgba(16,185,129,.3);letter-spacing:.3px}
    .btn-place:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(16,185,129,.4)}
    .btn-back-cart{display:block;text-align:center;margin-top:12px;font-size:13px;color:#94a3b8;text-decoration:none}
    .btn-back-cart:hover{color:#64748b}
    .secure-note{display:flex;align-items:center;justify-content:center;gap:5px;font-size:11px;color:#94a3b8;margin-top:12px}

    /* ERROR */
    .error-banner{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:14px 18px;border-radius:10px;font-size:14px;font-weight:500;margin-bottom:22px;display:flex;align-items:center;gap:10px}

    /* EMPTY CART */
    .empty{text-align:center;padding:80px 20px;background:#fff;border-radius:16px;box-shadow:0 4px 16px rgba(0,0,0,.06)}
    .btn-shop{background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;padding:12px 32px;border-radius:30px;text-decoration:none;font-weight:700;display:inline-block;margin-top:20px}
</style>

<?php
// NAV
$cart_cnt_q   = $db->fetch_object($db->query("SELECT COUNT(*) as cnt FROM ".MAIN_DB_PREFIX."foodbank_cart WHERE fk_subscriber = ".$subscriber_id));
$cart_count   = $cart_cnt_q ? (int)$cart_cnt_q->cnt : 0;
$cart_badge   = $cart_count > 0 ? '<span style="background:#ef4444;color:#fff;font-size:10px;font-weight:800;padding:2px 6px;border-radius:10px;margin-left:2px">'.$cart_count.'</span>' : '';

print '<nav class="fb-nav">';
print '<a href="dashboard_beneficiary.php" class="fb-nav-brand">🥦 FoodbankCRM</a>';
print '<div class="fb-nav-links">';
print '<a href="product_catalog.php" class="fb-nav-link">Packages</a>';
print '<a href="view_cart.php" class="fb-nav-link">My Cart'.$cart_badge.'</a>';
print '<a href="my_orders.php" class="fb-nav-link">Orders</a>';
print '<a href="my_profile.php" class="fb-nav-link">Profile</a>';
print '</div>';
print '<div class="fb-nav-right">';
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="fb-logout">🚪 Logout</a>';
print '</div>';
print '</nav>';

print '<div class="page-wrap">';

// HERO
print '<div class="page-hero">';
print '<div><h1>Secure Checkout</h1><p>Review your order and complete your delivery details</p></div>';
print '<div class="checkout-steps">';
print '<span class="step done">🛒 Cart</span><span class="step-sep">›</span>';
print '<span class="step active">📋 Checkout</span><span class="step-sep">›</span>';
print '<span class="step">✅ Confirmation</span>';
print '</div>';
print '</div>';

// EMPTY CART
if (empty($cart_items)) {
    print '<div class="empty">';
    print '<div style="font-size:72px;margin-bottom:20px;opacity:.5">🛒</div>';
    print '<h2 style="margin:0 0 10px;color:#334155">Your cart is empty</h2>';
    print '<p style="color:#94a3b8;margin:0">Add some packages before checking out.</p>';
    print '<a href="product_catalog.php" class="btn-shop">Browse Packages →</a>';
    print '</div>';
    llxFooter(); ob_end_flush(); exit;
}

// ERROR BANNER
if ($error) {
    print '<div class="error-banner">⚠️ '.dol_escape_htmltag($error).'</div>';
}

// MAIN FORM (wraps both columns so payment method radios submit together)
print '<form method="POST" action="checkout.php" id="checkout-form">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<div class="checkout-grid">';

// ===== LEFT COLUMN =====
print '<div>';

// Order Summary
$pkg_emojis = ['📦','🥗','🥘','🍱','🧺','🥫','🫙','🥦'];
$ei = 0;
print '<div class="card">';
print '<div class="card-header"><h3>📦 Order Summary</h3><span style="font-size:12px;color:#94a3b8;font-weight:600;">'.count($cart_items).' Package'.( count($cart_items) != 1 ? 's' : '').'</span></div>';
print '<div class="card-body">';
foreach ($cart_items as $item) {
    $emoji = $pkg_emojis[$ei++ % count($pkg_emojis)];
    print '<div class="pkg-row">';
    print '<div class="pkg-icon">'.$emoji.'</div>';
    print '<div style="flex:1;min-width:0">';
    print '<div class="pkg-name">'.dol_escape_htmltag($item->package_name).'</div>';
    print '<div class="pkg-qty">Qty: '.$item->quantity.'</div>';
    print '</div>';
    print '<div class="pkg-price">₦'.number_format($item->line_total).'</div>';
    print '</div>';
}
print '</div></div>';

// Delivery Details
$src_label = '';
if ($address_source === 'last_order') {
    $src_label = '<span class="saved-badge">↩ From last order</span>';
} elseif ($address_source === 'profile') {
    $src_label = '<span class="saved-badge">↩ From your profile</span>';
}

print '<div class="card">';
print '<div class="card-header"><h3>🚚 Delivery Details</h3></div>';
print '<div class="card-body">';

// Delivery Address
print '<div class="form-group">';
print '<div class="form-label"><span>Delivery Address <span style="color:#ef4444">*</span></span>'.$src_label.'</div>';
print '<textarea name="delivery_address" rows="3" class="form-control" required placeholder="Enter your full delivery address...">'.dol_escape_htmltag($saved_address).'</textarea>';
print '<div class="address-hint">💾 This address will be saved and pre-filled on your next order.</div>';
print '</div>';

// Phone
print '<div class="form-group">';
print '<div class="form-label"><span>Contact Phone <span style="color:#ef4444">*</span></span></div>';
print '<input type="tel" name="delivery_phone" class="form-control" value="'.dol_escape_htmltag($saved_phone).'" required placeholder="Your phone number for delivery">';
print '</div>';

// Notes
print '<div class="form-group">';
print '<div class="form-label"><span>Delivery Notes</span><span style="font-size:10px;color:#94a3b8;text-transform:none;font-weight:400;letter-spacing:0">Optional</span></div>';
print '<textarea name="delivery_notes" rows="2" class="form-control" placeholder="e.g. Call when outside, leave at gate, ring bell..."></textarea>';
print '</div>';

print '</div></div>'; // card-body, card

// Payment Method
print '<div class="card">';
print '<div class="card-header"><h3>💳 Payment Method</h3></div>';
print '<div class="card-body">';

print '<label class="pay-option" id="opt-pay-now" onclick="selectPay(\'pay_now\')">';
print '<input type="radio" name="payment_method" value="pay_now" id="pay_now">';
print '<div class="pay-radio-dot"><div class="pay-radio-inner"></div></div>';
print '<div class="pay-icon-wrap pay-now-icon">💳</div>';
print '<div>';
print '<div class="pay-title">Pay Now with Paystack</div>';
print '<div class="pay-sub">Instant & secure — card, transfer, USSD</div>';
print '</div>';
print '</label>';

print '<label class="pay-option" id="opt-pay-cod" onclick="selectPay(\'pay_on_delivery\')">';
print '<input type="radio" name="payment_method" value="pay_on_delivery" id="pay_on_delivery">';
print '<div class="pay-radio-dot"><div class="pay-radio-inner"></div></div>';
print '<div class="pay-icon-wrap pay-cod-icon">💵</div>';
print '<div>';
print '<div class="pay-title">Pay on Delivery</div>';
print '<div class="pay-sub">Cash or transfer when your order arrives</div>';
print '</div>';
print '</label>';

print '</div></div>'; // card-body, card

print '</div>'; // left col

// ===== RIGHT COLUMN — sticky total card =====
print '<div>';
print '<div class="total-card">';
print '<div class="total-header"><h3>🧾 Order Total</h3></div>';
print '<div class="total-body">';
foreach ($cart_items as $item) {
    print '<div class="total-row">';
    print '<span>'.dol_escape_htmltag(dol_trunc($item->package_name, 20)).' ×'.$item->quantity.'</span>';
    print '<span>₦'.number_format($item->line_total).'</span>';
    print '</div>';
}
print '<hr class="total-divider">';
print '<div class="total-row"><span>Delivery</span><span style="color:#10b981;font-weight:700;">FREE</span></div>';
print '<hr class="total-divider">';
print '<div class="total-grand"><span>Total</span><span>₦'.number_format($grand_total).'</span></div>';
print '<button type="submit" class="btn-place" id="place-btn">Place Order →</button>';
print '<a href="view_cart.php" class="btn-back-cart">← Back to Cart</a>';
print '<div class="secure-note">🔒 Your details are secure & encrypted</div>';
print '</div>';
print '</div>'; // total-card
print '</div>'; // right col

print '</div>'; // checkout-grid
print '</form>';
print '</div>'; // page-wrap
?>

<script>
function selectPay(method) {
    document.querySelectorAll('.pay-option').forEach(function(el) {
        el.classList.remove('selected');
    });
    document.getElementById('opt-' + (method === 'pay_now' ? 'pay-now' : 'pay-cod')).classList.add('selected');
    document.getElementById(method).checked = true;

    // Update place order button label
    var btn = document.getElementById('place-btn');
    if (method === 'pay_now') {
        btn.textContent = '💳 Pay Now →';
    } else {
        btn.textContent = '📦 Place Order →';
    }
}

// Guard: require payment method selected before submit
document.getElementById('checkout-form').addEventListener('submit', function(e) {
    var selected = document.querySelector('input[name="payment_method"]:checked');
    if (!selected) {
        e.preventDefault();
        // Highlight the payment card
        document.querySelectorAll('.pay-option').forEach(function(el) {
            el.style.borderColor = '#ef4444';
        });
        window.scrollTo({ top: document.querySelector('.card:last-of-type').offsetTop - 100, behavior: 'smooth' });
    }
});

// Auto-select first option on click label (for browser compatibility)
document.querySelectorAll('.pay-option').forEach(function(label) {
    label.addEventListener('click', function() {
        document.querySelectorAll('.pay-option').forEach(function(el) {
            el.style.borderColor = '';
        });
    });
});
</script>

<?php
llxFooter();
ob_end_flush();
?>
