<?php
require_once dirname(__DIR__, 2) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';
$langs->load("admin");

if (!empty($user->id) && !isset($_GET['action']) && !isset($_GET['mainmenu']) && !isset($_SESSION['dol_loginmesg'])) {
    
    // Only redirect on first page load, not when navigating menus
    if (!isset($_SESSION['foodbank_redirected']) || $_SESSION['foodbank_redirected'] !== true) {
        
        // Check if vendor
        $sql_vendor = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
        $res_vendor = $db->query($sql_vendor);
        if ($res_vendor) {
            $obj = $db->fetch_object($res_vendor);
            if ($obj->count > 0) {
                $_SESSION['foodbank_redirected'] = true;
                header('Location: /custom/foodbankcrm/core/pages/dashboard_vendor.php');
                exit;
            }
        }
        
        // Check if beneficiary
        $sql_ben = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
        $res_ben = $db->query($sql_ben);
        if ($res_ben) {
            $obj = $db->fetch_object($res_ben);
            if ($obj->count > 0) {
                $_SESSION['foodbank_redirected'] = true;
                header('Location: /custom/foodbankcrm/core/pages/dashboard_beneficiary.php');
                exit;
            }
        }
        
        // Mark as checked for admin users too
        $_SESSION['foodbank_redirected'] = true;
    }
}

// Determine user role and redirect to appropriate dashboard
if (FoodbankPermissions::isAdmin($user)) {
    // Admin - redirect to admin dashboard
    header('Location: /custom/foodbankcrm/core/pages/dashboard_admin.php');
    exit;
} elseif (FoodbankPermissions::isVendor($user, $db)) {
    // Vendor - redirect to vendor dashboard
    header('Location: /custom/foodbankcrm/core/pages/dashboard_vendor.php');
    exit;
} elseif (FoodbankPermissions::isBeneficiary($user, $db)) {
    // Beneficiary - redirect to beneficiary dashboard
    header('Location: /custom/foodbankcrm/core/pages/dashboard_beneficiary.php');
    exit;
} else {
    // No permissions - show error
    llxHeader();
    
    print '<div style="text-align: center; padding: 60px 20px;">';
    print '<div style="font-size: 64px; margin-bottom: 20px;">🔒</div>';
    print '<h2 style="color: #dc3545;">Access Denied</h2>';
    print '<p style="color: #666; font-size: 16px;">You do not have permission to access the Foodbank CRM system.</p>';
    print '<p style="color: #666;">Please contact your administrator to request access.</p>';
    print '<br>';
    print '<a href="/" class="button">Return to Home</a>';
    print '</div>';
    
    llxFooter();
}
?>
