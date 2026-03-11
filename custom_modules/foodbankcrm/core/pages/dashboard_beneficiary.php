<?php
/**
 * SUBSCRIBER DASHBOARD - FoodbankCRM
 */
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;
$langs->load("admin");

if (!FoodbankPermissions::isBeneficiary($user, $db)) {
    accessforbidden('Access Denied: You are not a registered subscriber.');
}

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);
if (!$res || $db->num_rows($res) == 0) {
    accessforbidden('Profile not found. Please contact support.');
}
$sub = $db->fetch_object($res);

// Stats
$stats = $db->fetch_object($db->query(
    "SELECT COUNT(*) as total,
            SUM(CASE WHEN status='Delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status IN ('Prepared','In Transit','Bundled') THEN 1 ELSE 0 END) as active,
            COALESCE(SUM(total_amount),0) as spent
     FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE fk_beneficiary=".(int)$sub->rowid
));

// Recent orders (last 4)
$res_orders = $db->query(
    "SELECT rowid, ref, status, datec, total_amount
     FROM ".MAIN_DB_PREFIX."foodbank_distributions
     WHERE fk_beneficiary = ".(int)$sub->rowid."
     ORDER BY datec DESC LIMIT 4"
);

// Days until expiry
$days_left = null;
if ($sub->subscription_end_date) {
    $days_left = (int)floor((strtotime($sub->subscription_end_date) - time()) / 86400);
}

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$sub_name = trim($sub->firstname.' '.$sub->lastname) ?: $user->login;

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'My Dashboard');
?>
<link rel="stylesheet" href="<?php echo DOL_URL_ROOT; ?>/custom/foodbankcrm/css/responsive.css">
<style>
    /* ── RESET ──────────────────────────────────── */
    #id-top,.side-nav,.side-nav-vert,#id-left,.login_block,.tmenudiv,.nav-bar,header{display:none!important;width:0!important;height:0!important;pointer-events:none!important}
    html,body{background:#f0f4f8!important;margin:0!important;padding:0!important;width:100%!important;overflow-x:hidden!important;font-family:'Segoe UI',system-ui,sans-serif}
    #id-container,#id-right,.id-right{margin:0!important;padding:0!important;width:100vw!important;max-width:100vw!important;flex:none!important;display:block!important}
    .fiche{width:100%!important;max-width:100%!important;margin:0!important}
    body{padding-top:64px!important}
    *{box-sizing:border-box}

    /* ── TOPNAV ─────────────────────────────────── */
    .fb-nav{position:fixed;top:0;left:0;right:0;height:64px;background:linear-gradient(90deg,#0f766e,#0d9488);display:flex;align-items:center;justify-content:space-between;padding:0 28px;z-index:9999;box-shadow:0 2px 16px rgba(0,0,0,.18)}
    .fb-nav-brand{font-size:17px;font-weight:800;color:#fff;text-decoration:none;display:flex;align-items:center;gap:9px;letter-spacing:-.3px}
    .fb-nav-links{display:flex;gap:2px}
    @media(max-width:768px){
        .fb-nav-links{display:none!important;position:fixed;top:64px;left:0;right:0;flex-direction:column;background:linear-gradient(180deg,#0f766e,#0d9488);padding:6px 0 12px;z-index:9998;box-shadow:0 8px 24px rgba(0,0,0,.25);gap:0}
        .fb-nav-links.fb-open{display:flex!important}
    }
    .fb-nav-link{color:rgba(255,255,255,.82);text-decoration:none;font-size:13px;font-weight:500;padding:7px 14px;border-radius:20px;transition:background .15s,color .15s}
    .fb-nav-link:hover,.fb-nav-link.active{background:rgba(255,255,255,.18);color:#fff}
    .fb-nav-right{display:flex;align-items:center;gap:14px}
    .fb-nav-name{color:rgba(255,255,255,.85);font-size:13px;font-weight:500}
    .fb-logout{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.35);padding:6px 16px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600;transition:all .2s}
    .fb-logout:hover{background:rgba(255,255,255,.25)}

    /* ── HERO ────────────────────────────────────── */
    .fb-hero{background:linear-gradient(135deg,#134e4a 0%,#0d9488 55%,#14b8a6 100%);padding:44px 32px 80px;position:relative;overflow:hidden}
    .fb-hero::after{content:'';position:absolute;bottom:-40px;left:0;right:0;height:80px;background:#f0f4f8;border-radius:50% 50% 0 0/100% 100% 0 0}
    .fb-hero-inner{max-width:1200px;margin:0 auto;position:relative;z-index:1}
    .fb-hero-top{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:20px}
    .fb-greeting{color:rgba(255,255,255,.75);font-size:14px;font-weight:500;margin-bottom:6px;letter-spacing:.5px;text-transform:uppercase}
    .fb-hero-name{color:#fff;font-size:32px;font-weight:800;margin:0 0 4px;line-height:1.1}
    .fb-hero-id{color:rgba(255,255,255,.65);font-size:13px;margin-top:4px}
    .fb-sub-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.15);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.25);padding:12px 20px;border-radius:14px;color:#fff}
    .fb-sub-badge .plan{font-size:16px;font-weight:800}
    .fb-sub-badge .status{font-size:12px;background:rgba(255,255,255,.2);padding:3px 10px;border-radius:20px;font-weight:700}
    .fb-sub-badge .days{font-size:12px;color:rgba(255,255,255,.75);margin-top:3px}
    .fb-manage-btn{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.18);color:#fff;padding:10px 20px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:700;border:1px solid rgba(255,255,255,.3);transition:all .2s;margin-top:10px}
    .fb-manage-btn:hover{background:rgba(255,255,255,.3);transform:translateY(-1px)}

    /* ── STATS ───────────────────────────────────── */
    .fb-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;max-width:1200px;margin:-42px auto 36px;padding:0 28px;position:relative;z-index:2}
    @media(max-width:900px){.fb-stats{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:480px){.fb-stats{grid-template-columns:1fr}}
    .fb-stat{background:#fff;border-radius:14px;padding:22px 20px;box-shadow:0 4px 20px rgba(0,0,0,.07);border:1px solid #e8edf2;transition:transform .2s}
    .fb-stat:hover{transform:translateY(-3px)}
    .fb-stat-icon{font-size:24px;margin-bottom:10px}
    .fb-stat-val{font-size:30px;font-weight:800;color:#1e293b;line-height:1}
    .fb-stat-lbl{font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.7px;margin-top:6px}

    /* ── LAYOUT ──────────────────────────────────── */
    .fb-body{max-width:1200px;margin:0 auto;padding:0 28px 60px;display:grid;grid-template-columns:1fr 360px;gap:24px}
    @media(max-width:1024px){.fb-body{grid-template-columns:1fr}}

    /* ── SECTION HEADER ──────────────────────────── */
    .fb-sec-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
    .fb-sec-title{font-size:16px;font-weight:700;color:#1e293b;margin:0}
    .fb-sec-link{font-size:13px;color:#0d9488;font-weight:600;text-decoration:none}
    .fb-sec-link:hover{text-decoration:underline}

    /* ── RECENT ORDERS ───────────────────────────── */
    .fb-card{background:#fff;border-radius:14px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,.06);border:1px solid #e8edf2}
    .fb-order-row{display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit;transition:background .15s;border-radius:8px;margin:0 -8px;padding-left:8px;padding-right:8px}
    .fb-order-row:last-child{border-bottom:none}
    .fb-order-row:hover{background:#f8fafc}
    .fb-order-ref{font-weight:700;font-size:14px;color:#334155}
    .fb-order-date{font-size:12px;color:#94a3b8;margin-top:3px}
    .fb-order-amt{font-size:15px;font-weight:700;color:#0d9488}
    .fb-badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px}
    .badge-delivered{background:#d1fae5;color:#065f46}
    .badge-transit{background:#ede9fe;color:#5b21b6}
    .badge-prepared{background:#fef3c7;color:#92400e}
    .badge-bundled{background:#dbeafe;color:#1e40af}
    .badge-pending{background:#f1f5f9;color:#475569}

    /* ── RIGHT COLUMN ────────────────────────────── */
    .fb-actions-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:0}
    .fb-action{display:flex;flex-direction:column;align-items:center;justify-content:center;background:#fff;border-radius:14px;padding:24px 14px;box-shadow:0 4px 20px rgba(0,0,0,.06);border:1px solid #e8edf2;text-decoration:none;color:#334155;text-align:center;transition:all .2s;gap:10px}
    .fb-action:hover{transform:translateY(-4px);border-color:#0d9488;box-shadow:0 8px 30px rgba(13,148,136,.15)}
    .fb-action-icon{font-size:36px}
    .fb-action-label{font-size:13px;font-weight:700;color:#334155}
    .fb-sub-info{display:flex;flex-direction:column;gap:14px}
    .fb-sub-row{display:flex;justify-content:space-between;font-size:14px;color:#64748b;padding:10px 0;border-bottom:1px solid #f1f5f9}
    .fb-sub-row:last-child{border-bottom:none}
    .fb-sub-row strong{color:#1e293b;font-weight:700}
    .fb-renew-btn{display:block;text-align:center;background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;padding:13px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;margin-top:18px;transition:all .2s;box-shadow:0 4px 12px rgba(13,148,136,.3)}
    .fb-renew-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(13,148,136,.4)}
    .empty-orders{text-align:center;padding:40px 20px;color:#94a3b8}
    .empty-orders span{font-size:48px;display:block;margin-bottom:12px}

    /* ── PAYMENT BANNER ──────────────────────────── */
    .fb-pay-banner{max-width:1200px;margin:0 auto 28px;padding:0 28px}
    .fb-pay-inner{background:linear-gradient(135deg,#fffbeb,#fef3c7);border:2px solid #f59e0b;border-radius:16px;padding:24px 28px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
    .fb-pay-icon{font-size:40px;flex-shrink:0}
    .fb-pay-text{flex:1;min-width:200px}
    .fb-pay-title{font-size:18px;font-weight:800;color:#92400e;margin:0 0 6px}
    .fb-pay-desc{font-size:14px;color:#78350f;margin:0;line-height:1.5}
    .fb-pay-btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:13px 24px;border-radius:10px;text-decoration:none;font-weight:800;font-size:14px;white-space:nowrap;box-shadow:0 4px 14px rgba(245,158,11,.4);transition:all .2s;flex-shrink:0}
    .fb-pay-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(245,158,11,.5)}
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
// ── TOPNAV ──────────────────────────────────────────────────────────────────
print '<nav class="fb-nav">';
print '<a href="dashboard_beneficiary.php" class="fb-nav-brand">🥦 FoodbankCRM</a>';
print '<div class="fb-nav-links">';
print '<a href="product_catalog.php" class="fb-nav-link">Packages</a>';
print '<a href="view_cart.php"       class="fb-nav-link">My Cart</a>';
print '<a href="my_orders.php"       class="fb-nav-link">Orders</a>';
print '<a href="my_profile.php"      class="fb-nav-link">Profile</a>';
print '</div>';
print '<div class="fb-nav-right">';
print '<span class="fb-nav-name">Hi, '.dol_escape_htmltag($sub->firstname).'</span>';
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="fb-logout">🚪 Logout</a>';
print '</div>';
print '<button class="fb-nav-hamburger" id="fb-hamburger" aria-label="Toggle menu"><span></span><span></span><span></span></button>';
print '</nav>';

// ── HERO ─────────────────────────────────────────────────────────────────────
$status_class = ($sub->subscription_status == 'Active') ? '✅' : ($sub->subscription_status == 'Expired' ? '❌' : '⏳');
print '<div class="fb-hero">';
print '<div class="fb-hero-inner">';
print '<div class="fb-hero-top">';

// Left: greeting
print '<div>';
print '<div class="fb-greeting">'.$greeting.'</div>';
print '<h1 class="fb-hero-name">'.dol_escape_htmltag($sub_name).'!</h1>';
print '<div class="fb-hero-id">Subscriber ID: '.dol_escape_htmltag($sub->ref).'</div>';
print '</div>';

// Right: subscription badge
print '<div style="text-align:right">';
print '<div class="fb-sub-badge">';
print '<div>';
print '<div class="plan">'.dol_escape_htmltag($sub->subscription_type ?: 'Guest').'</div>';
print '<span class="status">'.$status_class.' '.dol_escape_htmltag($sub->subscription_status ?: 'Pending').'</span>';
if ($days_left !== null) {
    $days_txt = $days_left > 0 ? $days_left.' days left' : 'Expired';
    print '<div class="days">⏱ '.$days_txt.'</div>';
}
print '</div>';
print '</div>';
print '<a href="renew_subscription.php" class="fb-manage-btn">Manage Plan →</a>';
print '</div>';

print '</div>'; // hero-top
print '</div></div>'; // hero-inner / hero

// ── STATS ────────────────────────────────────────────────────────────────────
print '<div class="fb-stats">';

$stat_items = [
    ['📦', number_format($stats->total ?? 0),   'Total Orders'],
    ['🚚', number_format($stats->active ?? 0),   'In Progress'],
    ['✅', number_format($stats->delivered ?? 0),'Delivered'],
    ['💰', '₦'.number_format($stats->spent ?? 0),'Total Spent'],
];
foreach ($stat_items as [$icon, $val, $lbl]) {
    print '<div class="fb-stat">';
    print '<div class="fb-stat-icon">'.$icon.'</div>';
    print '<div class="fb-stat-val">'.$val.'</div>';
    print '<div class="fb-stat-lbl">'.$lbl.'</div>';
    print '</div>';
}
print '</div>';

// ── PAYMENT BANNER (Pending users only) ──────────────────────────────────────
if ($sub->subscription_status === 'Pending') {
    print '<div class="fb-pay-banner">';
    print '<div class="fb-pay-inner">';
    print '<div class="fb-pay-icon">⚠️</div>';
    print '<div class="fb-pay-text">';
    print '<div class="fb-pay-title">Payment Required to Start Ordering</div>';
    print '<div class="fb-pay-desc">Your account has been verified but you need to <strong>choose a subscription plan and complete payment</strong> before you can browse food packages or place orders.</div>';
    print '</div>';
    print '<a href="renew_subscription.php" class="fb-pay-btn">💳 Choose a Plan &amp; Pay &rarr;</a>';
    print '</div>';
    print '</div>';
}

// ── BODY ─────────────────────────────────────────────────────────────────────
print '<div class="fb-body">';

// ── LEFT: Recent Orders ───────────────────────────────────────────────────────
print '<div>';
print '<div class="fb-sec-head">';
print '<h2 class="fb-sec-title">📋 Recent Orders</h2>';
print '<a href="my_orders.php" class="fb-sec-link">View all →</a>';
print '</div>';
print '<div class="fb-card">';

$badge_map = [
    'Delivered'  => 'badge-delivered',
    'In Transit' => 'badge-transit',
    'Prepared'   => 'badge-prepared',
    'Bundled'    => 'badge-bundled',
    'Pending'    => 'badge-pending',
];

if ($res_orders && $db->num_rows($res_orders) > 0) {
    while ($ord = $db->fetch_object($res_orders)) {
        $bc = $badge_map[$ord->status] ?? 'badge-pending';
        print '<a href="view_order.php?id='.$ord->rowid.'" class="fb-order-row">';
        print '<div>';
        print '<div class="fb-order-ref">'.dol_escape_htmltag($ord->ref).'</div>';
        print '<div class="fb-order-date">'.dol_print_date($db->jdate($ord->datec), 'day').'</div>';
        print '</div>';
        print '<div style="display:flex;align-items:center;gap:12px">';
        print '<span class="fb-badge '.$bc.'">'.dol_escape_htmltag($ord->status).'</span>';
        print '<span class="fb-order-amt">₦'.number_format($ord->total_amount, 0).'</span>';
        print '</div>';
        print '</a>';
    }
} else {
    print '<div class="empty-orders">';
    print '<span>📦</span>';
    print '<p>No orders yet. <a href="product_catalog.php" style="color:#0d9488;font-weight:700;">Browse packages →</a></p>';
    print '</div>';
}
print '</div>'; // card
print '</div>'; // left col

// ── RIGHT: Subscription info + Quick actions ──────────────────────────────────
print '<div>';

// Subscription card
print '<div class="fb-sec-head"><h2 class="fb-sec-title">🔑 My Subscription</h2></div>';
print '<div class="fb-card" style="margin-bottom:24px">';
print '<div class="fb-sub-info">';
$rows = [
    'Plan'    => dol_escape_htmltag($sub->subscription_type ?: '—'),
    'Status'  => dol_escape_htmltag($sub->subscription_status ?: '—'),
    'Expires' => $sub->subscription_end_date ? dol_print_date($db->jdate($sub->subscription_end_date), 'day') : '—',
    'Orders/mo' => $sub->max_orders_per_month ? $sub->max_orders_per_month : 'Unlimited',
];
foreach ($rows as $label => $value) {
    print '<div class="fb-sub-row"><span>'.$label.'</span><strong>'.$value.'</strong></div>';
}
print '</div>';
print '<a href="renew_subscription.php" class="fb-renew-btn">🔄 Manage / Upgrade Plan</a>';
print '</div>';

// Quick actions
print '<div class="fb-sec-head"><h2 class="fb-sec-title">⚡ Quick Actions</h2></div>';
print '<div class="fb-actions-grid">';
$actions = [
    ['product_catalog.php', '🛍️', 'Browse Packages'],
    ['view_cart.php',       '🛒', 'My Cart'],
    ['my_orders.php',       '📦', 'My Orders'],
    ['my_profile.php',      '👤', 'My Profile'],
];
foreach ($actions as [$url, $icon, $label]) {
    print '<a href="'.$url.'" class="fb-action">';
    print '<div class="fb-action-icon">'.$icon.'</div>';
    print '<div class="fb-action-label">'.$label.'</div>';
    print '</a>';
}
print '</div>';
print '</div>'; // right col

print '</div>'; // body

llxFooter();
?>
