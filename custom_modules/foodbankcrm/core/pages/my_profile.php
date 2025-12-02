<?php
/**
 * My Profile - View and Edit Profile Information
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

// Reset redirect flag
if (isset($_SESSION['foodbank_checked'])) {
    $_SESSION['foodbank_checked'] = false;
}

$langs->load("admin");

// Security check - beneficiary only
$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);

if (!$user_is_beneficiary) {
    accessforbidden('You do not have access to profile.');
}

// Get beneficiary information
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);
$subscriber = $db->fetch_object($res);

// Handle form submission
$action = GETPOST('action', 'alpha');

if ($action == 'update') {
    $firstname = GETPOST('firstname', 'alpha');
    $lastname = GETPOST('lastname', 'alpha');
    $email = GETPOST('email', 'alpha');
    $phone = GETPOST('phone', 'alpha');
    $address = GETPOST('address', 'none');
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
        setEventMessages('Profile updated successfully!', null, 'mesgs');
        // Refresh data
        $res = $db->query($sql);
        $subscriber = $db->fetch_object($res);
    } else {
        setEventMessages('Error updating profile: '.$db->lasterror(), null, 'errors');
    }
}

llxHeader('', 'My Profile');

// Hide left menu and make FULL WIDTH
echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }
</style>';

print '<div style="width: 100%; padding: 30px; box-sizing: border-box;">';

print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<h1 style="margin: 0;">👤 My Profile</h1>';
print '<a href="dashboard_beneficiary.php" class="butAction">← Back to Dashboard</a>';
print '</div>';

print '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">';

// Left column - Edit form
print '<div>';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="action" value="update">';

print '<div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<h2 style="margin-top: 0;">Personal Information</h2>';

print '<div style="margin-bottom: 20px;">';
print '<label style="display: block; margin-bottom: 8px; font-weight: bold; font-size: 14px;">First Name</label>';
print '<input type="text" name="firstname" value="'.dol_escape_htmltag($subscriber->firstname).'" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 15px;">';
print '</div>';

print '<div style="margin-bottom: 20px;">';
print '<label style="display: block; margin-bottom: 8px; font-weight: bold; font-size: 14px;">Last Name</label>';
print '<input type="text" name="lastname" value="'.dol_escape_htmltag($subscriber->lastname).'" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 15px;">';
print '</div>';

print '<div style="margin-bottom: 20px;">';
print '<label style="display: block; margin-bottom: 8px; font-weight: bold; font-size: 14px;">Email</label>';
print '<input type="email" name="email" value="'.dol_escape_htmltag($subscriber->email).'" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 15px;">';
print '</div>';

print '<div style="margin-bottom: 20px;">';
print '<label style="display: block; margin-bottom: 8px; font-weight: bold; font-size: 14px;">Phone</label>';
print '<input type="text" name="phone" value="'.dol_escape_htmltag($subscriber->phone).'" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 15px;">';
print '</div>';

print '<div style="margin-bottom: 20px;">';
print '<label style="display: block; margin-bottom: 8px; font-weight: bold; font-size: 14px;">Address</label>';
print '<textarea name="address" rows="3" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 15px;">'.dol_escape_htmltag($subscriber->address).'</textarea>';
print '</div>';

print '<div style="margin-bottom: 25px;">';
print '<label style="display: block; margin-bottom: 8px; font-weight: bold; font-size: 14px;">Household Size</label>';
print '<input type="number" name="household_size" value="'.$subscriber->household_size.'" min="1" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 15px;">';
print '</div>';

print '<button type="submit" class="butAction" style="margin: 0; padding: 12px 24px; font-size: 16px;">💾 SAVE CHANGES</button>';

print '</div>';
print '</form>';
print '</div>';

// Right column - Account info
print '<div>';

// Account Information Card
print '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<h3 style="margin: 0 0 20px 0; font-size: 18px;">📋 Account Information</h3>';
print '<div style="margin-bottom: 15px;">';
print '<div style="opacity: 0.9; font-size: 13px; margin-bottom: 3px;">Subscriber ID:</div>';
print '<div style="font-weight: bold; font-size: 20px;">'.dol_escape_htmltag($subscriber->ref).'</div>';
print '</div>';
print '<div style="margin-bottom: 15px;">';
print '<div style="opacity: 0.9; font-size: 13px; margin-bottom: 3px;">Member Since:</div>';
print '<div style="font-weight: bold; font-size: 16px;">'.dol_print_date($db->jdate($subscriber->registration_date), 'day').'</div>';
print '</div>';
print '<div>';
print '<div style="opacity: 0.9; font-size: 13px; margin-bottom: 3px;">Username:</div>';
print '<div style="font-weight: bold; font-size: 16px;">'.dol_escape_htmltag($user->login).'</div>';
print '</div>';
print '</div>';

// Subscription Card
print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">';
print '<h3 style="margin: 0 0 20px 0; font-size: 18px;">💳 Subscription</h3>';
print '<div style="margin-bottom: 15px;">';
print '<div style="color: #666; font-size: 13px; margin-bottom: 3px;">Plan:</div>';
print '<div style="font-weight: bold; font-size: 20px;">'.dol_escape_htmltag($subscriber->subscription_type ?: 'Guest').'</div>';
print '</div>';
print '<div style="margin-bottom: 15px;">';
print '<div style="color: #666; font-size: 13px; margin-bottom: 3px;">Status:</div>';
print '<div style="display: inline-block; padding: 6px 14px; background: #28a745; color: white; border-radius: 15px; font-weight: bold; font-size: 14px;">';
print dol_escape_htmltag($subscriber->subscription_status);
print '</div>';
print '</div>';
if (!empty($subscriber->subscription_fee)) {
    print '<div>';
    print '<div style="color: #666; font-size: 13px; margin-bottom: 3px;">Fee:</div>';
    print '<div style="font-weight: bold; font-size: 18px;">₦'.number_format($subscriber->subscription_fee, 0).'</div>';
    print '</div>';
}
print '</div>';

// Password Change Card
print '<div style="background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 8px;">';
print '<h3 style="margin: 0 0 10px 0; font-size: 16px;">🔐 Change Password</h3>';
print '<p style="margin: 0 0 15px 0; font-size: 14px; color: #856404;">To change your password, use the Dolibarr account settings page.</p>';
print '<a href="/user/card.php?id='.$user->id.'" class="butAction" style="margin: 0; padding: 10px 20px; font-size: 14px;">GO TO ACCOUNT SETTINGS</a>';
print '</div>';

print '</div>';

print '</div>';

print '</div>';

llxFooter();
?>