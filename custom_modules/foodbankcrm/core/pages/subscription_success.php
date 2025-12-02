
<?php
/**
 * Subscription Success Page
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);

if (!$user_is_beneficiary) {
    accessforbidden('You do not have access.');
}

$tier = GETPOST('tier', 'alpha');
$end_date = GETPOST('end_date', 'alpha');

llxHeader('', 'Subscription Activated');

echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }
</style>';

print '<div style="width: 100%; padding: 30px; box-sizing: border-box; max-width: 800px; margin: 0 auto;">';

print '<div style="background: white; padding: 50px; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">';

print '<div style="font-size: 80px; margin-bottom: 20px;">🎉</div>';

print '<h1 style="color: #28a745; margin: 0 0 15px 0;">Subscription Activated!</h1>';

print '<p style="font-size: 18px; color: #666; margin: 0 0 30px 0;">Your subscription has been successfully activated.</p>';

print '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px; margin-bottom: 30px;">';
print '<h3 style="margin: 0 0 20px 0; font-size: 20px;">Subscription Details</h3>';

print '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; text-align: left;">';

print '<div>';
print '<div style="opacity: 0.9; font-size: 14px; margin-bottom: 5px;">Plan</div>';
print '<div style="font-size: 22px; font-weight: bold;">'.dol_escape_htmltag($tier).'</div>';
print '</div>';

print '<div>';
print '<div style="opacity: 0.9; font-size: 14px; margin-bottom: 5px;">Status</div>';
print '<div style="font-size: 22px; font-weight: bold;">✅ Active</div>';
print '</div>';

print '<div>';
print '<div style="opacity: 0.9; font-size: 14px; margin-bottom: 5px;">Start Date</div>';
print '<div style="font-size: 18px; font-weight: bold;">'.date('M d, Y').'</div>';
print '</div>';

print '<div>';
print '<div style="opacity: 0.9; font-size: 14px; margin-bottom: 5px;">Valid Until</div>';
print '<div style="font-size: 18px; font-weight: bold;">'.date('M d, Y', strtotime($end_date)).'</div>';
print '</div>';

print '</div>';
print '</div>';

print '<div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin-bottom: 30px;">';
print '<h4 style="margin: 0 0 10px 0;">🎁 What You Can Do Now:</h4>';
print '<ul style="text-align: left; margin: 0; padding-left: 20px; line-height: 2;">';
print '<li>Browse all available packages</li>';
print '<li>Add packages to cart</li>';
print '<li>Place unlimited orders</li>';
print '<li>Track your order history</li>';
print '</ul>';
print '</div>';

print '<div style="display: flex; gap: 15px; justify-content: center;">';
print '<a href="dashboard_beneficiary.php" class="butAction" style="margin: 0; padding: 12px 24px; font-size: 16px;">Go to Dashboard</a>';
print '<a href="product_catalog.php" class="butAction" style="margin: 0; padding: 12px 24px; font-size: 16px; background: #28a745;">Start Shopping</a>';
print '</div>';

print '</div>';

print '</div>';

llxFooter();
?>
