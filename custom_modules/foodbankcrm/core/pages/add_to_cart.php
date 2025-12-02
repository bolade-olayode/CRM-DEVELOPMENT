
<?php
/**
 * Add Package to Cart
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';
require_once __DIR__."/check_subscription_status.php";

global $user, $db, $conf;

// Security check
$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);

if (!$user_is_beneficiary) {
    accessforbidden('You do not have access.');
}

// Get beneficiary ID
$sql_ben = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res_ben = $db->query($sql_ben);

if (!$res_ben || $db->num_rows($res_ben) == 0) {
    accessforbidden('Beneficiary profile not found.');
}

$beneficiary = $db->fetch_object($res_ben);
$subscriber_id = $beneficiary->rowid;

// Get package ID from URL
$package_id = GETPOST('id', 'int');
$quantity = GETPOST('quantity', 'int') ?: 1;

if (empty($package_id)) {
    setEventMessages('Invalid package', null, 'errors');
    header('Location: product_catalog.php');
    exit;
}

// Get package details and calculate price from package_items
$sql = "SELECT p.rowid, p.name, p.status,
        SUM(pi.quantity * pi.unit_price) as total_price
        FROM ".MAIN_DB_PREFIX."foodbank_packages p
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_package_items pi ON p.rowid = pi.fk_package
        WHERE p.rowid = ".(int)$package_id."
        GROUP BY p.rowid";
$res = $db->query($sql);

if (!$res || $db->num_rows($res) == 0) {
    setEventMessages('Package not found', null, 'errors');
    header('Location: product_catalog.php');
    exit;
}

$package = $db->fetch_object($res);

// Check if package is active
if ($package->status != 'Active') {
    setEventMessages('This package is not available', null, 'errors');
    header('Location: product_catalog.php');
    exit;
}

$package_price = $package->total_price ?? 0;

if ($package_price <= 0) {
    setEventMessages('This package is not available for purchase (price not set)', null, 'warnings');
    header('Location: product_catalog.php');
    exit;
}

// Check if already in cart
$sql_check = "SELECT rowid, quantity FROM ".MAIN_DB_PREFIX."foodbank_cart 
              WHERE fk_subscriber = ".(int)$subscriber_id." 
              AND fk_package = ".(int)$package_id;
$res_check = $db->query($sql_check);

if ($res_check && $db->num_rows($res_check) > 0) {
    // Update quantity
    $cart_item = $db->fetch_object($res_check);
    $new_quantity = $cart_item->quantity + $quantity;
    
    $sql_update = "UPDATE ".MAIN_DB_PREFIX."foodbank_cart 
                   SET quantity = ".(float)$new_quantity.",
                       unit_price = ".(float)$package_price."
                   WHERE rowid = ".(int)$cart_item->rowid;
    
    if ($db->query($sql_update)) {
        setEventMessages('Package quantity updated in cart!', null, 'mesgs');
    } else {
        setEventMessages('Error updating cart: '.$db->lasterror(), null, 'errors');
    }
} else {
    // Add new item to cart
    $sql_insert = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_cart 
                   (fk_subscriber, fk_package, fk_donation, quantity, unit_price, date_added)
                   VALUES (
                       ".(int)$subscriber_id.",
                       ".(int)$package_id.",
                       NULL,
                       ".(float)$quantity.",
                       ".(float)$package_price.",
                       NOW()
                   )";
    
    if ($db->query($sql_insert)) {
        setEventMessages('✅ '.dol_escape_htmltag($package->name).' added to cart!', null, 'mesgs');
    } else {
        setEventMessages('Error adding to cart: '.$db->lasterror(), null, 'errors');
    }
}

// Redirect back to catalog with success message
header('Location: product_catalog.php');
exit;
?>
