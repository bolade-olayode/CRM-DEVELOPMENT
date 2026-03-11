<?php
/**
 * View Cart
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;
$langs->load("admin");

if (!FoodbankPermissions::isBeneficiary($user, $db)) {
    accessforbidden('You do not have access to this page.');
}

$sql_ben = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res_ben = $db->query($sql_ben);
if (!$res_ben || $db->num_rows($res_ben) == 0) {
    accessforbidden('Beneficiary profile not found.');
}
$subscriber_id = (int)$db->fetch_object($res_ben)->rowid;

// Update Quantity (CSRF PROTECTED)
if (isset($_POST['update_qty'])) {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        setEventMessages("Security check failed.", null, 'errors');
    } else {
        foreach ($_POST['qty'] as $id => $val) {
            $id  = (int)$id;
            $val = (int)$val;
            if ($val > 0) {
                $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_cart SET quantity = ".$val." WHERE fk_subscriber = ".$subscriber_id." AND fk_package = ".$id);
            } else {
                $db->query("DELETE FROM ".MAIN_DB_PREFIX."foodbank_cart WHERE fk_subscriber = ".$subscriber_id." AND fk_package = ".$id);
            }
        }
    }
    header("Location: view_cart.php"); exit;
}

// Remove Item (CSRF protected)
if (isset($_GET['remove'])) {
    if (!isset($_GET['token']) || $_GET['token'] != $_SESSION['newtoken']) {
        setEventMessages("Security check failed.", null, 'errors');
    } else {
        $id = (int)$_GET['remove'];
        $db->query("DELETE FROM ".MAIN_DB_PREFIX."foodbank_cart WHERE fk_subscriber = ".$subscriber_id." AND fk_package = ".$id);
    }
    header("Location: view_cart.php"); exit;
}

// Fetch cart
$sql_cart = "SELECT c.rowid, c.fk_package, c.quantity, c.unit_price,
             p.name, p.description,
             SUM(pi.quantity * pi.unit_price) as calculated_price
             FROM ".MAIN_DB_PREFIX."foodbank_cart c
             LEFT JOIN ".MAIN_DB_PREFIX."foodbank_packages p ON c.fk_package = p.rowid
             LEFT JOIN ".MAIN_DB_PREFIX."foodbank_package_items pi ON p.rowid = pi.fk_package
             WHERE c.fk_subscriber = ".$subscriber_id."
             GROUP BY c.rowid, c.fk_package, c.quantity, c.unit_price, p.name, p.description";
$res_cart = $db->query($sql_cart);
$cart_count = $res_cart ? $db->num_rows($res_cart) : 0;

// Pre-calculate total for nav badge
$total_cart = 0;
$cart_rows = [];
if ($cart_count > 0) {
    while ($obj = $db->fetch_object($res_cart)) {
        $price = ($obj->calculated_price > 0) ? (float)$obj->calculated_price : (float)$obj->unit_price;
        $subtotal = $price * (int)$obj->quantity;
        $total_cart += $subtotal;
        $cart_rows[] = ['obj' => $obj, 'price' => $price, 'subtotal' => $subtotal];
    }
}

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'My Cart');
?>
<link rel="stylesheet" href="<?php echo DOL_URL_ROOT; ?>/custom/foodbankcrm/css/responsive.css">
<style>
    #id-top,.side-nav,.side-nav-vert,#id-left,.login_block,.tmenudiv,.nav-bar,header{display:none!important;width:0!important;height:0!important;pointer-events:none!important}
    html,body{background:#f0f4f8!important;margin:0!important;padding:0!important;width:100%!important;overflow-x:hidden!important;font-family:'Segoe UI',system-ui,sans-serif}
    #id-container,#id-right,.id-right{margin:0!important;padding:0!important;width:100vw!important;max-width:100vw!important;display:block!important}
    .fiche{width:100%!important;max-width:100%!important;margin:0!important}
    *{box-sizing:border-box}
    body{padding-top:64px!important}

    /* NAV */
    .fb-nav{position:fixed;top:0;left:0;right:0;height:64px;background:linear-gradient(90deg,#0f766e,#0d9488);display:flex;align-items:center;justify-content:space-between;padding:0 28px;z-index:9999;box-shadow:0 2px 16px rgba(0,0,0,.18)}
    .fb-nav-brand{font-size:17px;font-weight:800;color:#fff;text-decoration:none;display:flex;align-items:center;gap:9px}
    .fb-nav-links{display:flex;gap:2px}
    @media(max-width:768px){
        .fb-nav-links{display:none!important;position:fixed;top:64px;left:0;right:0;flex-direction:column;background:linear-gradient(180deg,#0f766e,#0d9488);padding:6px 0 12px;z-index:9998;box-shadow:0 8px 24px rgba(0,0,0,.25);gap:0}
        .fb-nav-links.fb-open{display:flex!important}
    }
    .fb-nav-link{color:rgba(255,255,255,.82);text-decoration:none;font-size:13px;font-weight:500;padding:7px 14px;border-radius:20px;transition:background .15s}
    .fb-nav-link:hover,.fb-nav-link.active{background:rgba(255,255,255,.18);color:#fff}
    .fb-nav-right{display:flex;align-items:center;gap:14px}
    .fb-logout{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.35);padding:6px 16px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600}
    .cart-badge{background:#ef4444;color:#fff;font-size:10px;font-weight:800;padding:2px 6px;border-radius:10px;margin-left:2px;vertical-align:middle}

    /* PAGE */
    .page-wrap{max-width:1100px;margin:0 auto;padding:36px 24px 60px}

    /* HERO */
    .page-hero{background:linear-gradient(135deg,#134e4a,#0d9488);border-radius:16px;padding:28px 32px;margin-bottom:30px;color:#fff;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px}
    .page-hero h1{margin:0 0 4px;font-size:24px;font-weight:800}
    .page-hero p{margin:0;opacity:.75;font-size:14px}

    /* LAYOUT */
    .cart-layout{display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start}
    @media(max-width:800px){.cart-layout{grid-template-columns:1fr}}

    /* CART ITEMS */
    .cart-items{background:#fff;border-radius:14px;box-shadow:0 4px 16px rgba(0,0,0,.06);border:1px solid #e8edf2;overflow:hidden}
    .cart-items-header{padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center}
    .cart-items-header h3{margin:0;font-size:15px;font-weight:700;color:#1e293b}
    .cart-item{display:flex;align-items:center;gap:16px;padding:18px 24px;border-bottom:1px solid #f8fafc;transition:background .15s}
    .cart-item:last-child{border-bottom:none}
    .cart-item:hover{background:#fafbfc}
    .cart-item-icon{width:52px;height:52px;background:linear-gradient(135deg,#d1fae5,#a7f3d0);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0}
    .cart-item-name{font-weight:700;font-size:14px;color:#1e293b;margin-bottom:3px}
    .cart-item-ref{font-size:11px;color:#94a3b8}
    .cart-item-info{flex:1;min-width:0}
    .cart-item-price{font-size:15px;font-weight:700;color:#0d9488;white-space:nowrap}
    .cart-item-controls{display:flex;align-items:center;gap:8px}
    .qty-wrap{display:flex;align-items:center;gap:4px}
    .qty-btn{width:28px;height:28px;border:1px solid #e2e8f0;background:#f8fafc;border-radius:6px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#475569;transition:all .15s}
    .qty-btn:hover{background:#e2e8f0}
    .qty-input{width:44px;height:28px;border:1px solid #e2e8f0;border-radius:6px;text-align:center;font-size:13px;font-weight:700;color:#1e293b}
    .btn-remove{background:none;border:none;color:#94a3b8;font-size:18px;cursor:pointer;padding:4px 8px;border-radius:6px;transition:all .15s;line-height:1}
    .btn-remove:hover{color:#ef4444;background:#fef2f2}

    /* SUMMARY CARD */
    .summary-card{background:#fff;border-radius:14px;box-shadow:0 4px 16px rgba(0,0,0,.06);border:1px solid #e8edf2;overflow:hidden;position:sticky;top:80px}
    .summary-header{padding:18px 24px;border-bottom:1px solid #f1f5f9}
    .summary-header h3{margin:0;font-size:15px;font-weight:700;color:#1e293b}
    .summary-body{padding:20px 24px}
    .summary-row{display:flex;justify-content:space-between;font-size:13px;color:#64748b;margin-bottom:10px}
    .summary-total{display:flex;justify-content:space-between;font-size:18px;font-weight:800;color:#0d9488;padding-top:14px;border-top:2px solid #f1f5f9;margin-top:4px}
    .btn-checkout{display:block;width:100%;padding:14px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:15px;font-weight:700;border:none;border-radius:10px;cursor:pointer;text-align:center;text-decoration:none;margin-top:18px;transition:all .2s;box-shadow:0 4px 12px rgba(16,185,129,.3)}
    .btn-checkout:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(16,185,129,.4)}
    .btn-continue{display:block;text-align:center;margin-top:12px;color:#64748b;font-size:13px;text-decoration:none;font-weight:500}
    .btn-continue:hover{color:#0d9488}

    /* EMPTY */
    .empty{text-align:center;padding:70px 20px;background:#fff;border-radius:16px;box-shadow:0 4px 16px rgba(0,0,0,.06)}
    .empty-icon{font-size:72px;display:block;margin-bottom:20px;opacity:.5}
    .empty h2{margin:0 0 10px;color:#334155;font-size:22px}
    .empty p{color:#94a3b8;margin-bottom:28px;font-size:15px}
    .btn-shop{background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;padding:12px 32px;border-radius:30px;text-decoration:none;font-weight:700;display:inline-block}
</style>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var btn = document.getElementById('fb-hamburger');
    var links = document.querySelector('.fb-nav-links');
    if (!btn || !links) return;
    var closeBtn = document.createElement('button');
    closeBtn.className = 'fb-nav-close-btn';
    closeBtn.innerHTML = '&#10005;&nbsp;&nbsp;Close';
    closeBtn.addEventListener('click', function(){
        links.classList.remove('fb-open');
        btn.classList.remove('open');
    });
    links.insertBefore(closeBtn, links.firstChild);
    btn.addEventListener('click', function(){
        links.classList.toggle('fb-open');
        btn.classList.toggle('open');
    });
    document.addEventListener('click', function(e){
        if (!btn.contains(e.target) && !links.contains(e.target)) {
            links.classList.remove('fb-open');
            btn.classList.remove('open');
        }
    });
});
</script>

<?php
// NAV
print '<nav class="fb-nav">';
print '<a href="dashboard_beneficiary.php" class="fb-nav-brand">🥦 FoodbankCRM</a>';
print '<div class="fb-nav-links">';
print '<a href="product_catalog.php" class="fb-nav-link">Packages</a>';
$badge = $cart_count > 0 ? '<span class="cart-badge">'.$cart_count.'</span>' : '';
print '<a href="view_cart.php" class="fb-nav-link active">My Cart'.$badge.'</a>';
print '<a href="my_orders.php" class="fb-nav-link">Orders</a>';
print '<a href="my_profile.php" class="fb-nav-link">Profile</a>';
print '</div>';
print '<div class="fb-nav-right">';
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="fb-logout">🚪 Logout</a>';
print '</div>';
print '<button class="fb-nav-hamburger" id="fb-hamburger" aria-label="Toggle menu"><span></span><span></span><span></span></button>';
print '</nav>';

print '<div class="page-wrap">';

// HERO
print '<div class="page-hero">';
print '<div>';
print '<h1>🛒 My Cart</h1>';
print '<p>'.$cart_count.' item'.($cart_count != 1 ? 's' : '').' ready for checkout</p>';
print '</div>';
if ($cart_count > 0) {
    print '<div style="text-align:right">';
    print '<div style="font-size:28px;font-weight:800;line-height:1">₦'.number_format($total_cart).'</div>';
    print '<div style="font-size:12px;opacity:.75;margin-top:3px">Cart Total</div>';
    print '</div>';
}
print '</div>';

if ($cart_count == 0) {
    print '<div class="empty">';
    print '<span class="empty-icon">🛒</span>';
    print '<h2>Your Cart is Empty</h2>';
    print '<p>You haven\'t added any food packages yet. Browse our available packages and add some to your cart.</p>';
    print '<a href="product_catalog.php" class="btn-shop">Browse Packages →</a>';
    print '</div>';
} else {
    print '<form method="POST" action="view_cart.php" id="cart-form">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="update_qty" value="1">';

    $pkg_emojis = ['📦','🥗','🥘','🍱','🧺','🥫','🫙','🥦'];
    $ei = 0;

    print '<div class="cart-layout">';

    // LEFT: Items
    print '<div class="cart-items">';
    print '<div class="cart-items-header">';
    print '<h3>Cart Items</h3>';
    print '<span style="font-size:12px;color:#94a3b8;font-weight:600;">'.$cart_count.' PACKAGE'.($cart_count != 1 ? 'S' : '').'</span>';
    print '</div>';

    foreach ($cart_rows as $row) {
        $obj     = $row['obj'];
        $price   = $row['price'];
        $subtotal = $row['subtotal'];
        $qty     = (int)$obj->quantity;
        $emoji   = $pkg_emojis[$ei++ % count($pkg_emojis)];

        print '<div class="cart-item">';
        print '<div class="cart-item-icon">'.$emoji.'</div>';
        print '<div class="cart-item-info">';
        print '<div class="cart-item-name">'.dol_escape_htmltag($obj->name).'</div>';
        print '<div class="cart-item-ref">PKG-'.str_pad($obj->fk_package, 4, '0', STR_PAD_LEFT).' · ₦'.number_format($price).'/pkg</div>';
        print '</div>';
        print '<div class="cart-item-controls">';
        // Qty controls
        print '<div class="qty-wrap">';
        print '<button type="button" class="qty-btn" onclick="changeQty('.$obj->fk_package.',-1)">−</button>';
        print '<input type="number" name="qty['.$obj->fk_package.']" id="qty-'.$obj->fk_package.'" value="'.$qty.'" min="1" class="qty-input" onchange="updateSubtotal()">';
        print '<button type="button" class="qty-btn" onclick="changeQty('.$obj->fk_package.',1)">+</button>';
        print '</div>';
        // Subtotal
        print '<div class="cart-item-price" id="sub-'.$obj->fk_package.'" data-price="'.htmlspecialchars($price).'">₦'.number_format($subtotal).'</div>';
        // Remove
        print '<a href="view_cart.php?remove='.$obj->fk_package.'&token='.newToken().'" class="btn-remove" title="Remove" onclick="return confirm(\'Remove this item?\')">🗑</a>';
        print '</div>';
        print '</div>';
    }
    print '</div>'; // cart-items

    // RIGHT: Summary
    print '<div class="summary-card">';
    print '<div class="summary-header"><h3>Order Summary</h3></div>';
    print '<div class="summary-body">';
    foreach ($cart_rows as $row) {
        print '<div class="summary-row">';
        print '<span>'.dol_escape_htmltag(dol_trunc($row['obj']->name, 22)).' ×'.((int)$row['obj']->quantity).'</span>';
        print '<span>₦'.number_format($row['subtotal']).'</span>';
        print '</div>';
    }
    print '<div class="summary-total"><span>Total</span><span id="grand-total">₦'.number_format($total_cart).'</span></div>';
    print '<button type="submit" name="update_qty" value="1" style="display:block;width:100%;margin-top:14px;padding:10px;background:#f1f5f9;color:#475569;font-size:13px;font-weight:600;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;">↻ Update Cart</button>';
    print '<a href="checkout.php" class="btn-checkout">Proceed to Checkout →</a>';
    print '<a href="product_catalog.php" class="btn-continue">← Continue Shopping</a>';
    print '</div>';
    print '</div>'; // summary-card

    print '</div>'; // cart-layout
    print '</form>';
}

print '</div>'; // page-wrap
?>

<script>
function changeQty(pkgId, delta) {
    var input = document.getElementById('qty-' + pkgId);
    var newVal = Math.max(1, parseInt(input.value || 1) + delta);
    input.value = newVal;
    updateSubtotal();
}
function updateSubtotal() {
    var total = 0;
    document.querySelectorAll('.qty-input').forEach(function(input) {
        var pkgId = input.name.match(/\[(\d+)\]/)[1];
        var price = parseFloat(document.getElementById('sub-' + pkgId).dataset.price || 0);
        var qty   = parseInt(input.value || 1);
        var sub   = price * qty;
        document.getElementById('sub-' + pkgId).textContent = '₦' + sub.toLocaleString('en-NG', {minimumFractionDigits:0, maximumFractionDigits:0});
        total += sub;
    });
    document.getElementById('grand-total').textContent = '₦' + total.toLocaleString('en-NG', {minimumFractionDigits:0, maximumFractionDigits:0});
}
</script>

<?php llxFooter(); ?>
