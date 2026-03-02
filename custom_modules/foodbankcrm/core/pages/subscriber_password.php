<?php
/**
 * Subscriber Change Password - Modern UI
 */
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';

global $user, $db;

$langs->load("admin");
$langs->load("users");

// 1. SECURITY CHECK (BENEFICIARY ONLY)
$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);
if (!$user_is_beneficiary) {
    accessforbidden('You do not have access.');
}

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Change Password');

// 2. MODERN UI & HIDE CHROME CSS
print '<style>
    /* 1. HIDE DOLIBARR CHROME */
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header {
        display: none !important;
    }
    
    /* 2. LAYOUT */
    html, body { background-color: #f8f9fa !important; margin: 0; width: 100%; overflow-x: hidden; }
    #id-right, .id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
    .fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }

    /* 3. CONTAINER & CARD */
    .ben-container { width: 95%; max-width: 600px; margin: 0 auto; padding: 40px 20px; font-family: "Segoe UI", sans-serif; }
    
    .auth-card {
        background: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border: 1px solid #f0f0f0;
    }

    /* 4. FORM ELEMENTS */
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; font-size: 14px; }
    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
        transition: all 0.2s;
        box-sizing: border-box;
    }
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        outline: none;
    }

    /* 5. BUTTONS */
    .btn-save {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 14px 28px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        width: 100%;
        transition: transform 0.1s;
    }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(118,75,162,0.3); }
    
    .btn-back {
        text-decoration: none;
        color: #666;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 6px;
        transition: background 0.2s;
    }
    .btn-back:hover { background: #e9ecef; color: #333; }

    /* 6. ALERTS */
    .alert-box { padding: 15px; border-radius: 8px; margin-bottom: 25px; font-weight: 500; font-size: 14px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>';

$error = '';
$msg = '';

// 3. HANDLE PASSWORD CHANGE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['savepass'])) {
    $pass_old = GETPOST('pass_old', 'none');
    $pass_new = GETPOST('pass_new', 'none');
    $pass_new2 = GETPOST('pass_new2', 'none');

    if (empty($pass_old)) $error = "Current password is required";
    elseif (empty($pass_new)) $error = "New password is required";
    elseif ($pass_new != $pass_new2) $error = "New passwords do not match";
    else {
        // Verify Old Password
        include_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';
        $check = checkLoginPassEntity($user->login, $pass_old, $user->entity, 'dolibarr');
        
        if (!$check) {
            $error = "Incorrect current password";
        } else {
            // Update Password
            $user->setPassword($user, $pass_new, 0, 0, 1);
            $msg = '<div class="alert-box alert-success">✓ Password changed successfully!</div>';
        }
    }
}

// 4. MAIN CONTAINER
print '<div class="ben-container">';

// Header
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">';
print '<div>';
print '<h1 style="margin: 0 0 5px 0; color: #2c3e50;">🔐 Change Password</h1>';
print '<p style="margin: 0; color: #666;">Secure your account</p>';
print '</div>';
print '<a href="my_profile.php" class="btn-back"><span>←</span> Back to Profile</a>';
print '</div>';

if ($error) {
    print '<div class="alert-box alert-error">Error: '.$error.'</div>';
}
print $msg;

// FORM CARD
print '<div class="auth-card">';
print '<form method="POST" action="'.basename(__FILE__).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<div class="form-group">';
print '<label class="form-label">Current Password</label>';
print '<input type="password" name="pass_old" required class="form-control" placeholder="Enter current password">';
print '</div>';

print '<hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">';

print '<div class="form-group">';
print '<label class="form-label">New Password</label>';
print '<input type="password" name="pass_new" required class="form-control" placeholder="Enter new password">';
print '</div>';

print '<div class="form-group" style="margin-bottom: 30px;">';
print '<label class="form-label">Confirm New Password</label>';
print '<input type="password" name="pass_new2" required class="form-control" placeholder="Repeat new password">';
print '</div>';

print '<button type="submit" name="savepass" class="btn-save">Update Password</button>';

print '</form>';
print '</div>';

print '</div>'; // End Container

llxFooter();
?>