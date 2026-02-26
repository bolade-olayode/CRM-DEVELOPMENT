<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// Security check - Admin only
if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

llxHeader('', 'Manage Orders');

print '<h1>📦 Manage Orders</h1>';

// Filter and search
$filter_status = GETPOST('status', 'alpha');
$search_query = GETPOST('search', 'alpha');

print '<form method="GET" action="'.basename(__FILE__).'" style="margin-bottom: 20px;">';
print '<div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">';

print '<input type="text" name="search" class="flat" placeholder="Search by ref, subscriber..." value="'.dol_escape_htmltag($search_query).'" style="min-width: 250px;">';

print '<select name="status" class="flat" style="width: 150px;">';
print '<option value="">All Statuses</option>';
print '<option value="Pending" '.($filter_status == 'Pending' ? 'selected' : '').'>Pending</option>';
print '<option value="Bundled" '.($filter_status == 'Bundled' ? 'selected' : '').'>Bundled</option>';
print '<option value="Picked Up" '.($filter_status == 'Picked Up' ? 'selected' : '').'>Picked Up</option>';
print '<option value="In Transit" '.($filter_status == 'In Transit' ? 'selected' : '').'>In Transit</option>';
print '<option value="Delivered" '.($filter_status == 'Delivered' ? 'selected' : '').'>Delivered</option>';
print '</select>';

print '<button type="submit" class="button">Search</button>';
print '<a href="'.basename(__FILE__).'" class="button">Clear</a>';

print '</div>';
print '</form>';

// Build query
$sql = "SELECT d.*, 
        b.ref as subscriber_ref, b.firstname, b.lastname,
        COUNT(dl.rowid) as item_count
        FROM ".MAIN_DB_PREFIX."foodbank_distributions d
        INNER JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON d.fk_beneficiary = b.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_distribution_lines dl ON d.rowid = dl.fk_distribution
        WHERE 1=1";

if ($filter_status) {
    $sql .= " AND d.status = '".$db->escape($filter_status)."'";
}

if ($search_query) {
    $sql .= " AND (d.ref LIKE '%".$db->escape($search_query)."%' 
              OR b.firstname LIKE '%".$db->escape($search_query)."%' 
              OR b.lastname LIKE '%".$db->escape($search_query)."%')";
}

$sql .= " GROUP BY d.rowid ORDER BY d.datec DESC";

$res = $db->query($sql);

if (!$res || $db->num_rows($res) == 0) {
    print '<div style="text-align: center; padding: 60px; background: #f9f9f9; border-radius: 8px;">';
    print '<div style="font-size: 64px; margin-bottom: 20px;">📦</div>';
    print '<h2>No Orders Found</h2>';
    print '<p style="color: #666;">'.($filter_status || $search_query ? 'Try adjusting your filters.' : 'No orders have been placed yet.').'</p>';
    print '</div>';
    llxFooter();
    exit;
}

// Summary stats
$sql_summary = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status IN ('Bundled', 'Picked Up', 'In Transit') THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(total_amount) as total_revenue
    FROM ".MAIN_DB_PREFIX."foodbank_distributions";

$res_summary = $db->query($sql_summary);
$summary = $db->fetch_object($res_summary);

print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">';

print '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px;">';
print '<div style="font-size: 32px; font-weight: bold;">'.($summary->total ?? 0).'</div>';
print '<div style="font-size: 13px; opacity: 0.9;">Total Orders</div>';
print '</div>';

print '<div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 20px; border-radius: 8px;">';
print '<div style="font-size: 32px; font-weight: bold;">'.($summary->pending ?? 0).'</div>';
print '<div style="font-size: 13px; opacity: 0.9;">Pending</div>';
print '</div>';

print '<div style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; padding: 20px; border-radius: 8px;">';
print '<div style="font-size: 32px; font-weight: bold;">'.($summary->in_progress ?? 0).'</div>';
print '<div style="font-size: 13px; opacity: 0.9;">In Progress</div>';
print '</div>';

print '<div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 20px; border-radius: 8px;">';
print '<div style="font-size: 32px; font-weight: bold;">'.($summary->delivered ?? 0).'</div>';
print '<div style="font-size: 13px; opacity: 0.9;">Delivered</div>';
print '</div>';

