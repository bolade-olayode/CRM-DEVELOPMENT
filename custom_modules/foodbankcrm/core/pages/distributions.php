<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");

// --- QUICK ACTIONS (status changes) ---
$notice = '';
if (isset($_POST['action']) && isset($_POST['id']) && !empty($_SESSION['newtoken']) && $_POST['token'] == $_SESSION['newtoken']) {
    $dist = new Distribution($db);
    $dist->fetch((int)$_POST['id']);
    $valid_statuses = ['Prepared', 'In Transit', 'Delivered'];
    if ($_POST['action'] == 'ship')    $dist->status = 'In Transit';
    if ($_POST['action'] == 'deliver') $dist->status = 'Delivered';
    if ($_POST['action'] == 'set_status' && in_array($_POST['new_status'], $valid_statuses)) {
        $dist->status = $_POST['new_status'];
    }
    if ($dist->update($user) > 0) {
        $notice = ['success', 'Distribution status updated to <strong>'.$dist->status.'</strong>.'];
    } else {
        $notice = ['error', 'Failed to update status.'];
    }
}

// Active tab filter
$filter = GETPOST('filter', 'alpha');
$allowed_filters = ['Prepared', 'In Transit', 'Delivered'];
if (!in_array($filter, $allowed_filters)) $filter = '';

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Logistics Center');

