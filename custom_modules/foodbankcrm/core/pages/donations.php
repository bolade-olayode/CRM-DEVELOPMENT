<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/donation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader('', 'Manage Donations');

// --- MODERN UI STYLES ---
print '<style>
    div#id-top, #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; }
    
    #mainmenutd_commercial, #mainmenutd_billing, #mainmenutd_compta, 
    #mainmenutd_projet, #mainmenutd_mrp, #mainmenutd_hrm, 
    #mainmenutd_ticket, #mainmenutd_agenda, #mainmenutd_documents, #mainmenutd_bank {
        display: none !important;
    }

    /* Layout */
    .fb-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
    
    /* Stats Cards */
    .stats-grid { display: flex; gap: 20px; margin-bottom: 30px; }
    .stat-card { flex: 1; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #667eea; }
    .stat-value { font-size: 28px; font-weight: 800; color: #333; margin-bottom: 5px; }
    .stat-label { color: #666; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    
    /* Filters */
    .filter-box { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid #eee; }
    
    /* Table */
    .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); padding: 0; overflow: hidden; border: 1px solid #eee; }
    .clean-table { width: 100%; border-collapse: collapse; }
    .clean-table th { text-align: left; padding: 15px 20px; background: #f8f9fa; color: #666; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #eee; }
    .clean-table td { padding: 15px 20px; border-bottom: 1px solid #f5f5f5; font-size: 14px; color: #444; vertical-align: middle; }
    .clean-table tr:hover { background: #fafafa; }
    
    /* Badges & Progress */
    .badge { padding: 5px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; color: white !important; display: inline-block; }
    .badge.green { background-color: #28a745 !important; }
    .badge.orange { background-color: #fd7e14 !important; }
    .badge.blue { background-color: #17a2b8 !important; }
    
    .progress-bg { background: #e9ecef; height: 8px; border-radius: 4px; overflow: hidden; width: 100px; display: inline-block; vertical-align: middle; margin-right: 8px; }
    .progress-bar { height: 100%; background: #667eea; }
    
    /* Actions */
    .action-link { font-size: 13px; color: #667eea; text-decoration: none; font-weight: 500; margin-left: 10px; }
    .action-link:hover { text-decoration: underline; }
    .action-link.delete { color: #dc3545; }
</style>';

// --- HANDLE INLINE STATUS UPDATE ---
if (isset($_POST['update_status'])) {
    if (isset($_POST['token']) && $_POST['token'] == $_SESSION['newtoken']) {
        $id = (int)$_POST['id'];
        $status = $_POST['status'];
        
        $donation = new DonationFB($db);
        if ($donation->fetch($id) > 0) {
            $donation->status = $status;
            if ($donation->update($user) > 0) {
                setEventMessages('Status updated successfully!', null, 'mesgs');
            } else {
                setEventMessages('Error: '.$donation->error, null, 'errors');
            }
        }
    }
}

// --- MAIN CONTENT ---
print '<div class="fb-container">';

// 1. Header
print '<div class="page-header">';
print '<div><h1 style="margin: 0;">🎁 Donations</h1><p style="color:#888; margin: 5px 0 0 0;">Track inbound inventory and approvals</p></div>';
print '<div>';
print '<a class="butAction" href="create_donation.php" style="padding: 10px 20px;">+ Create Donation</a>';
print '<a class="button" href="dashboard_admin.php" style="margin-left: 10px; background:#eee; color:#333;">Back to Dashboard</a>';
print '</div>';
print '</div>';

// 2. Filters & Logic
$filter_status = GETPOST('filter_status', 'alpha');
$filter_vendor = GETPOST('filter_vendor', 'int');

// Build Query
// Ensure we fetch product_name AND category
$sql = "SELECT d.rowid, d.ref, d.product_name, d.label, d.category, d.quantity, d.quantity_allocated, d.unit, 
               d.date_donation, d.status,
               (d.quantity - d.quantity_allocated) as available,
               v.name AS vendor_name
        FROM ".MAIN_DB_PREFIX."foodbank_donations AS d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors AS v ON v.rowid = d.fk_vendor
        WHERE 1=1";

if ($filter_status) $sql .= " AND d.status = '".$db->escape($filter_status)."'";
if ($filter_vendor) $sql .= " AND d.fk_vendor = ".(int)$filter_vendor;

$sql .= " ORDER BY d.date_donation DESC";
$res = $db->query($sql);

// 3. Calculate Stats
$total_donations = 0;
$stats_by_unit = array();
$donations_data = array();

while ($o = $db->fetch_object($res)) {
    $donations_data[] = $o;
    $total_donations++;
    $unit = $o->unit ?: 'unknown';
    
    if (!isset($stats_by_unit[$unit])) {
        $stats_by_unit[$unit] = array('total' => 0, 'allocated' => 0);
    }
    $stats_by_unit[$unit]['total'] += $o->quantity;
    $stats_by_unit[$unit]['allocated'] += $o->quantity_allocated;
}

// Stats Cards
print '<div class="stats-grid">';
print '<div class="stat-card" style="border-left-color: #4facfe;">';
print '<div class="stat-value">'.$total_donations.'</div><div class="stat-label">Total Donations</div>';
print '</div>';

// Calculate overall fill rate
$total_qty = 0; $total_alloc = 0;
foreach($stats_by_unit as $u) { $total_qty += $u['total']; $total_alloc += $u['allocated']; }
$fill_rate = ($total_qty > 0) ? round(($total_alloc / $total_qty) * 100) : 0;

print '<div class="stat-card" style="border-left-color: #43e97b;">';
print '<div class="stat-value">'.$fill_rate.'%</div><div class="stat-label">Inventory Allocated</div>';
print '</div>';

print '<div class="stat-card" style="border-left-color: #fa709a;">';
print '<div class="stat-value">'.count($stats_by_unit).'</div><div class="stat-label">Product Categories</div>';
print '</div>';
print '</div>';

// 4. Filter Form
print '<div class="filter-box">';
print '<form method="GET" action="'.basename(__FILE__).'" style="display: flex; gap: 15px; align-items: flex-end;">';

print '<div style="flex:1;"><label style="font-size:12px; color:#666; font-weight:bold;">Filter by Status</label>';
print '<select name="filter_status" class="flat" style="width:100%; margin-top:5px; padding:8px;">';
print '<option value="">All Statuses</option>';
print '<option value="Pending"'.($filter_status == 'Pending' ? ' selected' : '').'>⏳ Pending</option>';
print '<option value="Received"'.($filter_status == 'Received' ? ' selected' : '').'>✅ Received</option>';
print '<option value="Allocated"'.($filter_status == 'Allocated' ? ' selected' : '').'>📦 Allocated</option>';
print '</select></div>';

print '<div style="flex:1;"><label style="font-size:12px; color:#666; font-weight:bold;">Filter by Vendor</label>';
print '<select name="filter_vendor" class="flat" style="width:100%; margin-top:5px; padding:8px;">';
print '<option value="">All Vendors</option>';
$sql_v = "SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors ORDER BY name";
$res_v = $db->query($sql_v);
while ($v = $db->fetch_object($res_v)) {
    print '<option value="'.$v->rowid.'"'.($filter_vendor == $v->rowid ? ' selected' : '').'>'.dol_escape_htmltag($v->name).'</option>';
}
print '</select></div>';

print '<div><input type="submit" class="button" value="Apply Filters" style="padding: 10px 20px;"></div>';
if ($filter_status || $filter_vendor) {
    print '<div><a href="'.basename(__FILE__).'" class="button" style="background:#eee; color:#333; padding: 10px 20px;">Clear</a></div>';
}
print '</form></div>';

// 5. Data Table
print '<div class="fb-card">';
if (count($donations_data) > 0) {
    print '<table class="clean-table">';
    print '<thead><tr>
            <th>Ref</th>
            <th>Product</th>
            <th>Category</th>
            <th>Vendor</th>
            <th>Stock Status</th>
            <th>Status</th>
            <th style="text-align:right;">Actions</th>
           </tr></thead>';
    print '<tbody>';

    foreach ($donations_data as $row) {
        $vendor = $row->vendor_name ?: '<span style="color:#999">Unknown</span>';
        $date = dol_print_date($db->jdate($row->date_donation), 'day');
        
        // Use Product Name if available, else label
        $display_name = !empty($row->product_name) ? $row->product_name : $row->label;
        
        // Progress Calculation
        $percent = ($row->quantity > 0) ? round(($row->available / $row->quantity) * 100) : 0;
        $progress_color = ($percent < 20) ? '#dc3545' : '#28a745'; // Red if low, Green if high
        
        // Status Badge
        $s_label = $row->status;
        $s_class = 'gray';
        if ($s_label == 'Received') $s_class = 'green';
        if ($s_label == 'Pending') $s_class = 'orange';
        if ($s_label == 'Allocated') $s_class = 'blue';

        print '<tr>';
        print '<td><strong>'.$row->ref.'</strong></td>';
        print '<td>'.dol_escape_htmltag($display_name).'<br><small style="color:#888">'.number_format($row->quantity).' '.$row->unit.'</small></td>';
        print '<td>'.dol_escape_htmltag($row->category).'</td>';
        print '<td>'.$vendor.'</td>';
        
        // Stock Bar
        print '<td>';
        print '<div style="display:flex; align-items:center;">';
        print '<div class="progress-bg"><div class="progress-bar" style="width:'.$percent.'%; background:'.$progress_color.';"></div></div>';
        print '<span style="font-size:12px; color:#666;">'.number_format($row->available).' left</span>';
        print '</div>';
        print '</td>';
        
        // Inline Status Changer
        print '<td>';
        print '<form method="POST" action="'.basename(__FILE__).'" style="margin:0; display:flex; align-items:center gap:5px;">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="id" value="'.$row->rowid.'">';
        print '<select name="status" class="flat" onchange="this.form.submit()" style="font-size:12px; padding:4px; border-radius:4px; border:1px solid #ddd; background:#f8f9fa;">';
        print '<option value="Pending"'.($row->status=='Pending'?' selected':'').'>⏳ Pending</option>';
        print '<option value="Received"'.($row->status=='Received'?' selected':'').'>✅ Received</option>';
        print '<option value="Allocated"'.($row->status=='Allocated'?' selected':'').'>📦 Allocated</option>';
        print '</select>';
        print '<input type="hidden" name="update_status" value="1">';
        print '</form>';
        print '</td>';
        
        // Actions
        print '<td style="text-align: right;">';
        print '<a href="edit_donation.php?id='.$row->rowid.'" class="action-link">Edit</a>';
        print '<a href="delete_donation.php?id='.$row->rowid.'" class="action-link delete">Delete</a>';
        print '</td>';
        print '</tr>';
    }
    print '</tbody></table>';
} else {
    print '<div style="text-align: center; padding: 60px; color: #999;">';
    print '<div style="font-size: 40px; margin-bottom: 10px;">🎁</div>';
    print 'No donations found matching your filters.';
    print '</div>';
}
print '</div>'; // End Card

print '</div>'; // End Container

llxFooter();
?>