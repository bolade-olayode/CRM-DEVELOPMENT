<?php
/**
 * ADMIN VENDOR PROFILE (FINAL WITH NOTIFICATIONS)
 * Features: 
 * 1. Auto-Emails Vendor upon Approval.
 * 2. Shows visible Success Message to Admin.
 */
define('NOCSRFCHECK', 1);
define('NOTOKENRENEWAL', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php'; // Required for Email
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader('', 'Vendor Profile');

$id = GETPOST('id', 'int');
if (!$id) accessforbidden();

$action = GETPOST('action', 'alpha');
$msg_code = GETPOST('msg', 'alpha');

// --- HANDLE APPROVAL ---
if ($action == 'approve') {
    // 1. Fetch Vendor Details (To get email for notification)
    $sql_v = "SELECT contact_email, contact_person, name FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE rowid = " . $id;
    $res_v = $db->query($sql_v);
    $vendor = $db->fetch_object($res_v);

    // 2. Update Database
    $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_vendors SET status = 'Active' WHERE rowid = " . $id);
    
    // 3. SEND EMAIL NOTIFICATION
    if ($vendor && $vendor->contact_email) {
        $subject = "Account Approved - Foodbank Partner";
        $message = "Dear " . $vendor->contact_person . ",\n\n";
        $message .= "We are pleased to inform you that your vendor account for " . $vendor->name . " has been APPROVED.\n\n";
        $message .= "You can now log in to your dashboard to manage inventory and supplies.\n";
        $message .= "Login here: " . DOL_MAIN_URL_ROOT . "/custom/foodbankcrm/index.php\n\n";
        $message .= "Best Regards,\nFoodbank Admin Team";
        
        $from = !empty($conf->global->MAIN_MAIL_EMAIL_FROM) ? $conf->global->MAIN_MAIL_EMAIL_FROM : 'no-reply@foodbank.com';
        $mail = new CMailFile($subject, $vendor->contact_email, $from, $message);
        $mail->sendfile();
    }

    // 4. Redirect with Success Flag
    header("Location: view_vendor.php?id=" . $id . "&msg=approved");
    exit;
}

// --- HANDLE REJECTION ---
if ($action == 'reject') {
    $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_vendors SET status = 'Inactive' WHERE rowid = " . $id);
    header("Location: view_vendor.php?id=" . $id . "&msg=rejected");
    exit;
}

// Fetch Data for Display
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE rowid = " . $id;
$res = $db->query($sql);
if (!$res || $db->num_rows($res) == 0) exit("Vendor not found.");
$obj = $db->fetch_object($res);

// CSS
print '<style>
    #id-top, .tmenu, .login_block { display: none !important; }
    body { padding-top: 0 !important; background: #f8f9fa; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; margin-top: 0 !important; }
    
    .profile-card { background: white; border-radius: 8px; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); max-width: 1000px; margin: 0 auto; }
    
    .status-badge { padding: 5px 12px; border-radius: 15px; font-weight: bold; font-size: 12px; text-transform: uppercase; color: white; }
    .status-pending { background: #fd7e14; }
    .status-active { background: #28a745; }
    .status-inactive { background: #dc3545; }
    
    .btn-approve { background: #28a745; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; transition: 0.2s; margin-right: 10px; }
    .btn-approve:hover { background: #218838; }

    .btn-reject { background: #dc3545; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; transition: 0.2s; }
    .btn-reject:hover { background: #c82333; }
    
    .alert-box { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; display: flex; align-items: center; gap: 10px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    
    .data-label { color: #888; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .data-value { font-size: 16px; color: #333; font-weight: 500; margin-bottom: 20px; }
    
    h4 { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; color: #2c3e50; margin-top: 0; }
</style>';

print '<div class="profile-card">';

// --- SUCCESS/ERROR MESSAGES ---
if ($msg_code == 'approved') {
    print '<div class="alert-box alert-success">✅ Vendor Approved! Notification email sent.</div>';
}
if ($msg_code == 'rejected') {
    print '<div class="alert-box alert-danger">❌ Vendor Rejected. Access has been disabled.</div>';
}

// Header
$status_color = 'status-pending';
if ($obj->status == 'Active') $status_color = 'status-active';
if ($obj->status == 'Inactive') $status_color = 'status-inactive';

print '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:40px;">';
print '<div>';
print '<h1 style="margin:0 0 10px 0;">🏢 '.dol_escape_htmltag($obj->name).'</h1>';
print '<span class="status-badge '.$status_color.'">'.$obj->status.'</span>';
print ' <span style="color:#999; margin-left:10px;">REF: '.dol_escape_htmltag($obj->ref).'</span>';
print '</div>';
print '<div>';
print '<a href="edit_vendor.php?id='.$obj->rowid.'" class="button" style="margin-right:10px; background:#fff; border:1px solid #ddd; color:#333;">✏️ Edit Profile</a>';
print '<a href="vendors.php" class="button" style="background:#edf2f7; color:#4a5568;">Back to List</a>';
print '</div>';
print '</div>';

// --- APPROVAL / REJECTION SECTION ---
if ($obj->status == 'Pending') {
    print '<div style="background:#fff3cd; border:1px solid #ffeeba; padding:20px; border-radius:8px; margin-bottom:40px;">';
    print '<h3 style="color:#856404; margin:0 0 10px 0;">⚠️ Action Required</h3>';
    print '<p style="margin:0 0 20px 0; color:#856404;">This vendor application is pending. Please review the details below and make a decision.</p>';
    
    print '<div style="display:flex;">';
        // Approve Form
        print '<form method="POST" onsubmit="return confirm(\'Approve this vendor? An email will be sent.\');" style="margin:0;">';
        print '<input type="hidden" name="action" value="approve">';
        print '<button type="submit" class="btn-approve">✅ Approve Access</button>';
        print '</form>';

        // Reject Form
        print '<form method="POST" onsubmit="return confirm(\'Reject this vendor?\');" style="margin:0;">';
        print '<input type="hidden" name="action" value="reject">';
        print '<button type="submit" class="btn-reject">❌ Reject / Disapprove</button>';
        print '</form>';
    print '</div>';
    
    print '</div>';
}

// Data Grid
print '<div style="display:grid; grid-template-columns: 1fr 1fr; gap:40px;">';
print '<div><h4>👤 Contact Details</h4>';
print '<div class="data-label">Contact Person</div><div class="data-value">'.dol_escape_htmltag($obj->contact_person).'</div>';
print '<div class="data-label">Email Address</div><div class="data-value"><a href="mailto:'.dol_escape_htmltag($obj->contact_email).'">'.dol_escape_htmltag($obj->contact_email).'</a></div>';
print '<div class="data-label">Phone Number</div><div class="data-value">'.dol_escape_htmltag($obj->contact_phone).'</div>';
print '<div class="data-label">Office Address</div><div class="data-value">'.nl2br(dol_escape_htmltag($obj->address)).'</div>';
print '</div>';

print '<div><h4>🏢 Business Info</h4>';
print '<div class="data-label">Supply Category</div><div class="data-value">'.dol_escape_htmltag($obj->category).'</div>';
print '<div class="data-label">Registration No (RC)</div><div class="data-value">'.dol_escape_htmltag($obj->registration_number).'</div>';
print '<div class="data-label">Tax ID (TIN)</div><div class="data-value">'.dol_escape_htmltag($obj->tax_id).'</div>';
print '<div class="data-label">Website</div><div class="data-value">'.($obj->website ? '<a href="'.dol_escape_htmltag($obj->website).'" target="_blank">'.dol_escape_htmltag($obj->website).'</a>' : 'N/A').'</div>';
print '</div>';
print '</div>';

// Banking
print '<div style="margin-top:20px; background:#f8f9fa; padding:20px; border-radius:8px;">';
print '<h4>🏦 Banking Information</h4>';
print '<div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">';
print '<div><div class="data-label">Bank Name</div><div class="data-value">'.($obj->bank_name ? dol_escape_htmltag($obj->bank_name) : 'N/A').'</div></div>';
print '<div><div class="data-label">Account Number</div><div class="data-value">'.($obj->bank_account_number ? dol_escape_htmltag($obj->bank_account_number) : 'N/A').'</div></div>';
print '</div>';
print '</div>';

print '</div>';
llxFooter();
?>