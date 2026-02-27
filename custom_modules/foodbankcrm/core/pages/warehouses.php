<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/warehouse.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader('', 'Warehouses');
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

.fb-wrap { max-width: 1300px; margin: 0 auto; padding: 24px 28px; font-family: var(--font); }

/* Page header */
.fb-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
.fb-page-header h1 { margin: 0; font-size: 24px; font-weight: 800; color: #1e293b; }
.fb-page-header p  { margin: 4px 0 0; color: #64748b; font-size: 14px; }

/* Buttons */
.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; border: none; cursor: pointer; transition: background .2s; }
.btn-primary:hover { background: var(--accent-dark); }
.btn-ghost  { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; }

/* Stats strip */
.stats-strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
.stat-tile { background: #fff; border-radius: var(--radius); padding: 20px 24px; box-shadow: var(--shadow); border-top: 4px solid var(--accent); }
.stat-tile .val { font-size: 30px; font-weight: 800; color: #1e293b; line-height: 1; }
.stat-tile .lbl { font-size: 12px; color: #64748b; margin-top: 6px; text-transform: uppercase; letter-spacing: .5px; }

/* Card table */
.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
.fb-table { width: 100%; border-collapse: collapse; }
.fb-table thead th { text-align: left; padding: 14px 20px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
.fb-table tbody td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #374151; vertical-align: middle; }
.fb-table tbody tr:last-child td { border-bottom: none; }
.fb-table tbody tr:hover { background: #fafbff; }

/* Capacity bar */
.cap-bar-wrap { display: flex; align-items: center; gap: 10px; }
.cap-bar { flex: 1; max-width: 100px; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; }
.cap-fill { height: 100%; background: var(--accent); border-radius: 3px; }
.cap-text { font-size: 13px; color: #374151; font-weight: 600; white-space: nowrap; }

/* Actions */
.act-btn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; text-decoration: none; border: 1px solid #e2e8f0; color: #475569 !important; background: #fff; margin-left: 6px; transition: all .15s; }
.act-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
.act-btn.danger { color: #ef4444 !important; border-color: #fecaca; }
.act-btn.danger:hover { background: #fef2f2; }

/* Ref chip */
.ref-chip { background: var(--accent-light); color: var(--accent-dark); padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }

/* Empty state */
.empty-state { text-align: center; padding: 70px 40px; color: #94a3b8; }
.empty-state svg { margin-bottom: 16px; opacity: .4; }
.empty-state h3 { font-size: 18px; color: #64748b; margin: 0 0 8px; }
.empty-state p { margin: 0 0 24px; font-size: 14px; }
</style>

<?php
// --- STATS ---
$sql_total = "SELECT COUNT(*) as cnt FROM ".MAIN_DB_PREFIX."foodbank_warehouses";
$sql_cap   = "SELECT COALESCE(SUM(capacity), 0) as total_cap FROM ".MAIN_DB_PREFIX."foodbank_warehouses";

$r_total = $db->query($sql_total);
$r_cap   = $db->query($sql_cap);
$total_warehouses = $r_total ? (int)$db->fetch_object($r_total)->cnt : 0;
$total_capacity   = $r_cap   ? (int)$db->fetch_object($r_cap)->total_cap : 0;

// Fetch all rows for display
$sql = "SELECT rowid, ref, label, address, capacity FROM ".MAIN_DB_PREFIX."foodbank_warehouses ORDER BY ref ASC";
$res = $db->query($sql);
$all_warehouses = array();
if ($res) {
    while ($o = $db->fetch_object($res)) {
        $all_warehouses[] = $o;
    }
}
$max_cap = $total_capacity > 0 ? $total_capacity : 1;
?>

<div class="fb-wrap">

    <!-- Header -->
    <div class="fb-page-header">
        <div>
            <h1>🏭 Warehouses</h1>
            <p>Manage storage locations and capacity</p>
        </div>
        <div>
            <a href="dashboard_admin.php" class="btn-ghost">← Dashboard</a>
            <a href="create_warehouse.php" class="btn-primary">+ Add Warehouse</a>
        </div>
    </div>

    <!-- Stats Strip -->
    <div class="stats-strip">
        <div class="stat-tile">
            <div class="val"><?php echo $total_warehouses; ?></div>
            <div class="lbl">Total Warehouses</div>
        </div>
        <div class="stat-tile">
            <div class="val"><?php echo number_format($total_capacity); ?></div>
            <div class="lbl">Total Capacity (units)</div>
        </div>
        <div class="stat-tile">
            <div class="val"><?php echo $total_warehouses > 0 ? number_format($total_capacity / $total_warehouses) : 0; ?></div>
            <div class="lbl">Avg Capacity per Location</div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="fb-card">
        <?php if (!empty($all_warehouses)) : ?>
        <table class="fb-table">
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Warehouse Name</th>
                    <th>Address</th>
                    <th>Capacity</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($all_warehouses as $o) :
                $cap_pct = $total_capacity > 0 ? min(100, round(($o->capacity / $max_cap) * 100)) : 0;
            ?>
                <tr>
                    <td><span class="ref-chip"><?php echo dol_escape_htmltag($o->ref); ?></span></td>
                    <td><strong><?php echo dol_escape_htmltag($o->label); ?></strong></td>
                    <td style="color:#64748b;"><?php echo dol_escape_htmltag($o->address) ?: '<span style="color:#cbd5e1">—</span>'; ?></td>
                    <td>
                        <div class="cap-bar-wrap">
                            <div class="cap-bar"><div class="cap-fill" style="width:<?php echo $cap_pct; ?>%"></div></div>
                            <span class="cap-text"><?php echo number_format((int)$o->capacity); ?> units</span>
                        </div>
                    </td>
                    <td style="text-align:right;">
                        <a href="edit_warehouse.php?id=<?php echo $o->rowid; ?>" class="act-btn">✏️ Edit</a>
                        <a href="delete_warehouse.php?id=<?php echo $o->rowid; ?>" class="act-btn danger"
                           onclick="return confirm('Delete this warehouse?')">🗑️ Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <div class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <h3>No warehouses yet</h3>
            <p>Add your first storage location to get started.</p>
            <a href="create_warehouse.php" class="btn-primary">+ Add Warehouse</a>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php
llxFooter();
$db->close();
