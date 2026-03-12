<?php
/**
 * Subscription Success
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;
$langs->load("admin");

if (!FoodbankPermissions::isBeneficiary($user, $db)) {
    accessforbidden();
}

$tier_name = GETPOST('tier', 'alpha');
$end_date  = GETPOST('end_date', 'alpha');

// Reload subscriber for fresh data
$sql_ben    = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$subscriber = $db->fetch_object($db->query($sql_ben));

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Subscription Activated');
?>
<link rel="stylesheet" href="<?php echo DOL_URL_ROOT; ?>/custom/foodbankcrm/css/responsive.css">
<style>
    #id-top,.side-nav,.side-nav-vert,#id-left,.login_block,.tmenudiv,.nav-bar,header{display:none!important;width:0!important;height:0!important;pointer-events:none!important}
    html,body{background:#f0f4f8!important;margin:0!important;padding:0!important;width:100%!important;overflow-x:hidden!important;font-family:'Segoe UI',system-ui,sans-serif}
    #id-container,#id-right,.id-right{margin:0!important;padding:0!important;width:100vw!important;max-width:100vw!important;display:block!important}
    .fiche{width:100%!important;max-width:100%!important;margin:0!important}
    *{box-sizing:border-box}
    body{padding-top:64px!important}

    .fb-nav{position:fixed;top:0;left:0;right:0;height:64px;background:linear-gradient(90deg,#0f766e,#0d9488);display:flex;align-items:center;justify-content:space-between;padding:0 28px;z-index:9999;box-shadow:0 2px 16px rgba(0,0,0,.18)}
    .fb-nav-brand{font-size:17px;font-weight:800;color:#fff;text-decoration:none}
    .fb-nav-links{display:flex;gap:2px}
    @media(max-width:768px){
        .fb-nav-links{display:none!important;position:fixed;top:64px;left:0;right:0;flex-direction:column;background:linear-gradient(180deg,#0f766e,#0d9488);padding:6px 0 12px;z-index:9998;box-shadow:0 8px 24px rgba(0,0,0,.25);gap:0}
        .fb-nav-links.fb-open{display:flex!important}
    }
    .fb-nav-link{color:rgba(255,255,255,.82);text-decoration:none;font-size:13px;font-weight:500;padding:7px 14px;border-radius:20px}
    .fb-nav-link:hover{background:rgba(255,255,255,.18);color:#fff}
    .fb-logout{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.35);padding:6px 16px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600}

    .page-wrap{max-width:560px;margin:0 auto;padding:48px 20px 60px;text-align:center}

    /* Confetti ring animation */
    @keyframes popIn{0%{transform:scale(.5);opacity:0}70%{transform:scale(1.1)}100%{transform:scale(1);opacity:1}}
    @keyframes shimmer{0%,100%{opacity:1}50%{opacity:.6}}
    .success-ring{width:120px;height:120px;border-radius:50%;background:linear-gradient(135deg,#d1fae5,#a7f3d0);display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:56px;animation:popIn .5s ease-out forwards;box-shadow:0 0 0 12px rgba(16,185,129,.12),0 0 0 24px rgba(16,185,129,.06)}

    .success-title{font-size:30px;font-weight:800;color:#064e3b;margin:0 0 8px}
    .success-sub{font-size:15px;color:#64748b;margin:0 0 32px}

    .plan-card{background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,.07);border:1px solid #e8edf2;overflow:hidden;margin-bottom:28px;text-align:left}
    .plan-card-top{background:linear-gradient(135deg,#0f766e,#0d9488);padding:20px 24px;color:#fff}
    .plan-card-top h3{margin:0 0 4px;font-size:18px;font-weight:800}
    .plan-card-top p{margin:0;font-size:13px;opacity:.8}
    .plan-card-body{padding:20px 24px}
    .plan-detail{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:14px}
    .plan-detail:last-child{border-bottom:none}
    .plan-detail-label{color:#64748b}
    .plan-detail-value{font-weight:700;color:#1e293b}
    .active-badge{background:#d1fae5;color:#065f46;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase}

    .btn-dash{display:inline-flex;align-items:center;gap:8px;padding:14px 36px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:15px;font-weight:800;border-radius:30px;text-decoration:none;transition:all .2s;box-shadow:0 4px 16px rgba(16,185,129,.35)}
    .btn-dash:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(16,185,129,.45)}
    .btn-orders{display:inline-flex;align-items:center;gap:6px;margin-top:12px;padding:10px 24px;background:#f1f5f9;color:#475569;font-size:13px;font-weight:600;border-radius:20px;text-decoration:none;transition:all .15s}
    .btn-orders:hover{background:#e2e8f0}
</style>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var btn = document.getElementById('fb-hamburger');
    var links = document.querySelector('.fb-nav-links');
    if (!btn || !links) return;
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
print '<nav class="fb-nav">';
print '<a href="dashboard_beneficiary.php" class="fb-nav-brand">🥦 FoodbankCRM</a>';
print '<div class="fb-nav-links">';
print '<a href="product_catalog.php" class="fb-nav-link">Packages</a>';
print '<a href="view_cart.php" class="fb-nav-link">My Cart</a>';
print '<a href="my_orders.php" class="fb-nav-link">Orders</a>';
print '<a href="my_profile.php" class="fb-nav-link">Profile</a>';
print '</div>';
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="fb-logout">🚪 Logout</a>';
print '<button class="fb-nav-hamburger" id="fb-hamburger" aria-label="Toggle menu"><span></span><span></span><span></span></button>';
print '</nav>';

print '<div class="page-wrap">';

// Success icon
print '<div class="success-ring">🎉</div>';
print '<h1 class="success-title">Subscription Activated!</h1>';
print '<p class="success-sub">Welcome to the <strong>'.dol_escape_htmltag($tier_name).'</strong> plan,<br>'.dol_escape_htmltag($subscriber->firstname).'!</p>';

// Plan card
print '<div class="plan-card">';
print '<div class="plan-card-top">';
print '<h3>'.dol_escape_htmltag($tier_name).'</h3>';
print '<p>Your plan is now active and ready to use</p>';
print '</div>';
print '<div class="plan-card-body">';
print '<div class="plan-detail"><span class="plan-detail-label">Status</span><span class="active-badge">✅ Active</span></div>';
if ($end_date) {
    $friendly_end = date('F j, Y', strtotime($end_date));
    $days_left    = max(0, (int)floor((strtotime($end_date) - time()) / 86400));
    print '<div class="plan-detail"><span class="plan-detail-label">Valid Until</span><span class="plan-detail-value">'.$friendly_end.'</span></div>';
    print '<div class="plan-detail"><span class="plan-detail-label">Days Remaining</span><span class="plan-detail-value" style="color:#0d9488">'.$days_left.' days</span></div>';
}
print '<div class="plan-detail"><span class="plan-detail-label">Payment</span><span class="plan-detail-value">✅ Paid via Paystack</span></div>';
print '</div>';
print '</div>'; // plan-card

// Action buttons
print '<a href="dashboard_beneficiary.php" class="btn-dash">🏠 Go to Dashboard</a><br>';
print '<a href="product_catalog.php" class="btn-orders">📦 Browse Packages →</a>';

print '</div>'; // page-wrap

llxFooter();
?>
