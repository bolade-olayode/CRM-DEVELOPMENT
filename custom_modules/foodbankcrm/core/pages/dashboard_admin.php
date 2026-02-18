<?php
/**
 * ADMIN DASHBOARD - FINAL (With Sidebar Visible)
 */
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

$langs->load("admin");

// 1. STRICT SECURITY CHECK
if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Access Denied: Administrative privileges required.');
}

llxHeader('', 'Admin Control Room');

// 2. CSS ADJUSTMENTS (Keeps Sidebar Visible)
echo '<style>
    /* HIDE TOP BAR ONLY (Optional - keeps it clean) */
    #id-top { display: none !important; }
    
    /* ADJUST SIDEBAR POSITION since top bar is gone */
    .side-nav { top: 0 !important; height: 100vh !important; }
    .side-nav-vert { top: 0 !important; }
    
    /* MAIN CONTENT AREA FIX */
    #id-right { 
        padding-top: 30px !important; 
        background-color: #f8f9fa !important;
        min-height: 100vh;
    }
    
    /* DASHBOARD CONTAINER */
    .admin-container { 
        width: 96%; 
        max-width: 1400px; 
        margin: 0 auto; 
        padding: 20px; 
        font-family: "Segoe UI", sans-serif; 
    }

    /* TOP STATS GRID */
    .stats-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
        gap: 20px; 
        margin-bottom: 40px; 
    }
    
    .stat-card { 
        background: white; 
        padding: 25px; 
        border-radius: 12px; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
        border: 1px solid #f0f0f0;
        transition: transform 0.2s, box-shadow 0.2s;
        text-decoration: none;
        display: flex; flex-direction: column; justify-content: center;
        height: 100%; box-sizing: border-box;
    }
    .stat-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 8px 20px rgba(0,0,0,0.1); 
        border-color: #667eea;
    }

    /* SPLIT LAYOUT */
    .dashboard-split {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
        align-items: start;
    }
    @media (max-width: 1100px) { .dashboard-split { grid-template-columns: 1fr; } }

    /* MODERN TABLE */
    .table-card { 
        background: white; 
        border-radius: 12px; 
        padding: 0; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
        border: 1px solid #f0f0f0;
        overflow: hidden;
    }
    .modern-table { width: 100%; border-collapse: collapse; }
    .modern-table th { 
        text-align: left; padding: 15px 20px; 
        color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
        background: #f8fafc; border-bottom: 1px solid #e2e8f0;
    }
    .modern-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; color: #333; font-size: 14px; }
    .modern-table tr:last-child td { border-bottom: none; }
    .modern-table tr:hover { background-color: #f8f9fa; }

    /* TOOLS & BUTTONS */
    .tool-list { display: flex; flex-direction: column; gap: 15px; }
    .btn-tool { 
        display: flex; align-items: center; gap: 15px;
        background: white; padding: 20px; border-radius: 12px; 
        text-decoration: none; color: #333; border: 1px solid #e2e8f0; 
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        transition: all 0.2s; 
    }
    .btn-tool:hover { 
        border-color: #667eea; color: #667eea; 
        transform: translateX(5px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
    }
    .tool-icon { font-size: 24px; width: 30px; text-align: center; }
    
    .btn-logout {
        background: #dc3545; color: white; border: none; 
        padding: 8px 20px; border-radius: 30px; text-decoration: none; 
        font-weight: bold; font-size: 13px; display: inline-flex; align-items: center; gap: 5px;
    }
    
    .section-title { font-size: 18px; font-weight: 700; color: #2c3e50; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .badge-count { background: #ef4444; color: white; font-size: 11px; padding: 2px 8px; border-radius: 10px; vertical-align: middle; }
</style>';

// --- 3. BUSINESS LOGIC (Fetch Real Data) ---

// A. Revenue
$sql_rev = "SELECT SUM(amount) as total FROM ".MAIN_DB_PREFIX."foodbank_payments WHERE payment_status = 'Success'";
$obj_rev = $db->fetch_object($db->query($sql_rev));
$revenue = $obj_rev->total ?? 0;

// B. Active Subscribers
$sql_sub = "SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE subscription_status = 'Active'";
$obj_sub = $db->fetch_object($db->query($sql_sub));
$subscribers = $obj_sub->total ?? 0;

// C. Pending Inventory
$sql_inv = "SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."foodbank_donations WHERE status = 'Pending'";
$obj_inv = $db->fetch_object($db->query($sql_inv));
$pending_inv = $obj_inv->total ?? 0;

// D. Open Support Tickets
$open_tickets = 0;
$res_check = $db->query("SHOW TABLES LIKE '".MAIN_DB_PREFIX."foodbank_support'");
if ($res_check && $db->num_rows($res_check) > 0) {
    $sql_tkt = "SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."foodbank_support WHERE status = 'Open'";
    $obj_tkt = $db->fetch_object($db->query($sql_tkt));
    $open_tickets = $obj_tkt->total ?? 0;
}

print '<div class="admin-container">';

// HEADER
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">';
print '<div>';
print '<h1 style="margin: 0; color: #1e293b; font-size: 28px;">🚀 Admin Control Room</h1>';
print '<p style="color: #64748b; margin: 5px 0 0 0;">System Overview & Management</p>';
print '</div>';
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="btn-logout"><span>🚪</span> Logout</a>';
print '</div>';

// STATS GRID
print '<div class="stats-grid">';

// 1. Revenue
print '<div class="stat-card" style="border-left: 4px solid #10b981;">';
print '<div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Total Revenue</div>';
print '<div style="font-size: 28px; font-weight: 800; color: #1e293b; margin: 5px 0;">₦'.number_format($revenue).'</div>';
print '<div style="font-size: 12px; color: #10b981;">✔ Verified Payments</div>';
print '</div>';

// 2. Subscribers
print '<div class="stat-card" style="border-left: 4px solid #3b82f6;">';
print '<div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Active Subscribers</div>';
print '<div style="font-size: 28px; font-weight: 800; color: #1e293b; margin: 5px 0;">'.number_format($subscribers).'</div>';
print '<div style="font-size: 12px; color: #3b82f6;">👥 Subscribers</div>';
print '</div>';

// 3. Inventory Action
print '<a href="donations.php?status=Pending" class="stat-card" style="border-left: 4px solid #f59e0b;">';
print '<div style="display:flex; justify-content:space-between;">';
print '<div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Pending Stock</div>';
if($pending_inv > 0) print '<span class="badge-count">'.$pending_inv.'</span>';
print '</div>';
print '<div style="font-size: 28px; font-weight: 800; color: #1e293b; margin: 5px 0;">'.number_format($pending_inv).'</div>';
print '<div style="font-size: 12px; color: #f59e0b;">Requires Approval →</div>';
print '</a>';

// 4. Support Tickets
print '<a href="vendor_support.php" class="stat-card" style="border-left: 4px solid #ef4444;">';
print '<div style="display:flex; justify-content:space-between;">';
print '<div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Support Tickets</div>';
if($open_tickets > 0) print '<span class="badge-count">'.$open_tickets.'</span>';
print '</div>';
print '<div style="font-size: 28px; font-weight: 800; color: #1e293b; margin: 5px 0;">'.number_format($open_tickets).'</div>';
print '<div style="font-size: 12px; color: #ef4444;">Needs Reply →</div>';
print '</a>';

print '</div>'; // End Stats Grid

// MAIN SPLIT SECTION
print '<div class="dashboard-split">';

// --- LEFT COLUMN: PENDING APPROVALS TABLE ---
print '<div>';
print '<div class="section-title">📋 Pending Approvals <span style="font-size:13px; color:#888; font-weight:400; margin-left:10px;">(Recent Inventory Logs)</span></div>';

print '<div class="table-card">';
$sql_pending = "SELECT d.rowid, d.ref, d.product_name, d.quantity, d.unit, v.name as vendor_name, d.date_donation 
                FROM ".MAIN_DB_PREFIX."foodbank_donations d
                LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON d.fk_vendor = v.rowid
                WHERE d.status = 'Pending'
                ORDER BY d.date_donation ASC LIMIT 5";
$res_pending = $db->query($sql_pending);

if ($res_pending && $db->num_rows($res_pending) > 0) {
    print '<table class="modern-table">';
    print '<thead><tr><th>Date</th><th>Ref</th><th>Vendor</th><th>Product</th><th>Qty</th><th style="text-align:right">Action</th></tr></thead>';
    print '<tbody>';
    while ($row = $db->fetch_object($res_pending)) {
        print '<tr>';
        print '<td style="color:#666;">'.dol_print_date($db->jdate($row->date_donation), 'day').'</td>';
        print '<td><strong>'.dol_escape_htmltag($row->ref).'</strong></td>';
        print '<td>'.dol_escape_htmltag($row->vendor_name).'</td>';
        print '<td>'.dol_escape_htmltag($row->product_name).'</td>';
        print '<td>'.number_format($row->quantity).' '.$row->unit.'</td>';
        print '<td style="text-align:right"><a href="view_donation.php?id='.$row->rowid.'" style="background:#e0e7ff; color:#4f46e5; padding:6px 12px; border-radius:20px; text-decoration:none; font-size:12px; font-weight:700;">Review</a></td>';
        print '</tr>';
    }
    print '</tbody></table>';
    print '<div style="padding:15px; text-align:center; border-top:1px solid #f1f5f9;">';
    print '<a href="donations.php?status=Pending" style="color:#667eea; text-decoration:none; font-weight:600; font-size:13px;">View All Pending →</a>';
    print '</div>';
} else {
    print '<div style="padding: 40px; text-align: center; color: #94a3b8;">';
    print '<div style="font-size:32px; margin-bottom:10px;">✅</div>';
    print 'All caught up! No inventory pending approval.';
    print '</div>';
}
print '</div>'; // End table card
print '</div>'; // End left column

// --- RIGHT COLUMN: QUICK TOOLS ---
print '<div>';
print '<div class="section-title">⚡ Quick Tools</div>';
print '<div class="tool-list">';

print '<a href="subscription_tiers.php" class="btn-tool">';
print '<div class="tool-icon">💳</div>';
print '<div><div class="tool-text">Manage Plans</div><div class="tool-desc">Update subscription pricing</div></div>';
print '</a>';

print '<a href="create_donation.php" class="btn-tool">';
print '<div class="tool-icon">📦</div>';
print '<div><div class="tool-text">Log Inventory</div><div class="tool-desc">Manually record stock</div></div>';
print '</a>';

print '<a href="donations.php" class="btn-tool">';
print '<div class="tool-icon">🏭</div>';
print '<div><div class="tool-text">Stock Overview</div><div class="tool-desc">See all warehouse items</div></div>';
print '</a>';

print '<a href="vendor_support.php" class="btn-tool">';
print '<div class="tool-icon">🛡️</div>';
print '<div><div class="tool-text">Helpdesk</div><div class="tool-desc">View & reply to tickets</div></div>';
print '</a>';

print '</div>'; // End tool list
print '</div>'; // End right column

print '</div>'; // End Split Layout
print '</div>'; // End Container

llxFooter();
?>