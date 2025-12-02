<?php
/**
 * View Order Details (Subscriber)
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

// Reset redirect flag
if (isset($_SESSION['foodbank_checked'])) {
    $_SESSION['foodbank_checked'] = false;
}

$langs->load("admin");

// Security check
$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);

if (!$user_is_beneficiary) {
    accessforbidden('You do not have access to view orders.');
}

// Get order ID
$order_id = GETPOST('id', 'int');

if (empty($order_id)) {
    header('Location: my_orders.php');
    exit;
}

// Get beneficiary ID
$sql_ben = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res_ben = $db->query($sql_ben);
$beneficiary = $db->fetch_object($res_ben);
$subscriber_id = $beneficiary->rowid;

// Get order details
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_distributions 
        WHERE rowid = ".(int)$order_id." 
        AND fk_beneficiary = ".(int)$subscriber_id;
$res = $db->query($sql);

if (!$res || $db->num_rows($res) == 0) {
    llxHeader('', 'Order Not Found');
    print '<div class="error">Order not found or you do not have permission to view it.</div>';
    print '<a href="my_orders.php" class="butAction">← Back to My Orders</a>';
    llxFooter();
    exit;
}

$order = $db->fetch_object($res);

llxHeader('', 'Order Details');

// Hide left menu and make FULL WIDTH
echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }
</style>';

print '<div style="width: 100%; padding: 30px; box-sizing: border-box; max-width: 1400px; margin: 0 auto;">';

print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<h1 style="margin: 0;">📦 Order Details: '.dol_escape_htmltag($order->ref).'</h1>';
print '<a href="my_orders.php" class="butAction">← Back to My Orders</a>';
print '</div>';

// Status color
$status_colors = array(
    'Pending' => '#ffc107',
    'Prepared' => '#17a2b8',
    'Bundled' => '#6f42c1',
    'Picked Up' => '#fd7e14',
    'In Transit' => '#007bff',
    'Delivered' => '#28a745'
);
$color = $status_colors[$order->status] ?? '#6c757d';

// Order Tracking
print '<div style="background: white; padding: 30px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<h2 style="margin: 0 0 30px 0;">📍 Order Tracking</h2>';

// Simple status timeline
$statuses = array('Pending', 'Bundled', 'Picked Up', 'In Transit', 'Delivered');
$current_index = array_search($order->status, $statuses);
if ($current_index === false) $current_index = 0;

print '<div style="display: flex; justify-content: space-between; position: relative; margin: 40px 0;">';

// Progress line
print '<div style="position: absolute; top: 20px; left: 0; right: 0; height: 4px; background: #e0e0e0; z-index: 0;"></div>';
print '<div style="position: absolute; top: 20px; left: 0; width: '.($current_index * 25).'%; height: 4px; background: '.$color.'; z-index: 0; transition: width 0.5s;"></div>';

foreach ($statuses as $index => $status) {
    $is_current = ($status == $order->status);
    $is_completed = ($index <= $current_index);
    
    $bg_color = $is_completed ? $color : '#e0e0e0';
    $text_color = $is_completed ? '#333' : '#999';
    
    print '<div style="text-align: center; z-index: 1; flex: 1;">';
    print '<div style="width: 40px; height: 40px; margin: 0 auto 10px; background: '.$bg_color.'; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">';
    print '<span style="font-size: 20px;">'.($is_completed ? '✓' : '○').'</span>';
    print '</div>';
    print '<div style="font-size: 12px; font-weight: '.($is_current ? 'bold' : 'normal').'; color: '.$text_color.';">'.dol_escape_htmltag($status).'</div>';
    print '</div>';
}

print '</div>';
print '</div>';

// Two columns layout
print '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">';

// Order Information
print '<div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<h2 style="margin: 0 0 20px 0;">📋 Order Information</h2>';

print '<div style="margin-bottom: 15px;">';
print '<div style="color: #666; font-size: 13px; margin-bottom: 5px;">Order Ref:</div>';
print '<div style="font-weight: bold; font-size: 16px;">'.dol_escape_htmltag($order->ref).'</div>';
print '</div>';

print '<div style="margin-bottom: 15px;">';
print '<div style="color: #666; font-size: 13px; margin-bottom: 5px;">Order Date:</div>';
print '<div style="font-weight: bold; font-size: 16px;">'.dol_print_date($db->jdate($order->scheduled_date), 'day').'</div>';
print '</div>';

print '<div style="margin-bottom: 15px;">';
print '<div style="color: #666; font-size: 13px; margin-bottom: 5px;">Status:</div>';
print '<div style="display: inline-block; padding: 8px 16px; background: '.$color.'; color: white; border-radius: 20px; font-weight: bold;">'.dol_escape_htmltag($order->status).'</div>';
print '</div>';

if ($order->payment_method) {
    print '<div style="margin-bottom: 15px;">';
    print '<div style="color: #666; font-size: 13px; margin-bottom: 5px;">Payment Method:</div>';
    print '<div style="font-weight: bold; font-size: 16px;">'.dol_escape_htmltag($order->payment_method).'</div>';
    print '</div>';
}

if ($order->payment_status) {
    print '<div style="margin-bottom: 15px;">';
    print '<div style="color: #666; font-size: 13px; margin-bottom: 5px;">Payment Status:</div>';
    print '<div style="font-weight: bold; font-size: 16px;">'.dol_escape_htmltag($order->payment_status).'</div>';
    print '</div>';
}

print '</div>';

// Delivery Information
print '<div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<h2 style="margin: 0 0 20px 0;">🚚 Delivery Information</h2>';

print '<div style="margin-bottom: 15px;">';
print '<div style="color: #666; font-size: 13px; margin-bottom: 5px;">Name:</div>';
print '<div style="font-weight: bold; font-size: 16px;">'.dol_escape_htmltag($beneficiary->firstname.' '.$beneficiary->lastname).'</div>';
print '</div>';

if ($order->delivery_address) {
    print '<div style="margin-bottom: 15px;">';
    print '<div style="color: #666; font-size: 13px; margin-bottom: 5px;">Address:</div>';
    print '<div style="font-size: 15px;">'.nl2br(dol_escape_htmltag($order->delivery_address)).'</div>';
    print '</div>';
}

if ($order->notes) {
    print '<div style="margin-bottom: 15px;">';
    print '<div style="color: #666; font-size: 13px; margin-bottom: 5px;">Delivery Notes:</div>';
    print '<div style="background: #f8f9fa; padding: 12px; border-radius: 4px; font-size: 14px;">'.nl2br(dol_escape_htmltag($order->notes)).'</div>';
    print '</div>';
}

print '</div>';

print '</div>';

// Order Items
print '<div style="background: white; padding: 30px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<h2 style="margin: 0 0 20px 0;">📦 Order Items</h2>';

// Get order items - FIXED: Use product_name directly from distribution_lines
$sql_items = "SELECT product_name, quantity_distributed, unit, unit_price
              FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines
              WHERE fk_distribution = ".(int)$order_id;
$res_items = $db->query($sql_items);

if ($res_items && $db->num_rows($res_items) > 0) {
    print '<table style="width: 100%; border-collapse: collapse;">';
    print '<thead style="background: #f8f9fa;">';
    print '<tr>';
    print '<th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Product</th>';
    print '<th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Quantity</th>';
    print '<th style="padding: 15px; text-align: right; border-bottom: 2px solid #dee2e6;">Unit Price</th>';
    print '<th style="padding: 15px; text-align: right; border-bottom: 2px solid #dee2e6;">Total</th>';
    print '</tr>';
    print '</thead>';
    print '<tbody>';
    
    $subtotal = 0;
    while ($item = $db->fetch_object($res_items)) {
        $item_total = $item->quantity_distributed * $item->unit_price;
        $subtotal += $item_total;
        
        print '<tr style="border-bottom: 1px solid #dee2e6;">';
        print '<td style="padding: 15px;"><strong>'.dol_escape_htmltag($item->product_name).'</strong></td>';
        print '<td style="padding: 15px; text-align: center;">'.number_format($item->quantity_distributed, 2).' '.dol_escape_htmltag($item->unit).'</td>';
        print '<td style="padding: 15px; text-align: right;">₦'.number_format($item->unit_price, 2).'</td>';
        print '<td style="padding: 15px; text-align: right;"><strong>₦'.number_format($item_total, 2).'</strong></td>';
        print '</tr>';
    }
    
    print '<tr style="background: #f8f9fa; font-weight: bold;">';
    print '<td colspan="3" style="padding: 20px; text-align: right; font-size: 18px;">Total Amount:</td>';
    print '<td style="padding: 20px; text-align: right; font-size: 24px; color: #28a745;">₦'.number_format($order->total_amount ?? $subtotal, 2).'</td>';
    print '</tr>';
    
    print '</tbody>';
    print '</table>';
} else {
    print '<p style="color: #999; text-align: center; padding: 40px;">No items found for this order.</p>';
}

print '</div>';

print '</div>'; // Close main container

llxFooter();
?>
