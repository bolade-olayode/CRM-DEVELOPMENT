<?php
/**
 * Smart Dashboard Router
 * Accessed directly via the top "Foodbank CRM" menu item.
 * Reads the logged-in user's role and redirects to the correct dashboard.
 */
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;

// Not logged in — send to Dolibarr login page
if (empty($user) || empty($user->id)) {
    header('Location: ' . DOL_URL_ROOT . '/index.php');
    exit;
}

// Admin
if (FoodbankPermissions::isAdmin($user)) {
    header('Location: ' . DOL_URL_ROOT . '/custom/foodbankcrm/core/pages/dashboard_admin.php');
    exit;
}

// Vendor
if (FoodbankPermissions::isVendor($user, $db)) {
    header('Location: ' . DOL_URL_ROOT . '/custom/foodbankcrm/core/pages/dashboard_vendor.php');
    exit;
}

// Beneficiary
if (FoodbankPermissions::isBeneficiary($user, $db)) {
    header('Location: ' . DOL_URL_ROOT . '/custom/foodbankcrm/core/pages/dashboard_beneficiary.php');
    exit;
}

// Fallback — unlinked user, send to Dolibarr home
header('Location: ' . DOL_URL_ROOT . '/index.php');
exit;
