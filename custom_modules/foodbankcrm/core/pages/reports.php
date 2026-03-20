<?php
/**
 * Reports - Coming Soon
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$_SESSION["mainmenu"] = "foodbankcrm";
$_fb_admin_head = '<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">'
              . '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/css/admin_mobile.css">';
llxHeader($_fb_admin_head, 'Reports');

print '<div style="text-align: center; padding: 80px 20px;">';
print '<div style="font-size: 64px; margin-bottom: 20px;">📊</div>';
print '<h2 style="color: #555;">Reports Coming Soon</h2>';
print '<p style="color: #888;">This feature is currently under development.</p>';
print '<a href="dashboard_admin.php" class="button" style="margin-top: 20px;">Back to Dashboard</a>';
print '</div>';

llxFooter();
?>
