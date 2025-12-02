
<?php
/**
 * View Shopping Cart
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';
require_once __DIR__."/check_subscription_status.php";

global $user, $db, $conf;

// Reset redirect flag
if (isset($_SESSION['foodbank_checked'])) {
    $_SESSION['foodbank_checked'] = false;
}

$langs->load("admin");

// Security check
$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);

if (!$user_is_beneficiary) {
    accessforbidden('You do not have access to the cart.');
}

// Get beneficiary ID
$sql_ben = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res_ben = $db->query($sql_ben);
$beneficiary = $db->fetch_object($res_ben);
$subscriber_id = $beneficiary->rowid;

// Handle cart actions
$action = GETPOST('action', 'alpha');

if ($action == 'remove') {
    if (!isset($_GET['token']) || $_GET['token'] != newToken()) {
        accessforbidden('Invalid security token');
    }
    
    $item_id = GETPOST('id', 'int');
    if ($item_id > 0) {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_cart WHERE rowid = ".(int)$item_id." AND fk_subscriber = ".(int)$subscriber_id;
        if ($db->query($sql)) {
            setEventMessages('Item removed from cart', null, 'mesgs');
        }
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'update') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        accessforbidden('Invalid security token');
    }
    
    $item_id = GETPOST('id', 'int');
    $quantity = GETPOST('quantity', 'int');
    if ($quantity > 0 && $item_id > 0) {
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_cart SET quantity = ".(int)$quantity." WHERE rowid = ".(int)$item_id." AND fk_subscriber = ".(int)$subscriber_id;
        if ($db->query($sql)) {
            setEventMessages('Quantity updated', null, 'mesgs');
        }
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

llxHeader('', 'Shopping Cart');

// Hide left menu and make FULL WIDTH
echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }
</style>';

print '<div style="width: 100%; padding: 30px; box-sizing: border-box; max-width: 1200px; margin: 0 auto;">';

print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<h1 style="margin: 0;">🛍️ Your Shopping Cart</h1>';
print '<a href="dashboard_beneficiary.php" class="butAction">← Back to Dashboard</a>';
print '</div>';

// Get cart items
$sql = "SELECT c.rowid, c.quantity, c.unit_price, c.fk_package,
        p.name as package_name, p.ref as package_ref,
        (c.quantity * c.unit_price) as line_total
        FROM ".MAIN_DB_PREFIX."foodbank_cart c
        INNER JOIN ".MAIN_DB_PREFIX."foodbank_packages p ON c.fk_package = p.rowid
        WHERE c.fk_subscriber = ".(int)$subscriber_id."
        AND c.fk_package IS NOT NULL";

$res = $db->query($sql);

if (!$res) {
    print '<div class="error">Error loading cart: '.$db->lasterror().'</div>';
    print '</div>';
    llxFooter();
    exit;
}

$num = $db->num_rows($res);

if ($num == 0) {
    print '<div style="text-align: center; padding: 80px 20px; background: white; border-radius: 8px;">';
    print '<div style="font-size: 80px; margin-bottom: 20px;">🛒</div>';
    print '<h2 style="margin: 0 0 10px 0;">Your Cart is Empty</h2>';
    print '<p style="color: #666; font-size: 16px; margin-bottom: 30px;">Start shopping to add packages to your cart!</p>';
    print '<a href="product_catalog.php" class="butAction" style="padding: 12px 24px; font-size: 16px;">BROWSE PACKAGES</a>';
    print '</div>';
} else {
    $grand_total = 0;
    
    print '<div style="background: white; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    print '<table style="width: 100%; border-collapse: collapse;">';
    print '<thead style="background: #f8f9fa;">';
    print '<tr>';
    print '<th style="padding: 20px; text-align: left; font-size: 15px;">Package</th>';
    print '<th style="padding: 20px; text-align: center; font-size: 15px;">Quantity</th>';
    print '<th style="padding: 20px; text-align: right; font-size: 15px;">Price</th>';
    print '<th style="padding: 20px; text-align: right; font-size: 15px;">Total</th>';
    print '<th style="padding: 20px; text-align: center; font-size: 15px;">Actions</th>';
    print '</tr>';
    print '</thead>';
    print '<tbody>';
    
    while ($obj = $db->fetch_object($res)) {
        $grand_total += $obj->line_total;
        
        print '<tr style="border-top: 1px solid #ddd;">';
        print '<td style="padding: 20px;">';
        print '<strong style="font-size: 16px;">'.dol_escape_htmltag($obj->package_name).'</strong><br>';
        print '<small style="color: #666;">Ref: '.dol_escape_htmltag($obj->package_ref).'</small>';
        print '</td>';
        
        print '<td style="padding: 20px; text-align: center;">';
        print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" style="display: inline-flex; align-items: center; gap: 10px;">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="id" value="'.$obj->rowid.'">';
        print '<input type="number" name="quantity" value="'.$obj->quantity.'" min="1" max="99" style="width: 80px; padding: 8px; text-align: center; border: 1px solid #ddd; border-radius: 4px; font-size: 15px;">';
        print '<button type="submit" class="butAction" style="padding: 8px 16px; margin: 0;">Update</button>';
        print '</form>';
        print '</td>';
        
        print '<td style="padding: 20px; text-align: right; font-size: 16px;">₦'.number_format($obj->unit_price, 2).'</td>';
        print '<td style="padding: 20px; text-align: right;"><strong style="font-size: 18px; color: #28a745;">₦'.number_format($obj->line_total, 2).'</strong></td>';
        
        print '<td style="padding: 20px; text-align: center;">';
        print '<a href="'.$_SERVER['PHP_SELF'].'?action=remove&id='.$obj->rowid.'&token='.newToken().'" class="butActionDelete" style="padding: 8px 16px;" onclick="return confirm(\'Remove this item from cart?\');">Remove</a>';
        print '</td>';
        print '</tr>';
    }
    
    print '<tr style="background: #f8f9fa; font-weight: bold;">';
    print '<td colspan="3" style="padding: 25px; text-align: right; font-size: 20px;">Grand Total:</td>';
    print '<td style="padding: 25px; text-align: right; font-size: 28px; color: #28a745;">₦'.number_format($grand_total, 2).'</td>';
    print '<td></td>';
    print '</tr>';
    
    print '</tbody>';
    print '</table>';
    print '</div>';
    
    print '<div style="margin-top: 30px; text-align: right; display: flex; gap: 15px; justify-content: flex-end;">';
    print '<a href="product_catalog.php" class="butAction" style="background: #6c757d; padding: 15px 30px; font-size: 16px;">Continue Shopping</a>';
    print '<a href="checkout.php" class="butAction" style="padding: 15px 30px; font-size: 16px; background: #28a745;">Proceed to Checkout →</a>';
    print '</div>';
}

print '</div>';

llxFooter();
?>
