<?php
/**
 * Checkout Page - REFINED UI
 */

// 1. Output Buffering
ob_start();

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

// Wipe noise
ob_clean();

$langs->load("admin");

// --- USER CHECKS ---
$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);
$subscriber_id = null;
$subscriber = null;

if ($user_is_beneficiary) {
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
    $res = $db->query($sql);
    if ($res && $db->num_rows($res) > 0) {
        $subscriber = $db->fetch_object($res);
        $subscriber_id = $subscriber->rowid;
        
        if ($subscriber->subscription_status != 'Active') {
            header('Location: renew_subscription.php');
            exit;
        }
    }
}

if (!$subscriber_id) {
    accessforbidden('You must be a subscriber to checkout.');
}

// --- FETCH CART ---
$cart_items = array();
$grand_total = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $pkg_id => $qty) {
        $sql = "SELECT rowid, name, ref, description FROM ".MAIN_DB_PREFIX."foodbank_packages WHERE rowid = ".(int)$pkg_id;
        $res = $db->query($sql);
        if ($obj = $db->fetch_object($res)) {
            // Calculate real price from package items
            $sql_price = "SELECT SUM(pi.quantity * pi.unit_price) as total_price FROM ".MAIN_DB_PREFIX."foodbank_package_items pi WHERE pi.fk_package = ".(int)$pkg_id;
            $res_price = $db->query($sql_price);
            $obj_price = $db->fetch_object($res_price);
            $unit_price = ($obj_price && $obj_price->total_price) ? (float)$obj_price->total_price : 0;
            $line_total = $unit_price * $qty;
            
            $item = new stdClass();
            $item->fk_package = $pkg_id;
            $item->package_name = $obj->name;
            $item->quantity = $qty;
            $item->unit_price = $unit_price;
            $item->line_total = $line_total;
            
            $cart_items[] = $item;
            $grand_total += $line_total;
        }
    }
}

ob_clean();
llxHeader('', 'Checkout');

