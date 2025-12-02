<?php
/**
 * Vendor Dashboard - Custom dashboard for vendors to manage donations
 */

// CRITICAL: Define these BEFORE including main.inc.php to bypass CSRF checks
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

// Reset redirect flag so navigation works
if (isset($_SESSION['foodbank_checked'])) {
    $_SESSION['foodbank_checked'] = false;
}

$langs->load("admin");

// Security check - vendor only
$user_is_vendor = FoodbankPermissions::isVendor($user, $db);

if (!$user_is_vendor) {
    accessforbidden('You do not have access to the vendor dashboard.');
}

// Get vendor information
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);

if (!$res || $db->num_rows($res) == 0) {
    llxHeader('', 'Vendor Dashboard');
    print '<div class="error">Vendor profile not found. Please contact administrator.</div>';
    llxFooter();
    exit;
}

$vendor = $db->fetch_object($res);
$vendor_id = $vendor->rowid;

llxHeader('', 'Vendor Dashboard');

// HIDE UNAUTHORIZED MENU ITEMS WITH JAVASCRIPT
?>
<script>
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Foodbank CRM: Hiding vendor unauthorized menus...');
    
    // Menu items to hide for vendors
    const menusToHide = [
        'Beneficiaries',
        'Vendors',
        'Donations',
        'Packages',
        'Distributions',
        'Warehouses',
        'Subscription Tiers',
        'User Management',
        'Product Catalog',
        'My Orders',
        'Available Packages'
    ];
    
    // Get all menu links
    const menuLinks = document.querySelectorAll('a.vmenu, a.vmenudisabled, div.menu_titre, div.blockvmenu');
    
    menuLinks.forEach(function(element) {
        const menuText = element.textContent.trim();
        
        menusToHide.forEach(function(hideText) {
            if (menuText.includes(hideText)) {
                // Hide the menu item
                const menuItem = element.closest('div.blockvmenu') || element.closest('div.menu') || element.closest('li') || element;
                menuItem.style.display = 'none';
                console.log('Foodbank CRM: Hid menu:', hideText);
            }
        });
    });
});
</script>
<?php

print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<div>';
print '<h1>👋 Welcome, '.dol_escape_htmltag($vendor->name).'!</h1>';
print '<p style="color: #666; margin: 5px 0;">Vendor ID: '.dol_escape_htmltag($vendor->ref).'</p>';
print '</div>';
print '<a class="butAction" href="create_donation.php">+ Submit Donation</a>';
print '</div>';

print '<div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">';

print '<div>';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Business Name</div>';
print '<div style="font-size: 24px; font-weight: bold;">'.dol_escape_htmltag($vendor->name).'</div>';
print '</div>';

print '<div>';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Category</div>';
print '<div style="font-size: 20px; font-weight: bold;">'.dol_escape_htmltag($vendor->category ?: 'General').'</div>';
print '</div>';

print '<div>';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Contact</div>';
print '<div style="font-size: 16px;">'.dol_escape_htmltag($vendor->contact_phone ?: $vendor->phone).'</div>';
if ($vendor->contact_email || $vendor->email) {
    print '<div style="font-size: 13px; opacity: 0.9;">'.dol_escape_htmltag($vendor->contact_email ?: $vendor->email).'</div>';
}
print '</div>';

if ($vendor->address) {
    print '<div>';
    print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Address</div>';
    print '<div style="font-size: 14px;">'.nl2br(dol_escape_htmltag($vendor->address)).'</div>';
    print '</div>';
}

print '</div>';
print '</div>';

// Get statistics - WITH ERROR CHECKING
$sql_stats = "SELECT 
    COUNT(DISTINCT d.rowid) as total_donations,
    COALESCE(SUM(d.quantity), 0) as total_quantity,
    COALESCE(SUM(CASE WHEN d.status = 'Received' THEN d.quantity ELSE 0 END), 0) as received_quantity,
    COALESCE(SUM(CASE WHEN d.status = 'Pending' THEN 1 ELSE 0 END), 0) as pending_donations,
    COUNT(DISTINCT d.product_name) as unique_products
    FROM ".MAIN_DB_PREFIX."foodbank_donations d
    WHERE d.fk_vendor = ".(int)$vendor_id;

$res_stats = $db->query($sql_stats);

// Initialize default stats
$stats = new stdClass();
$stats->total_donations = 0;
$stats->total_quantity = 0;
$stats->received_quantity = 0;
$stats->pending_donations = 0;
$stats->unique_products = 0;

// Only fetch if query succeeded
if ($res_stats) {
    $stats = $db->fetch_object($res_stats);
} else {
    // Log error but continue with default stats
    dol_syslog("Error fetching vendor stats: " . $db->lasterror(), LOG_ERR);
}

print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">';

print '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Total Donations</div>';
print '<div style="font-size: 40px; font-weight: bold;">'.($stats->total_donations ?? 0).'</div>';
print '</div>';

print '<div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Pending Review</div>';
print '<div style="font-size: 40px; font-weight: bold;">'.($stats->pending_donations ?? 0).'</div>';
print '</div>';

print '<div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Product Types</div>';
print '<div style="font-size: 40px; font-weight: bold;">'.($stats->unique_products ?? 0).'</div>';
print '</div>';

print '<div style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Total Quantity Supplied</div>';
print '<div style="font-size: 32px; font-weight: bold;">'.number_format($stats->total_quantity ?? 0, 0).'</div>';
print '</div>';

print '</div>';

print '<h2>🎉 Vendor Dashboard Loaded Successfully!</h2>';
print '<p style="color: #666;">Your custom dashboard is working. Stats will populate as you add donations.</p>';

print '<div style="margin-top: 40px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">';

print '<a href="create_donation.php" style="display: block; padding: 20px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit;">';
print '<div style="font-size: 40px; margin-bottom: 10px;">➕</div>';
print '<h3 style="margin: 0 0 5px 0;">Submit Donation</h3>';
print '<p style="margin: 0; color: #666; font-size: 13px;">Add a new product donation</p>';
print '</a>';

print '<a href="my_donations.php" style="display: block; padding: 20px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit;">';
print '<div style="font-size: 40px; margin-bottom: 10px;">📦</div>';
print '<h3 style="margin: 0 0 5px 0;">My Donations</h3>';
print '<p style="margin: 0; color: #666; font-size: 13px;">View donation history</p>';
print '</a>';

print '<a href="vendor_profile.php" style="display: block; padding: 20px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit;">';
print '<div style="font-size: 40px; margin-bottom: 10px;">👤</div>';
print '<h3 style="margin: 0 0 5px 0;">My Profile</h3>';
print '<p style="margin: 0; color: #666; font-size: 13px;">Update business info</p>';
print '</a>';

print '</div>';

llxFooter();
?>
