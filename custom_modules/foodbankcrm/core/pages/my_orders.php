<?php
/**
 * My Orders - Subscriber Order History
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;
$langs->load("admin");

if (!FoodbankPermissions::isBeneficiary($user, $db)) {
    accessforbidden('You do not have access to orders.');
}

$sql_ben = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$beneficiary_id = (int)$db->fetch_object($db->query($sql_ben))->rowid;

$status_filter = GETPOST('status', 'alpha');

// Summary counts
$cnt = $db->fetch_object($db->query(
    "SELECT COUNT(*) as total,
            SUM(CASE WHEN status='Delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status IN ('Prepared','In Transit','Bundled') THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) as pending
     FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE fk_beneficiary=".(int)$beneficiary_id
));

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'My Orders');
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
    .fb-logout{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.35);padding:6px 16px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600;transition:all .2s}

    /* PAGE */
    .page-wrap{max-width:1000px;margin:0 auto;padding:36px 24px 60px}

    /* HERO */
    .page-hero{background:linear-gradient(135deg,#134e4a,#0d9488);border-radius:16px;padding:32px;margin-bottom:30px;color:#fff;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px}
    .page-hero h1{margin:0 0 6px;font-size:26px;font-weight:800}
    .page-hero p{margin:0;opacity:.75;font-size:14px}
    .hero-stats{display:flex;gap:24px;flex-wrap:wrap}
    .hs{text-align:center}
    .hs-val{font-size:28px;font-weight:800;line-height:1}
    .hs-lbl{font-size:11px;opacity:.75;text-transform:uppercase;letter-spacing:.5px;margin-top:4px}

    /* FILTER TABS */
    .filter-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:26px}
    .ftab{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:30px;text-decoration:none;font-size:13px;font-weight:600;border:2px solid #e2e8f0;color:#64748b;background:#fff;transition:all .15s;cursor:pointer}
    .ftab:hover{border-color:#0d9488;color:#0d9488}
    .ftab.active{background:#0d9488;color:#fff;border-color:#0d9488}
    .ftab-dot{width:8px;height:8px;border-radius:50%;display:inline-block}

    /* ORDER CARDS */
    .orders-list{display:flex;flex-direction:column;gap:16px}
    .order-card{background:#fff;border-radius:14px;box-shadow:0 4px 16px rgba(0,0,0,.06);border:1px solid #e8edf2;overflow:hidden;transition:box-shadow .2s,transform .2s}
    .order-card:hover{box-shadow:0 8px 28px rgba(0,0,0,.1);transform:translateY(-2px)}
    .order-card-top{display:flex;justify-content:space-between;align-items:center;padding:18px 22px;border-bottom:1px solid #f1f5f9}
    .order-ref{font-weight:800;font-size:16px;color:#1e293b}
    .order-date{font-size:12px;color:#94a3b8;margin-top:3px}
    .order-body{display:grid;grid-template-columns:1fr 1fr 1fr;gap:0;padding:16px 22px}
    @media(max-width:600px){.order-body{grid-template-columns:1fr 1fr}}
    .order-field{}
    .order-field-lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:4px}
    .order-field-val{font-size:15px;font-weight:700;color:#1e293b}
    .order-card-foot{padding:14px 22px;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:12px;background:#fafbfc}
    .btn-view{display:inline-flex;align-items:center;gap:6px;padding:9px 20px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;transition:all .2s}
    .btn-view.default{background:#f1f5f9;color:#334155}
    .btn-view.default:hover{background:#e2e8f0}
    .btn-view.pay{background:linear-gradient(135deg,#10b981,#059669);color:#fff;box-shadow:0 3px 10px rgba(16,185,129,.3)}
    .btn-view.pay:hover{transform:translateY(-1px);box-shadow:0 5px 16px rgba(16,185,129,.4)}

    /* BADGES */
    .badge{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px}
    .b-pending{background:#f1f5f9;color:#64748b}
    .b-prepared{background:#fef9c3;color:#854d0e}
    .b-bundled{background:#dbeafe;color:#1e40af}
    .b-transit{background:#ede9fe;color:#6d28d9}
    .b-delivered{background:#d1fae5;color:#065f46}

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
print '<a href="view_cart.php"       class="fb-nav-link">My Cart</a>';
print '<a href="my_orders.php"       class="fb-nav-link active">Orders</a>';
print '<a href="my_profile.php"      class="fb-nav-link">Profile</a>';
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
print '<h1>📦 My Orders</h1>';
print '<p>Track all your food package requests in one place</p>';
print '</div>';
print '<div class="hero-stats">';
$hs = [['📋',$cnt->total,'Total'],['🚚',$cnt->active,'Active'],['✅',$cnt->delivered,'Delivered'],['⏳',$cnt->pending,'Pending']];
foreach ($hs as [$ic,$v,$l]) {
    print '<div class="hs"><div class="hs-val">'.$ic.' '.(int)$v.'</div><div class="hs-lbl">'.$l.'</div></div>';
}
print '</div></div>';

// FILTER TABS
$tabs = [
    [''          , '🗂', 'All',        '#64748b'],
    ['Pending'   , '⏳', 'Pending',    '#64748b'],
    ['Prepared'  , '📦', 'Prepared',   '#d97706'],
    ['Bundled'   , '📫', 'Bundled',    '#2563eb'],
    ['In Transit', '🚚', 'In Transit', '#7c3aed'],
    ['Delivered' , '✅', 'Delivered',  '#059669'],
];
print '<div class="filter-tabs">';
foreach ($tabs as [$val, $ic, $lbl, $col]) {
    $active = ($status_filter === $val) ? ' active' : '';
    $qs = $val ? '?status='.urlencode($val) : '?';
    print '<a href="my_orders.php'.$qs.'" class="ftab'.$active.'">';
    print '<span class="ftab-dot" style="background:'.$col.'"></span>';
    print $ic.' '.$lbl;
    print '</a>';
}
print '</div>';

// FETCH ORDERS
$sql = "SELECT rowid, ref, datec, status, payment_method, total_amount, payment_status, note
        FROM ".MAIN_DB_PREFIX."foodbank_distributions
        WHERE fk_beneficiary = ".(int)$beneficiary_id;
if ($status_filter) $sql .= " AND status = '".$db->escape($status_filter)."'";
$sql .= " ORDER BY datec DESC";
$resql = $db->query($sql);

$badge_map = ['Pending'=>'b-pending','Prepared'=>'b-prepared','Bundled'=>'b-bundled','In Transit'=>'b-transit','Delivered'=>'b-delivered'];
$dot_map   = ['Pending'=>'#94a3b8','Prepared'=>'#d97706','Bundled'=>'#2563eb','In Transit'=>'#7c3aed','Delivered'=>'#059669'];

if ($resql && $db->num_rows($resql) > 0) {
    print '<div class="orders-list">';
    while ($o = $db->fetch_object($resql)) {
        $bc   = $badge_map[$o->status] ?? 'b-pending';
        $dot  = $dot_map[$o->status]   ?? '#94a3b8';
        $pm   = $o->payment_method == 'pay_now' ? '💳 Paystack' : '💵 Cash on Delivery';
        $need_pay = ($o->payment_method == 'pay_now' && !in_array($o->payment_status, ['Paid','Success']));

        print '<div class="order-card">';
        // Top
        print '<div class="order-card-top">';
        print '<div>';
        print '<div class="order-ref">'.dol_escape_htmltag($o->ref).'</div>';
        print '<div class="order-date">'.dol_print_date($db->jdate($o->datec), 'day').'</div>';
        print '</div>';
        print '<span class="badge '.$bc.'"><span style="width:7px;height:7px;border-radius:50%;background:'.$dot.';display:inline-block"></span>'.dol_escape_htmltag($o->status).'</span>';
        print '</div>';
        // Body
        print '<div class="order-body">';
        print '<div class="order-field"><div class="order-field-lbl">Amount</div><div class="order-field-val" style="color:#0d9488">₦'.number_format($o->total_amount, 2).'</div></div>';
        print '<div class="order-field"><div class="order-field-lbl">Payment</div><div class="order-field-val" style="font-size:13px">'.$pm.'</div></div>';
        if ($o->note) {
            print '<div class="order-field"><div class="order-field-lbl">Note</div><div class="order-field-val" style="font-size:13px;color:#64748b">'.dol_escape_htmltag(dol_trunc($o->note, 40)).'</div></div>';
        }
        print '</div>';
        // Footer
        print '<div class="order-card-foot">';
        if ($need_pay) {
            print '<a href="process_order_payment.php?order_id='.$o->rowid.'" class="btn-view pay">💳 Pay Now</a>';
        }
        print '<a href="view_order.php?id='.$o->rowid.'" class="btn-view default">View Details →</a>';
        print '</div>';
        print '</div>';
    }
    print '</div>';
} else {
    print '<div class="empty">';
    print '<span class="empty-icon">📦</span>';
    print '<h2>No Orders Found</h2>';
    print '<p>'.($status_filter ? 'No orders with status "'.$status_filter.'".' : "You haven't placed any orders yet.").'</p>';
    print '<a href="product_catalog.php" class="btn-shop">Browse Packages →</a>';
    print '</div>';
}

print '</div>'; // page-wrap
llxFooter();
?>