// --- UI CLEANUP (Hides Top Bar) ---
print '<style>
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header { display: none !important; }
    div.error, div[class*="error"], div[style*="background"][style*="255"] { display: none !important; }
    html, body { background-color: #f8f9fa !important; margin: 0; width: 100%; overflow-x: hidden; }
    #id-right, .id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
    #id-container { width: 100% !important; margin: 0 !important; display: block !important; }
    .fiche { width: 100% !important; max-width: 100% !important; margin: 0 !important; }
    .ben-container { width: 95%; max-width: 1400px; margin: 0 auto; padding: 40px 20px; font-family: "Segoe UI", sans-serif; }
    .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 20px; border: 1px solid #eee; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px; box-sizing: border-box; }
    .radio-card { display: block; padding: 20px; background: #f8f9fa; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer; margin-bottom: 15px; transition: all 0.2s; }
    .radio-card:hover { border-color: #667eea; }
    .radio-card input { margin-right: 10px; }
    .btn-checkout { background: #28a745; color: white; padding: 15px; width: 100%; border-radius: 8px; border: none; font-weight: bold; font-size: 18px; cursor: pointer; transition: background 0.2s; }
    .btn-checkout:hover { background: #218838; }
    .ben-error { background: #fee !important; color: #c00 !important; padding: 15px !important; border-radius: 6px !important; margin-bottom: 20px !important; border: 1px solid #fcc !important; font-weight: bold !important; display: block !important; }
</style>';

// --- JS CLEANER ---
print '<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll("div").forEach(div => {
        if(div.textContent.trim() === "0" && !div.classList.contains("ben-error")) {
            div.style.display = "none";
        }
    });
});
</script>';

if (empty($cart_items)) {
    print '<div class="ben-container" style="text-align: center; padding-top: 100px;">
            <div style="font-size: 80px; margin-bottom: 20px;">🛒</div>
            <h2 style="color: #555;">Your cart is empty</h2>
            <a href="product_catalog.php" class="button" style="background: #667eea; color: white; padding: 12px 30px; border-radius: 30px;">Browse Packages</a>
           </div>';
    llxFooter();
    exit;
}

// --- PROCESS ORDER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $error = 'Session expired. Please refresh.';
    } else {
        $delivery_address = GETPOST('delivery_address', 'restricthtml');
        $delivery_notes = GETPOST('delivery_notes', 'restricthtml');
        $payment_method = GETPOST('payment_method', 'alpha');
        
        if (empty($delivery_address)) {
            $error = 'Delivery address is required.';
        } elseif (empty($payment_method)) {
            $error = 'Please select a payment method.';
        } else {
            $db->begin();
            try {
                $ref = 'DIS'.date('Y').'-'.str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                $status = 'Pending';
                $pay_status_text = ($payment_method == 'pay_now') ? 'Pending' : 'Pay on Delivery';
                
                // 1. Create Distribution
                $sql_dist = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_distributions 
                            (ref, fk_beneficiary, date_distribution, note, status, 
                             payment_status, payment_method, total_amount, datec)
                            VALUES (
                                '".$db->escape($ref)."',
                                ".(int)$subscriber_id.",
                                NOW(),
                                '".$db->escape($delivery_address.' - '.$delivery_notes)."',
                                '".$db->escape($status)."',
                                '".$db->escape($pay_status_text)."',
                                '".$db->escape($payment_method)."',
                                ".(float)$grand_total.",
                                NOW()
                            )";
                
                if (!$db->query($sql_dist)) throw new Exception('DB Error (Dist): '.$db->lasterror());
                $dist_id = $db->last_insert_id(MAIN_DB_PREFIX."foodbank_distributions");
                
                // 2. Lines
                foreach ($cart_items as $item) {
                    $sql_items = "SELECT product_name, quantity, unit FROM ".MAIN_DB_PREFIX."foodbank_package_items WHERE fk_package = ".(int)$item->fk_package;
                    $res_items = $db->query($sql_items);
                    while ($pkg_item = $db->fetch_object($res_items)) {
                        $line_qty = (float)$pkg_item->quantity * (int)$item->quantity;
                        $sql_line = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_distribution_lines 
                                (fk_distribution, product_name, quantity, unit) 
                                VALUES (
                                    ".(int)$dist_id.",
                                    '".$db->escape($pkg_item->product_name)."',
                                    ".(float)$line_qty.",
                                    '".$db->escape($pkg_item->unit)."'
                                )";
                        if (!$db->query($sql_line)) throw new Exception('DB Error (Line): '.$db->lasterror());
                    }
                }
                
                // 3. Payment
                $pay_record_status = ($payment_method == 'pay_now') ? 'Pending' : 'Unpaid';
                $sql_pay = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_payments 
                        (fk_subscriber, fk_order, payment_type, amount, payment_method, payment_status, payment_date) 
                        VALUES (
                            ".(int)$subscriber_id.",
                            ".(int)$dist_id.",
                            'Order',
                            ".(float)$grand_total.",
                            '".$db->escape($payment_method)."',
                            '".$db->escape($pay_record_status)."',
                            NOW()
                        )";
                if (!$db->query($sql_pay)) throw new Exception('DB Error (Pay): '.$db->lasterror());
                
                // 4. Clear Cart
                unset($_SESSION['cart']);
                $db->query("DELETE FROM ".MAIN_DB_PREFIX."foodbank_cart WHERE fk_subscriber = ".(int)$subscriber_id);
                
                $db->commit();
                ob_end_clean();
                
                // REDIRECTS
                if ($payment_method == 'pay_now') {
                    header('Location: process_order_payment.php?order_id='.$dist_id);
                } else {
                    header('Location: order_confirmation.php?order_id='.$dist_id);
                }
                exit;
                
            } catch (Exception $e) {
                $db->rollback();
                $error = 'System Error: '.$e->getMessage();
            }
        }
    }
}

