<?php
/**
 * VENDOR DASHBOARD - FoodbankCRM Design System
 * Role Color: Amber (#d97706) | Layout: Fullscreen with Sticky Top Nav
 * Logic: Pending vendors see lock screen. Active vendors see full portal.
 */
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

if (isset($_SESSION['foodbank_checked'])) {
    $_SESSION['foodbank_checked'] = false;
}

$langs->load("admin");

// Security Check
if (!FoodbankPermissions::isVendor($user, $db)) {
    accessforbidden('You do not have access to the vendor dashboard.');
}

// Fetch Vendor
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);

if (!$res || $db->num_rows($res) == 0) {
    $_SESSION["mainmenu"] = "foodbankcrm";
    llxHeader('<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">', 'Vendor Dashboard');
    print '<div class="error">Vendor profile not found. Please contact administrator.</div>';
    llxFooter();
    exit;
}

$vendor        = $db->fetch_object($res);
$vendor_id     = $vendor->rowid;
$vendor_status = $vendor->status;

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">', 'Vendor Dashboard');
?>
<style>
    :root {
        --accent:       #d97706;
        --accent-light: #fef3c7;
        --accent-dark:  #b45309;
        --surface:      #fffbeb;
        --radius:       12px;
        --shadow:       0 4px 12px rgba(0,0,0,0.06);
        --font:         "Segoe UI", Roboto, Arial, sans-serif;
    }

    /* Fullscreen layout — hide all Dolibarr chrome */
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        pointer-events: none !important;
    }
    html, body {
        background: #f8f9fa !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        overflow-x: hidden !important;
        font-family: var(--font);
    }
    #id-container, #id-right, .id-right {
        margin: 0 !important;
        padding: 0 !important;
        width: 100vw !important;
        max-width: 100vw !important;
        flex: none !important;
        display: block !important;
    }
    .fiche { width: 100% !important; max-width: 100% !important; margin: 0 !important; }
    body { padding-top: 60px !important; }

    /* Sticky Top Nav */
    .fb-topnav {
        position: fixed;
        top: 0; left: 0; right: 0;
        height: 60px;
        background: var(--accent);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 30px;
        z-index: 9999;
        box-shadow: 0 2px 12px rgba(217,119,6,0.25);
    }
    .fb-topnav-brand { font-size: 18px; font-weight: 800; color: white; text-decoration: none; }
    .fb-topnav-links { display: flex; gap: 4px; align-items: center; }
    .fb-topnav-link {
        color: rgba(255,255,255,0.85);
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        padding: 6px 14px;
        border-radius: 20px;
        transition: background 0.15s;
    }
    .fb-topnav-link:hover { background: rgba(255,255,255,0.2); color: white; }
    .fb-topnav-user { display: flex; align-items: center; gap: 12px; }
    .fb-topnav-name { color: rgba(255,255,255,0.9); font-size: 13px; font-weight: 500; }
    .fb-btn-logout {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(255,255,255,0.15);
        color: white;
        border: 1px solid rgba(255,255,255,0.4);
        padding: 7px 16px;
        border-radius: 20px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s;
    }
    .fb-btn-logout:hover { background: white; color: var(--accent); border-color: white; }

    /* ── Hamburger ────────────────────────────────── */
    .fb-hamburger { display:none; flex-direction:column; justify-content:center; gap:5px; background:transparent; border:none; cursor:pointer; padding:8px; border-radius:6px; transition:background .2s; flex-shrink:0; }
    .fb-hamburger:hover { background:rgba(255,255,255,.15); }
    .fb-hamburger span { display:block; width:22px; height:2px; background:#fff; border-radius:2px; transition:all .25s; }
    .fb-hamburger.open span:nth-child(1) { transform:translateY(7px) rotate(45deg); }
    .fb-hamburger.open span:nth-child(2) { opacity:0; }
    .fb-hamburger.open span:nth-child(3) { transform:translateY(-7px) rotate(-45deg); }

    /* ── Mobile (≤768px) ──────────────────────────── */
    @media (max-width: 768px) {
        .fb-topnav { padding: 0 14px !important; }
        .fb-topnav-links {
            display: none;
            position: fixed;
            top: 60px; left: 0; right: 0;
            flex-direction: column;
            background: linear-gradient(180deg, #b45309, #d97706);
            padding: 6px 0 12px;
            z-index: 9998;
            box-shadow: 0 8px 24px rgba(0,0,0,.25);
            gap: 0;
        }
        .fb-topnav-links.fb-open { display: flex; }
        .fb-topnav-link { padding: 14px 22px 14px 28px !important; border-radius: 0 !important; font-size: 15px !important; border-bottom: 1px solid rgba(255,255,255,.12); width: 100%; }
        .fb-hamburger { display: flex; }
        .fb-topnav-name { display: none !important; }
        .fb-container { padding: 24px 14px !important; }
        .fb-header h1 { font-size: 22px !important; }
    }
    @media (max-width: 480px) {
        .fb-topnav-brand { font-size: 15px !important; }
    }

    /* Main Container */
    .fb-container {
        width: 95%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    /* Page Header */
    .fb-header { margin-bottom: 30px; }
    .fb-header h1 { margin: 0; font-size: 28px; font-weight: 800; color: #1e293b; }
    .fb-header p  { margin: 5px 0 0; color: #64748b; font-size: 14px; }

    /* Stat Cards */
    .fb-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 20px;
        margin-bottom: 35px;
    }
    .fb-stat-card {
        background: white;
        padding: 24px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        border: 1px solid #f1f5f9;
        border-top: 4px solid var(--accent);
        text-decoration: none;
        display: block;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .fb-stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
    .fb-stat-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #94a3b8; margin-bottom: 8px; }
    .fb-stat-value { font-size: 30px; font-weight: 800; color: #1e293b; line-height: 1; }
    .fb-stat-sub   { font-size: 12px; margin-top: 6px; color: #64748b; }

    /* Section Title */
    .fb-section-title { font-size: 17px; font-weight: 700; color: #1e293b; margin: 0 0 18px; }

    /* Action Cards */
    .fb-action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 18px;
        margin-bottom: 40px;
    }
    .fb-action-card {
        background: white;
        padding: 28px 20px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        border: 1px solid #f1f5f9;
        text-align: center;
        text-decoration: none;
        color: #334155;
        transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
        display: block;
    }
    .fb-action-card:hover {
        transform: translateY(-5px);
        border-color: var(--accent);
        box-shadow: 0 8px 24px rgba(217,119,6,0.12);
    }
    .fb-action-icon  { font-size: 38px; margin-bottom: 12px; }
    .fb-action-title { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
    .fb-action-desc  { font-size: 12px; color: #94a3b8; }

    /* Card Wrapper */
    .fb-card { background: white; border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid #f1f5f9; overflow: hidden; }
    .fb-card-footer { padding: 14px 20px; border-top: 1px solid #f1f5f9; text-align: right; }
    .fb-card-footer a { color: var(--accent); text-decoration: none; font-weight: 600; font-size: 13px; }
    .fb-card-empty  { padding: 50px; text-align: center; color: #94a3b8; }

    /* Table */
    .fb-table { width: 100%; border-collapse: collapse; }
    .fb-table th { text-align: left; padding: 13px 20px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    .fb-table td { padding: 13px 20px; border-bottom: 1px solid #f8fafc; color: #334155; font-size: 14px; }
    .fb-table tr:last-child td { border-bottom: none; }
    .fb-table tr:hover td { background: #fef9f0; }
    .fb-badge { display: inline-block; padding: 3px 11px; border-radius: 20px; font-size: 11px; font-weight: 700; color: white; }

    /* Pending Lock Screen */
    .fb-locked-wrapper { display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 60px); padding: 40px 20px; }
    .fb-locked-box {
        background: white;
        max-width: 560px;
        width: 100%;
        padding: 50px 40px;
        border-radius: var(--radius);
        text-align: center;
        box-shadow: var(--shadow);
        border-top: 5px solid var(--accent);
    }
    .fb-pending-badge {
        background: var(--accent-light);
        color: var(--accent-dark);
        padding: 7px 20px;
        border-radius: 30px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 11px;
        display: inline-block;
        margin-bottom: 20px;
        border: 1px solid #fcd34d;
    }
    .fb-logout-plain {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        padding: 10px 22px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s;
    }
    .fb-logout-plain:hover { background: #f1f5f9; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn   = document.getElementById('fbHamburger');
    var links = document.querySelector('.fb-topnav-links');
    if (!btn || !links) return;
    btn.addEventListener('click', function () {
        links.classList.toggle('fb-open');
        btn.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
        if (!btn.contains(e.target) && !links.contains(e.target)) {
            links.classList.remove('fb-open');
            btn.classList.remove('open');
        }
    });
});
</script>

<?php
// ===== GATEKEEPER: Pending Vendors =====
if ($vendor_status == 'Pending') {
    print '<div class="fb-locked-wrapper">';
    print '<div class="fb-locked-box">';
    print '<span class="fb-pending-badge">Application Under Review</span>';
    print '<div style="font-size:56px; margin:10px 0 18px;">⏳</div>';
    print '<h2 style="margin:0 0 12px; color:#1e293b; font-size:24px;">Welcome, '.dol_escape_htmltag($vendor->name).'!</h2>';
    print '<p style="color:#64748b; font-size:16px; line-height:1.7; margin:0 0 8px;">';
    print 'Thank you for registering as a vendor partner.<br>';
    print 'Your application is being reviewed by our procurement team.';
    print '</p>';
    print '<p style="color:#94a3b8; font-size:13px; margin:0 0 30px;">You will receive an email once your account is activated.</p>';
    print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="fb-logout-plain">🚪 Log Out</a>';
    print '</div>';
    print '</div>';
    llxFooter();
    exit;
}

// ===== ACTIVE VENDOR DASHBOARD =====

// Stats Query
$sql_stats = "SELECT
    COUNT(DISTINCT d.rowid) as total_batches,
    COALESCE(SUM(d.quantity), 0) as total_quantity,
    COALESCE(SUM(CASE WHEN d.status = 'Pending' THEN 1 ELSE 0 END), 0) as pending_review,
    COUNT(DISTINCT d.product_name) as unique_products
    FROM ".MAIN_DB_PREFIX."foodbank_donations d
    WHERE d.fk_vendor = ".(int)$vendor_id;
$res_stats = $db->query($sql_stats);
$stats = ($res_stats) ? $db->fetch_object($res_stats) : new stdClass();

// --- STICKY TOP NAV ---
print '<div class="fb-topnav">';
print '<a href="dashboard_vendor.php" class="fb-topnav-brand"><img src="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/logo-white.png" alt="Foodbank CRM" style="height:32px;display:block"></a>';
print '<div class="fb-topnav-links">';
print '<a href="my_donations.php"   class="fb-topnav-link">Supply History</a>';
print '<a href="vendor_reports.php" class="fb-topnav-link">Performance</a>';
print '<a href="vendor_profile.php" class="fb-topnav-link">Profile</a>';
print '<a href="vendor_support.php" class="fb-topnav-link">Support</a>';
print '</div>';
print '<div class="fb-topnav-user">';
print '<span class="fb-topnav-name">'.dol_escape_htmltag($vendor->name).'</span>';
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="fb-btn-logout">🚪 Logout</a>';
print '</div>';
print '<button class="fb-hamburger" id="fbHamburger" aria-label="Toggle menu">';
print '<span></span><span></span><span></span>';
print '</button>';
print '</div>';

// --- MAIN CONTENT ---
print '<div class="fb-container">';

print '<div class="fb-header">';
print '<h1>👋 Welcome, '.dol_escape_htmltag($vendor->name).'!</h1>';
print '<p>Vendor Portal &bull; ID: '.dol_escape_htmltag($vendor->ref).'</p>';
print '</div>';

// Stats Grid
print '<div class="fb-stats-grid">';

print '<div class="fb-stat-card" style="border-top-color:#667eea;">';
print '<div class="fb-stat-label">Total Batches</div>';
print '<div class="fb-stat-value">'.($stats->total_batches ?? 0).'</div>';
print '<div class="fb-stat-sub">Inventory submissions</div>';
print '</div>';

print '<div class="fb-stat-card" style="border-top-color:#f59e0b;">';
print '<div class="fb-stat-label">Pending Review</div>';
print '<div class="fb-stat-value">'.($stats->pending_review ?? 0).'</div>';
print '<div class="fb-stat-sub">Awaiting admin approval</div>';
print '</div>';

print '<div class="fb-stat-card" style="border-top-color:#10b981;">';
print '<div class="fb-stat-label">Active Products</div>';
print '<div class="fb-stat-value">'.($stats->unique_products ?? 0).'</div>';
print '<div class="fb-stat-sub">Distinct items supplied</div>';
print '</div>';

print '<div class="fb-stat-card" style="border-top-color:#8b5cf6;">';
print '<div class="fb-stat-label">Units Supplied</div>';
print '<div class="fb-stat-value">'.number_format($stats->total_quantity ?? 0).'</div>';
print '<div class="fb-stat-sub">Total quantity logged</div>';
print '</div>';

print '</div>';

// Action Cards
print '<div class="fb-section-title">⚡ Management Console</div>';
print '<div class="fb-action-grid">';

$actions = [
    ['icon' => '📦', 'title' => 'Add Inventory',    'desc' => 'Submit a new supply batch',  'link' => 'create_donation.php'],
    ['icon' => '📋', 'title' => 'Supply History',   'desc' => 'Track stock logs & status',  'link' => 'my_donations.php'],
    ['icon' => '📊', 'title' => 'Performance',      'desc' => 'View supply metrics',        'link' => 'vendor_reports.php'],
    ['icon' => '🏢', 'title' => 'Business Profile', 'desc' => 'Update company details',     'link' => 'vendor_profile.php'],
    ['icon' => '💬', 'title' => 'Vendor Support',   'desc' => 'Contact administration',     'link' => 'vendor_support.php'],
];

foreach ($actions as $act) {
    print '<a href="'.$act['link'].'" class="fb-action-card">';
    print '<div class="fb-action-icon">'.$act['icon'].'</div>';
    print '<div class="fb-action-title">'.$act['title'].'</div>';
    print '<div class="fb-action-desc">'.$act['desc'].'</div>';
    print '</a>';
}
print '</div>';

// Recent Inventory Table
print '<div class="fb-section-title">📋 Recent Inventory Logs</div>';

$sql_recent = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_donations
               WHERE fk_vendor = ".(int)$vendor_id."
               ORDER BY date_donation DESC LIMIT 5";
$res_recent = $db->query($sql_recent);

if ($res_recent && $db->num_rows($res_recent) > 0) {
    print '<div class="fb-card">';
    print '<table class="fb-table">';
    print '<thead><tr><th>Batch Ref</th><th>Date</th><th>Product</th><th style="text-align:center">Qty</th><th style="text-align:center">Status</th></tr></thead>';
    print '<tbody>';
    while ($row = $db->fetch_object($res_recent)) {
        $color = ($row->status == 'Received') ? '#10b981' : (($row->status == 'Pending') ? '#f59e0b' : '#ef4444');
        print '<tr>';
        print '<td><strong>'.dol_escape_htmltag($row->ref).'</strong></td>';
        print '<td>'.dol_print_date($db->jdate($row->date_donation), 'day').'</td>';
        print '<td>'.dol_escape_htmltag($row->product_name).'</td>';
        print '<td style="text-align:center">'.number_format($row->quantity).'</td>';
        print '<td style="text-align:center"><span class="fb-badge" style="background:'.$color.'">'.dol_escape_htmltag($row->status).'</span></td>';
        print '</tr>';
    }
    print '</tbody></table>';
    print '<div class="fb-card-footer"><a href="my_donations.php">View Full Stock Log →</a></div>';
    print '</div>';
} else {
    print '<div class="fb-card"><div class="fb-card-empty">';
    print '<div style="font-size:36px; margin-bottom:10px;">📉</div>';
    print 'No inventory logs yet. Start supplying products today!';
    print '</div></div>';
}

print '</div>'; // End container
llxFooter();
?>
