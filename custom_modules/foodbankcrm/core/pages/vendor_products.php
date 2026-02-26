<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// Check if user is a vendor
$user_is_vendor = FoodbankPermissions::isVendor($user, $db);

if (!$user_is_vendor) {
    accessforbidden('You do not have access.');
}

// Get vendor information
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);
$vendor = $db->fetch_object($res);
$vendor_id = $vendor->rowid;

llxHeader('', 'My Product Catalog');

print '<div><a href="dashboard_vendor.php">← Back to Dashboard</a></div><br>';

print '<h1>📋 My Product Catalog</h1>';

// Get unique products this vendor supplies
$sql = "SELECT 
    product_name, 
    category, 
    unit,
    COUNT(*) as donation_count,
    SUM(quantity) as total_quantity,
    SUM(CASE WHEN status = 'Received' THEN quantity ELSE 0 END) as received_quantity,
    AVG(CASE WHEN unit_price > 0 THEN unit_price ELSE NULL END) as avg_price,
    MAX(datec) as last_supplied
    FROM ".MAIN_DB_PREFIX."foodbank_donations
    WHERE fk_vendor = ".(int)$vendor_id."
    GROUP BY product_name, category, unit
    ORDER BY total_quantity DESC";

$res = $db->query($sql);

if (!$res || $db->num_rows($res) == 0) {
    print '<div style="text-align: center; padding: 60px; background: #f9f9f9; border-radius: 8px;">';
    print '<div style="font-size: 64px; margin-bottom: 20px;">📦</div>';
    print '<h2>No Products Yet</h2>';
    print '<p style="color: #666;">Submit donations to build your product catalog.</p>';
    print '<br><a class="butAction" href="create_donation.php">+ Submit Donation</a>';
    print '</div>';
    llxFooter();
    exit;
}

print '<p style="color: #666;">This is a summary of all products you\'ve supplied to the foodbank.</p>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>Product Name</th>';
print '<th>Category</th>';
print '<th class="center">Times Supplied</th>';
print '<th class="center">Total Quantity</th>';
print '<th class="center">Received</th>';
print '<th class="center">Avg. Price</th>';
print '<th>Last Supplied</th>';
print '</tr>';

while ($product = $db->fetch_object($res)) {
    print '<tr class="oddeven">';
    print '<td><strong>'.dol_escape_htmltag($product->product_name).'</strong></td>';
    print '<td>'.dol_escape_htmltag($product->category).'</td>';
    print '<td class="center">'.$product->donation_count.'</td>';
    print '<td class="center"><strong>'.number_format($product->total_quantity, 1).' '.$product->unit.'</strong></td>';
    print '<td class="center"><strong style="color: #2e7d32;">'.number_format($product->received_quantity, 1).' '.$product->unit.'</strong></td>';
    print '<td class="center">';
    if ($product->avg_price) {
        print '₦'.number_format($product->avg_price, 2);
    } else {
        print '<span style="color: #999;">N/A</span>';
    }
    print '</td>';
    print '<td>'.dol_print_date($db->jdate($product->last_supplied), 'day').'</td>';
    print '</tr>';
}

print '</table>';

// Category breakdown
$sql_cats = "SELECT 
    category, 
    COUNT(DISTINCT product_name) as product_count,
    SUM(CASE WHEN status = 'Received' THEN quantity ELSE 0 END) as total_qty
    FROM ".MAIN_DB_PREFIX."foodbank_donations
    WHERE fk_vendor = ".(int)$vendor_id."
    GROUP BY category
    ORDER BY total_qty DESC";

$res_cats = $db->query($sql_cats);

if ($res_cats && $db->num_rows($res_cats) > 0) {
    print '<h2 style="margin-top: 40px;">📊 By Category</h2>';
    print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">';
    
    while ($cat = $db->fetch_object($res_cats)) {
        $emoji = '📦';
        switch($cat->category) {
            case 'Grains': $emoji = '🌾'; break;
            case 'Vegetables': $emoji = '🥕'; break;
            case 'Proteins': $emoji = '🍗'; break;
            case 'Dairy': $emoji = '🥛'; break;
        }
        
        print '<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">';
        print '<div style="font-size: 48px; margin-bottom: 10px;">'.$emoji.'</div>';
        print '<h3 style="margin: 10px 0 5px 0;">'.dol_escape_htmltag($cat->category).'</h3>';
        print '<div style="font-size: 24px; font-weight: bold; color: #1976d2; margin: 10px 0;">'.$cat->product_count.'</div>';
        print '<div style="font-size: 12px; color: #666;">Product Types</div>';
        print '<div style="font-size: 14px; color: #2e7d32; margin-top: 10px;">'.number_format($cat->total_qty, 0).' units supplied</div>';
        print '</div>';
    }
    
    print '</div>';
}

llxFooter();
?>
