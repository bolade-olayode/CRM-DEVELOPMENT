<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

// --- FILTERS ---
$filter_status = GETPOST('status', 'alpha');
$search_query  = GETPOST('search', 'alpha');

// --- SUMMARY STATS (always) ---
$sql_summary = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending'   THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status IN ('Bundled','Picked Up','In Transit') THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered,
    COALESCE(SUM(total_amount), 0) as total_revenue
    FROM ".MAIN_DB_PREFIX."foodbank_distributions";
$res_sum = $db->query($sql_summary);
$summary = $res_sum ? $db->fetch_object($res_sum) : null;

// --- MAIN QUERY ---
$sql = "SELECT d.*,
        b.ref AS subscriber_ref, b.firstname, b.lastname,
        COUNT(dl.rowid) AS item_count
        FROM ".MAIN_DB_PREFIX."foodbank_distributions d
        INNER JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON d.fk_beneficiary = b.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_distribution_lines dl ON d.rowid = dl.fk_distribution
        WHERE 1=1";

if ($filter_status) $sql .= " AND d.status = '".$db->escape($filter_status)."'";
if ($search_query)  $sql .= " AND (d.ref LIKE '%".$db->escape($search_query)."%'
                               OR b.firstname LIKE '%".$db->escape($search_query)."%'
                               OR b.lastname  LIKE '%".$db->escape($search_query)."%')";

$sql .= " GROUP BY d.rowid ORDER BY d.datec DESC";
$res = $db->query($sql);

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Distributions');
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
#id-top { display: none !important; }
.side-nav, .side-nav-vert { top: 0 !important; height: 100vh !important; }
#id-right { padding-top: 30px !important; background: var(--surface) !important; min-height: 100vh; }
.fiche { max-width: 100% !important; margin: 0 !important; }

.fb-wrap { max-width: 1400px; margin: 0 auto; padding: 24px 28px; font-family: var(--font); }

