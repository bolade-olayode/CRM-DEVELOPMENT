<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

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
            setEventMessages('Your subscription is not active. Please renew to continue.', null, 'warnings');
            header('Location: renew_subscription.php');
            exit;
        }
    }
}

if (!$subscriber_id) {
    accessforbidden('You must be a subscriber to checkout.');
}

llxHeader('', 'Checkout');

echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }
</style>';

print '<div style="width: 100%; padding: 30px; box-sizing: border-box; max-width: 1400px; margin: 0 auto;">';

$sql = "SELECT c.*, p.name as package_name, p.ref as package_ref,
        (c.quantity * c.unit_price) as line_total
        FROM ".MAIN_DB_PREFIX."foodbank_cart c
        INNER JOIN ".MAIN_DB_PREFIX."foodbank_packages p ON c.fk_package = p.rowid
        WHERE c.fk_subscriber = ".(int)$subscriber_id;

$res = $db->query($sql);

if (!$res || $db->num_rows($res) == 0) {
    print '<div style="text-align: center; padding: 60px; background: white; border-radius: 8px;">';
    print '<div style="font-size: 64px; margin-bottom: 20px;">🛒</div>';
    print '<h2>Your cart is empty</h2>';
    print '<p style="color: #666;">Add some packages to your cart to continue.</p>';
    print '<br><a href="product_catalog.php" class="butAction">← Browse Packages</a>';
    print '</div>';
    print '</div>';
    llxFooter();
    exit;
}

$cart_items = array();
$grand_total = 0;

while ($obj = $db->fetch_object($res)) {
    $cart_items[] = $obj;
    $grand_total += $obj->line_total;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        print '<div class="error">Security check failed.</div>';
    } else {
        $delivery_address = GETPOST('delivery_address', 'restricthtml');
        $delivery_notes = GETPOST('delivery_notes', 'restricthtml');
        $payment_method = GETPOST('payment_method', 'alpha');
        
        if (empty($delivery_address)) {
            print '<div class="error">Delivery address is required.</div>';
        } elseif (empty($payment_method)) {
            print '<div class="error">Please select a payment method.</div>';
        } else {
            $db->begin();
            
            try {
                $ref = 'DIS'.date('Y').'-'.str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $payment_status = ($payment_method == 'pay_now') ? 'Pending' : 'Pay_On_Delivery';
                
                $sql_dist = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_distributions 
                            (ref, fk_beneficiary, date_distribution, note, status, 
                             payment_status, payment_method, total_amount, datec)
                            VALUES (
                                '".$db->escape($ref)."',
                                ".(int)$subscriber_id.",
                                NOW(),
                                '".$db->escape($delivery_address.' - '.$delivery_notes)."',
                                'Prepared',
                                '".$db->escape($payment_status)."',
                                '".$db->escape($payment_method)."',
                                ".(float)$grand_total.",
                                NOW()
                            )";
                
                if (!$db->query($sql_dist)) {
                    throw new Exception('Failed to create order: '.$db->lasterror());
                }
                
                $dist_id = $db->last_insert_id(MAIN_DB_PREFIX."foodbank_distributions");
                
                foreach ($cart_items as $item) {
                    $sql_items = "SELECT pi.product_name, pi.quantity, pi.unit
                                 FROM ".MAIN_DB_PREFIX."foodbank_package_items pi
                                 WHERE pi.fk_package = ".(int)$item->fk_package;
                    $res_items = $db->query($sql_items);
                    
                    while ($pkg_item = $db->fetch_object($res_items)) {
                        $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_distribution_lines 
                                (fk_distribution, fk_donation, product_name, quantity, unit) 
                                VALUES (
                                    ".(int)$dist_id.",
                                    NULL,
                                    '".$db->escape($pkg_item->product_name)."',
                                    ".((float)$pkg_item->quantity * (int)$item->quantity).",
                                    '".$db->escape($pkg_item->unit)."'
                                )";
                        
                        if (!$db->query($sql)) {
                            throw new Exception('Failed to add order item: '.$db->lasterror());
                        }
                    }
                }
                
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_payments 
                        (fk_subscriber, fk_order, payment_type, amount, payment_method, payment_status, payment_date) 
                        VALUES (
                            ".(int)$subscriber_id.",
                            ".(int)$dist_id.",
                            'Order',
                            ".(float)$grand_total.",
                            '".$db->escape($payment_method)."',
                            '".$db->escape($payment_status)."',
                            NOW()
                        )";
                
                if (!$db->query($sql)) {
                    throw new Exception('Failed to record payment: '.$db->lasterror());
                }
                
                $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_cart WHERE fk_subscriber = ".(int)$subscriber_id;
                $db->query($sql);
                
                $db->commit();
                
                if ($payment_method == 'pay_now') {
                    header('Location: order_confirmation.php?order_id='.$dist_id.'&payment=pending');
                } else {
                    header('Location: order_confirmation.php?order_id='.$dist_id);
                }
                exit;
                
            } catch (Exception $e) {
                $db->rollback();
                print '<div class="error">Error placing order: '.dol_escape_htmltag($e->getMessage()).'</div>';
            }
        }
    }
}

