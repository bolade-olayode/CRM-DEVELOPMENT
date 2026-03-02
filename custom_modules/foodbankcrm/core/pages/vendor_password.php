<?php
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';

global $user, $db;

$langs->load("admin");
$langs->load("users");

// 1. SECURITY CHECK
$user_is_vendor = FoodbankPermissions::isVendor($user, $db);
if (!$user_is_vendor) {
    accessforbidden('You do not have access.');
}

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Change Password');

// 2. PORTAL MODE CSS
echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }
</style>';

$error = '';
$msg = '';

// 3. HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['savepass'])) {
    $pass_old = GETPOST('pass_old', 'none');
    $pass_new = GETPOST('pass_new', 'none');
    $pass_new2 = GETPOST('pass_new2', 'none');

    // Verification
    if (empty($pass_old)) $error = "Current password is required";
    elseif (empty($pass_new)) $error = "New password is required";
    elseif ($pass_new != $pass_new2) $error = "New passwords do not match";
    else {
        // Verify Old Password
        // We use a small trick: try to "login" with old pass to verify it
        include_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';
        $check = checkLoginPassEntity($user->login, $pass_old, $user->entity, 'dolibarr');
        
        if (!$check) {
            $error = "Incorrect current password";
        } else {
            // Update Password
            $user->setPassword($user, $pass_new, 0, 0, 1);
            $msg = '<div class="ok" style="padding:15px; background:#d4edda; color:#155724; border:1px solid #c3e6cb; border-radius:5px; margin-bottom:20px;">✓ Password changed successfully!</div>';
        }
    }
}

// 4. MAIN CONTAINER
print '<div style="width: 100%; padding: 30px; box-sizing: border-box; max-width: 600px; margin: 0 auto;">';

// Header
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<div>';
print '<h1 style="margin: 0;">🔒 Change Password</h1>';
print '<p style="color: #666; margin: 5px 0 0 0;">Update your login credentials</p>';
print '</div>';
print '<a href="vendor_profile.php" class="butAction">← Back to Profile</a>';
print '</div>';

if ($error) {
    print '<div class="error" style="padding:15px; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; border-radius:5px; margin-bottom:20px;">Error: '.$error.'</div>';
}
print $msg;

// FORM
print '<div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">';
print '<form method="POST" action="'.basename(__FILE__).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<div style="margin-bottom: 20px;">';
print '<label style="display:block; margin-bottom:8px; font-weight:bold;">Current Password</label>';
print '<input type="password" name="pass_old" required class="flat" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:5px;">';
print '</div>';

print '<hr style="margin: 25px 0; border: 0; border-top: 1px solid #eee;">';

print '<div style="margin-bottom: 20px;">';
print '<label style="display:block; margin-bottom:8px; font-weight:bold;">New Password</label>';
print '<input type="password" name="pass_new" required class="flat" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:5px;">';
print '</div>';

print '<div style="margin-bottom: 30px;">';
print '<label style="display:block; margin-bottom:8px; font-weight:bold;">Confirm New Password</label>';
print '<input type="password" name="pass_new2" required class="flat" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:5px;">';
print '</div>';

print '<div style="text-align: center;">';
print '<button type="submit" name="savepass" class="butAction" style="padding: 12px 40px; font-size: 16px;">Update Password</button>';
print '</div>';

print '</form>';
print '</div>';

print '</div>';

llxFooter();
?>