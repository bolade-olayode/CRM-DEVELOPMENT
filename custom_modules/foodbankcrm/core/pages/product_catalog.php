<?php
/**
 * Packages — Subscriber Package Browser
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;
$langs->load("admin");

if (!FoodbankPermissions::isBeneficiary($user, $db)) {
    accessforbidden('You do not have access to packages.');
}

$search_text = GETPOST('search_text', 'alpha');
$sort_by     = GETPOST('sort_by', 'alpha') ?: 'name';

// Handle Add-to-Cart BEFORE any output — header() only works here
if (isset($_GET['add_id'])) {
    $pkg_id = (int)$_GET['add_id'];
    $qty    = max(1, (int)($_GET['qty'] ?? 1));
    header('Location: add_to_cart.php?id='.$pkg_id.'&quantity='.$qty);
    exit;
}

// Subscription gate check
$sub_status = 'Pending';
$res_check = $db->query("SELECT subscription_status FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id);
if ($res_check && $db->num_rows($res_check) > 0) {
    $sub_status = $db->fetch_object($res_check)->subscription_status;
}

// Cart count
$res_ben2 = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id);
$sub_id   = $res_ben2 ? (int)$db->fetch_object($res_ben2)->rowid : 0;
$cart_cnt = 0;
if ($sub_id) {
    $res_c = $db->query("SELECT COALESCE(SUM(quantity),0) as cnt FROM ".MAIN_DB_PREFIX."foodbank_cart WHERE fk_subscriber=".$sub_id);
    if ($res_c) $cart_cnt = (int)$db->fetch_object($res_c)->cnt;
}

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Packages');
?>
<style>
    #id-top,.side-nav,.side-nav-vert,#id-left,.login_block,.tmenudiv,.nav-bar,header{display:none!important;width:0!important;height:0!important;pointer-events:none!important}
    html,body{background:#f0f4f8!important;margin:0!important;padding:0!important;width:100%!important;overflow-x:hidden!important;font-family:'Segoe UI',system-ui,sans-serif}
    #id-container,#id-right,.id-right{margin:0!important;padding:0!important;width:100vw!important;max-width:100vw!important;display:block!important}
    .fiche{width:100%!important;max-width:100%!important;margin:0!important}
    *{box-sizing:border-box}
    body{padding-top:64px!important}

    /* NAV */
    .fb-nav{position:fixed;top:0;left:0;right:0;height:64px;background:linear-gradient(90deg,#0f766e,#0d9488);display:flex;align-items:center;justify-content:space-between;padding:0 28px;z-index:9999;box-shadow:0 2px 16px rgba(0,0,0,.18)}
    .fb-nav-brand{font-size:17px;font-weight:800;color:#fff;text-decoration:none}
    .fb-nav-links{display:flex;gap:2px}
    .fb-nav-link{color:rgba(255,255,255,.82);text-decoration:none;font-size:13px;font-weight:500;padding:7px 14px;border-radius:20px;transition:background .15s}
    .fb-nav-link:hover,.fb-nav-link.active{background:rgba(255,255,255,.18);color:#fff}
    .fb-nav-right{display:flex;align-items:center;gap:14px}
    .fb-cart-btn{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.35);padding:7px 16px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:700;transition:all .2s}
    .fb-cart-btn:hover{background:rgba(255,255,255,.25)}
    .cart-badge{background:#f59e0b;color:#fff;border-radius:20px;padding:2px 8px;font-size:11px;font-weight:800}
    .fb-logout{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.3);padding:6px 14px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600}

    /* PAGE */
    .page-wrap{max-width:1200px;margin:0 auto;padding:36px 24px 80px}

    /* HERO */
    .page-hero{background:linear-gradient(135deg,#134e4a,#0d9488);border-radius:16px;padding:36px 32px;margin-bottom:30px;color:#fff}
    .page-hero h1{margin:0 0 6px;font-size:28px;font-weight:800}
    .page-hero p{margin:0;opacity:.75;font-size:14px}

    /* SEARCH */
    .search-bar{background:#fff;border-radius:12px;padding:20px 24px;margin-bottom:28px;box-shadow:0 4px 16px rgba(0,0,0,.06);border:1px solid #e8edf2;display:grid;grid-template-columns:1fr auto auto;gap:14px;align-items:end}
    @media(max-width:640px){.search-bar{grid-template-columns:1fr}}
    .search-bar input,.search-bar select{width:100%;padding:11px 16px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none;transition:border .15s;background:#f8fafc}
    .search-bar input:focus,.search-bar select:focus{border-color:#0d9488;background:#fff}
    .btn-search{padding:11px 24px;background:#0d9488;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;white-space:nowrap;transition:background .15s}
    .btn-search:hover{background:#0f766e}
    .btn-clear{padding:11px 18px;background:#f1f5f9;color:#64748b;border:none;border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;white-space:nowrap}

    /* GATE */
    .gate{text-align:center;padding:70px 20px;background:#fff;border-radius:16px;box-shadow:0 4px 16px rgba(0,0,0,.06);max-width:560px;margin:0 auto}
    .gate-icon{font-size:64px;display:block;margin-bottom:20px}
    .gate h2{color:#1e293b;margin:0 0 12px;font-size:22px}
    .gate p{color:#64748b;margin:0 0 28px;font-size:15px}
    .btn-gate{background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;padding:13px 32px;border-radius:30px;text-decoration:none;font-weight:700;display:inline-block;box-shadow:0 4px 12px rgba(13,148,136,.3)}

    /* PACKAGE GRID */
    .pkg-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:22px}
    .pkg-card{background:#fff;border-radius:16px;box-shadow:0 4px 16px rgba(0,0,0,.06);border:1px solid #e8edf2;overflow:hidden;display:flex;flex-direction:column;transition:all .2s}
    .pkg-card:hover{transform:translateY(-5px);box-shadow:0 12px 32px rgba(0,0,0,.1);border-color:#0d9488}
    .pkg-thumb{height:120px;display:flex;align-items:center;justify-content:center;font-size:64px;background:linear-gradient(135deg,#f0fdfa,#ccfbf1)}
    .pkg-body{padding:22px;flex:1;display:flex;flex-direction:column}
    .pkg-name{font-size:18px;font-weight:800;color:#1e293b;margin:0 0 6px}
    .pkg-desc{font-size:13px;color:#64748b;margin:0 0 14px;line-height:1.5}
    .pkg-items{background:#f8fafc;border:1px solid #e8edf2;border-radius:8px;padding:12px 14px;margin-bottom:14px;flex:1}
    .pkg-items-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:8px}
    .pkg-items ul{margin:0;padding-left:18px;color:#475569;font-size:13px}
    .pkg-items ul li{margin-bottom:3px}
    .pkg-price{font-size:26px;font-weight:800;color:#0d9488;margin-bottom:16px}
    .qty-row{display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:14px}
    .qty-btn{width:38px;height:38px;border:2px solid #0d9488;background:#fff;color:#0d9488;font-size:20px;font-weight:700;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;line-height:1}
    .qty-btn:hover{background:#0d9488;color:#fff}
    .qty-num{font-size:20px;font-weight:800;min-width:32px;text-align:center;color:#1e293b}
    .btn-add{width:100%;padding:13px;background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;border:none;border-radius:10px;font-weight:800;font-size:14px;cursor:pointer;letter-spacing:.3px;transition:all .2s;box-shadow:0 4px 12px rgba(13,148,136,.25)}
    .btn-add:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(13,148,136,.4)}
    .btn-add:active{transform:scale(.98)}
    .added-msg{display:none;text-align:center;padding:10px;background:#d1fae5;color:#065f46;border-radius:8px;font-weight:700;font-size:13px}

    /* EMPTY */
    .empty{text-align:center;padding:70px 20px;background:#fff;border-radius:16px;box-shadow:0 4px 16px rgba(0,0,0,.06)}
    .empty-icon{font-size:64px;display:block;margin-bottom:16px;opacity:.5}

    /* STICKY CART */
    .sticky-cart{position:fixed;bottom:28px;right:28px;z-index:1000}
    .sticky-cart a{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;padding:14px 26px;border-radius:50px;font-weight:800;font-size:15px;text-decoration:none;box-shadow:0 6px 24px rgba(13,148,136,.4);transition:all .2s}
    .sticky-cart a:hover{transform:translateY(-2px);box-shadow:0 8px 32px rgba(13,148,136,.5)}
</style>

<?php
// NAV
print '<nav class="fb-nav">';
print '<a href="dashboard_beneficiary.php" class="fb-nav-brand">🥦 FoodbankCRM</a>';
print '<div class="fb-nav-links">';
print '<a href="product_catalog.php" class="fb-nav-link active">Packages</a>';
print '<a href="view_cart.php"       class="fb-nav-link">My Cart</a>';
print '<a href="my_orders.php"       class="fb-nav-link">Orders</a>';
print '<a href="my_profile.php"      class="fb-nav-link">Profile</a>';
print '</div>';
print '<div class="fb-nav-right">';
print '<a href="view_cart.php" class="fb-cart-btn">🛒 Cart'.($cart_cnt > 0 ? ' <span class="cart-badge">'.$cart_cnt.'</span>' : '').'</a>';
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="fb-logout">🚪</a>';
print '</div>';
print '</nav>';

print '<div class="page-wrap">';

// HERO
print '<div class="page-hero">';
print '<h1>🛍️ Packages</h1>';
print '<p>Select a food package to add to your cart</p>';
print '</div>';

// GATE: non-active subscribers
if ($sub_status != 'Active') {
    print '<div class="gate">';
    print '<span class="gate-icon">🔒</span>';
    if ($sub_status == 'Pending') {
        print '<h2>Payment Required</h2>';
        print '<p>Your account is active but you need to <strong>complete a subscription payment</strong> before you can browse and order food packages.</p>';
        print '<a href="renew_subscription.php" class="btn-gate">Choose a Plan &amp; Pay &rarr;</a>';
    } elseif ($sub_status == 'Expired') {
        print '<h2>Subscription Expired</h2>';
        print '<p>Your subscription has <strong>expired</strong>. Renew your plan to regain access.</p>';
        print '<a href="renew_subscription.php" class="btn-gate">Renew Subscription →</a>';
    } else {
        print '<h2>Account Inactive</h2>';
        print '<p>Please contact support to activate your account.</p>';
        print '<a href="dashboard_beneficiary.php" class="btn-gate">Go to Dashboard</a>';
    }
    print '</div></div>';
    llxFooter();
    exit;
}

// SEARCH BAR
print '<form method="GET" action="'.basename(__FILE__).'" class="search-bar">';
print '<div><label style="display:block;margin-bottom:6px;font-weight:600;color:#475569;font-size:13px">Search packages</label>';
print '<input type="text" name="search_text" value="'.dol_escape_htmltag($search_text).'" placeholder="e.g. Family Box, Vegetables…"></div>';
print '<div style="display:flex;gap:10px;align-items:flex-end">';
print '<button type="submit" class="btn-search">🔍 Search</button>';
if ($search_text) print '<a href="product_catalog.php" class="btn-clear">✕ Clear</a>';
print '</div>';
print '</form>';

// QUERY
$sql = "SELECT p.rowid, p.ref, p.name, p.description,
        GROUP_CONCAT(CONCAT(pi.product_name,' (',pi.quantity,' ',pi.unit,')') SEPARATOR '||') as items_list,
        COALESCE(SUM(pi.quantity * pi.unit_price),0) as total_price
        FROM ".MAIN_DB_PREFIX."foodbank_packages p
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_package_items pi ON p.rowid = pi.fk_package
        WHERE p.status = 'Active'";
if ($search_text) $sql .= " AND p.name LIKE '%".$db->escape($search_text)."%'";
$sql .= " GROUP BY p.rowid ORDER BY p.name ASC";
$resql = $db->query($sql);

if ($resql && $db->num_rows($resql) > 0) {
    print '<div class="pkg-grid">';
    $emoji_pool = ['📦','🥗','🥦','🥕','🍎','🫘','🌾','🧺','🫙','🥫'];
    $ei = 0;
    while ($pkg = $db->fetch_object($resql)) {
        $price = (float)$pkg->total_price;
        $em    = $emoji_pool[$ei % count($emoji_pool)];
        $ei++;

        print '<div class="pkg-card">';
        print '<div class="pkg-thumb">'.$em.'</div>';
        print '<div class="pkg-body">';
        print '<div class="pkg-name">'.dol_escape_htmltag($pkg->name).'</div>';
        if ($pkg->description) {
            print '<div class="pkg-desc">'.dol_escape_htmltag(dol_trunc($pkg->description, 90)).'</div>';
        }
        if ($pkg->items_list) {
            print '<div class="pkg-items">';
            print '<div class="pkg-items-title">📦 Includes:</div><ul>';
            foreach (explode('||', $pkg->items_list) as $item) {
                print '<li>'.dol_escape_htmltag($item).'</li>';
            }
            print '</ul></div>';
        }
        print '<div class="pkg-price">'.($price > 0 ? '₦'.number_format($price, 0) : 'Free').'</div>';
        print '<div class="qty-row">';
        print '<button type="button" class="qty-btn" onclick="chgQty('.$pkg->rowid.',-1)">−</button>';
        print '<span class="qty-num" id="qty-'.$pkg->rowid.'">1</span>';
        print '<button type="button" class="qty-btn" onclick="chgQty('.$pkg->rowid.',1)">+</button>';
        print '</div>';
        print '<div class="added-msg" id="msg-'.$pkg->rowid.'">✅ Added to cart!</div>';
        print '<button class="btn-add" onclick="addToCart('.$pkg->rowid.')">Add to Cart 🛒</button>';
        print '</div></div>';
    }
    print '</div>';
} else {
    print '<div class="empty">';
    print '<span class="empty-icon">🔍</span>';
    print '<h2>No Packages Found</h2>';
    print '<p>'.($search_text ? 'Try a different search.' : 'No packages available right now.').'</p>';
    print '</div>';
}

print '</div>'; // page-wrap

// STICKY CART
print '<div class="sticky-cart"><a href="view_cart.php">🛒 View Cart'.($cart_cnt > 0 ? ' ('.$cart_cnt.')' : '').'</a></div>';
?>
<script>
function chgQty(id, delta) {
    var el  = document.getElementById('qty-' + id);
    var val = parseInt(el.innerText) + delta;
    if (val < 1) val = 1;
    if (val > 20) val = 20;
    el.innerText = val;
}
function addToCart(id) {
    var qty = document.getElementById('qty-' + id).innerText;
    // Show brief feedback then navigate
    var msg = document.getElementById('msg-' + id);
    msg.style.display = 'block';
    setTimeout(function() {
        window.location.href = '?add_id=' + id + '&qty=' + qty;
    }, 600);
}
</script>
<?php
llxFooter();
?>
