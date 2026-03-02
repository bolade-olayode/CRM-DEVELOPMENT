<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: warehouses.php'); exit;
}
$wh_id = (int)$_GET['id'];

// Fetch warehouse
$res = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."foodbank_warehouses WHERE rowid = ".$wh_id);
$wh  = $res ? $db->fetch_object($res) : null;
if (!$wh) {
    header('Location: warehouses.php'); exit;
}

// Live inventory: donations stored at this warehouse that still have stock
$sql_inv = "SELECT
                d.rowid,
                d.ref,
                d.product_name,
                d.category,
                d.unit,
                d.quantity,
                d.quantity_allocated,
                (d.quantity - COALESCE(d.quantity_allocated, 0)) AS qty_available,
                d.status,
                d.date_donation,
                v.company_name AS vendor_name
            FROM ".MAIN_DB_PREFIX."foodbank_donations d
            LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON v.rowid = d.fk_vendor
            WHERE d.fk_warehouse = ".$wh_id."
            ORDER BY d.date_donation ASC";

$res_inv = $db->query($sql_inv);
$inventory = array();
if ($res_inv) {
    while ($row = $db->fetch_object($res_inv)) {
        $inventory[] = $row;
    }
}

// Summary stats
$total_units    = 0;
$total_avail    = 0;
$total_alloc    = 0;
$product_groups = array();  // group by product_name for the summary

foreach ($inventory as $item) {
    $total_units += (int)$item->quantity;
    $total_avail += (int)$item->qty_available;
    $total_alloc += (int)$item->quantity_allocated;
    $pname = $item->product_name ?: 'Unknown';
    if (!isset($product_groups[$pname])) {
        $product_groups[$pname] = array('total' => 0, 'avail' => 0, 'unit' => $item->unit);
    }
    $product_groups[$pname]['total'] += (int)$item->quantity;
    $product_groups[$pname]['avail'] += (int)$item->qty_available;
}

$capacity   = max(1, (int)$wh->capacity);
$fill_pct   = min(100, round(($total_units / $capacity) * 100));
$fill_color = $fill_pct >= 90 ? '#ef4444' : ($fill_pct >= 70 ? '#f59e0b' : '#4f46e5');

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Warehouse: '.dol_escape_htmltag($wh->label));
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

.fb-wrap { max-width: 1200px; margin: 0 auto; padding: 24px 28px; font-family: var(--font); }
.fb-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
.fb-page-header h1 { margin: 0; font-size: 24px; font-weight: 800; color: #1e293b; }
.fb-page-header p  { margin: 4px 0 0; color: #64748b; font-size: 14px; }

.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none !important; border: none; cursor: pointer; transition: background .2s; }
.btn-primary:hover { background: var(--accent-dark); text-decoration: none !important; }
.btn-ghost  { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none !important; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; text-decoration: none !important; }

/* Info + capacity row */
.top-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }

