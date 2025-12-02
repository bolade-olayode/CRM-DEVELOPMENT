
<?php
/**
 * Product Catalog - Browse Available Packages
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';
require_once __DIR__."/check_subscription_status.php";

global $user, $db, $conf;

if (isset($_SESSION['foodbank_checked'])) {
    $_SESSION['foodbank_checked'] = false;
}

$langs->load("admin");

$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);

if (!$user_is_beneficiary) {
    accessforbidden('You do not have access to the product catalog.');
}

$search_text = GETPOST('search_text', 'alpha');
$sort_by = GETPOST('sort_by', 'alpha') ?: 'name';

llxHeader('', 'Browse Packages');

echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }

.quantity-selector {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 15px;
}

.quantity-btn {
    width: 35px;
    height: 35px;
    border: 2px solid #667eea;
    background: white;
    color: #667eea;
    font-size: 20px;
    font-weight: bold;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s;
}

.quantity-btn:hover {
    background: #667eea;
    color: white;
}

.quantity-display {
    font-size: 20px;
    font-weight: bold;
    min-width: 40px;
    text-align: center;
}
</style>';

print '<div style="width: 100%; padding: 30px; box-sizing: border-box;">';

print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<h1 style="margin: 0;">🎁 Available Packages</h1>';
print '<a href="dashboard_beneficiary.php" class="butAction">← Back to Dashboard</a>';
print '</div>';

print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" style="background: white; padding: 25px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">';

print '<div>';
print '<label style="display: block; margin-bottom: 8px; font-weight: bold;">Search packages</label>';
print '<input type="text" name="search_text" value="'.dol_escape_htmltag($search_text).'" placeholder="Search by package name..." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 15px;">';
print '</div>';

print '<div>';
print '<label style="display: block; margin-bottom: 8px; font-weight: bold;">Sort by</label>';
print '<select name="sort_by" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 15px;">';
print '<option value="name" '.($sort_by == 'name' ? 'selected' : '').'>Name</option>';
print '<option value="price" '.($sort_by == 'price' ? 'selected' : '').'>Price</option>';
print '</select>';
print '</div>';

print '<div style="display: flex; gap: 10px;">';
print '<button type="submit" class="butAction" style="margin: 0; padding: 12px 24px;">SEARCH</button>';
print '<a href="'.$_SERVER['PHP_SELF'].'" class="butAction" style="margin: 0; padding: 12px 24px; background: #6c757d;">CLEAR</a>';
print '</div>';

print '</div>';
print '</form>';

$sql = "SELECT p.rowid, p.ref, p.name, p.description, p.status,
        GROUP_CONCAT(CONCAT(pi.product_name, ' (', pi.quantity, ' ', pi.unit, ')') SEPARATOR ', ') as items_list,
        SUM(pi.quantity * pi.unit_price) as total_price
        FROM ".MAIN_DB_PREFIX."foodbank_packages p
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_package_items pi ON p.rowid = pi.fk_package
        WHERE p.status = 'Active'";

if ($search_text) {
    $sql .= " AND p.name LIKE '%".$db->escape($search_text)."%'";
}

$sql .= " GROUP BY p.rowid";

if ($sort_by == 'price') {
    $sql .= " ORDER BY total_price ASC";
} else {
    $sql .= " ORDER BY p.name ASC";
}

$resql = $db->query($sql);

if ($resql) {
    $num = $db->num_rows($resql);
    
    if ($num > 0) {
        print '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px;">';
        
        while ($obj = $db->fetch_object($resql)) {
            $package_price = $obj->total_price ?? 0;
            
            print '<div style="background: white; border: 2px solid #e0e0e0; border-radius: 8px; padding: 25px; transition: all 0.2s; position: relative;" onmouseover="this.style.borderColor=\'#667eea\'; this.style.transform=\'translateY(-5px)\'; this.style.boxShadow=\'0 4px 12px rgba(0,0,0,0.1)\'" onmouseout="this.style.borderColor=\'#e0e0e0\'; this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'none\'">';
            
            print '<div style="font-size: 64px; text-align: center; margin-bottom: 20px;">📦</div>';
            
            print '<h3 style="margin: 0 0 10px 0; color: #333; font-size: 20px; text-align: center;">'.dol_escape_htmltag($obj->name).'</h3>';
            
            if ($obj->description) {
                print '<p style="color: #666; font-size: 14px; margin: 0 0 15px 0; text-align: center; min-height: 40px;">'.dol_escape_htmltag($obj->description).'</p>';
            }
            
            if ($obj->items_list) {
                print '<div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 15px; min-height: 80px;">';
                print '<strong style="font-size: 13px; color: #666;">📋 Package Includes:</strong><br>';
                print '<div style="font-size: 13px; margin-top: 8px; line-height: 1.6;">'.dol_escape_htmltag($obj->items_list).'</div>';
                print '</div>';
            }
            
            print '<div style="text-align: center; margin-bottom: 20px;">';
            if ($package_price > 0) {
                print '<div style="font-size: 28px; font-weight: bold; color: #28a745;">₦'.number_format($package_price, 0).'</div>';
            } else {
                print '<div style="font-size: 16px; color: #dc3545;">Price not set</div>';
            }
            print '</div>';
            
            if ($package_price > 0) {
                // Quantity selector
                print '<div class="quantity-selector">';
                print '<button type="button" class="quantity-btn" onclick="decreaseQty('.$obj->rowid.')">−</button>';
                print '<span class="quantity-display" id="qty-'.$obj->rowid.'">1</span>';
                print '<button type="button" class="quantity-btn" onclick="increaseQty('.$obj->rowid.')">+</button>';
                print '</div>';
                
                print '<a href="javascript:void(0);" onclick="addToCart('.$obj->rowid.')" id="btn-'.$obj->rowid.'" class="butAction" style="display: block; text-align: center; margin: 0; padding: 12px; font-size: 15px;">🛒 Add to Cart</a>';
            } else {
                print '<button disabled class="butAction" style="display: block; width: 100%; text-align: center; margin: 0; padding: 12px; font-size: 15px; background: #ccc; cursor: not-allowed;">Unavailable</button>';
            }
            
            print '</div>';
        }
        
        print '</div>';
    } else {
        print '<div style="text-align: center; padding: 80px 20px; background: white; border-radius: 8px;">';
        print '<div style="font-size: 80px; margin-bottom: 20px;">📭</div>';
        print '<h2 style="margin: 0 0 10px 0;">No Packages Available</h2>';
        print '<p style="color: #666; font-size: 16px;">Check back later for new packages!</p>';
        print '</div>';
    }
} else {
    print '<div class="error">Error loading packages: '.$db->lasterror().'</div>';
}

print '</div>';

print '<div style="position: fixed; bottom: 30px; right: 30px; z-index: 1000;">';
print '<a href="view_cart.php" class="butAction" style="display: block; padding: 15px 25px; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); background: #28a745; border-radius: 50px; text-decoration: none;">🛒 View Cart</a>';
print '</div>';

?>

<script>
function increaseQty(packageId) {
    let qtyEl = document.getElementById('qty-' + packageId);
    let currentQty = parseInt(qtyEl.textContent);
    if (currentQty < 99) {
        qtyEl.textContent = currentQty + 1;
    }
}

function decreaseQty(packageId) {
    let qtyEl = document.getElementById('qty-' + packageId);
    let currentQty = parseInt(qtyEl.textContent);
    if (currentQty > 1) {
        qtyEl.textContent = currentQty - 1;
    }
}

function addToCart(packageId) {
    let qty = parseInt(document.getElementById('qty-' + packageId).textContent);
    let btn = document.getElementById('btn-' + packageId);
    
    // Disable button and show loading
    btn.innerHTML = '⏳ Adding...';
    btn.style.pointerEvents = 'none';
    
    // Redirect with quantity
    window.location.href = 'add_to_cart.php?id=' + packageId + '&quantity=' + qty;
}
</script>

<?php
llxFooter();
?>
