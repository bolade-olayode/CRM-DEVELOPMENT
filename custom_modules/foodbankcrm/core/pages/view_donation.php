<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

$donation_id = GETPOST('id', 'int');

if (!$donation_id) {
    header('Location: my_donations.php');
    exit;
}

llxHeader('', 'Donation Details');

// 1. PORTAL MODE CSS (Hides the menu)
echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }
</style>';

// 2. FIXED SQL QUERY (Using correct column names 'label' and 'address')
$sql = "SELECT d.*, 
        v.name as vendor_name, v.contact_email as vendor_email, v.contact_phone as vendor_phone,
        w.label as warehouse_name, w.address as warehouse_location
        FROM ".MAIN_DB_PREFIX."foodbank_donations d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON d.fk_vendor = v.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_warehouses w ON d.fk_warehouse = w.rowid
        WHERE d.rowid = ".(int)$donation_id;

$res = $db->query($sql);

if (!$res) {
    // If query fails, show the actual database error so we know why
    print '<div class="error">Database Error: '.$db->lasterror().'</div>';
    llxFooter();
    exit;
}

$donation = $db->fetch_object($res);

if (!$donation) {
    print '<div style="padding: 50px; text-align: center;">';
    print '<div class="error">Donation not found.</div>';
    print '<br><a href="my_donations.php" class="butAction">← Back to Donations</a>';
    print '</div>';
    llxFooter();
    exit;
}

// Check permissions
$user_is_admin = FoodbankPermissions::isAdmin($user);
$user_is_vendor = FoodbankPermissions::isVendor($user, $db);

// If vendor, verify they own this donation
if ($user_is_vendor && !$user_is_admin) {
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
    $res_check = $db->query($sql);
    $vendor_check = $db->fetch_object($res_check);
    
    if ($vendor_check->rowid != $donation->fk_vendor) {
        accessforbidden('You do not have permission to view this donation.');
    }
}

// 3. MAIN CONTAINER
print '<div style="width: 100%; padding: 30px; box-sizing: border-box; max-width: 1200px; margin: 0 auto;">';

// Header with Back Button
$back_link = $user_is_admin ? 'donations.php' : 'my_donations.php';

print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<div>';
print '<h1 style="margin: 0;">📦 Donation Details</h1>';
print '<p style="color: #666; margin: 5px 0 0 0;">Reference: <strong>'.dol_escape_htmltag($donation->ref).'</strong></p>';
print '</div>';
print '<a href="'.$back_link.'" class="butAction">← Back to List</a>';
print '</div>';

// Status badge
$status_colors = array(
    'Pending' => array('bg' => '#fff3e0', 'color' => '#f57c00', 'icon' => '⏳'),
    'Received' => array('bg' => '#e8f5e9', 'color' => '#2e7d32', 'icon' => '✓'),
    'Rejected' => array('bg' => '#ffebee', 'color' => '#d32f2f', 'icon' => '✗')
);
$colors = $status_colors[$donation->status] ?? array('bg' => '#f5f5f5', 'color' => '#666', 'icon' => '?');

// Main info grid
print '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;">';

// Left: Product Details
print '<div>';

print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">';
print '<h2 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">📦 Product Information</h2>';
print '<table class="border centpercent" style="border: none;">';
print '<tr><td width="30%" style="border: none; padding: 10px 0;"><strong>Product Name:</strong></td><td style="border: none; padding: 10px 0;"><span style="font-size: 18px; font-weight: bold;">'.dol_escape_htmltag($donation->product_name).'</span></td></tr>';
print '<tr><td style="border: none; padding: 10px 0;"><strong>Category:</strong></td><td style="border: none; padding: 10px 0;">'.dol_escape_htmltag($donation->category).'</td></tr>';
print '<tr><td style="border: none; padding: 10px 0;"><strong>Quantity:</strong></td><td style="border: none; padding: 10px 0;"><strong style="font-size: 18px; color: #1976d2;">'.number_format($donation->quantity).' '.$donation->unit.'</strong></td></tr>';