.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); }
.fb-card-head { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; }
.fb-card-head h2 { margin: 0; font-size: 15px; font-weight: 700; color: #1e293b; }
.fb-card-body { padding: 20px 24px; }

/* Info grid */
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.info-item .lbl { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .6px; color: #94a3b8; margin-bottom: 4px; }
.info-item .val { font-size: 15px; font-weight: 600; color: #1e293b; }

/* Capacity gauge */
.gauge-wrap { text-align: center; }
.gauge-ring-wrap { position: relative; display: inline-block; }
.gauge-label { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; }
.gauge-label .pct { font-size: 26px; font-weight: 800; color: #1e293b; line-height: 1; }
.gauge-label .sub { font-size: 11px; color: #64748b; margin-top: 2px; }
.gauge-stats { display: flex; justify-content: space-around; margin-top: 16px; }
.gauge-stat .g-val { font-size: 18px; font-weight: 800; color: #1e293b; }
.gauge-stat .g-lbl { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }

/* Product summary */
.summary-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; margin-bottom: 24px; }
.product-tile { background: #fff; border-radius: 10px; box-shadow: var(--shadow); padding: 16px 20px; border-left: 4px solid var(--accent); }
.product-tile .p-name { font-weight: 700; font-size: 14px; color: #1e293b; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.product-tile .p-avail { font-size: 22px; font-weight: 800; color: var(--accent); line-height: 1; }
.product-tile .p-total { font-size: 12px; color: #94a3b8; margin-top: 3px; }

/* Inventory table */
.fb-table-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
.fb-table-head { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.fb-table-head h2 { margin: 0; font-size: 15px; font-weight: 700; color: #1e293b; }
.fb-table { width: 100%; border-collapse: collapse; }
.fb-table thead th { text-align: left; padding: 12px 20px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
.fb-table tbody td { padding: 13px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #374151; vertical-align: middle; }
.fb-table tbody tr:last-child td { border-bottom: none; }
.fb-table tbody tr:hover { background: #fafbff; }

/* Status badge */
.badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
.badge-active   { background: #dcfce7; color: #15803d; }
.badge-pending  { background: #fef3c7; color: #92400e; }
.badge-depleted { background: #fee2e2; color: #991b1b; }
.badge-default  { background: #f1f5f9; color: #475569; }

/* Avail bar */
.avail-bar-wrap { display: flex; align-items: center; gap: 8px; }
.avail-bar { flex: 1; max-width: 80px; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; }
.avail-fill { height: 100%; background: var(--accent); border-radius: 3px; }

/* Empty */
.empty-state { text-align: center; padding: 60px 40px; color: #94a3b8; }
.empty-state h3 { font-size: 16px; color: #64748b; margin: 0 0 6px; }
.empty-state p  { margin: 0 0 20px; font-size: 14px; }
</style>

<div class="fb-wrap">

    <!-- Header -->
    <div class="fb-page-header">
        <div>
            <h1>🏭 <?php echo dol_escape_htmltag($wh->label); ?></h1>
            <p>Ref: <strong><?php echo dol_escape_htmltag($wh->ref); ?></strong>
               &nbsp;·&nbsp; Live inventory view</p>
        </div>
        <div>
            <a href="warehouses.php" class="btn-ghost">← Back</a>
            <a href="edit_warehouse.php?id=<?php echo $wh_id; ?>" class="btn-primary">✏️ Edit Warehouse</a>
        </div>
    </div>

    <!-- Info + Capacity -->
    <div class="top-row">

        <!-- Warehouse Info -->
        <div class="fb-card">
            <div class="fb-card-head"><h2>Warehouse Details</h2></div>
            <div class="fb-card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="lbl">Reference</div>
                        <div class="val"><?php echo dol_escape_htmltag($wh->ref); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="lbl">Name</div>
                        <div class="val"><?php echo dol_escape_htmltag($wh->label); ?></div>
                    </div>
                    <div class="info-item" style="grid-column: span 2;">
                        <div class="lbl">Address</div>
                        <div class="val" style="font-weight:400; color:#374151;">
                            <?php echo $wh->address ? nl2br(dol_escape_htmltag($wh->address)) : '<span style="color:#94a3b8">Not specified</span>'; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="lbl">Total Capacity</div>
                        <div class="val"><?php echo number_format((int)$wh->capacity); ?> units</div>
                    </div>
                    <div class="info-item">
                        <div class="lbl">Donation Lines</div>
                        <div class="val"><?php echo count($inventory); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Capacity Gauge -->
        <div class="fb-card">
            <div class="fb-card-head"><h2>Capacity Usage</h2></div>
            <div class="fb-card-body">
                <div class="gauge-wrap">
                    <div class="gauge-ring-wrap">
                        <?php
                        $r = 54; $cx = 64; $cy = 64;
                        $circum = 2 * M_PI * $r;
                        $dash   = round(($fill_pct / 100) * $circum, 2);
                        $gap    = round($circum - $dash, 2);
                        ?>
                        <svg width="128" height="128" viewBox="0 0 128 128">
                            <circle cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="<?php echo $r; ?>"
                                    fill="none" stroke="#e2e8f0" stroke-width="12"/>
                            <circle cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="<?php echo $r; ?>"
                                    fill="none" stroke="<?php echo $fill_color; ?>" stroke-width="12"
                                    stroke-dasharray="<?php echo $dash.' '.$gap; ?>"
                                    stroke-dashoffset="<?php echo round($circum / 4, 2); ?>"
                                    stroke-linecap="round"/>
                        </svg>
                        <div class="gauge-label">
                            <div class="pct"><?php echo $fill_pct; ?>%</div>
                            <div class="sub">in use</div>
                        </div>
                    </div>
                    <div class="gauge-stats">
                        <div class="gauge-stat">
                            <div class="g-val"><?php echo number_format($total_units); ?></div>
                            <div class="g-lbl">Total Units</div>
                        </div>
                        <div class="gauge-stat">
                            <div class="g-val" style="color:#22c55e;"><?php echo number_format($total_avail); ?></div>
                            <div class="g-lbl">Available</div>
                        </div>
                        <div class="gauge-stat">
                            <div class="g-val" style="color:#f59e0b;"><?php echo number_format($total_alloc); ?></div>
                            <div class="g-lbl">Allocated</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /top-row -->

    <!-- Product Summary Tiles -->
    <?php if (!empty($product_groups)) : ?>
    <div class="summary-grid">
        <?php foreach ($product_groups as $pname => $pg) : ?>
        <div class="product-tile">
            <div class="p-name" title="<?php echo dol_escape_htmltag($pname); ?>"><?php echo dol_escape_htmltag($pname); ?></div>
            <div class="p-avail"><?php echo number_format($pg['avail']); ?> <span style="font-size:14px;font-weight:500;color:#94a3b8;"><?php echo dol_escape_htmltag($pg['unit']); ?></span></div>
            <div class="p-total"><?php echo number_format($pg['total']); ?> total · <?php echo number_format($pg['total'] - $pg['avail']); ?> allocated</div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Full Inventory Table -->
    <div class="fb-table-card">
        <div class="fb-table-head">
            <h2>Inventory Lines (<?php echo count($inventory); ?>)</h2>
            <a href="create_donation.php" class="btn-primary" style="padding:8px 16px; font-size:13px;">+ Add Donation</a>
        </div>

        <?php if (!empty($inventory)) : ?>
        <table class="fb-table">
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Vendor</th>
                    <th>Total Qty</th>
                    <th>Available</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($inventory as $item) :
                $avail_pct = $item->quantity > 0 ? min(100, round(($item->qty_available / $item->quantity) * 100)) : 0;
                $status    = $item->status ?: 'Active';
                $badge_cls = 'badge-default';
                if ($status === 'Active' || $status === 'Approved') $badge_cls = 'badge-active';
                elseif ($status === 'Pending') $badge_cls = 'badge-pending';
                elseif ($status === 'Depleted' || $item->qty_available <= 0) $badge_cls = 'badge-depleted';
            ?>
                <tr>
                    <td><span style="background:var(--accent-light);color:var(--accent-dark);padding:2px 8px;border-radius:20px;font-size:12px;font-weight:600;"><?php echo dol_escape_htmltag($item->ref); ?></span></td>
                    <td><strong><?php echo dol_escape_htmltag($item->product_name); ?></strong></td>
                    <td style="color:#64748b;"><?php echo dol_escape_htmltag($item->category) ?: '—'; ?></td>
                    <td style="color:#64748b;"><?php echo dol_escape_htmltag($item->vendor_name) ?: '—'; ?></td>
                    <td><?php echo number_format((int)$item->quantity); ?> <?php echo dol_escape_htmltag($item->unit); ?></td>
                    <td>
                        <div class="avail-bar-wrap">
                            <div class="avail-bar"><div class="avail-fill" style="width:<?php echo $avail_pct; ?>%"></div></div>
                            <span style="font-weight:600;font-size:13px;"><?php echo number_format((int)$item->qty_available); ?></span>
                        </div>
                    </td>
                    <td><span class="badge <?php echo $badge_cls; ?>"><?php echo dol_escape_htmltag($status); ?></span></td>
                    <td style="color:#64748b; font-size:13px;"><?php echo $item->date_donation ? dol_print_date($db->jdate($item->date_donation), 'day') : '—'; ?></td>
                    <td style="text-align:right;">
                        <a href="view_donation.php?id=<?php echo $item->rowid; ?>" style="font-size:12px;color:var(--accent);text-decoration:none;font-weight:500;">View →</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php else : ?>
        <div class="empty-state">
            <h3>No inventory yet</h3>
            <p>This warehouse has no donations linked to it.<br>Add a donation and select this warehouse to see stock here.</p>
            <a href="create_donation.php" class="btn-primary">+ Add Donation to this Warehouse</a>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php llxFooter(); ?>