/* Page header */
.fb-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
.fb-page-header h1 { margin: 0; font-size: 24px; font-weight: 800; color: #1e293b; }
.fb-page-header p  { margin: 4px 0 0; color: #64748b; font-size: 14px; }

/* Buttons */
.btn-ghost { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none; border: 1px solid #e2e8f0; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; }

/* Stats */
.stats-strip { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px; }
.stat-tile { background: #fff; border-radius: var(--radius); padding: 18px 22px; box-shadow: var(--shadow); border-top: 4px solid var(--accent); }
.stat-tile.amber  { border-color: #f59e0b; }
.stat-tile.blue   { border-color: #3b82f6; }
.stat-tile.green  { border-color: #10b981; }
.stat-tile.purple { border-color: #8b5cf6; }
.stat-tile .val { font-size: 26px; font-weight: 800; color: #1e293b; line-height: 1; }
.stat-tile .lbl { font-size: 11px; color: #64748b; margin-top: 5px; text-transform: uppercase; letter-spacing: .5px; }

/* Filter bar */
.filter-bar { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); padding: 16px 20px; margin-bottom: 20px; display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
.filter-bar label { display: block; font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 6px; }
.filter-bar input, .filter-bar select { border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; font-size: 13px; font-family: var(--font); outline: none; background: #f8fafc; color: #374151; }
.filter-bar input  { min-width: 220px; }
.filter-bar select { min-width: 160px; }
.filter-bar input:focus, .filter-bar select:focus { border-color: var(--accent); background: #fff; }
.btn-filter { background: var(--accent); color: #fff !important; padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; border: none; cursor: pointer; font-family: var(--font); }
.btn-clear  { background: #f1f5f9; color: #475569 !important; padding: 9px 14px; border-radius: 8px; font-size: 13px; text-decoration: none; font-weight: 500; }
.btn-clear:hover { background: #e2e8f0; }

/* Table */
.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
.fb-table { width: 100%; border-collapse: collapse; }
.fb-table thead th { text-align: left; padding: 13px 18px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
.fb-table tbody td { padding: 13px 18px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #374151; vertical-align: middle; }
.fb-table tbody tr:last-child td { border-bottom: none; }
.fb-table tbody tr:hover { background: #fafbff; }

/* Ref chip */
.ref-chip { background: var(--accent-light); color: var(--accent-dark); padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }

/* Subscriber cell */
.sub-name  { font-weight: 600; color: #1e293b; font-size: 13px; }
.sub-ref   { font-size: 11px; color: #94a3b8; margin-top: 1px; }

/* Status badges */
.status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.s-pending   { background: #fef3c7; color: #92400e; }
.s-bundled   { background: #dbeafe; color: #1e40af; }
.s-pickedup  { background: #e0f2fe; color: #075985; }
.s-transit   { background: #fef9c3; color: #854d0e; }
.s-delivered { background: #dcfce7; color: #15803d; }
.s-default   { background: #f1f5f9; color: #475569; }

/* Payment badge */
.pay-badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
.pay-paid   { background: #dcfce7; color: #15803d; }
.pay-pend   { background: #fef3c7; color: #92400e; }
.pay-pod    { background: #dbeafe; color: #1e40af; }

/* Action links */
.act-btn { display: inline-flex; align-items: center; gap: 4px; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; text-decoration: none; border: 1px solid #e2e8f0; color: #475569 !important; background: #fff; margin-left: 4px; transition: all .15s; }
.act-btn:hover { background: #f1f5f9; }
.act-btn.advance { color: var(--accent) !important; border-color: var(--accent-light); background: var(--accent-light); }
.act-btn.advance:hover { background: #c7d2fe; }

.empty-state { text-align: center; padding: 70px 40px; color: #94a3b8; }
.empty-state h3 { font-size: 18px; color: #64748b; margin: 0 0 8px; }
</style>

<div class="fb-wrap">

    <!-- Page Header -->
    <div class="fb-page-header">
        <div>
            <h1>📦 Distributions</h1>
            <p>Track and manage all food box orders</p>
        </div>
        <div>
            <a href="dashboard_admin.php" class="btn-ghost">← Dashboard</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-strip">
        <div class="stat-tile">
            <div class="val"><?php echo $summary->total ?? 0; ?></div>
            <div class="lbl">Total Orders</div>
        </div>
        <div class="stat-tile amber">
            <div class="val"><?php echo $summary->pending ?? 0; ?></div>
            <div class="lbl">Pending</div>
        </div>
        <div class="stat-tile blue">
            <div class="val"><?php echo $summary->in_progress ?? 0; ?></div>
            <div class="lbl">In Progress</div>
        </div>
        <div class="stat-tile green">
            <div class="val"><?php echo $summary->delivered ?? 0; ?></div>
            <div class="lbl">Delivered</div>
        </div>
        <div class="stat-tile purple">
            <div class="val">₦<?php echo number_format($summary->total_revenue ?? 0, 0); ?></div>
            <div class="lbl">Total Revenue</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="<?php echo basename(__FILE__); ?>">
    <div class="filter-bar">
        <div>
            <label>Search</label>
            <input type="text" name="search" placeholder="Order ref or subscriber name..." value="<?php echo dol_escape_htmltag($search_query); ?>">
        </div>
        <div>
            <label>Status</label>
            <select name="status">
                <option value="">All Statuses</option>
                <?php foreach (['Pending','Bundled','Picked Up','In Transit','Delivered'] as $st) : ?>
                <option value="<?php echo $st; ?>" <?php echo $filter_status == $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex; gap:8px; align-items:flex-end; padding-bottom:1px;">
            <button type="submit" class="btn-filter">Apply</button>
            <?php if ($filter_status || $search_query) : ?>
            <a href="<?php echo basename(__FILE__); ?>" class="btn-clear">Clear</a>
            <?php endif; ?>
        </div>
    </div>
    </form>

    <!-- Table -->
    <div class="fb-card">
    <?php if ($res && $db->num_rows($res) > 0) : ?>
    <table class="fb-table">
        <thead>
            <tr>
                <th>Order Ref</th>
                <th>Subscriber</th>
                <th>Date</th>
                <th style="text-align:center;">Items</th>
                <th style="text-align:center;">Amount</th>
                <th>Status</th>
                <th>Payment</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $status_class = [
            'Pending'   => 's-pending',
            'Bundled'   => 's-bundled',
            'Picked Up' => 's-pickedup',
            'In Transit'=> 's-transit',
            'Delivered' => 's-delivered',
        ];
        $status_icon = [
            'Pending'   => '⏳',
            'Bundled'   => '📦',
            'Picked Up' => '🚚',
            'In Transit'=> '🚛',
            'Delivered' => '✓',
        ];
        $pay_class = [
            'Paid'            => 'pay-paid',
            'Pending'         => 'pay-pend',
            'Pay_On_Delivery' => 'pay-pod',
        ];

        while ($order = $db->fetch_object($res)) :
            $sc = $status_class[$order->status] ?? 's-default';
            $si = $status_icon[$order->status]  ?? '?';
            $pc = $pay_class[$order->payment_status] ?? 'pay-pend';
        ?>
        <tr>
            <td><span class="ref-chip"><?php echo dol_escape_htmltag($order->ref); ?></span></td>
            <td>
                <div class="sub-name"><?php echo dol_escape_htmltag($order->firstname.' '.$order->lastname); ?></div>
                <div class="sub-ref"><?php echo dol_escape_htmltag($order->subscriber_ref); ?></div>
            </td>
            <td><?php echo dol_print_date($db->jdate($order->datec), 'day'); ?></td>
            <td style="text-align:center;"><?php echo (int)$order->item_count; ?></td>
            <td style="text-align:center;"><strong>₦<?php echo number_format($order->total_amount, 2); ?></strong></td>
            <td><span class="status-badge <?php echo $sc; ?>"><?php echo $si; ?> <?php echo dol_escape_htmltag($order->status); ?></span></td>
            <td><span class="pay-badge <?php echo $pc; ?>"><?php echo str_replace('_', ' ', $order->payment_status); ?></span></td>
            <td style="text-align:right; white-space:nowrap;">
                <a href="view_distribution.php?id=<?php echo $order->rowid; ?>" class="act-btn">View</a>
                <?php if ($order->status == 'Pending') : ?>
                <a href="update_order_status.php?id=<?php echo $order->rowid; ?>&status=Bundled" class="act-btn advance">→ Bundle</a>
                <?php elseif ($order->status == 'Bundled') : ?>
                <a href="update_order_status.php?id=<?php echo $order->rowid; ?>&status=In Transit" class="act-btn advance">→ Ship</a>
                <?php elseif ($order->status == 'In Transit') : ?>
                <a href="update_order_status.php?id=<?php echo $order->rowid; ?>&status=Delivered" class="act-btn advance">→ Deliver</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php else : ?>
    <div class="empty-state">
        <h3>No orders found<?php echo ($filter_status || $search_query) ? ' matching filters' : ' yet'; ?></h3>
        <?php if ($filter_status || $search_query) : ?>
        <p style="font-size:13px; margin:0;"><a href="<?php echo basename(__FILE__); ?>" style="color:var(--accent);">Clear filters</a></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    </div>

</div>

<?php
llxFooter();
?>
