<?php
/**
 * CUSTOM SUBSCRIBER EDIT PAGE
 * Full Field Set + Top Bar Removed + Validation
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader('', 'Edit Subscriber');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: beneficiaries.php"); exit;
}

// --- AGGRESSIVE CSS (Top Bar Removal & Form Styling) ---
print '<style>
    /* HIDE TOP BAR ELEMENTS */
    #id-top, 
    .tmenu, 
    .login_block, 
    div[class*="login_block"], 
    div[id^="tmenu"],
    .side-nav-vert .user-menu,
    .topnav-row { 
        display: none !important; 
        height: 0 !important; 
        overflow: hidden !important; 
        opacity: 0 !important;
        visibility: hidden !important;
    }

    /* FIX LAYOUT SHIFT */
    body { padding-top: 0 !important; }
    .side-nav { top: 0 !important; height: 100vh !important; padding-top: 20px !important; z-index: 9999; }
    #id-right { padding-top: 30px !important; margin-top: 0 !important; }

    /* FORM STYLES */
    .fb-container { max-width: 1000px; margin: 0 auto; padding: 0 20px; font-family: "Segoe UI", sans-serif; }
    .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 40px; border: 1px solid #eee; }
    
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 13px; color: #444; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-size: 14px; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #667eea; outline: none; }

    .butAction { background: #27ae60; color: white; border: none; padding: 12px 30px; font-size: 16px; cursor: pointer; border-radius: 4px; transition: 0.2s; }
    .butAction:hover { background: #219150; }
    .button { text-decoration: none; padding: 10px 20px; border-radius: 4px; }
    
    .msg-error { background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 6px; border: 1px solid #fca5a5; margin-bottom: 20px; }
    .msg-success { background: #dcfce7; color: #15803d; padding: 20px; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 20px; text-align: center; }
</style>';

$b = new Beneficiary($db);
$result = $b->fetch((int) $_GET['id']);
if ($result < 0) { print "Error: Subscriber not found."; exit; }

$notice = '';
$hide_form = false;

// --- POST HANDLING ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="msg-error">⚠️ Security Token Expired. Please refresh.</div>';
    } else {
        // 1. Validate Input
        $valid = true;
        $errs = [];

        // Date Validation
        $raw_dob = GETPOST('dob', 'alpha');
        if (!empty($raw_dob)) {
            $d_parts = explode('-', $raw_dob);
            if (count($d_parts) != 3 || (int)$d_parts[0] < 1900 || (int)$d_parts[0] > (int)date('Y')) {
                $valid = false;
                $errs[] = "Invalid Birth Year (must be between 1900 and ".date('Y').")";
            }
        }

        // 2. Assign Data
        if ($valid) {
            $b->firstname = GETPOST('firstname', 'alpha');
            $b->lastname = GETPOST('lastname', 'alpha');
            $b->phone = GETPOST('phone', 'alpha');
            $b->email = GETPOST('email', 'alpha');
            $b->gender = GETPOST('gender', 'alpha');
            $b->dob = $raw_dob;
            $b->identification_number = GETPOST('identification_number', 'alpha');
            
            $b->address = GETPOST('address', 'restricthtml');
            $b->city = GETPOST('city', 'alpha');
            $b->state = GETPOST('state', 'alpha');
            $b->family_size = GETPOST('family_size', 'int');
            $b->employment_status = GETPOST('employment_status', 'alpha');
            
            $b->subscription_status = GETPOST('subscription_status', 'alpha');
            $b->subscription_type = GETPOST('subscription_type', 'alpha');
            $b->note = GETPOST('note', 'restricthtml');
            
            // 3. Update Database
            if ($b->update($user) > 0) {
                $notice = '<div class="msg-success">
                            <div style="font-size: 30px; margin-bottom: 10px;">✅</div>
                            <strong>Profile Updated Successfully</strong><br><br>
                            <a href="view_beneficiary.php?id='.$b->id.'" class="button" style="background:#27ae60; color:white;">View Profile</a>
                            <a href="beneficiaries.php" class="button" style="background:#f3f4f6; color:#333; margin-left:10px;">Back to List</a>
                           </div>';
                $hide_form = true;
            } else {
                $notice = '<div class="msg-error">Database Error: '.$b->error.'</div>';
            }
        } else {
            $notice = '<div class="msg-error"><strong>Input Error:</strong><br>'.implode('<br>', $errs).'</div>';
        }
    }
}

print '<div class="fb-container">';

// --- HEADER ---
if (!$hide_form) {
    print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-top: 20px;">';
    print '<div><h1 style="margin: 0;">✏️ Edit Subscriber</h1><p style="color:#888; margin: 5px 0 0 0;">Editing: <strong>'.dol_escape_htmltag($b->ref).'</strong></p></div>';
    print '<a href="view_beneficiary.php?id='.$b->id.'" class="button" style="background:#eee; color:#333;">Cancel</a>';
    print '</div>';
}

print $notice;

// --- FORM ---
if (!$hide_form) {
    print '<div class="fb-card">';
    print '<form method="POST" action="'.basename(__FILE__).'?id='.(int)$b->id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';

    // 1. PERSONAL DETAILS
    print '<h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; color:#2c3e50;">Personal Information</h3>';
    
    print '<div class="form-grid">';
        print '<div class="form-group"><label>First Name</label><input type="text" name="firstname" value="'.dol_escape_htmltag($b->firstname).'" required></div>';
        print '<div class="form-group"><label>Last Name</label><input type="text" name="lastname" value="'.dol_escape_htmltag($b->lastname).'" required></div>';
    print '</div>';

    print '<div class="form-grid">';
        print '<div class="form-group"><label>Email Address</label><input type="email" name="email" value="'.dol_escape_htmltag($b->email).'"></div>';
        print '<div class="form-group"><label>Phone Number</label><input type="text" name="phone" value="'.dol_escape_htmltag($b->phone).'"></div>';
    print '</div>';

    print '<div class="form-grid">';
        print '<div class="form-group"><label>Gender</label>';
        print '<select name="gender">';
        print '<option value="">-- Select --</option>';
        print '<option value="Male" '.($b->gender == 'Male' ? 'selected' : '').'>Male</option>';
        print '<option value="Female" '.($b->gender == 'Female' ? 'selected' : '').'>Female</option>';
        print '</select></div>';
        
        print '<div class="form-group"><label>Date of Birth</label><input type="date" name="dob" value="'.$b->dob.'"></div>';
    print '</div>';
    
    print '<div class="form-group"><label>NIN / ID Number</label><input type="text" name="identification_number" value="'.dol_escape_htmltag($b->identification_number).'" placeholder="National Identity Number"></div>';

    // 2. LOCATION & HOUSEHOLD
    print '<h3 style="margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; color:#2c3e50;">Address & Household</h3>';
    print '<div class="form-group"><label>Street Address</label><textarea name="address" rows="2">'.dol_escape_htmltag($b->address).'</textarea></div>';
    
    print '<div class="form-grid">';
        print '<div class="form-group"><label>City / LGA</label><input type="text" name="city" value="'.dol_escape_htmltag($b->city).'"></div>';
        print '<div class="form-group"><label>State</label><input type="text" name="state" value="'.dol_escape_htmltag($b->state).'"></div>';
    print '</div>';

    print '<div class="form-grid">';
        print '<div class="form-group"><label>Family Size</label><input type="number" name="family_size" value="'.(int)$b->family_size.'" min="1"></div>';
        
        print '<div class="form-group"><label>Employment Status</label>';
        print '<select name="employment_status">';
        print '<option value="">-- Select --</option>';
        $opts = ['Employed', 'Unemployed', 'Self-Employed', 'Student', 'Retired'];
        foreach($opts as $op) {
            $sel = ($b->employment_status == $op) ? 'selected' : '';
            print '<option value="'.$op.'" '.$sel.'>'.$op.'</option>';
        }
        print '</select></div>';
    print '</div>';

    // 3. SUBSCRIPTION MANAGEMENT
    print '<h3 style="margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; color: #667eea;">👑 Subscription Management</h3>';
    print '<div class="form-grid">';

        // Status Dropdown
        print '<div class="form-group"><label>Subscription Status</label>';
        print '<select name="subscription_status" style="font-weight:bold;">';
        $statuses = ['Pending', 'Active', 'Inactive', 'Expired'];
        foreach ($statuses as $s) {
            $selected = ($b->subscription_status == $s) ? 'selected' : '';
            $style = ($s == 'Active') ? 'color:green;' : (($s == 'Inactive') ? 'color:red;' : '');
            print '<option value="'.$s.'" '.$selected.' style="'.$style.'">'.$s.'</option>';
        }
        print '</select></div>';

        // Plan Dropdown (Dynamic)
        print '<div class="form-group"><label>Active Plan</label>';
        print '<select name="subscription_type">';
        print '<option value="">-- No Plan --</option>';
        
        $sql_tiers = "SELECT tier_type, tier_name, price FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE is_active = 1";
        $res_tiers = $db->query($sql_tiers);
        
        if ($res_tiers && $db->num_rows($res_tiers) > 0) {
            while ($tier = $db->fetch_object($res_tiers)) {
                $selected = ($b->subscription_type == $tier->tier_type) ? 'selected' : '';
                print '<option value="'.$tier->tier_type.'" '.$selected.'>'.$tier->tier_name.' ('.price($tier->price).')</option>';
            }
        }
        print '</select></div>';
    print '</div>';

    print '<div class="form-group"><label>Admin Internal Notes</label><textarea name="note" rows="3" placeholder="Private notes visible only to admins...">'.dol_escape_htmltag($b->note).'</textarea></div>';

    // SAVE BUTTON
    print '<div style="margin-top: 30px; text-align: center; border-top: 1px solid #eee; padding-top: 30px;">';
    print '<button type="submit" class="butAction" style="padding: 14px 50px; font-weight: bold;">Save Changes</button>';
    print '</div>';

    print '</form>';
    print '</div>'; // End Card
}

print '</div>'; // End Container

llxFooter();
?>