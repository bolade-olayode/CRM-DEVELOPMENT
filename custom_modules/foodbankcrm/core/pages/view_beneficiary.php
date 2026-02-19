<?php
/**
 * CUSTOM SUBSCRIBER PROFILE VIEW
 * Matches DB Columns: gender, dob, city, state, family_size, employment_status, identification_number
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$id = GETPOST('id', 'int');
if (!$id) accessforbidden();

$object = new Beneficiary($db);
if ($object->fetch($id) < 1) accessforbidden();

// Load User Account Status (for Login access)
$user_static = new User($db);
$user_status_label = 'No Login Account';
$user_status_color = 'bg-gray';

if ($object->email) {
    // Try to find the linked Dolibarr user by email
    $result = $user_static->fetch('', $object->email); 
    if ($result > 0) {
        $user_status_label = ($user_static->statut == 1) ? 'Login Enabled' : 'Login Disabled';
        $user_status_color = ($user_static->statut == 1) ? 'bg-green' : 'bg-red';
    }
}

llxHeader('', 'Subscriber Profile');

// --- 1. AGGRESSIVE CSS TO HIDE TOP BAR & MENU ---
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

    /* PROFILE PAGE STYLES */
    .fb-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; font-family: "Segoe UI", sans-serif; }
    
    .profile-header { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border: 1px solid #e0e0e0; }
    .profile-info h1 { margin: 0; font-size: 24px; color: #2c3e50; font-weight: 700; }
    .profile-info p { margin: 5px 0 0; color: #7f8c8d; font-size: 14px; }
    
    .grid-container { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; border: 1px solid #e0e0e0; }
    .card h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; color: #34495e; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .data-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f9f9f9; align-items: center; }
    .data-row:last-child { border-bottom: none; }
    .label { color: #95a5a6; font-weight: 500; font-size: 13px; }
    .value { color: #2c3e50; font-weight: 600; font-size: 14px; text-align: right; }
    
    .badge { padding: 6px 12px; border-radius: 4px; font-size: 11px; color: #fff; font-weight: bold; text-transform: uppercase; }
    .bg-green { background: #27ae60; } 
    .bg-orange { background: #f39c12; } 
    .bg-red { background: #c0392b; }
    .bg-gray { background: #95a5a6; }
    
    .btn-action { text-decoration: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; font-size: 14px; margin-left: 10px; display: inline-block; transition: 0.2s; border: 1px solid transparent; }
    .btn-edit { background: #3498db; color: #fff; } .btn-edit:hover { background: #2980b9; }
    .btn-back { background: #fff; color: #7f8c8d; border-color: #ddd; } .btn-back:hover { background: #f9f9f9; }
</style>';

// --- CONTENT WRAPPER ---
print '<div class="fb-container">';

// --- HEADER SECTION ---
print '<div class="profile-header">';
    print '<div class="profile-info">';
        print '<h1>'.dol_escape_htmltag($object->firstname.' '.$object->lastname).'</h1>';
        print '<p>Subscriber Ref: <strong>'.dol_escape_htmltag($object->ref).'</strong> &nbsp;|&nbsp; Joined: '.dol_print_date($object->date_creation, 'day').'</p>';
    print '</div>';
    
    print '<div class="actions">';
        print '<a href="beneficiaries.php" class="btn-action btn-back">← Back to List</a>';
        print '<a href="edit_beneficiary.php?id='.$object->id.'" class="btn-action btn-edit">Edit Profile</a>';
    print '</div>';
print '</div>';

// --- MAIN GRID ---
print '<div class="grid-container">';

    // --- LEFT COLUMN ---
    print '<div>';

        // 1. PERSONAL INFORMATION
        print '<div class="card">';
        print '<h3>Personal Information</h3>';
        print '<div class="data-row"><span class="label">Email</span> <span class="value">'.($object->email ? dol_escape_htmltag($object->email) : '<span style="color:#ccc">--</span>').'</span></div>';
        print '<div class="data-row"><span class="label">Phone</span> <span class="value">'.($object->phone ? dol_escape_htmltag($object->phone) : '<span style="color:#ccc">--</span>').'</span></div>';
        print '<div class="data-row"><span class="label">Gender</span> <span class="value">'.($object->gender ? dol_escape_htmltag($object->gender) : '<span style="color:#ccc">--</span>').'</span></div>';
        print '<div class="data-row"><span class="label">Date of Birth</span> <span class="value">'.($object->dob ? dol_print_date($object->dob, 'day') : '<span style="color:#ccc">--</span>').'</span></div>';
        print '<div class="data-row"><span class="label">NIN / ID Number</span> <span class="value">'.($object->identification_number ? dol_escape_htmltag($object->identification_number) : '<span style="color:#ccc">Not Provided</span>').'</span></div>';
        print '</div>';

        // 2. ADDRESS & LOCATION
        print '<div class="card">';
        print '<h3>Address & Location</h3>';
        print '<div class="data-row"><span class="label">Street Address</span> <span class="value">'.($object->address ? dol_escape_htmltag($object->address) : '<span style="color:#ccc">--</span>').'</span></div>';
        print '<div class="data-row"><span class="label">City / LGA</span> <span class="value">'.($object->city ? dol_escape_htmltag($object->city) : '<span style="color:#ccc">--</span>').'</span></div>';
        print '<div class="data-row"><span class="label">State</span> <span class="value">'.($object->state ? dol_escape_htmltag($object->state) : '<span style="color:#ccc">--</span>').'</span></div>';
        print '</div>';

        // 3. HOUSEHOLD DETAILS
        print '<div class="card">';
        print '<h3>Household & Employment</h3>';
        // Using 'family_size' column specifically
        print '<div class="data-row"><span class="label">Family Size</span> <span class="value">'.((int)$object->family_size > 0 ? (int)$object->family_size.' Members' : '<span style="color:#ccc">1 Member</span>').'</span></div>';
        print '<div class="data-row"><span class="label">Employment Status</span> <span class="value">'.($object->employment_status ? dol_escape_htmltag($object->employment_status) : '<span style="color:#ccc">--</span>').'</span></div>';
        print '</div>';

    print '</div>'; // End Left Column

    // --- RIGHT COLUMN ---
    print '<div>';

        // 4. SUBSCRIPTION STATUS
        print '<div class="card">';
        print '<h3>Subscription Status</h3>';
        
        $status_label = !empty($object->subscription_status) ? $object->subscription_status : 'Pending';
        $status_color = 'bg-orange'; // Default Pending
        if($status_label == 'Active') $status_color = 'bg-green';
        if($status_label == 'Expired' || $status_label == 'Inactive') $status_color = 'bg-red';
        
        print '<div style="text-align:center; padding: 25px 0;">';
            print '<span class="badge '.$status_color.'" style="font-size:14px; padding: 8px 16px;">'.dol_escape_htmltag($status_label).'</span>';
            print '<p style="margin-top:15px; color:#7f8c8d;">Current Plan:<br><strong style="color:#2c3e50; font-size:16px;">'.($object->subscription_type ? dol_escape_htmltag($object->subscription_type) : 'No Plan Selected').'</strong></p>';
        print '</div>';
        
        print '<div class="data-row"><span class="label">Start Date</span> <span class="value">'.($object->subscription_start_date ? dol_print_date($object->subscription_start_date, 'day') : '<span style="color:#ccc">--</span>').'</span></div>';
        print '<div class="data-row"><span class="label">Renewal Due</span> <span class="value">'.($object->subscription_end_date ? dol_print_date($object->subscription_end_date, 'day') : '<span style="color:#ccc">--</span>').'</span></div>';
        print '</div>';

        // 5. ADMIN NOTES
        if (!empty($object->note)) {
            print '<div class="card" style="background:#fffbe6; border-color:#ffe58f;">';
            print '<h3 style="color:#d48806; border-bottom-color:#ffe58f;">Admin Notes</h3>';
            print '<p style="font-size:13px; color:#555; line-height:1.5;">'.dol_escape_htmltag($object->note).'</p>';
            print '</div>';
        }

        // 6. SYSTEM INFO
        print '<div class="card">';
        print '<h3>System Account</h3>';
        print '<div class="data-row"><span class="label">Login Status</span> <span class="badge '.$user_status_color.'">'.$user_status_label.'</span></div>';
        if ($user_static->id) {
            print '<div style="margin-top:15px; text-align:right;"><a href="'.DOL_URL_ROOT.'/user/card.php?id='.$user_static->id.'" style="font-size:12px; color:#3498db; text-decoration:none;">Manage Login Credentials &rarr;</a></div>';
        }
        print '</div>';

    print '</div>'; // End Right Column

print '</div>'; // End Grid
print '</div>'; // End Container

llxFooter();
?>