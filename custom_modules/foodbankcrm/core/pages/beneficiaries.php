<?php
/**
 * ADMIN VIEW: Subscriber (Beneficiary) List
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");

// --- QUICK ACTIONS (before header output) ---
$notice = '';
$action = GETPOST('action', 'alpha');

if ($action == 'approve' && GETPOST('id', 'int')) {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed.'];
    } else {
        $bid = (int)GETPOST('id', 'int');
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET subscription_status = 'Active' WHERE rowid = ".$bid;
        if ($db->query($sql)) {
            // Also activate the linked Dolibarr user account
            $db->query("UPDATE ".MAIN_DB_PREFIX."user u
                        JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON b.fk_user = u.rowid
                        SET u.statut = 1 WHERE b.rowid = ".$bid);
            $notice = ['success', 'Subscriber account activated successfully.'];
        } else {
            $notice = ['error', 'Error: '.$db->lasterror()];
        }
    }
}

$_SESSION["mainmenu"] = "foodbankcrm";
$_fb_admin_head = '<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">'
              . '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/css/admin_mobile.css">';
llxHeader($_fb_admin_head, 'Subscriber Management');
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

/* Table */
.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
.fb-table { width: 100%; border-collapse: collapse; }
.fb-table thead th { text-align: left; padding: 14px 20px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
.fb-table tbody td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #374151; vertical-align: middle; }
.fb-table tbody tr:last-child td { border-bottom: none; }
.fb-table tbody tr:hover { background: #fafbff; }

/* Avatar */
.sub-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--accent-light); color: var(--accent); font-weight: 800; font-size: 14px; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px; flex-shrink: 0; }
.sub-cell  { display: flex; align-items: center; }
.sub-name  { font-weight: 600; color: #1e293b; font-size: 14px; text-decoration: none; }
.sub-name:hover { color: var(--accent); }
.sub-email { font-size: 12px; color: #94a3b8; margin-top: 1px; }

/* Plan chip */
.plan-chip { background: var(--accent-light); color: var(--accent-dark); padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }

/* Badges */
.badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; text-transform: uppercase; letter-spacing: .4px; }
.badge-active   { background: #dcfce7; color: #15803d; }
.badge-pending  { background: #fef3c7; color: #92400e; }
.badge-expired  { background: #fee2e2; color: #991b1b; }
.badge-default  { background: #f1f5f9; color: #475569; }

/* Actions */
.act-btn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; text-decoration: none; border: 1px solid #e2e8f0; color: #475569 !important; background: #fff; margin-left: 6px; transition: all .15s; cursor: pointer; }
.act-btn:hover { background: #f1f5f9; }
.act-btn.danger { color: #ef4444 !important; border-color: #fecaca; }
.act-btn.danger:hover { background: #fef2f2; }
.act-btn.approve { color: #15803d !important; border-color: #bbf7d0; background: #f0fdf4; }
.act-btn.approve:hover { background: #dcfce7; }

/* Notice */
.fb-notice { padding: 13px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
.fb-notice.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.fb-notice.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

.empty-state { text-align: center; padding: 70px 40px; color: #94a3b8; }
.empty-state h3 { font-size: 18px; color: #64748b; margin: 0 0 8px; }
.empty-state p { margin: 0 0 24px; font-size: 14px; }
</style>

<?php
// Stats
$total   = (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries"))->c;
$active  = (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE subscription_status='Active'"))->c;
$pending = (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE subscription_status='Pending'"))->c;
$expired = (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE subscription_status='Expired'"))->c;

$sql = "SELECT t.*, u.statut as account_active
        FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries as t
        LEFT JOIN ".MAIN_DB_PREFIX."user as u ON t.fk_user = u.rowid
        ORDER BY t.rowid DESC";
$res = $db->query($sql);
?>

<div class="fb-wrap">

    <div class="fb-page-header">
        <div>
            <h1>👥 Subscribers</h1>
            <p>Manage beneficiary subscription accounts</p>
        </div>
        <div>
            <a href="dashboard_admin.php" class="btn-ghost">← Dashboard</a>
            <a href="create_beneficiary.php" class="btn-primary">+ Add Subscriber</a>
        </div>
    </div>

    <?php if ($notice) : ?>
    <div class="fb-notice <?php echo $notice[0]; ?>"><?php echo $notice[1]; ?></div>
    <?php endif; ?>

    <!-- Stats Strip -->
    <div class="stats-strip">
        <div class="stat-tile">
            <div class="val"><?php echo $total; ?></div>
            <div class="lbl">Total Subscribers</div>
        </div>
        <div class="stat-tile green">
            <div class="val"><?php echo $active; ?></div>
            <div class="lbl">Active</div>
        </div>
        <div class="stat-tile orange">
            <div class="val"><?php echo $pending; ?></div>
            <div class="lbl">Pending</div>
        </div>
        <div class="stat-tile red">
            <div class="val"><?php echo $expired; ?></div>
            <div class="lbl">Expired</div>
        </div>
    </div>

    <div class="fb-card">
        <?php if ($res && $db->num_rows($res) > 0) : ?>
        <table class="fb-table">
            <thead>
                <tr>
                    <th>Subscriber</th>
                    <th>Location</th>
                    <th>Family Size</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($obj = $db->fetch_object($res)) :
                $status = !empty($obj->subscription_status) ? $obj->subscription_status : 'Pending';
                $badge  = $status == 'Active' ? 'badge-active' : ($status == 'Expired' ? 'badge-expired' : ($status == 'Pending' ? 'badge-pending' : 'badge-default'));
                $name   = trim($obj->firstname . ' ' . $obj->lastname);
                $initial= strtoupper(mb_substr($obj->firstname ?: $obj->lastname ?: '?', 0, 1));
                $location = ($obj->city && $obj->state) ? dol_escape_htmltag($obj->city.', '.$obj->state) : '<span style="color:#cbd5e1">—</span>';
                $plan   = !empty($obj->subscription_type) ? $obj->subscription_type : 'Standard';
                $family = (int)$obj->family_size > 0 ? $obj->family_size.' members' : '1 member';
            ?>
                <tr>
                    <td>
                        <div class="sub-cell">
                            <div class="sub-avatar"><?php echo $initial; ?></div>
                            <div>
                                <a href="view_beneficiary.php?id=<?php echo $obj->rowid; ?>" class="sub-name">
                                    <?php echo dol_escape_htmltag($name); ?>
                                </a>
                                <div class="sub-email"><?php echo dol_escape_htmltag($obj->email); ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="color:#64748b;"><?php echo $location; ?></td>
                    <td><?php echo $family; ?></td>
                    <td><span class="plan-chip"><?php echo dol_escape_htmltag($plan); ?></span></td>
                    <td><span class="badge <?php echo $badge; ?>"><?php echo $status; ?></span></td>
                    <td style="text-align:right; white-space:nowrap;">
                        <a href="view_beneficiary.php?id=<?php echo $obj->rowid; ?>" class="act-btn">View</a>
                        <a href="edit_beneficiary.php?id=<?php echo $obj->rowid; ?>" class="act-btn">✏️ Edit</a>
                        <?php if ($status !== 'Active') : ?>
                        <form method="POST" action="beneficiaries.php" style="display:inline;">
                            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="id" value="<?php echo $obj->rowid; ?>">
                            <button type="submit" class="act-btn approve"
                                onclick="return confirm('Activate <?php echo dol_escape_js($name); ?>\'s account?')">
                                Manage Subscriber's Account
                            </button>
                        </form>
                        <?php endif; ?>
                        <a href="delete_beneficiary.php?id=<?php echo $obj->rowid; ?>" class="act-btn danger"
                           onclick="return confirm('Delete subscriber <?php echo dol_escape_js($name); ?>?')">🗑️</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else : ?>
        <div class="empty-state">
            <h3>No subscribers yet</h3>
            <p>Subscribers appear here once they register.</p>
            <a href="create_beneficiary.php" class="btn-primary">+ Add Subscriber</a>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php
llxFooter();
$db->close();
