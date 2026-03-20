<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/donation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Inventory Logs');
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

.fb-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
.fb-page-header h1 { margin: 0; font-size: 24px; font-weight: 800; color: #1e293b; }
.fb-page-header p  { margin: 4px 0 0; color: #64748b; font-size: 14px; }

.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: background .2s; }
.btn-primary:hover { background: var(--accent-dark); }
.btn-ghost  { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; }

/* Stats */
.stats-strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
.stat-tile { background: #fff; border-radius: var(--radius); padding: 20px 24px; box-shadow: var(--shadow); border-top: 4px solid var(--accent); }
.stat-tile.green { border-color: #10b981; }
.stat-tile.amber { border-color: #f59e0b; }
.stat-tile .val { font-size: 30px; font-weight: 800; color: #1e293b; line-height: 1; }
.stat-tile .lbl { font-size: 12px; color: #64748b; margin-top: 6px; text-transform: uppercase; letter-spacing: .5px; }

/* Filter bar */
.filter-bar { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); padding: 16px 20px; margin-bottom: 20px; display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
.filter-bar label { display: block; font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 6px; }
.filter-bar select, .filter-bar input[type=text] { border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; font-size: 13px; font-family: var(--font); outline: none; min-width: 160px; background: #f8fafc; color: #374151; }
.filter-bar select:focus, .filter-bar input:focus { border-color: var(--accent); background: #fff; }
.btn-filter { background: var(--accent); color: #fff !important; padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; border: none; cursor: pointer; font-family: var(--font); }
.btn-clear  { background: #f1f5f9; color: #475569 !important; padding: 9px 16px; border-radius: 8px; font-size: 13px; text-decoration: none; font-weight: 500; }
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

/* Stock progress */
.prog-wrap { display: flex; align-items: center; gap: 8px; }
.prog-bg   { width: 80px; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; }
.prog-fill { height: 100%; border-radius: 3px; }
.prog-fill.high { background: #10b981; }
.prog-fill.med  { background: #f59e0b; }
.prog-fill.low  { background: #ef4444; }
.prog-text { font-size: 12px; color: #64748b; white-space: nowrap; }

/* Inline status select */
.status-select { font-size: 12px; padding: 5px 8px; border-radius: 6px; border: 1px solid #e2e8f0; background: #f8fafc; font-family: var(--font); cursor: pointer; color: #374151; }

/* Actions */
.act-btn { display: inline-flex; align-items: center; gap: 4px; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; text-decoration: none; border: 1px solid #e2e8f0; color: #475569 !important; background: #fff; margin-left: 4px; transition: all .15s; }
.act-btn:hover { background: #f1f5f9; }
.act-btn.danger { color: #ef4444 !important; border-color: #fecaca; }
.act-btn.danger:hover { background: #fef2f2; }

.empty-state { text-align: center; padding: 70px 40px; color: #94a3b8; }
.empty-state h3 { font-size: 18px; color: #64748b; margin: 0 0 8px; }
</style>

<?php
// --- Handle inline status update ---
if (isset($_POST['update_status'])) {
    if (isset($_POST['token']) && $_POST['token'] == $_SESSION['newtoken']) {
        $id     = (int)$_POST['id'];
        $status = $_POST['status'];
        $donation = new DonationFB($db);
        if ($donation->fetch($id) > 0) {
            $donation->status = $status;
            if ($donation->update($user) > 0) {
                setEventMessages('Status updated.', null, 'mesgs');
            } else {
                setEventMessages('Update failed: '.$donation->error, null, 'errors');
            }
        }
    }
}

// --- Filters ---
$filter_status = GETPOST('filter_status', 'alpha');
$filter_vendor = GETPOST('filter_vendor', 'int');

// --- Main query ---
$sql = "SELECT d.rowid, d.ref, d.product_name, d.label, d.category, d.quantity,
               d.quantity_allocated, d.unit, d.datec, d.status,
               (d.quantity - d.quantity_allocated) as available,
               v.name AS vendor_name
        FROM ".MAIN_DB_PREFIX."foodbank_donations AS d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors AS v ON v.rowid = d.fk_vendor
        WHERE 1=1";
if ($filter_status) $sql .= " AND d.status = '".$db->escape($filter_status)."'";
if ($filter_vendor) $sql .= " AND d.fk_vendor = ".(int)$filter_vendor;
$sql .= " ORDER BY d.datec DESC";
$res = $db->query($sql);

$rows = array();
$total_qty = 0; $total_alloc = 0;
while ($o = $db->fetch_object($res)) {
    $rows[] = $o;
    $total_qty   += $o->quantity;
    $total_alloc += $o->quantity_allocated;
}
$total_donations = count($rows);
$fill_rate = $total_qty > 0 ? round(($total_alloc / $total_qty) * 100) : 0;
$available_total = $total_qty - $total_alloc;
?>

<div class="fb-wrap">

    <div class="fb-page-header">
        <div>
            <h1>📋 Inventory Logs</h1>
            <p>Track inbound stock and allocation status</p>
        </div>
        <div>
            <a href="dashboard_admin.php" class="btn-ghost">← Dashboard</a>
            <a href="create_donation.php" class="btn-primary">+ Log Inventory</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-strip">
        <div class="stat-tile">
            <div class="val"><?php echo $total_donations; ?></div>
            <div class="lbl">Total Log Entries</div>
        </div>
        <div class="stat-tile green">
            <div class="val"><?php echo number_format($available_total); ?></div>
            <div class="lbl">Units Available</div>
        </div>
        <div class="stat-tile amber">
            <div class="val"><?php echo $fill_rate; ?>%</div>
            <div class="lbl">Allocated Rate</div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?php echo basename(__FILE__); ?>">
    <div class="filter-bar">
        <div>
            <label>Status</label>
            <select name="filter_status" class="status-select" style="min-width:160px;">
                <option value="">All Statuses</option>
                <option value="Pending"  <?php if ($filter_status=='Pending')  echo 'selected'; ?>>⏳ Pending</option>
                <option value="Received" <?php if ($filter_status=='Received') echo 'selected'; ?>>✅ Received</option>
                <option value="Allocated"<?php if ($filter_status=='Allocated')echo 'selected'; ?>>📦 Allocated</option>
                <option value="Rejected" <?php if ($filter_status=='Rejected') echo 'selected'; ?>>✕ Rejected</option>
            </select>
        </div>
        <div>
            <label>Vendor</label>
            <select name="filter_vendor" class="status-select" style="min-width:180px;">
                <option value="">All Vendors</option>
                <?php
                $res_v = $db->query("SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors ORDER BY name");
                while ($v = $db->fetch_object($res_v)) {
                    echo '<option value="'.$v->rowid.'"'.($filter_vendor==$v->rowid?' selected':'').'>'.dol_escape_htmltag($v->name).'</option>';
                }
                ?>
            </select>
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end;padding-bottom:1px;">
            <button type="submit" class="btn-filter">Apply</button>
            <?php if ($filter_status || $filter_vendor) : ?>
            <a href="<?php echo basename(__FILE__); ?>" class="btn-clear">Clear</a>
            <?php endif; ?>
        </div>
    </div>
    </form>

    <!-- Table -->
    <div class="fb-card">
        <?php if (!empty($rows)) : ?>
        <table class="fb-table">
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Vendor</th>
                    <th>Available Stock</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row) :
                $display_name = !empty($row->product_name) ? $row->product_name : $row->label;
                $pct = $row->quantity > 0 ? round(($row->available / $row->quantity) * 100) : 0;
                $fill_class = $pct >= 50 ? 'high' : ($pct >= 20 ? 'med' : 'low');
            ?>
                <tr>
                    <td><span class="ref-chip"><?php echo dol_escape_htmltag($row->ref); ?></span></td>
                    <td>
                        <strong><?php echo dol_escape_htmltag($display_name); ?></strong><br>
                        <small style="color:#94a3b8;"><?php echo number_format($row->quantity); ?> <?php echo dol_escape_htmltag($row->unit); ?> total</small>
                    </td>
                    <td style="color:#64748b;"><?php echo dol_escape_htmltag($row->category) ?: '<span style="color:#cbd5e1">—</span>'; ?></td>
                    <td><?php echo $row->vendor_name ? dol_escape_htmltag($row->vendor_name) : '<span style="color:#cbd5e1">Unknown</span>'; ?></td>
                    <td>
                        <div class="prog-wrap">
                            <div class="prog-bg"><div class="prog-fill <?php echo $fill_class; ?>" style="width:<?php echo $pct; ?>%"></div></div>
                            <span class="prog-text"><?php echo number_format($row->available); ?> left</span>
                        </div>
                    </td>
                    <td>
                        <form method="POST" action="<?php echo basename(__FILE__); ?>" style="margin:0;">
                            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                            <input type="hidden" name="id" value="<?php echo $row->rowid; ?>">
                            <input type="hidden" name="update_status" value="1">
                            <select name="status" class="status-select" onchange="this.form.submit()">
                                <option value="Pending"  <?php if ($row->status=='Pending')  echo 'selected'; ?>>⏳ Pending</option>
                                <option value="Received" <?php if ($row->status=='Received') echo 'selected'; ?>>✅ Received</option>
                                <option value="Allocated"<?php if ($row->status=='Allocated')echo 'selected'; ?>>📦 Allocated</option>
                                <option value="Rejected" <?php if ($row->status=='Rejected') echo 'selected'; ?>>✕ Rejected</option>
                            </select>
                        </form>
                    </td>
                    <td style="text-align:right; white-space:nowrap;">
                        <a href="view_donation.php?id=<?php echo $row->rowid; ?>" class="act-btn">View</a>
                        <a href="edit_donation.php?id=<?php echo $row->rowid; ?>" class="act-btn">✏️</a>
                        <a href="delete_donation.php?id=<?php echo $row->rowid; ?>" class="act-btn danger"
                           onclick="return confirm('Delete this entry?')">🗑️</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <div class="empty-state">
            <h3>No inventory entries<?php echo ($filter_status || $filter_vendor) ? ' matching filters' : ' yet'; ?></h3>
            <?php if (!$filter_status && !$filter_vendor) : ?>
            <a href="create_donation.php" class="btn-primary" style="margin-top:8px;">+ Log Inventory</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php
llxFooter();
$db->close();
