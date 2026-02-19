<?php
/**
 * My Profile - VIEW MODE & EDIT MODE
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
    accessforbidden('You do not have access to profile.');
}

// Get beneficiary information
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);
$subscriber = $db->fetch_object($res);

// Check if we are in Edit Mode
$mode = GETPOST('mode', 'alpha');
$action = GETPOST('action', 'alpha');
$msg = '';
$msg_type = '';

// Handle Update
if ($action == 'update') {
    $firstname = GETPOST('firstname', 'alpha');
    $lastname = GETPOST('lastname', 'alpha');
    $email = GETPOST('email', 'alpha');
    $phone = GETPOST('phone', 'alpha');
    $address = GETPOST('address', 'restricthtml');
    $household_size = GETPOST('household_size', 'int');
    
    $sql_update = "UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET
                   firstname = '".$db->escape($firstname)."',
                   lastname = '".$db->escape($lastname)."',
                   email = '".$db->escape($email)."',
                   phone = '".$db->escape($phone)."',
                   address = '".$db->escape($address)."',
                   household_size = ".(int)$household_size."
                   WHERE rowid = ".(int)$subscriber->rowid;
    
    if ($db->query($sql_update)) {
        $msg = 'Profile updated successfully!';
        $msg_type = 'success';
        // Refresh data & Switch back to view mode
        $res = $db->query($sql);
        $subscriber = $db->fetch_object($res);
        $mode = 'view'; 
    } else {
        $msg = 'Error updating profile: '.$db->lasterror();
        $msg_type = 'error';
        $mode = 'edit'; // Keep in edit mode if error
    }
}

llxHeader('', 'My Profile');

// --- MODERN CSS ---
print '<style>
    /* 1. HIDE CHROME */
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header { display: none !important; }
    
    /* 2. LAYOUT */
    html, body { background-color: #f8f9fa !important; margin: 0; width: 100%; overflow-x: hidden; }
    #id-right, .id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
    .fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }

    /* 3. CONTAINER */
    .ben-container { width: 95%; max-width: 1200px; margin: 0 auto; padding: 40px 20px; font-family: "Segoe UI", sans-serif; }

    /* 4. HERO SECTION */
    .profile-hero {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 30px;
    }
    .hero-info { display: flex; align-items: center; gap: 20px; }
    .avatar-circle {
        width: 80px; height: 80px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 32px; font-weight: bold;
    }
    
    /* 5. CARDS */
    .profile-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        padding: 30px;
        margin-bottom: 25px;
        border: 1px solid #f0f0f0;
    }
    
    /* 6. DATA DISPLAY (View Mode) */
    .data-row { margin-bottom: 20px; border-bottom: 1px solid #f8f9fa; padding-bottom: 10px; }
    .data-label { font-size: 13px; color: #999; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
    .data-value { font-size: 16px; color: #333; font-weight: 500; }
    
    /* 7. FORM INPUTS (Edit Mode) */
    .form-group { margin-bottom: 20px; }
    .form-control {
        width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; box-sizing: border-box;
    }
    
    /* 8. BUTTONS */
    .btn-primary {
        background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-weight: 600;
    }
    .btn-outline {
        background: white; color: #666; border: 1px solid #ddd; padding: 10px 20px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-weight: 600;
    }
    .btn-edit {
        background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 8px 16px; border-radius: 20px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; font-weight: 600;
    }
    .btn-edit:hover { background: #e9ecef; }

    .btn-logout {
        background: #fff; color: #dc3545; border: 1px solid #dc3545; 
        padding: 8px 16px; border-radius: 20px; text-decoration: none; 
        font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 5px;
        margin-left: 10px;
    }
    .btn-logout:hover { background: #dc3545; color: white; }

    /* 9. ALERTS */
    .alert-box { padding: 15px; border-radius: 8px; margin-bottom: 25px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>';

print '<div class="ben-container">';

// --- ALERTS ---
if ($msg) {
    $class = ($msg_type == 'success') ? 'alert-success' : 'alert-error';
    print '<div class="alert-box '.$class.'">'.$msg.'</div>';
}

// --- HERO SECTION ---
$initials = strtoupper(substr($subscriber->firstname, 0, 1) . substr($subscriber->lastname, 0, 1));
print '<div class="profile-hero">';
print '<div class="hero-info">';
print '<div class="avatar-circle">'.$initials.'</div>';
print '<div>';
print '<h1 style="margin: 0 0 5px 0; font-size: 24px;">'.dol_escape_htmltag($subscriber->firstname.' '.$subscriber->lastname).'</h1>';
print '<div style="color: #666;">Subscriber • '.dol_escape_htmltag($subscriber->email).'</div>';
print '</div>';
print '</div>';

// Right side actions
print '<div style="display:flex; align-items:center;">';
if ($mode == 'edit') {
    print '<a href="'.basename(__FILE__).'" class="btn-outline">Cancel</a>';
} else {
    print '<a href="'.basename(__FILE__).'?mode=edit" class="btn-edit">✏️ Edit Profile</a>';
}
// LOGOUT BUTTON
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="btn-logout"><span>🚪</span> Logout</a>';
print '</div>';

print '</div>';

print '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">';

// --- LEFT COLUMN: PERSONAL DETAILS ---
print '<div>';
print '<div class="profile-card">';
print '<h3 style="margin: 0 0 25px 0; border-bottom: 1px solid #eee; padding-bottom: 15px;">Personal Details</h3>';

if ($mode == 'edit') {
    // === EDIT MODE (FORM) ===
    print '<form method="POST" action="'.basename(__FILE__).'">';
    print '<input type="hidden" name="action" value="update">';
    
    print '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">';
    print '<div class="form-group"><label class="data-label">First Name</label><input type="text" name="firstname" class="form-control" value="'.dol_escape_htmltag($subscriber->firstname).'" required></div>';
    print '<div class="form-group"><label class="data-label">Last Name</label><input type="text" name="lastname" class="form-control" value="'.dol_escape_htmltag($subscriber->lastname).'" required></div>';
    print '</div>';
    
    print '<div class="form-group"><label class="data-label">Email</label><input type="email" name="email" class="form-control" value="'.dol_escape_htmltag($subscriber->email).'" required></div>';
    print '<div class="form-group"><label class="data-label">Phone</label><input type="text" name="phone" class="form-control" value="'.dol_escape_htmltag($subscriber->phone).'"></div>';
    print '<div class="form-group"><label class="data-label">Address</label><textarea name="address" rows="3" class="form-control">'.dol_escape_htmltag($subscriber->address).'</textarea></div>';
    print '<div class="form-group"><label class="data-label">Household Size</label><input type="number" name="household_size" class="form-control" value="'.$subscriber->household_size.'" min="1"></div>';
    
    print '<div style="margin-top: 20px;">';
    print '<button type="submit" class="btn-primary">💾 Save Changes</button>';
    print '</div>';
    print '</form>';

} else {
    // === VIEW MODE (TEXT) ===
    print '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">';
    print '<div class="data-row"><div class="data-label">First Name</div><div class="data-value">'.dol_escape_htmltag($subscriber->firstname).'</div></div>';
    print '<div class="data-row"><div class="data-label">Last Name</div><div class="data-value">'.dol_escape_htmltag($subscriber->lastname).'</div></div>';
    print '</div>';
    
    print '<div class="data-row"><div class="data-label">Email</div><div class="data-value">'.dol_escape_htmltag($subscriber->email).'</div></div>';
    print '<div class="data-row"><div class="data-label">Phone</div><div class="data-value">'.($subscriber->phone ? dol_escape_htmltag($subscriber->phone) : '<span style="color:#ccc;">Not set</span>').'</div></div>';
    print '<div class="data-row"><div class="data-label">Address</div><div class="data-value">'.($subscriber->address ? nl2br(dol_escape_htmltag($subscriber->address)) : '<span style="color:#ccc;">Not set</span>').'</div></div>';
    print '<div class="data-row"><div class="data-label">Household Size</div><div class="data-value">'.$subscriber->household_size.' Members</div></div>';
}

print '</div>'; // End Card
print '</div>';

// --- RIGHT COLUMN: INFO & SUB ---
print '<div>';

// Account Info
print '<div class="profile-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">';
print '<h3 style="margin: 0 0 20px 0; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 10px;">📋 Account Info</h3>';
print '<div style="margin-bottom: 15px;"><div style="font-size: 13px; opacity: 0.8;">Subscriber ID</div><div style="font-size: 18px; font-weight: bold;">'.dol_escape_htmltag($subscriber->ref).'</div></div>';
print '<div style="margin-bottom: 15px;"><div style="font-size: 13px; opacity: 0.8;">Joined</div><div style="font-size: 16px;">'.dol_print_date($db->jdate($subscriber->registration_date), 'day').'</div></div>';
print '<div><div style="font-size: 13px; opacity: 0.8;">Username</div><div style="font-size: 16px;">'.dol_escape_htmltag($user->login).'</div></div>';
print '</div>';

// Subscription
print '<div class="profile-card">';
print '<h3 style="margin: 0 0 20px 0; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px;">💳 Subscription</h3>';
print '<div style="margin-bottom: 15px;"><div class="data-label">Plan</div><div style="font-size: 18px; font-weight: bold; color: #2c3e50;">'.($subscriber->subscription_type ?: 'Standard').'</div></div>';
print '<div><div class="data-label">Status</div><span style="background:#28a745; color:white; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:bold;">'.strtoupper($subscriber->subscription_status).'</span></div>';
print '</div>';

// Security
print '<div class="profile-card" style="text-align: center;">';
print '<div style="font-size: 32px; margin-bottom: 10px;">🔐</div>';
print '<p style="color: #666; font-size: 13px; margin-bottom: 15px;">Update your password to keep your account safe.</p>';
print '<a href="subscriber_password.php" class="btn-outline" style="width: 100%; box-sizing: border-box;">Change Password</a>';
print '</div>';

// Dashboard Link
print '<div style="text-align: center; margin-top: 20px;">';
print '<a href="dashboard_beneficiary.php" style="color: #666; text-decoration: none; font-weight: 600;">← Back to Dashboard</a>';
print '</div>';

print '</div>'; // End Right Column

print '</div>'; // End Grid
print '</div>'; // End Container

llxFooter();
?>