if ($donation->unit_price > 0) {
    print '<tr><td style="border: none; padding: 10px 0;"><strong>Unit Price:</strong></td><td style="border: none; padding: 10px 0;"><strong style="color: #666;">₦'.number_format($donation->unit_price, 2).'</strong></td></tr>';
    print '<tr><td style="border: none; padding: 10px 0;"><strong>Total Value:</strong></td><td style="border: none; padding: 10px 0;"><strong style="font-size: 20px; color: #2e7d32;">₦'.number_format($donation->quantity * $donation->unit_price, 2).'</strong></td></tr>';
}

if ($donation->description) {
    print '<tr><td style="border: none; padding: 10px 0;"><strong>Description:</strong></td><td style="border: none; padding: 10px 0;">'.nl2br(dol_escape_htmltag($donation->description)).'</td></tr>';
}

print '</table>';
print '</div>';

// Vendor Information
print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<h2 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">👤 Vendor Information</h2>';
print '<table class="border centpercent" style="border: none;">';
print '<tr><td width="30%" style="border: none; padding: 10px 0;"><strong>Vendor:</strong></td><td style="border: none; padding: 10px 0;">'.dol_escape_htmltag($donation->vendor_name).'</td></tr>';
if ($donation->vendor_phone) {
    print '<tr><td style="border: none; padding: 10px 0;"><strong>Phone:</strong></td><td style="border: none; padding: 10px 0;">'.dol_escape_htmltag($donation->vendor_phone).'</td></tr>';
}
if ($donation->vendor_email) {
    print '<tr><td style="border: none; padding: 10px 0;"><strong>Email:</strong></td><td style="border: none; padding: 10px 0;">'.dol_escape_htmltag($donation->vendor_email).'</td></tr>';
}
print '</table>';
print '</div>';

print '</div>'; // End left column

// Right: Status & Warehouse
print '<div>';

// Status Card
print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; text-align: center;">';
print '<h2 style="margin-top: 0; margin-bottom: 20px;">Current Status</h2>';
print '<div style="font-size: 16px; font-weight: bold; padding: 15px; border-radius: 50px; background:'.$colors['bg'].'; color:'.$colors['color'].'; display: inline-block; margin-bottom: 15px;">';
print $colors['icon'].' '.dol_escape_htmltag($donation->status);
print '</div>';
print '<div style="color: #666; font-size: 14px;">Submitted on '.dol_print_date($db->jdate($donation->datec), 'dayhour').'</div>';
print '</div>';

// Warehouse Info
print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">';
print '<h2 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">🏭 Warehouse & Delivery</h2>';
print '<table class="border centpercent" style="border: none;">';

if ($donation->warehouse_name) {
    print '<tr><td width="40%" style="border: none; padding: 10px 0;"><strong>Warehouse:</strong></td><td style="border: none; padding: 10px 0;">'.dol_escape_htmltag($donation->warehouse_name).'</td></tr>';
    if ($donation->warehouse_location) {
        print '<tr><td style="border: none; padding: 10px 0;"><strong>Address:</strong></td><td style="border: none; padding: 10px 0;">'.dol_escape_htmltag($donation->warehouse_location).'</td></tr>';
    }
} else {
    print '<tr><td colspan="2" style="border: none; padding: 10px 0; color: #999; text-align: center;">Not assigned to warehouse</td></tr>';
}

if ($donation->delivery_method) {
    print '<tr><td style="border: none; padding: 10px 0;"><strong>Delivery:</strong></td><td style="border: none; padding: 10px 0;">'.dol_escape_htmltag($donation->delivery_method).'</td></tr>';
}

print '</table>';
print '</div>';

// Admin Actions (Only show if Admin)
if ($user_is_admin) {
    print '<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #ddd; text-align: center;">';
    print '<h3 style="margin-top: 0;">Admin Actions</h3>';
    
    if ($donation->status == 'Pending') {
        print '<a class="butAction" href="approve_donation.php?id='.$donation_id.'" style="display: block; margin-bottom: 10px;">✓ Approve & Receive</a>';
        print '<a class="butActionDelete" href="reject_donation.php?id='.$donation_id.'" style="display: block;">✗ Reject</a>';
    } else {
        print '<span style="color: #666;">No actions available</span>';
    }
    
    print '</div>';
}

print '</div>'; // End right column

print '</div>'; // End grid
print '</div>'; // End main container

llxFooter();
?>