print '<h1 style="margin: 0 0 30px 0;">🛒 Checkout</h1>';
print '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">';
print '<div>';
print '<div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">';
print '<h2 style="margin: 0 0 20px 0;">📦 Order Summary</h2>';
print '<table style="width: 100%; border-collapse: collapse;">';
print '<thead style="background: #f8f9fa;"><tr>';
print '<th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Package</th>';
print '<th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Quantity</th>';
print '<th style="padding: 15px; text-align: right; border-bottom: 2px solid #dee2e6;">Price</th>';
print '<th style="padding: 15px; text-align: right; border-bottom: 2px solid #dee2e6;">Total</th>';
print '</tr></thead><tbody>';

foreach ($cart_items as $item) {
    print '<tr style="border-bottom: 1px solid #dee2e6;">';
    print '<td style="padding: 15px;"><strong>'.dol_escape_htmltag($item->package_name).'</strong></td>';
    print '<td style="padding: 15px; text-align: center;">'.$item->quantity.'</td>';
    print '<td style="padding: 15px; text-align: right;">₦'.number_format($item->unit_price, 2).'</td>';
    print '<td style="padding: 15px; text-align: right;"><strong>₦'.number_format($item->line_total, 2).'</strong></td>';
    print '</tr>';
}

print '<tr style="background: #f8f9fa;">';
print '<td colspan="3" style="padding: 20px; text-align: right;"><strong style="font-size: 18px;">Grand Total:</strong></td>';
print '<td style="padding: 20px; text-align: right;"><strong style="font-size: 24px; color: #28a745;">₦'.number_format($grand_total, 2).'</strong></td>';
print '</tr></tbody></table></div>';

print '<div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<h2 style="margin: 0 0 20px 0;">🚚 Delivery Information</h2>';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<div style="margin-bottom: 20px;">';
print '<label style="display: block; margin-bottom: 8px; font-weight: bold;"><span style="color: red;">*</span> Delivery Address</label>';
print '<textarea name="delivery_address" rows="4" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 15px;">'.dol_escape_htmltag($subscriber->address).'</textarea>';
print '<small style="color: #666;">Pre-filled from your profile.</small></div>';
print '<div style="margin-bottom: 20px;"><label style="display: block; margin-bottom: 8px; font-weight: bold;">Delivery Notes</label>';
print '<textarea name="delivery_notes" rows="3" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 15px;" placeholder="Gate code, delivery time..."></textarea></div>';
print '<div style="margin-bottom: 20px;"><label style="display: block; margin-bottom: 8px; font-weight: bold;">Contact Phone</label>';
print '<input type="text" value="'.dol_escape_htmltag($subscriber->phone).'" readonly style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 15px; background: #f8f9fa;"></div>';
print '</div></div>';

print '<div><div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: sticky; top: 20px;">';
print '<h2 style="margin: 0 0 20px 0;">💳 Payment Method</h2>';
print '<div style="margin-bottom: 15px;"><label style="display: block; padding: 20px; background: #f8f9fa; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer;">';
print '<input type="radio" name="payment_method" value="pay_now" required style="margin-right: 10px;"> <strong style="font-size: 16px;">Pay Now</strong>';
print '<div style="font-size: 13px; color: #666; margin-top: 8px; margin-left: 28px;">Paystack (Card/Bank)</div></label></div>';
print '<div style="margin-bottom: 25px;"><label style="display: block; padding: 20px; background: #f8f9fa; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer;">';
print '<input type="radio" name="payment_method" value="pay_on_delivery" required style="margin-right: 10px;"> <strong style="font-size: 16px;">Pay on Delivery</strong>';
print '<div style="font-size: 13px; color: #666; margin-top: 8px; margin-left: 28px;">Pay cash on delivery</div></label></div>';
print '<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">';
print '<div style="display: flex; justify-content: space-between; margin-bottom: 12px;"><span>Subtotal:</span><span>₦'.number_format($grand_total, 2).'</span></div>';
print '<div style="display: flex; justify-content: space-between; margin-bottom: 12px;"><span>Delivery:</span><span style="color: #28a745; font-weight: bold;">FREE</span></div>';
print '<hr style="margin: 15px 0;"><div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: bold;"><span>Total:</span><span style="color: #28a745;">₦'.number_format($grand_total, 2).'</span></div></div>';
print '<button type="submit" class="butAction" style="width: 100%; padding: 15px; font-size: 18px; margin: 0 0 15px 0;">Place Order</button>';
print '<div style="text-align: center;"><a href="view_cart.php" style="color: #666;">← Back to Cart</a></div>';
print '</form></div></div></div>';

print '<div style="margin-top: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px;">';
print '<h3 style="margin: 0 0 20px 0;">📋 What Happens Next?</h3>';
print '<ol style="margin: 0; padding-left: 20px; line-height: 2;">';
print '<li><strong>Confirmation:</strong> Immediate order confirmation</li>';
print '<li><strong>Preparation:</strong> Team prepares your order</li>';
print '<li><strong>Batching:</strong> Admin groups orders by region</li>';
print '<li><strong>Delivery:</strong> Delivered in 2-4 business days</li>';
print '</ol></div></div>';

llxFooter();
?>
