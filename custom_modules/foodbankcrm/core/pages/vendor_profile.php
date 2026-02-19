<?php
/**
 * Vendor Profile - ENTREPRENEUR FOCUSED
 */
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

llxHeader('', 'My Business Profile');

// --- MODERN CSS ---
echo '<style>
    /* 1. HIDE CHROME */
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header { display: none !important; }
    
    /* 2. LAYOUT */
    html, body { background-color: #f8f9fa !important; margin: 0; width: 100%; overflow-x: hidden; }
    #id-right, .id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
    .fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }

    /* 3. CONTAINER */
    .vendor-container { width: 95%; max-width: 1200px; margin: 0 auto; padding: 40px 20px; font-family: "Segoe UI", sans-serif; }

    /* 4. FORM STYLES */
    .form-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 14px; }
    .form-control { 
        width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; box-sizing: border-box; transition: border 0.2s;
    }
    .form-control:focus { border-color: #667eea; outline: none; }
    
    /* 5. BUTTONS */
    .btn-save { 
        background: #667eea; color: white; border: none; padding: 12px 40px; border-radius: 30px; 
        font-weight: bold; cursor: pointer; font-size: 16px; box-shadow: 0 4px 12px rgba(102,126,234,0.3);
        transition: transform 0.1s;
    }
    .btn-save:hover { transform: translateY(-2px); }
    
    .btn-outline {
        background: white; color: #666; border: 1px solid #ddd; padding: 8px 16px; border-radius: 30px; 
        text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block;
    }
    
    .btn-logout {
        background: white; color: #dc3545; border: 1px solid #dc3545; 
        padding: 8px 16px; border-radius: 30px; text-decoration: none; 
        font-weight: bold; font-size: 13px; display: inline-flex; align-items: center; gap: 5px;
    }
    .btn-logout:hover { background: #dc3545; color: white; }

    /* 6. ALERTS */
    .alert-box { padding: 15px; border-radius: 8px; margin-bottom: 25px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>';

$notice = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="alert-box alert-error">Security check failed.</div>';
    } else {
        $name = GETPOST('name', 'alpha');
        $category = GETPOST('category', 'alpha');
        $contact_person = GETPOST('contact_person', 'alpha');
        $contact_email = GETPOST('contact_email', 'email');
        $contact_phone = GETPOST('contact_phone', 'alpha');
        $address = GETPOST('address', 'restricthtml');
        $description = GETPOST('description', 'restricthtml');
        
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_vendors SET 
                name = '".$db->escape($name)."',
                category = '".$db->escape($category)."',
                contact_person = '".$db->escape($contact_person)."',
                contact_email = '".$db->escape($contact_email)."',
                contact_phone = '".$db->escape($contact_phone)."',
                address = '".$db->escape($address)."',
                description = '".$db->escape($description)."'
                WHERE rowid = ".(int)$vendor_id;
        
        if ($db->query($sql)) {
            $notice = '<div class="alert-box alert-success">✓ Business profile updated successfully!</div>';
            // Refresh data
            $res = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE rowid = ".(int)$vendor_id);
            $vendor = $db->fetch_object($res);
        } else {
            $notice = '<div class="alert-box alert-error">Error updating profile: '.$db->lasterror().'</div>';
        }
    }
}

// MAIN CONTAINER
print '<div class="vendor-container">';

// HEADER
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<div>';
print '<h1 style="margin: 0; color: #2c3e50;">👤 My Business Profile</h1>';
print '<p style="color: #666; margin: 5px 0 0 0;">Manage your company details</p>';
print '</div>';

// Actions
print '<div style="display: flex; gap: 10px; align-items: center;">';
print '<a href="dashboard_vendor.php" class="btn-outline">← Dashboard</a>';
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="btn-logout"><span>🚪</span> Logout</a>';
print '</div>';
print '</div>';

print $notice;

print '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">';

// --- LEFT COLUMN: EDIT FORM ---
print '<div>';
print '<div class="form-card">';
print '<h3 style="margin-top: 0; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px; color: #2c3e50;">Business Details</h3>';

print '<form method="POST" action="'.basename(__FILE__).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

// Business Name
print '<div class="form-group">';
print '<label class="form-label">Business Name</label>';
print '<input class="form-control" type="text" name="name" value="'.dol_escape_htmltag($vendor->name).'" required>';
print '</div>';

// Category
print '<div class="form-group">';
print '<label class="form-label">Primary Category</label>';
print '<select class="form-control" name="category">';
print '<option value="">-- Select --</option>';
$categories = array('Grains', 'Vegetables', 'Proteins', 'Dairy', 'Beverages', 'Packaged Foods', 'Other');
foreach ($categories as $cat) {
    $selected = ($vendor->category == $cat) ? 'selected' : '';
    print '<option value="'.$cat.'" '.$selected.'>'.$cat.'</option>';
}
print '</select>';
print '</div>';

// Contact Person
print '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">';
print '<div class="form-group">';
print '<label class="form-label">Contact Person</label>';
print '<input class="form-control" type="text" name="contact_person" value="'.dol_escape_htmltag($vendor->contact_person).'">';
print '</div>';
print '<div class="form-group">';
print '<label class="form-label">Phone Number</label>';
print '<input class="form-control" type="text" name="contact_phone" value="'.dol_escape_htmltag($vendor->contact_phone).'">';
print '</div>';
print '</div>';

// Email
print '<div class="form-group">';
print '<label class="form-label">Business Email</label>';
print '<input class="form-control" type="email" name="contact_email" value="'.dol_escape_htmltag($vendor->contact_email).'">';
print '</div>';

// Address
print '<div class="form-group">';
print '<label class="form-label">Business Address</label>';
print '<textarea class="form-control" name="address" rows="3">'.dol_escape_htmltag($vendor->address).'</textarea>';
print '</div>';

// Description
print '<div class="form-group">';
print '<label class="form-label">About Business</label>';
print '<textarea class="form-control" name="description" rows="4" placeholder="Brief description of products...">'.dol_escape_htmltag($vendor->description).'</textarea>';
print '</div>';

print '<div style="text-align: right; margin-top: 20px;">';
print '<button type="submit" class="btn-save">💾 Save Changes</button>';
print '</div>';

print '</form>';
print '</div>'; // End form card
print '</div>'; // End Left Column

// --- RIGHT COLUMN: INFO CARDS ---
print '<div>';

// Account Info
print '<div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<h3 style="margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.3); padding-bottom: 10px; font-size: 16px;">📋 Account Info</h3>';
print '<div style="margin-bottom: 15px;"><div style="font-size: 13px; opacity: 0.8;">Vendor ID</div><div style="font-size: 18px; font-weight: bold;">'.dol_escape_htmltag($vendor->ref).'</div></div>';
print '<div style="margin-bottom: 15px;"><div style="font-size: 13px; opacity: 0.8;">Member Since</div><div style="font-size: 16px;">'.dol_print_date($db->jdate($vendor->date_creation), 'day').'</div></div>';
print '<div><div style="font-size: 13px; opacity: 0.8;">Username</div><div style="font-size: 16px;">'.dol_escape_htmltag($user->login).'</div></div>';
print '</div>';

// Business Impact Stats
$sql_stats = "SELECT 
    COUNT(*) as total_donations,
    SUM(CASE WHEN status = 'Received' THEN quantity ELSE 0 END) as received_qty,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count
    FROM ".MAIN_DB_PREFIX."foodbank_donations
    WHERE fk_vendor = ".(int)$vendor_id;

$res_stats = $db->query($sql_stats);
$stats = $db->fetch_object($res_stats);

print '<div class="form-card" style="margin-bottom: 25px;">';
print '<h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; color: #2c3e50;">📊 Business Impact</h3>';
print '<table style="width: 100%; font-size: 14px;">';
print '<tr><td style="padding: 10px 0; color: #666;">Total Batches</td><td style="text-align: right;"><strong style="font-size: 16px; color: #1976d2;">'.($stats->total_donations ?? 0).'</strong></td></tr>';
print '<tr><td style="padding: 10px 0; color: #666;">Units Supplied</td><td style="text-align: right;"><strong style="font-size: 16px; color: #2e7d32;">'.number_format($stats->received_qty ?? 0, 0).'</strong></td></tr>';
print '<tr><td style="padding: 10px 0; color: #666;">Pending Review</td><td style="text-align: right;"><strong style="font-size: 16px; color: #f57c00;">'.($stats->pending_count ?? 0).'</strong></td></tr>';
print '</table>';
print '</div>';

// Security
print '<div class="form-card" style="text-align: center;">';
print '<div style="font-size: 32px; margin-bottom: 10px;">🔐</div>';
print '<h3 style="margin: 0 0 10px 0; color: #2c3e50;">Security</h3>';
print '<p style="color: #666; font-size: 13px; margin-bottom: 15px;">Update your login password regularly.</p>';
print '<a href="vendor_password.php" class="btn-outline" style="width: 100%; box-sizing: border-box;">Change Password</a>';
print '</div>';

print '</div>'; // End right column

print '</div>'; // End grid
print '</div>'; // End vendor-container

llxFooter();
?>