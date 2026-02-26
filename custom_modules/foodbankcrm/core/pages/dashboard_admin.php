<?php
/**
 * ADMIN DASHBOARD - FoodbankCRM Design System
 * Role Color: Indigo (#4f46e5) | Layout: Dolibarr Sidebar Preserved
 */
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;
$langs->load("admin");

// Security Check
if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Access Denied: Administrative privileges required.');
}

llxHeader('', 'Admin Control Room');
?>
<style>
    :root {
        --accent:       #4f46e5;
        --accent-light: #e0e7ff;
        --accent-dark:  #3730a3;
        --surface:      #f8fafc;
        --radius:       12px;
        --shadow:       0 4px 12px rgba(0,0,0,0.06);
        --font:         "Segoe UI", Roboto, Arial, sans-serif;
    }

    /* Hide top bar only — sidebar stays visible */
    #id-top { display: none !important; }
    .side-nav, .side-nav-vert { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; background: var(--surface) !important; min-height: 100vh; }
    .fiche { max-width: 100% !important; margin: 0 !important; }

    /* Layout */
    .fb-container {
        width: 96%;
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
        font-family: var(--font);
    }

    /* Page Header */
    .fb-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 35px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e2e8f0;
    }
    .fb-header h1 { margin: 0; font-size: 26px; font-weight: 800; color: #1e293b; }
    .fb-header p  { margin: 4px 0 0; color: #64748b; font-size: 14px; }

    /* Logout Button */
    .fb-btn-logout {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: white;
        color: #dc2626;
        border: 1.5px solid #dc2626;
        padding: 9px 20px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.2s;
    }
    .fb-btn-logout:hover { background: #dc2626; color: white; }

    /* Stats Grid */
    .fb-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
    .fb-stat-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #94a3b8;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .fb-stat-value { font-size: 30px; font-weight: 800; color: #1e293b; line-height: 1; }
    .fb-stat-sub   { font-size: 12px; margin-top: 6px; color: #64748b; }
    .fb-badge-alert {
        background: #ef4444;
        color: white;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 10px;
    }

    /* Split Layout */
    .fb-split {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 28px;
        align-items: start;
    }
    @media (max-width: 1100px) { .fb-split { grid-template-columns: 1fr; } }

    /* Card Wrapper */
    .fb-card { background: white; border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid #f1f5f9; overflow: hidden; }
    .fb-card-header {
        padding: 18px 22px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .fb-card-footer { padding: 14px 22px; border-top: 1px solid #f1f5f9; text-align: center; }
    .fb-card-footer a { color: var(--accent); text-decoration: none; font-weight: 600; font-size: 13px; }
    .fb-card-empty  { padding: 50px; text-align: center; color: #94a3b8; }

    /* Table */
    .fb-table { width: 100%; border-collapse: collapse; }
    .fb-table th {
        text-align: left;
        padding: 13px 20px;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .fb-table td { padding: 13px 20px; border-bottom: 1px solid #f8fafc; color: #334155; font-size: 14px; }
    .fb-table tr:last-child td { border-bottom: none; }
    .fb-table tr:hover td { background: #f8fafc; }

    /* Review Action Link */
    .fb-btn-review {
        background: var(--accent-light);
        color: var(--accent);
        padding: 5px 13px;
        border-radius: 20px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 700;
    }

    /* Tool List */
    .fb-tool-list { display: flex; flex-direction: column; gap: 12px; padding: 16px; }
    .fb-tool-btn {
        display: flex;
        align-items: center;
        gap: 14px;
        background: #fafafa;
        border: 1px solid #e2e8f0;
        padding: 16px 18px;
        border-radius: 10px;
        text-decoration: none;
        color: #334155;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.18s;
    }
    .fb-tool-btn:hover {
        background: var(--accent-light);
        border-color: var(--accent);
        color: var(--accent);
        transform: translateX(4px);
    }
    .fb-tool-icon { font-size: 22px; width: 28px; text-align: center; flex-shrink: 0; }
    .fb-tool-desc  { font-size: 12px; color: #94a3b8; margin-top: 2px; font-weight: 400; }
</style>

<?php
// --- Business Logic ---

$obj_rev = $db->fetch_object($db->query("SELECT SUM(amount) as total FROM ".MAIN_DB_PREFIX."foodbank_payments WHERE payment_status = 'Success'"));
$revenue = $obj_rev->total ?? 0;

$obj_sub = $db->fetch_object($db->query("SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE subscription_status = 'Active'"));
$subscribers = $obj_sub->total ?? 0;

$obj_inv = $db->fetch_object($db->query("SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."foodbank_donations WHERE status = 'Pending'"));
$pending_inv = $obj_inv->total ?? 0;

$open_tickets = 0;
$res_check = $db->query("SHOW TABLES LIKE '".MAIN_DB_PREFIX."foodbank_support'");
if ($res_check && $db->num_rows($res_check) > 0) {
    $obj_tkt = $db->fetch_object($db->query("SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."foodbank_support WHERE status = 'Open'"));
    $open_tickets = $obj_tkt->total ?? 0;
}

print '<div class="fb-container">';

// HEADER
print '<div class="fb-header">';
print '<div>';
print '<h1>🚀 Admin Control Room</h1>';
print '<p>FoodbankCRM &bull; System Overview &amp; Management</p>';
print '</div>';
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="fb-btn-logout"><span>🚪</span> Logout</a>';
print '</div>';

// STATS GRID
print '<div class="fb-stats-grid">';

print '<div class="fb-stat-card" style="border-top-color:#10b981;">';
print '<div class="fb-stat-label">Total Revenue</div>';
print '<div class="fb-stat-value">₦'.number_format($revenue).'</div>';
print '<div class="fb-stat-sub" style="color:#10b981;">✔ Verified Payments</div>';
print '</div>';

print '<div class="fb-stat-card" style="border-top-color:#3b82f6;">';
print '<div class="fb-stat-label">Active Subscribers</div>';
print '<div class="fb-stat-value">'.number_format($subscribers).'</div>';
print '<div class="fb-stat-sub">👥 Current subscribers</div>';
print '</div>';

print '<a href="donations.php?status=Pending" class="fb-stat-card" style="border-top-color:#f59e0b;">';
print '<div class="fb-stat-label">Pending Stock '.($pending_inv > 0 ? '<span class="fb-badge-alert">'.$pending_inv.'</span>' : '').'</div>';
print '<div class="fb-stat-value">'.number_format($pending_inv).'</div>';
print '<div class="fb-stat-sub" style="color:#f59e0b;">Requires Approval →</div>';
print '</a>';

print '<a href="vendor_support.php" class="fb-stat-card" style="border-top-color:#ef4444;">';
print '<div class="fb-stat-label">Support Tickets '.($open_tickets > 0 ? '<span class="fb-badge-alert">'.$open_tickets.'</span>' : '').'</div>';
print '<div class="fb-stat-value">'.number_format($open_tickets).'</div>';
print '<div class="fb-stat-sub" style="color:#ef4444;">Needs Reply →</div>';
print '</a>';

print '</div>';

// SPLIT LAYOUT
print '<div class="fb-split">';

// LEFT: PENDING APPROVALS
print '<div>';
print '<div class="fb-card">';
print '<div class="fb-card-header">📋 Pending Inventory Approvals <span style="font-size:12px; color:#94a3b8; font-weight:400; margin-left:6px;">(Most Recent)</span></div>';

$sql_pending = "SELECT d.rowid, d.ref, d.product_name, d.quantity, d.unit, v.name as vendor_name, d.date_donation
                FROM ".MAIN_DB_PREFIX."foodbank_donations d
                LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON d.fk_vendor = v.rowid
                WHERE d.status = 'Pending'
                ORDER BY d.date_donation ASC LIMIT 6";
$res_pending = $db->query($sql_pending);

if ($res_pending && $db->num_rows($res_pending) > 0) {
    print '<table class="fb-table">';
    print '<thead><tr><th>Date</th><th>Ref</th><th>Vendor</th><th>Product</th><th>Qty</th><th style="text-align:right">Action</th></tr></thead>';
    print '<tbody>';
    while ($row = $db->fetch_object($res_pending)) {
        print '<tr>';
        print '<td style="color:#94a3b8;">'.dol_print_date($db->jdate($row->date_donation), 'day').'</td>';
        print '<td><strong>'.dol_escape_htmltag($row->ref).'</strong></td>';
        print '<td>'.dol_escape_htmltag($row->vendor_name).'</td>';
        print '<td>'.dol_escape_htmltag($row->product_name).'</td>';
        print '<td>'.number_format($row->quantity).' '.dol_escape_htmltag($row->unit).'</td>';
        print '<td style="text-align:right"><a href="view_donation.php?id='.$row->rowid.'" class="fb-btn-review">Review</a></td>';
        print '</tr>';
    }
    print '</tbody></table>';
    print '<div class="fb-card-footer"><a href="donations.php?status=Pending">View All Pending →</a></div>';
} else {
    print '<div class="fb-card-empty"><div style="font-size:36px; margin-bottom:10px;">✅</div>All caught up! No inventory pending approval.</div>';
}

print '</div>';
print '</div>';

// RIGHT: QUICK TOOLS
print '<div>';
print '<div class="fb-card">';
print '<div class="fb-card-header">⚡ Quick Tools</div>';
print '<div class="fb-tool-list">';

$tools = [
    ['icon' => '💳', 'title' => 'Manage Plans',   'desc' => 'Update subscription pricing',     'link' => 'subscription_tiers.php'],
    ['icon' => '👥', 'title' => 'Subscribers',     'desc' => 'View & manage beneficiaries',     'link' => 'beneficiaries.php'],
    ['icon' => '🏭', 'title' => 'Vendors',          'desc' => 'Approve & manage vendor accounts','link' => 'vendors.php'],
    ['icon' => '📦', 'title' => 'Log Inventory',   'desc' => 'Manually record new stock',       'link' => 'create_donation.php'],
    ['icon' => '🚚', 'title' => 'Distributions',   'desc' => 'Manage food dispatches',          'link' => 'distributions.php'],
    ['icon' => '🛡️', 'title' => 'Helpdesk',        'desc' => 'View & reply to support tickets', 'link' => 'vendor_support.php'],
];

foreach ($tools as $t) {
    print '<a href="'.$t['link'].'" class="fb-tool-btn">';
    print '<div class="fb-tool-icon">'.$t['icon'].'</div>';
    print '<div><div>'.$t['title'].'</div><div class="fb-tool-desc">'.$t['desc'].'</div></div>';
    print '</a>';
}

print '</div></div>';
print '</div>';

print '</div>'; // End split
print '</div>'; // End container

llxFooter();
?>