print '<div style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; padding: 20px; border-radius: 8px;">';
print '<div style="font-size: 24px; font-weight: bold;">₦'.number_format($summary->total_revenue ?? 0, 0).'</div>';
print '<div style="font-size: 13px; opacity: 0.8;">Total Revenue</div>';
print '</div>';

print '</div>';

// Orders table
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>Order Ref</th>';
print '<th>Subscriber</th>';
print '<th>Date</th>';
print '<th class="center">Items</th>';
print '<th class="center">Amount</th>';
print '<th>Status</th>';
print '<th>Payment</th>';
print '<th class="center">Actions</th>';
print '</tr>';

while ($order = $db->fetch_object($res)) {
    // Status colors
    $status_colors = array(
        'Pending' => array('bg' => '#fff3e0', 'color' => '#f57c00', 'icon' => '⏳'),
        'Bundled' => array('bg' => '#e3f2fd', 'color' => '#1976d2', 'icon' => '📦'),
        'Picked Up' => array('bg' => '#e1f5fe', 'color' => '#0288d1', 'icon' => '🚚'),
        'In Transit' => array('bg' => '#fff9c4', 'color' => '#f57f17', 'icon' => '🚛'),
        'Delivered' => array('bg' => '#e8f5e9', 'color' => '#2e7d32', 'icon' => '✓')
    );
    $colors = $status_colors[$order->status] ?? array('bg' => '#f5f5f5', 'color' => '#666', 'icon' => '?');
    
    // Payment colors
    $payment_colors = array(
        'Paid' => array('bg' => '#e8f5e9', 'color' => '#2e7d32'),
        'Pending' => array('bg' => '#fff3e0', 'color' => '#f57c00'),
        'Pay_On_Delivery' => array('bg' => '#e3f2fd', 'color' => '#1976d2')
    );
    $pay_colors = $payment_colors[$order->payment_status] ?? array('bg' => '#f5f5f5', 'color' => '#666');
    
    print '<tr class="oddeven">';
    print '<td><strong>'.dol_escape_htmltag($order->ref).'</strong></td>';
    print '<td>';
    print dol_escape_htmltag($order->firstname.' '.$order->lastname).'<br>';
    print '<span style="font-size: 11px; color: #666;">'.dol_escape_htmltag($order->subscriber_ref).'</span>';
    print '</td>';
    print '<td>'.dol_print_date($db->jdate($order->datec), 'day').'</td>';
    print '<td class="center">'.$order->item_count.'</td>';
    print '<td class="center"><strong>₦'.number_format($order->total_amount, 2).'</strong></td>';
    print '<td>';
    print '<span style="display:inline-block; padding:4px 10px; border-radius:4px; background:'.$colors['bg'].'; color:'.$colors['color'].'; font-weight:bold; font-size:11px;">';
    print $colors['icon'].' '.dol_escape_htmltag($order->status);
    print '</span>';
    print '</td>';
    print '<td>';
    print '<span style="display:inline-block; padding:4px 8px; border-radius:3px; background:'.$pay_colors['bg'].'; color:'.$pay_colors['color'].'; font-size:10px; font-weight:bold;">';
    print str_replace('_', ' ', $order->payment_status);
    print '</span>';
    print '</td>';
    print '<td class="center">';
    print '<a href="view_distribution.php?id='.$order->rowid.'">View</a> | ';

    if ($order->status == 'Pending') {
        print '<a href="update_order_status.php?id='.$order->rowid.'&status=Prepared" style="color: #ffc107;">Mark Prepared</a>';
    } elseif ($order->status == 'Prepared') {
        print '<a href="update_order_status.php?id='.$order->rowid.'&status=Bundled" style="color: #1976d2;">Start Processing</a>';
    } elseif ($order->status == 'Bundled') {
        print '<a href="update_order_status.php?id='.$order->rowid.'&status=In Transit" style="color: #f57f17;">Mark In Transit</a>';
    } elseif ($order->status == 'In Transit') {
        print '<a href="update_order_status.php?id='.$order->rowid.'&status=Delivered" style="color: #2e7d32;">Mark Delivered</a>';
    }
    
    print '</td>';
    print '</tr>';
}

print '</table>';

llxFooter();
?>