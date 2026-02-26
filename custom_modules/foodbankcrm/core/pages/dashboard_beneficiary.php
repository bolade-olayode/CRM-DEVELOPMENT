<?php
/**
 * SUBSCRIBER DASHBOARD - FoodbankCRM Design System
 * Role Color: Teal (#0d9488) | Layout: Fullscreen with Sticky Top Nav
 */
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;
$langs->load("admin");

// Security Check
if (!FoodbankPermissions::isBeneficiary($user, $db)) {
    accessforbidden('Access Denied: You are not a registered beneficiary.');
}

// Fetch Subscriber Profile
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);
if (!$res || $db->num_rows($res) == 0) {
    accessforbidden('Profile not found. Please contact support.');
}
$subscriber = $db->fetch_object($res);

llxHeader('', 'My Dashboard');
?>
<style>
    :root {
        --accent:       #0d9488;
        --accent-light: #ccfbf1;
        --accent-dark:  #0f766e;
        --surface:      #f0fdfa;
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
        box-shadow: 0 2px 12px rgba(13,148,136,0.25);
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

    /* Main Container */
    .fb-container {
        width: 95%;
        max-width: none;
        margin: 0 auto;
        padding: 40px 20px;
    }

    /* Page Header */
    .fb-header { margin-bottom: 28px; }
    .fb-header h1 { margin: 0; font-size: 28px; font-weight: 800; color: #1e293b; }
    .fb-header p  { margin: 5px 0 0; color: #64748b; font-size: 14px; }

    /* Subscription Banner */
    .fb-sub-banner {
        background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
        color: white;
        padding: 26px 30px;
        border-radius: var(--radius);
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(13,148,136,0.3);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    .fb-sub-label { opacity: 0.8; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; font-weight: 700; }
    .fb-sub-value { font-size: 24px; font-weight: 800; }
    .fb-sub-status { font-size: 14px; font-weight: 700; background: rgba(255,255,255,0.2); padding: 6px 18px; border-radius: 30px; }
    .fb-sub-link {
        color: white;
        text-decoration: none;
        background: rgba(0,0,0,0.2);
        padding: 10px 22px;
        border-radius: 30px;
        font-weight: 700;
        font-size: 14px;
        transition: background 0.2s;
    }
    .fb-sub-link:hover { background: rgba(0,0,0,0.35); }

    /* Stats Grid */
    .fb-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 35px;
    }
    @media (max-width: 900px) { .fb-stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 480px) { .fb-stats-grid { grid-template-columns: 1fr; } }
    .fb-stat-card {
        background: white;
        padding: 24px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        border: 1px solid #f1f5f9;
        border-top: 4px solid var(--accent);
        text-align: center;
        transition: transform 0.2s;
    }
    .fb-stat-card:hover { transform: translateY(-3px); }
    .fb-stat-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #94a3b8; margin-bottom: 10px; }
    .fb-stat-value { font-size: 32px; font-weight: 800; color: #1e293b; }

    /* Section Title */
    .fb-section-title { font-size: 17px; font-weight: 700; color: #1e293b; margin: 0 0 18px; }

    /* Action Cards */
    .fb-action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
    }
    .fb-action-card {
        background: white;
        padding: 32px 22px;
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
        box-shadow: 0 8px 24px rgba(13,148,136,0.12);
    }
    .fb-action-icon  { font-size: 44px; margin-bottom: 14px; }
    .fb-action-title { font-size: 17px; font-weight: 700; margin-bottom: 4px; }
    .fb-action-desc  { font-size: 13px; color: #94a3b8; }
</style>

<?php
// Stats
$stats = $db->fetch_object($db->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status='Delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN status IN ('Prepared','In Transit') THEN 1 ELSE 0 END) as active,
    SUM(total_amount) as spent
    FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE fk_beneficiary=".$subscriber->rowid));

// --- STICKY TOP NAV ---
print '<div class="fb-topnav">';
print '<a href="dashboard_beneficiary.php" class="fb-topnav-brand">🥦 FoodbankCRM</a>';
print '<div class="fb-topnav-links">';
print '<a href="product_catalog.php" class="fb-topnav-link">Catalog</a>';
print '<a href="view_cart.php"       class="fb-topnav-link">My Cart</a>';
print '<a href="my_orders.php"       class="fb-topnav-link">Orders</a>';
print '<a href="my_profile.php"      class="fb-topnav-link">Profile</a>';
print '</div>';
print '<div class="fb-topnav-user">';
print '<span class="fb-topnav-name">'.dol_escape_htmltag($subscriber->firstname).'</span>';
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="fb-btn-logout">🚪 Logout</a>';
print '</div>';
print '</div>';

// --- MAIN CONTENT ---
print '<div class="fb-container">';

print '<div class="fb-header">';
print '<h1>👋 Welcome, '.dol_escape_htmltag($subscriber->firstname).'!</h1>';
print '<p>Subscriber ID: '.dol_escape_htmltag($subscriber->ref).'</p>';
print '</div>';

// Subscription Banner
print '<div class="fb-sub-banner">';
print '<div>';
print '<div class="fb-sub-label">Current Plan</div>';
print '<div class="fb-sub-value">'.dol_escape_htmltag($subscriber->subscription_type ?: 'Guest').'</div>';
print '</div>';
print '<div>';
print '<div class="fb-sub-label">Status</div>';
print '<div class="fb-sub-status">'.dol_escape_htmltag($subscriber->subscription_status ?: 'Active').'</div>';
print '</div>';
print '<a href="renew_subscription.php" class="fb-sub-link">Manage Plan →</a>';
print '</div>';

// Stats Grid
print '<div class="fb-stats-grid">';

print '<div class="fb-stat-card" style="border-top-color:#667eea;">';
print '<div class="fb-stat-label">Total Orders</div>';
print '<div class="fb-stat-value">'.($stats->total ?: 0).'</div>';
print '</div>';

print '<div class="fb-stat-card" style="border-top-color:#f59e0b;">';
print '<div class="fb-stat-label">Active</div>';
print '<div class="fb-stat-value">'.($stats->active ?: 0).'</div>';
print '</div>';

print '<div class="fb-stat-card" style="border-top-color:#10b981;">';
print '<div class="fb-stat-label">Delivered</div>';
print '<div class="fb-stat-value">'.($stats->delivered ?: 0).'</div>';
print '</div>';

print '<div class="fb-stat-card" style="border-top-color:#8b5cf6;">';
print '<div class="fb-stat-label">Total Spent</div>';
print '<div class="fb-stat-value" style="font-size:24px;">₦'.number_format($stats->spent ?: 0).'</div>';
print '</div>';

print '</div>';

// Quick Action Cards
print '<div class="fb-section-title">🛍️ Quick Actions</div>';
print '<div class="fb-action-grid">';
print '<a href="product_catalog.php" class="fb-action-card"><div class="fb-action-icon">🛍️</div><div class="fb-action-title">Browse Packages</div><div class="fb-action-desc">View available food boxes</div></a>';
print '<a href="view_cart.php"       class="fb-action-card"><div class="fb-action-icon">🛒</div><div class="fb-action-title">My Cart</div><div class="fb-action-desc">Manage your selection</div></a>';
print '<a href="my_orders.php"       class="fb-action-card"><div class="fb-action-icon">📦</div><div class="fb-action-title">My Orders</div><div class="fb-action-desc">Track order history</div></a>';
print '<a href="my_profile.php"      class="fb-action-card"><div class="fb-action-icon">👤</div><div class="fb-action-title">My Profile</div><div class="fb-action-desc">Update your info</div></a>';
print '</div>';

print '</div>'; // End container
llxFooter();
?>
