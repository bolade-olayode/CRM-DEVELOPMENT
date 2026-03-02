<?php
/**
 * My Supply History - ENTREPRENEUR FOCUSED
 */
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;

$langs->load("admin");

// 1. CHECK PERMISSIONS (Allow Admin OR Vendor)
$user_is_admin = FoodbankPermissions::isAdmin($user);
$user_is_vendor = FoodbankPermissions::isVendor($user, $db);

if (!$user_is_vendor && !$user_is_admin) {
    accessforbidden('You do not have access to this page.');
}

// 2. IDENTIFY THE VENDOR (If not admin)
$vendor_id = 0;
if ($user_is_vendor && !$user_is_admin) {
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
    $res = $db->query($sql);
    if ($res && $obj = $db->fetch_object($res)) {
        $vendor_id = $obj->rowid;
    } else {
        accessforbidden('Vendor profile not found linked to your user account.');
    }
}

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Supply History');

// --- MODERN CSS & RESET ---
print '<style>
    /* 1. HIDE CHROME */
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header { display: none !important; }
    
    /* 2. LAYOUT */
    html, body { background-color: #f8f9fa !important; margin: 0; width: 100%; overflow-x: hidden; }
    #id-right, .id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
    .fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }

    /* 3. CONTAINER */
    .vendor-container { width: 95%; max-width: 1400px; margin: 0 auto; padding: 40px 20px; font-family: "Segoe UI", sans-serif; }

    /* 4. BUTTONS */
    .btn-primary {
        background: #667eea; color: white; padding: 10px 20px; border-radius: 30px; 
        text-decoration: none; font-weight: bold; box-shadow: 0 4px 12px rgba(102,126,234,0.3);
        display: inline-block; font-size: 14px;
    }
    .btn-outline {
        background: white; color: #666; border: 1px solid #ddd; padding: 10px 20px; border-radius: 30px; 
        text-decoration: none; font-weight: bold; display: inline-block; font-size: 14px;
    }
    .btn-logout {
        background: white; color: #dc3545; border: 1px solid #dc3545; 
        padding: 8px 16px; border-radius: 30px; text-decoration: none; 
        font-weight: bold; font-size: 13px; display: inline-flex; align-items: center; gap: 5px;
    }
    .btn-logout:hover { background: #dc3545; color: white; }

    /* 5. STATS CARDS */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { color: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    
    /* 6. TABLE */
    .table-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow-x: auto; }
    .modern-table { width: 100%; border-collapse: collapse; min-width: 800px; }
    .modern-table th { text-align: left; padding: 15px; color: #888; border-bottom: 2px solid #eee; font-size: 13px; text-transform: uppercase; }
    .modern-table td { padding: 15px; border-bottom: 1px solid #f0f0f0; color: #333; }
    .modern-table tr:hover { background-color: #f9f9f9; }
    
    .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
    
    /* 7. FILTER BOX */
    .filter-box { background: white; padding: 15px 25px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 25px; display: inline-block; }
    .filter-select { padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; margin-left: 10px; }
</style>';

print '<div class="vendor-container">';

// --- HEADER ---
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<div>';
if ($user_is_admin) {
    print '<h1 style="margin: 0; color: #2c3e50; font-size: 28px;">📦 All Inventory Logs (Admin)</h1>';
    print '<p style="color: #666; margin: 5px 0 0 0;">Managing supply from all vendors</p>';
} else {
    print '<h1 style="margin: 0; color: #2c3e50; font-size: 28px;">📦 My Supply History</h1>';
    print '<p style="color: #666; margin: 5px 0 0 0;">Track your stock contributions</p>';
}
print '</div>';

// Actions
print '<div style="display: flex; gap: 10px; align-items: center;">';
if (!$user_is_admin) {
    print '<a href="create_donation.php" class="btn-primary">📦 Add Inventory</a>';
    print '<a href="dashboard_vendor.php" class="btn-outline">← Dashboard</a>';
} else {
    print '<a href="donations.php" class="btn-outline">← Admin List</a>';
}
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="btn-logout"><span>🚪</span> Logout</a>';
print '</div>';
print '</div>'; // End Header

// --- FILTER ---
$filter_status = GETPOST('status', 'alpha');
print '<div class="filter-box">';
print '<form method="GET" action="'.basename(__FILE__).'" style="display: flex; align-items: center;">';
print '<label style="font-weight: bold; color: #555;">Filter Status:</label>';
print '<select name="status" class="filter-select" onchange="this.form.submit()">';
print '<option value="">All Logs</option>';
print '<option value="Pending" '.($filter_status == 'Pending' ? 'selected' : '').'>⏳ Pending Review</option>';
print '<option value="Received" '.($filter_status == 'Received' ? 'selected' : '').'>✅ Accepted Stock</option>';
print '<option value="Rejected" '.($filter_status == 'Rejected' ? 'selected' : '').'>❌ Rejected</option>';
print '</select>';
print '</form>';
print '</div>';

// --- SQL QUERY ---
$sql = "SELECT d.*, w.label as warehouse_name, v.name as vendor_name
        FROM ".MAIN_DB_PREFIX."foodbank_donations d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_warehouses w ON d.fk_warehouse = w.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON d.fk_vendor = v.rowid
        WHERE 1=1"; 

if ($user_is_vendor && !$user_is_admin) {
    $sql .= " AND d.fk_vendor = ".(int)$vendor_id;
}
if ($filter_status) {
    $sql .= " AND d.status = '".$db->escape($filter_status)."'";
}
$sql .= " ORDER BY d.date_donation DESC";
$res = $db->query($sql);

// --- SUMMARY STATS ---
$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(quantity) as total_qty,
    SUM(CASE WHEN status = 'Received' THEN 1 ELSE 0 END) as received_count,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(quantity * unit_price) as total_value
    FROM ".MAIN_DB_PREFIX."foodbank_donations d
    WHERE 1=1";

if ($user_is_vendor && !$user_is_admin) {
    $sql_stats .= " AND d.fk_vendor = ".(int)$vendor_id;
}
$res_stats = $db->query($sql_stats);
$stats = $db->fetch_object($res_stats);

// --- STATS GRID ---
print '<div class="stats-grid">';
print '<div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">';
print '<div style="font-size: 13px; opacity: 0.9; margin-bottom: 5px;">Total Batches</div>';
print '<div style="font-size: 32px; font-weight: bold;">'.($stats->total ?? 0).'</div>';
print '</div>';

print '<div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">';
print '<div style="font-size: 13px; opacity: 0.9; margin-bottom: 5px;">Accepted Batches</div>';
print '<div style="font-size: 32px; font-weight: bold;">'.($stats->received_count ?? 0).'</div>';
print '</div>';

print '<div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">';
print '<div style="font-size: 13px; opacity: 0.9; margin-bottom: 5px;">Pending Review</div>';
print '<div style="font-size: 32px; font-weight: bold;">'.($stats->pending_count ?? 0).'</div>';
print '</div>';

print '<div class="stat-card" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">';
print '<div style="font-size: 13px; opacity: 0.9; margin-bottom: 5px;">Est. Supply Value</div>';
print '<div style="font-size: 32px; font-weight: bold;">₦'.number_format($stats->total_value ?? 0, 0).'</div>';
print '</div>';
print '</div>';

// --- DATA TABLE ---
print '<div class="table-card">';
print '<table class="modern-table">';
print '<thead>';
print '<tr>';
print '<th>Ref</th>';
if ($user_is_admin) print '<th>Vendor</th>';
print '<th>Product</th>';
print '<th style="text-align:center;">Qty</th>';
print '<th style="text-align:right;">Value</th>';
print '<th>Warehouse</th>';
print '<th>Date</th>';
print '<th style="text-align:center;">Status</th>';
print '<th style="text-align:center;">Action</th>';
print '</tr>';
print '</thead>';
print '<tbody>';

$status_styles = array(
    'Pending' => array('bg' => '#fff3e0', 'color' => '#f57c00', 'label' => 'Pending'),
    'Received' => array('bg' => '#e8f5e9', 'color' => '#2e7d32', 'label' => 'Accepted'),
    'Rejected' => array('bg' => '#ffebee', 'color' => '#d32f2f', 'label' => 'Rejected')
);

if ($res && $db->num_rows($res) > 0) {
    while ($row = $db->fetch_object($res)) {
        $st = $status_styles[$row->status] ?? array('bg' => '#eee', 'color' => '#666', 'label' => $row->status);
        $total_value = $row->quantity * $row->unit_price;
        
        print '<tr>';
        print '<td><strong>'.dol_escape_htmltag($row->ref).'</strong></td>';
        
        if ($user_is_admin) {
            print '<td style="color: #666;">'.dol_escape_htmltag($row->vendor_name).'</td>';
        }
        
        print '<td>'.dol_escape_htmltag($row->product_name).'</td>';
        print '<td style="text-align:center;">'.number_format($row->quantity, 0).' '.$row->unit.'</td>';
        print '<td style="text-align:right;">₦'.number_format($total_value, 2).'</td>';
        print '<td>'.dol_escape_htmltag($row->warehouse_name ?: '-').'</td>';
        print '<td>'.dol_print_date($db->jdate($row->date_donation), 'day').'</td>';
        print '<td style="text-align:center;"><span class="status-badge" style="background:'.$st['bg'].'; color:'.$st['color'].'">'.$st['label'].'</span></td>';
        print '<td style="text-align:center;"><a href="view_donation.php?id='.$row->rowid.'" style="color:#667eea; font-weight:bold; text-decoration:none;">Details</a></td>';
        print '</tr>';
    }
} else {
    print '<tr><td colspan="9" style="padding: 50px; text-align: center; color: #999;">No supply logs found.</td></tr>';
}

print '</tbody>';
print '</table>';
print '</div>'; // End Table Card

print '</div>'; // End Container

llxFooter();
?>