// --- CSS ---
print '<style>
    div#id-top, #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; }
    .fb-container { max-width: 1600px; margin: 0 auto; padding: 0 20px; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 20px; }
    .stat-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #667eea; }
    .stat-value { font-size: 24px; font-weight: 800; color: #333; margin-bottom: 5px; }
    .stat-label { color: #666; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Status Tabs */
    .status-tabs { display: flex; gap: 0; margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; }
    .status-tab { padding: 10px 22px; font-size: 13px; font-weight: 600; color: #64748b; text-decoration: none; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: all .15s; }
    .status-tab:hover { color: #334155; border-bottom-color: #cbd5e1; }
    .status-tab.active { color: #4f46e5; border-bottom-color: #4f46e5; }
    .status-tab .tab-count { display: inline-block; background: #e2e8f0; color: #475569; border-radius: 20px; padding: 1px 8px; font-size: 11px; margin-left: 6px; }
    .status-tab.active .tab-count { background: #e0e7ff; color: #4f46e5; }

    /* Notice */
    .fb-notice { padding: 13px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
    .fb-notice.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .fb-notice.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    .clean-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .clean-table th { text-align: left; padding: 15px 20px; background: #f8f9fa; color: #666; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #eee; }
    .clean-table td { padding: 14px 20px; border-bottom: 1px solid #f5f5f5; font-size: 14px; color: #444; vertical-align: middle; }
    .clean-table tr:hover { background: #fafafa; }
    .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; }
    .status-prepared { background: #fff3cd; color: #856404; }
    .status-transit { background: #cce5ff; color: #004085; }
    .status-delivered { background: #d4edda; color: #155724; }
    .action-link { font-weight: 500; text-decoration: none; margin-left: 8px; font-size: 13px; }
    .action-link.delete { color: #dc3545; }

    /* Status change select */
    .status-select { font-size: 12px; padding: 4px 8px; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; background: #fff; }
    .btn-set-status { font-size: 12px; padding: 5px 10px; background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer; margin-left: 4px; }
    .btn-set-status:hover { background: #3730a3; }
</style>';

// --- STATS ---
$sql_stats = "SELECT status, COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_distributions GROUP BY status";
$res_stats = $db->query($sql_stats);
$stats = ['Prepared'=>0, 'In Transit'=>0, 'Delivered'=>0];
while ($o = $db->fetch_object($res_stats)) { $stats[$o->status] = (int)$o->c; }
$total_dist = array_sum($stats);

print '<div class="fb-container">';

// Header
print '<div class="page-header">';
print '<div><h1 style="margin: 0;">🚚 Distribution Center</h1><p style="color:#888; margin: 5px 0 0 0;">Manage deliveries and logistics</p></div>';
print '<div><a class="butAction" href="create_distribution.php" style="padding: 10px 20px;">+ Create Shipment</a><a class="button" href="dashboard_admin.php" style="margin-left: 10px; background:#eee; color:#333;">Dashboard</a></div>';
print '</div>';

// Notice
if ($notice) {
    print '<div class="fb-notice '.$notice[0].'">'.$notice[1].'</div>';
}

// Stats
print '<div class="stats-grid">';
print '<div class="stat-card" style="border-left-color:#ffc107;"><div class="stat-value">'.$stats['Prepared'].'</div><div class="stat-label">⏳ To Be Dispatched</div></div>';
print '<div class="stat-card" style="border-left-color:#007bff;"><div class="stat-value">'.$stats['In Transit'].'</div><div class="stat-label">🚚 Out for Delivery</div></div>';
print '<div class="stat-card" style="border-left-color:#28a745;"><div class="stat-value">'.$stats['Delivered'].'</div><div class="stat-label">✅ Delivered</div></div>';
print '<div class="stat-card" style="border-left-color:#6c757d;"><div class="stat-value">'.$total_dist.'</div><div class="stat-label">📦 Total Shipments</div></div>';
print '</div>';

// Status Tabs
$tab_all   = $filter === '' ? ' active' : '';
$tab_prep  = $filter === 'Prepared' ? ' active' : '';
$tab_trans = $filter === 'In Transit' ? ' active' : '';
$tab_deliv = $filter === 'Delivered' ? ' active' : '';

print '<div class="status-tabs">';
print '<a href="distributions.php" class="status-tab'.$tab_all.'">All <span class="tab-count">'.$total_dist.'</span></a>';
print '<a href="distributions.php?filter=Prepared" class="status-tab'.$tab_prep.'">To Dispatch <span class="tab-count">'.$stats['Prepared'].'</span></a>';
print '<a href="distributions.php?filter=In+Transit" class="status-tab'.$tab_trans.'">In Transit <span class="tab-count">'.$stats['In Transit'].'</span></a>';
print '<a href="distributions.php?filter=Delivered" class="status-tab'.$tab_deliv.'">Delivered <span class="tab-count">'.$stats['Delivered'].'</span></a>';
print '</div>';

// Main Query (with optional filter)
$where = $filter ? "WHERE d.status = '".$db->escape($filter)."'" : '';
$sql = "SELECT d.*, b.firstname, b.lastname, b.address,
               w.label as warehouse_name, p.name as package_name,
               (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines WHERE fk_distribution = d.rowid) as item_count
        FROM ".MAIN_DB_PREFIX."foodbank_distributions d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON d.fk_beneficiary = b.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_warehouses w ON d.fk_warehouse = w.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_packages p ON d.fk_package = p.rowid
        ".$where."
        ORDER BY FIELD(d.status, 'Prepared', 'In Transit', 'Delivered'), d.date_distribution DESC";
$res = $db->query($sql);

if ($res && $db->num_rows($res) > 0) {
    print '<table class="clean-table"><thead><tr>';
    print '<th>Ref</th><th>Beneficiary</th><th>Package</th><th>Warehouse</th><th>Date</th><th>Payment</th><th>Status</th><th style="text-align:right">Actions</th>';
    print '</tr></thead><tbody>';

    while ($obj = $db->fetch_object($res)) {
        $s_class  = ($obj->status == 'In Transit') ? 'status-transit' : (($obj->status == 'Delivered') ? 'status-delivered' : 'status-prepared');
        $pay_icon = ($obj->payment_status == 'Paid') ? '🟢' : '🔴';

        print '<tr>';
        print '<td><strong>'.$obj->ref.'</strong></td>';
        print '<td><strong>'.dol_escape_htmltag($obj->firstname.' '.$obj->lastname).'</strong><br><span style="color:#888; font-size:11px;">'.dol_trunc($obj->address, 30).'</span></td>';
        print '<td>'.dol_escape_htmltag($obj->package_name ?: 'Custom Order').'<br><small style="color:#667eea">'.$obj->item_count.' Items</small></td>';
        print '<td>'.dol_escape_htmltag($obj->warehouse_name).'</td>';
        print '<td>'.dol_print_date($db->jdate($obj->date_distribution), 'day').'</td>';
        print '<td><span style="font-size:11px; font-weight:bold;">'.$pay_icon.' '.dol_escape_htmltag($obj->payment_status).'</span><br><small>₦'.number_format($obj->total_amount, 2).'</small></td>';
        print '<td><span class="status-badge '.$s_class.'">'.dol_escape_htmltag($obj->status).'</span></td>';

        print '<td style="text-align:right; white-space:nowrap;">';

        // Quick-step buttons (Dispatch / Confirm)
        print '<form method="POST" action="distributions.php'.($filter ? '?filter='.urlencode($filter) : '').'" style="display:inline;">';
        print '<input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="id" value="'.$obj->rowid.'">';
        if ($obj->status == 'Prepared') {
            print '<button name="action" value="ship" class="button small" style="background:#007bff; color:white; border:none; margin-right:4px;">Dispatch</button>';
        } elseif ($obj->status == 'In Transit') {
            print '<button name="action" value="deliver" class="button small" style="background:#28a745; color:white; border:none; margin-right:4px;">Confirm Delivery</button>';
        }

        // Manual status override dropdown (for all statuses)
        print '<select name="new_status" class="status-select" onchange="">';
        print '<option value="">Set status...</option>';
        foreach (['Prepared', 'In Transit', 'Delivered'] as $st) {
            $sel = ($obj->status == $st) ? ' selected' : '';
            print '<option value="'.dol_escape_htmltag($st).'"'.$sel.'>'.dol_escape_htmltag($st).'</option>';
        }
        print '</select>';
        print '<button type="submit" name="action" value="set_status" class="btn-set-status">Set</button>';
        print '</form>';

        print ' <a href="view_distribution.php?id='.$obj->rowid.'" class="action-link" style="color:#667eea;">View</a>';
        print ' <a href="edit_distribution.php?id='.$obj->rowid.'" class="action-link" style="color:#666;">Edit</a>';
        print ' <a href="delete_distribution.php?id='.$obj->rowid.'" class="action-link delete">Delete</a>';
        print '</td></tr>';
    }
    print '</tbody></table>';
} else {
    $label = $filter ? '"'.$filter.'"' : '';
    print '<div style="text-align: center; padding: 60px; color: #999; background:#fff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">No '.$label.' distributions found.</div>';
}

print '</div>';
llxFooter();
?>
