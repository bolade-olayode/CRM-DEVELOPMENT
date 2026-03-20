<?php
/**
 * ADMIN DASHBOARD - FoodbankCRM
 */
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;
$langs->load("admin");

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Access Denied: Administrative privileges required.');
}

// ── Summary stats ──────────────────────────────────────────────────────────────
$revenue        = (float)($db->fetch_object($db->query("SELECT COALESCE(SUM(amount),0) as t FROM ".MAIN_DB_PREFIX."foodbank_payments WHERE payment_status='Success'"))->t ?? 0);
$order_revenue  = (float)($db->fetch_object($db->query("SELECT COALESCE(SUM(total_amount),0) as t FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE payment_status='Paid'"))->t ?? 0);
$total_revenue  = $revenue + $order_revenue;

$subs_active    = (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE subscription_status='Active'"))->t ?? 0);
$subs_total     = (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries"))->t ?? 0);
$pending_inv    = (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_donations WHERE status='Pending'"))->t ?? 0);
$vendors_active = (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE status='Active'"))->t ?? 0);
$vendors_pending= (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE status='Pending'"))->t ?? 0);
$orders_total   = (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_distributions"))->t ?? 0);
$orders_pending = (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE status='Pending'"))->t ?? 0);

$open_tickets = 0;
if ($db->num_rows($db->query("SHOW TABLES LIKE '".MAIN_DB_PREFIX."foodbank_support'")) > 0) {
    $open_tickets = (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_support WHERE status='Open'"))->t ?? 0);
}

// Attention count (drives badge on tab)
$needs_attention = $pending_inv + $vendors_pending + $open_tickets;

// ── Tab data ───────────────────────────────────────────────────────────────────

// Pending inventory
$res_inv = $db->query(
    "SELECT d.rowid, d.ref, d.product_name, d.quantity, d.unit,
            v.name AS vendor_name, d.date_donation
     FROM ".MAIN_DB_PREFIX."foodbank_donations d
     LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON d.fk_vendor = v.rowid
     WHERE d.status = 'Pending' ORDER BY d.date_donation ASC LIMIT 10"
);

// Pending vendor approvals
$res_vend = $db->query(
    "SELECT rowid, name, category, date_creation FROM ".MAIN_DB_PREFIX."foodbank_vendors
     WHERE status='Pending' ORDER BY date_creation ASC LIMIT 8"
);

// Recent distributions (more rows now they're behind a tab)
$res_dist = $db->query(
    "SELECT d.rowid, d.ref, d.status, d.total_amount, d.datec,
            b.firstname, b.lastname
     FROM ".MAIN_DB_PREFIX."foodbank_distributions d
     INNER JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON d.fk_beneficiary = b.rowid
     ORDER BY d.datec DESC LIMIT 15"
);

// Recent subscribers
$res_subs = $db->query(
    "SELECT rowid, firstname, lastname, email, subscription_type, subscription_status, datec
     FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries
     ORDER BY datec DESC LIMIT 10"
);

// System counts for summary
$summary_rows = [
    ['Warehouses',          (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_warehouses"))->t ?? 0),                                              'warehouses.php',          'chip-slate'],
    ['Active Packages',     (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_packages WHERE status='Active'"))->t ?? 0),                          'packages.php',            'chip-purple'],
    ['Subscription Tiers',  (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE is_active=1"))->t ?? 0),                    'subscription_tiers.php',  'chip-green'],
    ['Inventory Entries',   (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_donations"))->t ?? 0),                                               'donations.php',           'chip-amber'],
    ['Expired Subscribers', (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE subscription_status='Expired'"))->t ?? 0),       'beneficiaries.php',       'chip-red'],
    ['Total Vendors',       $vendors_active + $vendors_pending,                                                                                                                           'vendors.php',             'chip-indigo'],
];

$admin_name = trim($user->firstname.' '.$user->lastname) ?: $user->login;
$hour       = (int)date('H');
$greeting   = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$_SESSION["mainmenu"] = "foodbankcrm";
$_fb_admin_head = '<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">'
              . '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/css/admin_mobile.css">';
llxHeader($_fb_admin_head, 'Admin Dashboard');
?>
<style>
:root {
    --accent:       #4f46e5;
    --accent-light: #e0e7ff;
    --accent-dark:  #3730a3;
    --green:        #10b981;
    --blue:         #3b82f6;
    --amber:        #f59e0b;
    --red:          #ef4444;
    --purple:       #8b5cf6;
    --surface:      #f1f5f9;
    --radius:       14px;
    --shadow:       0 2px 8px rgba(0,0,0,.07);
    --shadow-md:    0 6px 20px rgba(0,0,0,.10);
    --font:         "Segoe UI", Roboto, Arial, sans-serif;
}

#id-top { display: none !important; }
.side-nav, .side-nav-vert { top: 0 !important; height: 100vh !important; }
#id-right { padding-top: 0 !important; background: var(--surface) !important; min-height: 100vh; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }

.db-wrap { max-width: 1440px; margin: 0 auto; padding-bottom: 48px; font-family: var(--font); }

/* ── Hero ── */
.db-hero {
    background: linear-gradient(135deg, #3730a3 0%, #4f46e5 45%, #7c3aed 100%);
    padding: 32px 40px 96px; position: relative; overflow: hidden;
}
.db-hero::before { content:''; position:absolute; top:-60px; right:-60px; width:300px; height:300px; background:rgba(255,255,255,.06); border-radius:50%; }
.db-hero::after  { content:''; position:absolute; bottom:-80px; left:30%; width:240px; height:240px; background:rgba(255,255,255,.04); border-radius:50%; }
.db-hero-inner { position:relative; z-index:1; display:flex; justify-content:space-between; align-items:flex-start; }
.db-hero h1  { margin:0; font-size:26px; font-weight:800; color:#fff; letter-spacing:-.3px; }
.db-hero p   { margin:5px 0 0; color:rgba(255,255,255,.7); font-size:13px; }
.db-hero-right { display:flex; flex-direction:column; align-items:flex-end; gap:12px; }
.db-hero-date  { text-align:right; color:rgba(255,255,255,.65); font-size:13px; }
.db-hero-date strong { display:block; color:#fff; font-size:15px; font-weight:700; }
.db-logout {
    display:inline-flex; align-items:center; gap:6px;
    background:rgba(255,255,255,.15); color:#fff !important;
    border:1px solid rgba(255,255,255,.3); padding:8px 16px; border-radius:30px;
    font-weight:600; font-size:13px; text-decoration:none;
    transition:background .2s;
}
.db-logout:hover { background:rgba(255,255,255,.25); }

/* ── Stats strip ── */
.db-stats-outer { padding:0 32px; margin-top:-64px; position:relative; z-index:10; margin-bottom:0; }
.db-stats-grid  { display:grid; grid-template-columns:repeat(6,1fr); gap:14px; }
@media(max-width:1200px) { .db-stats-grid { grid-template-columns:repeat(3,1fr); } }
@media(max-width:700px)  { .db-stats-grid { grid-template-columns:repeat(2,1fr); } }

.db-stat {
    background:#fff; border-radius:var(--radius); box-shadow:var(--shadow-md);
    padding:18px 18px 16px; text-decoration:none; display:block;
    transition:transform .2s, box-shadow .2s; border-bottom:3px solid transparent;
}
.db-stat:hover { transform:translateY(-3px); box-shadow:0 10px 28px rgba(0,0,0,.12); }
.db-stat-icon  { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:19px; margin-bottom:12px; }
.db-stat-val   { font-size:24px; font-weight:800; color:#0f172a; line-height:1; }
.db-stat-lbl   { font-size:10px; color:#64748b; text-transform:uppercase; letter-spacing:.6px; margin-top:4px; font-weight:600; }
.db-stat-sub   { font-size:11px; margin-top:5px; font-weight:500; }

.s-green  { border-color:#10b981; } .s-green  .db-stat-icon { background:#d1fae5; }
.s-blue   { border-color:#3b82f6; } .s-blue   .db-stat-icon { background:#dbeafe; }
.s-indigo { border-color:#4f46e5; } .s-indigo .db-stat-icon { background:#e0e7ff; }
.s-amber  { border-color:#f59e0b; } .s-amber  .db-stat-icon { background:#fef3c7; }
.s-red    { border-color:#ef4444; } .s-red    .db-stat-icon { background:#fee2e2; }
.s-purple { border-color:#8b5cf6; } .s-purple .db-stat-icon { background:#ede9fe; }

/* ── Tab navigation ── */
.db-tab-shell  { padding:0 32px; margin-top:28px; }
.db-tab-bar    {
    display:flex; gap:0; background:#fff; border-radius:var(--radius);
    box-shadow:var(--shadow); padding:6px; margin-bottom:24px;
    overflow-x:auto; scrollbar-width:none;
}
.db-tab-bar::-webkit-scrollbar { display:none; }
.db-tab {
    display:flex; align-items:center; gap:7px;
    padding:9px 20px; border-radius:10px; cursor:pointer;
    font-size:13px; font-weight:600; color:#64748b; white-space:nowrap;
    transition:all .18s; user-select:none; border:none; background:none;
    position:relative;
}
.db-tab:hover { color:#334155; background:#f8fafc; }
.db-tab.active { background:var(--accent); color:#fff; box-shadow:0 4px 12px rgba(79,70,229,.35); }
.db-tab-badge {
    display:inline-flex; align-items:center; justify-content:center;
    background:#ef4444; color:#fff; font-size:10px; font-weight:800;
    width:18px; height:18px; border-radius:50%; line-height:1;
}
.db-tab.active .db-tab-badge { background:rgba(255,255,255,.3); }

/* ── Tab panels ── */
.db-panel       { display:none; }
.db-panel.active{ display:block; }

/* ── Panel layout helpers ── */
.db-two-col { display:grid; grid-template-columns:1fr 320px; gap:20px; align-items:start; }
@media(max-width:1100px) { .db-two-col { grid-template-columns:1fr; } }

/* ── Cards ── */
.db-card { background:#fff; border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; margin-bottom:20px; }
.db-card-head {
    padding:14px 20px; border-bottom:1px solid #f1f5f9;
    display:flex; justify-content:space-between; align-items:center;
}
.db-card-head h3 { margin:0; font-size:14px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px; }
.db-card-head a  { font-size:12px; font-weight:600; color:var(--accent); text-decoration:none; }
.db-card-head a:hover { text-decoration:underline; }
.db-card-foot { padding:11px 20px; border-top:1px solid #f1f5f9; text-align:center; }
.db-card-foot a { color:var(--accent); font-size:13px; font-weight:600; text-decoration:none; }

/* ── Table ── */
.db-table { width:100%; border-collapse:collapse; }
.db-table th { padding:10px 18px; background:#f8fafc; color:#64748b; font-size:10px; text-transform:uppercase; letter-spacing:.7px; border-bottom:1px solid #e2e8f0; font-weight:700; text-align:left; }
.db-table td { padding:11px 18px; border-bottom:1px solid #f8fafc; font-size:13px; color:#334155; }
.db-table tr:last-child td { border-bottom:none; }
.db-table tr:hover td { background:#fafbff; }

/* Chips */
.chip { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.chip-indigo { background:#e0e7ff; color:#3730a3; }
.chip-amber  { background:#fef3c7; color:#92400e; }
.chip-green  { background:#dcfce7; color:#15803d; }
.chip-blue   { background:#dbeafe; color:#1e40af; }
.chip-slate  { background:#f1f5f9; color:#475569; }
.chip-red    { background:#fee2e2; color:#991b1b; }
.chip-purple { background:#ede9fe; color:#6d28d9; }

.review-btn {
    background:var(--accent-light); color:var(--accent) !important;
    padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700;
    text-decoration:none; white-space:nowrap;
}

/* ── Quick actions grid ── */
.db-actions-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; padding:16px; }
@media(max-width:600px) { .db-actions-grid { grid-template-columns:repeat(2,1fr); } }
.db-action {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    text-align:center; padding:20px 12px; border-radius:12px; text-decoration:none;
    transition:transform .18s, box-shadow .18s; border:1.5px solid transparent;
}
.db-action:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(0,0,0,.1); }
.db-action-ico { font-size:26px; margin-bottom:8px; }
.db-action-ttl { font-size:12px; font-weight:700; color:#1e293b; }
.db-action-dsc { font-size:10px; color:#94a3b8; margin-top:2px; }

.a-indigo { background:#eef2ff; border-color:#c7d2fe; } .a-indigo:hover { background:#e0e7ff; }
.a-green  { background:#f0fdf4; border-color:#bbf7d0; } .a-green:hover  { background:#dcfce7; }
.a-amber  { background:#fffbeb; border-color:#fde68a; } .a-amber:hover  { background:#fef3c7; }
.a-blue   { background:#eff6ff; border-color:#bfdbfe; } .a-blue:hover   { background:#dbeafe; }
.a-purple { background:#faf5ff; border-color:#e9d5ff; } .a-purple:hover { background:#ede9fe; }
.a-red    { background:#fff1f2; border-color:#fecdd3; } .a-red:hover    { background:#fee2e2; }
.a-teal   { background:#f0fdfa; border-color:#99f6e4; } .a-teal:hover   { background:#ccfbf1; }
.a-rose   { background:#fff1f2; border-color:#fecdd3; } .a-rose:hover   { background:#ffe4e6; }
.a-sky    { background:#f0f9ff; border-color:#bae6fd; } .a-sky:hover    { background:#e0f2fe; }

/* Alert dot */
.alert-dot { display:inline-block; width:7px; height:7px; background:#ef4444; border-radius:50%; margin-left:5px; animation:pulse 1.6s infinite; }
@keyframes pulse { 0%,100%{opacity:1}50%{opacity:.4} }

/* All-clear state */
.db-clear { padding:48px; text-align:center; color:#94a3b8; }
.db-clear div { font-size:40px; margin-bottom:12px; }
.db-clear p   { margin:0; font-size:14px; font-weight:500; }

/* Empty state */
.db-empty { padding:32px; text-align:center; color:#94a3b8; }
.db-empty div { font-size:28px; margin-bottom:8px; }
.db-empty p   { margin:0; font-size:13px; }

/* Attention panel — two cards side by side */
.db-attention-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media(max-width:900px) { .db-attention-grid { grid-template-columns:1fr; } }

/* ── Responsive ─────────────────────────────────────────────────────── */
@media(max-width:768px) {
    .db-hero { padding:22px 16px 84px !important; }
    .db-hero-inner { flex-direction:column; gap:12px; }
    .db-hero-right { align-items:flex-start; flex-direction:row; flex-wrap:wrap; gap:8px; }
    .db-hero-date { text-align:left; }
    .db-stats-outer, .db-tab-shell { padding:0 12px !important; }
    .db-stats-outer { margin-top:-52px; }

    /* Tables: horizontal scroll on mobile */
    .db-card { overflow-x:auto; }
    .db-table { min-width:520px; }
    .db-table th, .db-table td { padding:9px 12px; font-size:12px; white-space:nowrap; }

    /* Quick actions: 2 columns on tablet */
    .db-actions-grid { grid-template-columns:repeat(2,1fr); padding:12px; gap:10px; }
    .db-action { padding:16px 10px; }

    /* Tab bar — smaller tabs */
    .db-tab { padding:8px 14px; font-size:12px; }

    /* Cards */
    .db-card-head { padding:12px 14px; }
    .db-card-head h3 { font-size:13px; }
    .db-card-foot { padding:10px 14px; }
}

@media(max-width:600px) {
    /* Stats: 2 col, compact */
    .db-stats-grid { grid-template-columns:repeat(2,1fr); gap:10px; }
    .db-stat { padding:14px; }
    .db-stat-icon { width:32px; height:32px; font-size:16px; margin-bottom:8px; }

    /* Actions: 3 per row on small phones */
    .db-actions-grid { grid-template-columns:repeat(3,1fr); padding:10px; gap:8px; }
    .db-action { padding:14px 8px; }
    .db-action-ico { font-size:22px; margin-bottom:6px; }
    .db-action-ttl { font-size:11px; }
    .db-action-dsc { display:none; } /* hide description on tiny screens */

    /* System summary rows */
    .db-card > div[style*="padding:4px"] a { padding:10px 14px; }
}

@media(max-width:480px) {
    .db-hero h1 { font-size:19px; }
    .db-hero p  { font-size:12px; }
    .db-hero img { height:28px !important; }
    .db-stat-val { font-size:20px; }
    .db-stat-lbl { font-size:9px; }
    .db-stat-sub { display:none; } /* hide sub-label on very small screens */

    /* Hide less critical table columns on phone */
    /* Distributions: hide Date */
    #panel-distributions .db-table th:first-child,
    #panel-distributions .db-table td:first-child { display:none; }
    /* Subscribers: hide Registered date */
    #panel-subscribers .db-table th:nth-child(5),
    #panel-subscribers .db-table td:nth-child(5) { display:none; }
    /* Inventory: hide Date */
    #panel-attention .db-table th:first-child,
    #panel-attention .db-table td:first-child { display:none; }

    .db-tab { padding:7px 10px; font-size:11px; gap:4px; }
    .db-tab-badge { width:15px; height:15px; font-size:9px; }
}
</style>

<div class="db-wrap">

    <!-- Hero -->
    <div class="db-hero">
        <div class="db-hero-inner">
            <div>
                <h1><?php echo $greeting.', '.dol_escape_htmltag($admin_name); ?> 👋</h1>
                <img src="<?php echo DOL_URL_ROOT; ?>/custom/foodbankcrm/img/logo-white.png" alt="Foodbank CRM" style="height:36px;margin-bottom:8px;display:block">
                <p>Admin Control Room</p>
            </div>
            <div class="db-hero-right">
                <a href="<?php echo DOL_URL_ROOT; ?>/user/logout.php" class="db-logout">🚪 Logout</a>
                <div class="db-hero-date">
                    <strong><?php echo date('l, F j'); ?></strong>
                    <?php echo date('Y'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats strip -->
    <div class="db-stats-outer">
        <div class="db-stats-grid">

            <a href="earnings.php" class="db-stat s-green" style="text-decoration:none;">
                <div class="db-stat-icon">💰</div>
                <div class="db-stat-val">₦<?php echo number_format($total_revenue, 0); ?></div>
                <div class="db-stat-lbl">Total Revenue</div>
                <div class="db-stat-sub" style="color:#10b981;">View breakdown →</div>
            </a>

            <div class="db-stat s-blue">
                <div class="db-stat-icon">👥</div>
                <div class="db-stat-val"><?php echo $subs_active; ?></div>
                <div class="db-stat-lbl">Active Subscribers</div>
                <div class="db-stat-sub" style="color:#64748b;"><?php echo $subs_total; ?> total</div>
            </div>

            <a href="?tab=attention" class="db-stat s-amber" style="text-decoration:none;">
                <div class="db-stat-icon">📦</div>
                <div class="db-stat-val"><?php echo $pending_inv; ?><?php if ($pending_inv > 0) echo '<span class="alert-dot"></span>'; ?></div>
                <div class="db-stat-lbl">Pending Stock</div>
                <div class="db-stat-sub" style="color:#f59e0b;">Needs approval →</div>
            </a>

            <a href="vendor_support.php" class="db-stat s-red" style="text-decoration:none;">
                <div class="db-stat-icon">🎫</div>
                <div class="db-stat-val"><?php echo $open_tickets; ?><?php if ($open_tickets > 0) echo '<span class="alert-dot"></span>'; ?></div>
                <div class="db-stat-lbl">Open Tickets</div>
                <div class="db-stat-sub" style="color:#ef4444;">Reply →</div>
            </a>

            <a href="vendors.php" class="db-stat s-indigo" style="text-decoration:none;">
                <div class="db-stat-icon">🏢</div>
                <div class="db-stat-val"><?php echo $vendors_active; ?></div>
                <div class="db-stat-lbl">Active Vendors</div>
                <div class="db-stat-sub" style="color:<?php echo $vendors_pending > 0 ? '#f59e0b' : '#64748b'; ?>;">
                    <?php echo $vendors_pending > 0 ? $vendors_pending.' pending' : 'All approved'; ?>
                </div>
            </a>

            <a href="?tab=distributions" class="db-stat s-purple" style="text-decoration:none;">
                <div class="db-stat-icon">🚚</div>
                <div class="db-stat-val"><?php echo $orders_total; ?></div>
                <div class="db-stat-lbl">Distributions</div>
                <div class="db-stat-sub" style="color:#8b5cf6;"><?php echo $orders_pending; ?> pending</div>
            </a>

        </div>
    </div>

    <!-- Tab shell -->
    <div class="db-tab-shell">

        <!-- Tab bar -->
        <div class="db-tab-bar">
            <button class="db-tab active" data-tab="overview">
                ⚡ Overview
            </button>
            <button class="db-tab" data-tab="attention">
                🔔 Needs Attention
                <?php if ($needs_attention > 0) : ?>
                <span class="db-tab-badge"><?php echo min($needs_attention, 99); ?></span>
                <?php endif; ?>
            </button>
            <button class="db-tab" data-tab="distributions">
                🚚 Distributions
            </button>
            <button class="db-tab" data-tab="subscribers">
                👥 Subscribers
            </button>
        </div>

        <!-- ── PANEL: Overview ── -->
        <div id="panel-overview" class="db-panel active">
            <div class="db-two-col">

                <!-- Quick Actions -->
                <div class="db-card">
                    <div class="db-card-head"><h3>⚡ Quick Actions</h3></div>
                    <div class="db-actions-grid">
                        <a href="beneficiaries.php"     class="db-action a-blue">
                            <div class="db-action-ico">👥</div>
                            <div class="db-action-ttl">Subscribers</div>
                            <div class="db-action-dsc">View &amp; manage</div>
                        </a>
                        <a href="vendors.php"           class="db-action a-indigo">
                            <div class="db-action-ico">🏢</div>
                            <div class="db-action-ttl">Vendors</div>
                            <div class="db-action-dsc">Approve &amp; manage</div>
                        </a>
                        <a href="create_donation.php"   class="db-action a-amber">
                            <div class="db-action-ico">📦</div>
                            <div class="db-action-ttl">Log Stock</div>
                            <div class="db-action-dsc">Record inventory</div>
                        </a>
                        <a href="packages.php"          class="db-action a-purple">
                            <div class="db-action-ico">🎁</div>
                            <div class="db-action-ttl">Packages</div>
                            <div class="db-action-dsc">Food box templates</div>
                        </a>
                        <a href="subscription_tiers.php" class="db-action a-green">
                            <div class="db-action-ico">💳</div>
                            <div class="db-action-ttl">Plans</div>
                            <div class="db-action-dsc">Pricing &amp; tiers</div>
                        </a>
                        <a href="earnings.php"          class="db-action a-teal">
                            <div class="db-action-ico">💰</div>
                            <div class="db-action-ttl">Earnings</div>
                            <div class="db-action-dsc">Revenue breakdown</div>
                        </a>
                        <a href="admin_orders.php"      class="db-action a-sky">
                            <div class="db-action-ico">🚚</div>
                            <div class="db-action-ttl">Orders</div>
                            <div class="db-action-dsc">Manage distributions</div>
                        </a>
                        <a href="vendor_support.php"    class="db-action a-red">
                            <div class="db-action-ico">🛡️</div>
                            <div class="db-action-ttl">Helpdesk</div>
                            <div class="db-action-dsc"><?php echo $open_tickets; ?> open ticket<?php echo $open_tickets != 1 ? 's' : ''; ?></div>
                        </a>
                        <a href="<?php echo DOL_URL_ROOT; ?>/custom/foodbankcrm/admin/setup.php" class="db-action a-rose">
                            <div class="db-action-ico">⚙️</div>
                            <div class="db-action-ttl">CRM Settings</div>
                            <div class="db-action-dsc">Paystack &amp; alerts</div>
                        </a>
                    </div>
                </div>

                <!-- System Summary -->
                <div>
                    <div class="db-card">
                        <div class="db-card-head"><h3>📊 System Summary</h3></div>
                        <div style="padding:4px 0;">
                        <?php foreach ($summary_rows as $sr) : ?>
                            <a href="<?php echo $sr[2]; ?>" style="display:flex;justify-content:space-between;align-items:center;padding:11px 20px;border-bottom:1px solid #f8fafc;text-decoration:none;transition:background .12s;">
                                <span style="font-size:13px;color:#334155;font-weight:500;"><?php echo $sr[0]; ?></span>
                                <span class="chip <?php echo $sr[3]; ?>"><?php echo $sr[1]; ?></span>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="db-card">
                        <div class="db-card-head"><h3>🔗 Admin Links</h3></div>
                        <div style="padding:4px 0;">
                        <?php
                        $links = [
                            ['📧 Email / SMTP',      DOL_URL_ROOT.'/admin/mails.php'],
                            ['👤 User Management',   'user_management.php'],
                            ['🏭 Warehouses',         'warehouses.php'],
                            ['📋 All Inventory',      'donations.php'],
                            ['📑 Reports',            'reports.php'],
                        ];
                        foreach ($links as $lk) :
                        ?>
                            <a href="<?php echo $lk[1]; ?>" style="display:block;padding:11px 20px;border-bottom:1px solid #f8fafc;text-decoration:none;font-size:13px;color:#334155;font-weight:500;transition:background .12s;">
                                <?php echo $lk[0]; ?>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div><!-- end overview -->

        <!-- ── PANEL: Needs Attention ── -->
        <div id="panel-attention" class="db-panel">
            <?php if ($needs_attention === 0) : ?>
            <div class="db-card">
                <div class="db-clear">
                    <div>✅</div>
                    <p>All caught up — nothing needs your attention right now.</p>
                </div>
            </div>
            <?php else : ?>

            <div class="db-attention-grid">

                <!-- Pending Inventory -->
                <div class="db-card">
                    <div class="db-card-head">
                        <h3>📦 Pending Inventory<?php if ($pending_inv > 0) echo '<span class="alert-dot"></span>'; ?></h3>
                        <a href="donations.php">View all →</a>
                    </div>
                    <?php if ($res_inv && $db->num_rows($res_inv) > 0) : ?>
                    <table class="db-table">
                        <thead><tr><th>Date</th><th>Product</th><th>Vendor</th><th>Qty</th><th></th></tr></thead>
                        <tbody>
                        <?php while ($row = $db->fetch_object($res_inv)) : ?>
                            <tr>
                                <td style="color:#94a3b8;white-space:nowrap;"><?php echo dol_print_date($db->jdate($row->date_donation), 'day'); ?></td>
                                <td><strong><?php echo dol_escape_htmltag($row->product_name); ?></strong></td>
                                <td style="color:#64748b;"><?php echo dol_escape_htmltag($row->vendor_name ?: '—'); ?></td>
                                <td><?php echo number_format($row->quantity).' '.dol_escape_htmltag($row->unit); ?></td>
                                <td><a href="view_donation.php?id=<?php echo $row->rowid; ?>" class="review-btn">Review</a></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div class="db-card-foot"><a href="donations.php?filter_status=Pending">View all pending →</a></div>
                    <?php else : ?>
                    <div class="db-empty"><div>✅</div><p>No pending inventory.</p></div>
                    <?php endif; ?>
                </div>

                <!-- Pending Vendor Approvals -->
                <div class="db-card">
                    <div class="db-card-head">
                        <h3>🏢 Vendor Approvals<?php if ($vendors_pending > 0) echo '<span class="alert-dot"></span>'; ?></h3>
                        <a href="vendors.php">View all →</a>
                    </div>
                    <?php if ($res_vend && $db->num_rows($res_vend) > 0) : ?>
                    <table class="db-table">
                        <thead><tr><th>Vendor</th><th>Category</th><th>Registered</th><th></th></tr></thead>
                        <tbody>
                        <?php while ($v = $db->fetch_object($res_vend)) : ?>
                            <tr>
                                <td><strong><?php echo dol_escape_htmltag($v->name); ?></strong></td>
                                <td><?php echo $v->category ? '<span class="chip chip-blue">'.dol_escape_htmltag($v->category).'</span>' : '—'; ?></td>
                                <td style="color:#94a3b8;"><?php echo dol_print_date($db->jdate($v->date_creation), 'day'); ?></td>
                                <td><a href="view_vendor.php?id=<?php echo $v->rowid; ?>" class="review-btn">Approve</a></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else : ?>
                    <div class="db-empty"><div>✅</div><p>No vendors awaiting approval.</p></div>
                    <?php endif; ?>
                </div>

            </div>

            <?php if ($open_tickets > 0) : ?>
            <div class="db-card">
                <div class="db-card-head">
                    <h3>🎫 Open Support Tickets<span class="alert-dot"></span></h3>
                    <a href="vendor_support.php">View all →</a>
                </div>
                <div style="padding:20px 22px;">
                    <p style="margin:0;font-size:14px;color:#334155;">There <?php echo $open_tickets == 1 ? 'is' : 'are'; ?> <strong><?php echo $open_tickets; ?> open ticket<?php echo $open_tickets != 1 ? 's' : ''; ?></strong> waiting for a reply.</p>
                    <a href="vendor_support.php" class="review-btn" style="display:inline-block;margin-top:12px;padding:8px 20px;">Open Helpdesk →</a>
                </div>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div><!-- end attention -->

        <!-- ── PANEL: Distributions ── -->
        <div id="panel-distributions" class="db-panel">
            <div class="db-card">
                <div class="db-card-head">
                    <h3>🚚 Recent Distributions</h3>
                    <a href="admin_orders.php">Manage all →</a>
                </div>
                <?php
                $chip_map = [
                    'Pending'    => 'chip-amber',
                    'Bundled'    => 'chip-blue',
                    'Picked Up'  => 'chip-blue',
                    'In Transit' => 'chip-amber',
                    'Delivered'  => 'chip-green',
                ];
                if ($res_dist && $db->num_rows($res_dist) > 0) :
                ?>
                <table class="db-table">
                    <thead>
                        <tr><th>Date</th><th>Ref</th><th>Subscriber</th><th>Amount</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php while ($d = $db->fetch_object($res_dist)) :
                        $chip = $chip_map[$d->status] ?? 'chip-slate';
                    ?>
                        <tr>
                            <td style="color:#94a3b8;white-space:nowrap;"><?php echo dol_print_date($db->jdate($d->datec), 'day'); ?></td>
                            <td><span class="chip chip-indigo"><?php echo dol_escape_htmltag($d->ref); ?></span></td>
                            <td><?php echo dol_escape_htmltag($d->firstname.' '.$d->lastname); ?></td>
                            <td style="font-weight:700;color:#0f172a;">₦<?php echo number_format($d->total_amount, 0); ?></td>
                            <td><span class="chip <?php echo $chip; ?>"><?php echo dol_escape_htmltag($d->status); ?></span></td>
                            <td><a href="view_distribution.php?id=<?php echo $d->rowid; ?>" class="review-btn">View</a></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="db-card-foot"><a href="admin_orders.php">View all distributions →</a></div>
                <?php else : ?>
                <div class="db-empty"><div>📭</div><p>No distributions yet.</p></div>
                <?php endif; ?>
            </div>
        </div><!-- end distributions -->

        <!-- ── PANEL: Subscribers ── -->
        <div id="panel-subscribers" class="db-panel">
            <div class="db-card">
                <div class="db-card-head">
                    <h3>👥 Recent Subscribers</h3>
                    <a href="beneficiaries.php">Manage all →</a>
                </div>
                <?php if ($res_subs && $db->num_rows($res_subs) > 0) : ?>
                <table class="db-table">
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Plan</th><th>Status</th><th>Registered</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php while ($s = $db->fetch_object($res_subs)) :
                        $status_chip = $s->subscription_status === 'Active' ? 'chip-green' : ($s->subscription_status === 'Expired' ? 'chip-red' : 'chip-amber');
                    ?>
                        <tr>
                            <td><strong><?php echo dol_escape_htmltag($s->firstname.' '.$s->lastname); ?></strong></td>
                            <td style="color:#64748b;"><?php echo dol_escape_htmltag($s->email); ?></td>
                            <td><?php echo $s->subscription_type ? '<span class="chip chip-purple">'.dol_escape_htmltag($s->subscription_type).'</span>' : '<span style="color:#94a3b8;">—</span>'; ?></td>
                            <td><span class="chip <?php echo $status_chip; ?>"><?php echo dol_escape_htmltag($s->subscription_status ?: 'Pending'); ?></span></td>
                            <td style="color:#94a3b8;white-space:nowrap;"><?php echo dol_print_date($db->jdate($s->datec), 'day'); ?></td>
                            <td><a href="view_beneficiary.php?id=<?php echo $s->rowid; ?>" class="review-btn">View</a></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="db-card-foot"><a href="beneficiaries.php">View all subscribers →</a></div>
                <?php else : ?>
                <div class="db-empty"><div>👤</div><p>No subscribers yet.</p></div>
                <?php endif; ?>
            </div>
        </div><!-- end subscribers -->

    </div><!-- end tab shell -->

</div><!-- end db-wrap -->

<script>
(function () {
    var tabs   = document.querySelectorAll('.db-tab');
    var panels = document.querySelectorAll('.db-panel');

    function activate(name) {
        tabs.forEach(function(t)   { t.classList.toggle('active', t.dataset.tab === name); });
        panels.forEach(function(p) { p.classList.toggle('active', p.id === 'panel-' + name); });
        history.replaceState(null, '', '?tab=' + name);
    }

    tabs.forEach(function(t) {
        t.addEventListener('click', function() { activate(t.dataset.tab); });
    });

    // Activate tab from URL param or stat card links
    var param = new URLSearchParams(window.location.search).get('tab');
    if (param && document.getElementById('panel-' + param)) {
        activate(param);
    }
})();
</script>

<?php llxFooter(); ?>
