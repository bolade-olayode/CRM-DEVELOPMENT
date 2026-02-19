<?php
/**
 * Order Success Page
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

if (isset($_SESSION['foodbank_checked'])) {
    $_SESSION['foodbank_checked'] = false;
}

$langs->load("admin");

$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);

if (!$user_is_beneficiary) {
    accessforbidden('You do not have access.');
}

$order_id = GETPOST('id', 'int');

// Get subscriber ID for ownership check
$sql_ben = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res_ben = $db->query($sql_ben);
if (!$res_ben || $db->num_rows($res_ben) == 0) {
    accessforbidden('Beneficiary profile not found.');
}
$subscriber_id = (int)$db->fetch_object($res_ben)->rowid;

// Get order details — only if it belongs to this subscriber
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE rowid = ".(int)$order_id." AND fk_beneficiary = ".$subscriber_id;
$res = $db->query($sql);
$order = $db->fetch_object($res);

if (!$order) {
    header('Location: dashboard_beneficiary.php');
    exit;
}

llxHeader('', 'Order Successful');

echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }
</style>';

print '<div style="width: 100%; padding: 30px; box-sizing: border-box; max-width: 800px; margin: 0 auto;">';

print '<div style="background: white; padding: 50px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';

print '<div style="font-size: 80px; margin-bottom: 20px;">✅</div>';

print '<h1 style="color: #28a745; margin: 0 0 15px 0;">Payment Successful!</h1>';

print '<p style="font-size: 18px; color: #666; margin: 0 0 30px 0;">Your order has been placed successfully.</p>';

print '<div style="background: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 30px; text-align: left;">';
print '<h3 style="margin: 0 0 15px 0;">Order Details</h3>';
print '<div style="display: flex; justify-content: space-between; margin-bottom: 10px;">';
print '<span><strong>Order Number:</strong></span>';
print '<span>'.dol_escape_htmltag($order->ref).'</span>';
print '</div>';
print '<div style="display: flex; justify-content: space-between; margin-bottom: 10px;">';
print '<span><strong>Date:</strong></span>';
print '<span>'.dol_print_date($db->jdate($order->date_distribution), 'day').'</span>';
print '</div>';
print '<div style="display: flex; justify-content: space-between; margin-bottom: 10px;">';
print '<span><strong>Total Amount:</strong></span>';
print '<span style="color: #28a745; font-weight: bold; font-size: 18px;">₦'.number_format($order->total_amount, 2).'</span>';
print '</div>';
print '<div style="display: flex; justify-content: space-between;">';
print '<span><strong>Status:</strong></span>';
print '<span style="color: #28a745; font-weight: bold;">'.$order->status.'</span>';
print '</div>';
print '</div>';

if (!empty($order->note)) {
    print '<div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin-bottom: 30px; text-align: left;">';
    print '<strong>📦 Tracking Information:</strong><br>';
    print nl2br(dol_escape_htmltag($order->note));
    print '</div>';
}

print '<div style="display: flex; gap: 15px; justify-content: center;">';
print '<a href="my_orders.php" class="butAction" style="margin: 0; padding: 12px 24px; font-size: 16px;">View My Orders</a>';
print '<a href="product_catalog.php" class="butAction" style="margin: 0; padding: 12px 24px; font-size: 16px; background: #6c757d;">Continue Shopping</a>';
print '</div>';

print '</div>';

print '</div>';

llxFooter();
?>
