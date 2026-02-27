<?php
/**
 * ADMIN: Vendor List
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendor.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader('', 'Vendor Management');
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

.fb-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
.fb-page-header h1 { margin: 0; font-size: 24px; font-weight: 800; color: #1e293b; }
.fb-page-header p  { margin: 4px 0 0; color: #64748b; font-size: 14px; }

.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: background .2s; }
.btn-primary:hover { background: var(--accent-dark); }
.btn-ghost  { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; }

/* Stats */
.stats-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
.stat-tile { background: #fff; border-radius: var(--radius); padding: 20px 24px; box-shadow: var(--shadow); border-top: 4px solid var(--accent); }
.stat-tile.green  { border-color: #10b981; }
.stat-tile.orange { border-color: #f59e0b; }
.stat-tile.red    { border-color: #ef4444; }
.stat-tile .val { font-size: 30px; font-weight: 800; color: #1e293b; line-height: 1; }
.stat-tile .lbl { font-size: 12px; color: #64748b; margin-top: 6px; text-transform: uppercase; letter-spacing: .5px; }

/* Search bar */
.search-bar { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
.search-bar input { flex: 1; border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 14px; font-size: 14px; outline: none; font-family: var(--font); }
.search-bar input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(79,70,229,.1); }

/* Table */
.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
.fb-table { width: 100%; border-collapse: collapse; }
.fb-table thead th { text-align: left; padding: 14px 20px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
.fb-table tbody td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #374151; vertical-align: middle; }
.fb-table tbody tr:last-child td { border-bottom: none; }
.fb-table tbody tr:hover { background: #fafbff; }

/* Vendor avatar */
.vendor-avatar { width: 38px; height: 38px; border-radius: 10px; background: var(--accent-light); color: var(--accent); font-weight: 800; font-size: 16px; display: inline-flex; align-items: center; justify-content: center; margin-right: 12px; flex-shrink: 0; }
.vendor-cell { display: flex; align-items: center; }
.vendor-name { font-weight: 600; color: #1e293b; font-size: 15px; }
.vendor-ref  { font-size: 11px; color: #94a3b8; margin-top: 2px; }

/* Status badges */
.badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; text-transform: uppercase; letter-spacing: .4px; }
.badge-active   { background: #dcfce7; color: #15803d; }
.badge-pending  { background: #fef3c7; color: #92400e; }
.badge-inactive { background: #fee2e2; color: #991b1b; }

/* Actions */
.act-btn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; text-decoration: none; border: 1px solid #e2e8f0; color: #475569 !important; background: #fff; margin-left: 6px; transition: all .15s; }
.act-btn:hover { background: #f1f5f9; }
.act-btn.danger { color: #ef4444 !important; border-color: #fecaca; }
.act-btn.danger:hover { background: #fef2f2; }
.act-btn.approve { color: #15803d !important; border-color: #bbf7d0; }
.act-btn.approve:hover { background: #f0fdf4; }

.empty-state { text-align: center; padding: 70px 40px; color: #94a3b8; }
.empty-state h3 { font-size: 18px; color: #64748b; margin: 0 0 8px; }
.empty-state p { margin: 0 0 24px; font-size: 14px; }
</style>

<?php
// --- Stats ---
$total  = (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_vendors"))->c;
$active = (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE status='Active'"))->c;
$pending= (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE status='Pending'"))->c;
$inactive=(int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE status='Inactive'"))->c;

// Handle inline approve
if (GETPOST('action', 'alpha') == 'approve' && GETPOST('id', 'int')) {
    $vid = (int)GETPOST('id', 'int');
    $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_vendors SET status='Active' WHERE rowid=".$vid);
    setEventMessages('Vendor approved successfully.', null, 'mesgs');
}

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_vendors ORDER BY rowid DESC";
$res = $db->query($sql);
?>

<div class="fb-wrap">

    <div class="fb-page-header">
        <div>
            <h1>🏢 Vendors</h1>
            <p>Manage food suppliers and donors</p>
        </div>
        <div>
            <a href="dashboard_admin.php" class="btn-ghost">← Dashboard</a>
            <a href="create_vendor.php" class="btn-primary">+ Register Vendor</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-strip">
        <div class="stat-tile">
            <div class="val"><?php echo $total; ?></div>
            <div class="lbl">Total Vendors</div>
        </div>
        <div class="stat-tile green">
            <div class="val"><?php echo $active; ?></div>
            <div class="lbl">Active</div>
        </div>
        <div class="stat-tile orange">
            <div class="val"><?php echo $pending; ?></div>
            <div class="lbl">Pending Approval</div>
        </div>
        <div class="stat-tile red">
            <div class="val"><?php echo $inactive; ?></div>
            <div class="lbl">Inactive</div>
        </div>
    </div>

    <div class="fb-card">
        <?php if ($res && $db->num_rows($res) > 0) : ?>
        <table class="fb-table">
            <thead>
                <tr>
                    <th>Vendor</th>
                    <th>Contact Person</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($obj = $db->fetch_object($res)) :
                $status = !empty($obj->status) ? $obj->status : 'Pending';
                $badge_class = $status == 'Active' ? 'badge-active' : ($status == 'Inactive' ? 'badge-inactive' : 'badge-pending');
                $initial = strtoupper(mb_substr($obj->name, 0, 1));
            ?>
                <tr>
                    <td>
                        <div class="vendor-cell">
                            <div class="vendor-avatar"><?php echo $initial; ?></div>
                            <div>
                                <a href="view_vendor.php?id=<?php echo $obj->rowid; ?>" class="vendor-name" style="text-decoration:none;">
                                    <?php echo dol_escape_htmltag($obj->name); ?>
                                </a>
                                <div class="vendor-ref"><?php echo dol_escape_htmltag($obj->ref); ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo dol_escape_htmltag($obj->contact_person) ?: '<span style="color:#cbd5e1">—</span>'; ?></td>
                    <td><?php echo dol_escape_htmltag($obj->contact_phone) ?: '<span style="color:#cbd5e1">—</span>'; ?></td>
                    <td style="color:#64748b;"><?php echo dol_escape_htmltag($obj->contact_email) ?: '<span style="color:#cbd5e1">—</span>'; ?></td>
                    <td><?php echo dol_escape_htmltag($obj->category) ?: '<span style="color:#cbd5e1">—</span>'; ?></td>
                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span></td>
                    <td style="text-align:right; white-space:nowrap;">
                        <?php if ($status == 'Pending') : ?>
                        <a href="?action=approve&id=<?php echo $obj->rowid; ?>" class="act-btn approve">✓ Approve</a>
                        <?php endif; ?>
                        <a href="view_vendor.php?id=<?php echo $obj->rowid; ?>" class="act-btn">View</a>
                        <a href="edit_vendor.php?id=<?php echo $obj->rowid; ?>" class="act-btn">✏️ Edit</a>
                        <a href="delete_vendor.php?id=<?php echo $obj->rowid; ?>" class="act-btn danger"
                           onclick="return confirm('Delete vendor <?php echo dol_escape_js($obj->name); ?>?')">🗑️</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else : ?>
        <div class="empty-state">
            <h3>No vendors registered</h3>
            <p>Vendors appear here once they register and await approval.</p>
            <a href="create_vendor.php" class="btn-primary">+ Register Vendor</a>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php
llxFooter();
$db->close();