// --- VIEW ---
print '<div class="ben-container">';

if (isset($error)) {
    print '<div class="ben-error">⚠️ '.dol_escape_htmltag($error).'</div>';
}

print '<h1 style="margin: 0 0 30px 0; color:#2c3e50;">🛒 Secure Checkout</h1>';
print '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">';

print '<div><div class="card">';
print '<h3 style="margin: 0 0 20px 0; border-bottom:1px solid #eee; padding-bottom:10px;">📦 Order Summary</h3>';
print '<table style="width: 100%; border-collapse: collapse;"><thead><tr style="color:#888; font-size:14px;"><th style="text-align:left; padding:10px;">Package</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Total</th></tr></thead><tbody>';
foreach ($cart_items as $item) {
    print '<tr style="border-bottom: 1px solid #f0f0f0;"><td style="padding: 15px 10px;"><strong>'.dol_escape_htmltag($item->package_name).'</strong></td><td style="padding: 15px 10px; text-align: center;">'.$item->quantity.'</td><td style="padding: 15px 10px; text-align: right;">₦'.number_format($item->line_total, 2).'</td></tr>';
}
print '</tbody></table></div>';

print '<div class="card">';
print '<h3 style="margin: 0 0 20px 0; border-bottom:1px solid #eee; padding-bottom:10px;">🚚 Delivery Details</h3>';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<div style="margin-bottom: 15px;"><label style="display: block; margin-bottom: 8px; font-weight: bold;">Delivery Address <span style="color:red">*</span></label>';
print '<textarea name="delivery_address" rows="3" required class="form-control">'.dol_escape_htmltag($subscriber->address).'</textarea></div>';
print '<div style="margin-bottom: 15px;"><label style="display: block; margin-bottom: 8px; font-weight: bold;">Notes</label>';
print '<textarea name="delivery_notes" rows="2" class="form-control" placeholder="E.g. Call when outside..."></textarea></div>';
print '<div style="margin-bottom: 15px;"><label style="display: block; margin-bottom: 8px; font-weight: bold;">Phone</label>';
print '<input type="text" value="'.dol_escape_htmltag($subscriber->phone).'" readonly class="form-control" style="background:#f0f0f0;"></div>';
print '</div></div>';

print '<div><div class="card" style="position: sticky; top: 20px;">';
print '<h3 style="margin: 0 0 20px 0;">💳 Payment</h3>';
print '<label class="radio-card"><input type="radio" name="payment_method" value="pay_now" required> <strong style="font-size: 16px;">Pay Now</strong><div style="font-size: 13px; color: #666; margin-top: 5px; margin-left: 24px;">Secure Payment (Paystack)</div></label>';
print '<label class="radio-card"><input type="radio" name="payment_method" value="pay_on_delivery" required> <strong style="font-size: 16px;">Pay on Delivery</strong><div style="font-size: 13px; color: #666; margin-top: 5px; margin-left: 24px;">Cash or Transfer upon arrival</div></label>';
print '<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">';
print '<div style="display: flex; justify-content: space-between; margin-bottom: 10px;"><span>Subtotal:</span><span>₦'.number_format($grand_total, 2).'</span></div>';
print '<div style="display: flex; justify-content: space-between; margin-bottom: 10px;"><span>Delivery:</span><span style="color: #28a745; font-weight: bold;">FREE</span></div>';
print '<hr style="margin: 15px 0; border-color:#e0e0e0;"><div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: bold; color:#2c3e50;"><span>Total:</span><span>₦'.number_format($grand_total, 2).'</span></div></div>';
print '<button type="submit" class="btn-checkout">Place Order</button>';
print '<div style="text-align: center; margin-top:15px;"><a href="view_cart.php" style="color: #666; text-decoration:none;">← Back to Cart</a></div>';
print '</div></div>';

print '</form></div>';
llxFooter();
ob_end_flush();
?>