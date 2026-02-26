<?php
// Redirect Dashboard - Routes users to appropriate dashboard
// This file is called from main.inc.php after login

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');

global $user, $db;

// Don't redirect if:
// 1. User is not logged in
// 2. Already on a dashboard page
// 3. Is admin
// 4. Is on a specific action page
if (empty($user) || empty($user->id)) {
    return;
}

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);

// Don't redirect if already on a dashboard or specific page
$skip_pages = array(
    'dashboard_admin.php',
    'dashboard_vendor.php',
    'dashboard_beneficiary.php',
    'index.php',
    'logout.php',
    'card.php',
    'perms.php',
    'list.php'
);

if (in_array($current_page, $skip_pages)) {
    return;
}

// Don't redirect if on main menu or setup pages
$current_uri = $_SERVER['REQUEST_URI'];
if (strpos($current_uri, 'mainmenu=') !== false || 
    strpos($current_uri, 'leftmenu=') !== false ||
    strpos($current_uri, '/admin/') !== false) {
    return;
}

// Load permissions class
require_once __DIR__ . '/../class/permissions.class.php';

// Check if user is admin
if (FoodbankPermissions::isAdmin($user)) {
    header('Location: '.DOL_URL_ROOT.'/custom/foodbankcrm/core/pages/dashboard_admin.php');
    exit;
}

// Check if user is a vendor
if (FoodbankPermissions::isVendor($user, $db)) {
    // Check if vendor record exists and is linked
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
    $resql = $db->query($sql);
    
    if ($resql && $db->num_rows($resql) > 0) {
        // Redirect to vendor dashboard
        header('Location: '.DOL_URL_ROOT.'/custom/foodbankcrm/core/pages/dashboard_vendor.php');
        exit;
    }
}

// Check if user is a beneficiary/subscriber
if (FoodbankPermissions::isBeneficiary($user, $db)) {
    // Check if beneficiary record exists and is linked
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
    $resql = $db->query($sql);
    
    if ($resql && $db->num_rows($resql) > 0) {
        // Redirect to beneficiary dashboard
        header('Location: '.DOL_URL_ROOT.'/custom/foodbankcrm/core/pages/dashboard_beneficiary.php');
        exit;
    }
}

// If we get here, user is either admin or unlinked - let them use default dashboard
return;
?>