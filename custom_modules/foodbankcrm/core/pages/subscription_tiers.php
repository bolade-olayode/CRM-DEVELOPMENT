<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$action = GETPOST('action', 'alpha');
$notice = '';

// Handle delete
if ($action == 'delete' && GETPOST('id', 'int')) {
    if (!isset($_GET['token']) || $_GET['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed. Please try again.'];
    } else {
        $id  = (int)GETPOST('id', 'int');
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE rowid = ".$id;
        if ($db->query($sql)) {
            $notice = ['success', 'Subscription tier deleted.'];
        } else {
            $notice = ['error', 'Error deleting tier: '.$db->lasterror()];
        }
    }
}

// Handle activate/deactivate
if ($action == 'toggle' && GETPOST('id', 'int')) {
    if (!isset($_GET['token']) || $_GET['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed. Please try again.'];
    } else {
        $id  = (int)GETPOST('id', 'int');
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_subscription_tiers SET is_active = IF(is_active = 1, 0, 1) WHERE rowid = ".$id;
        if ($db->query($sql)) {
            $notice = ['success', 'Tier status updated.'];
        } else {
            $notice = ['error', 'Error: '.$db->lasterror()];
        }
    }
}

// Stats
$total  = (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers"))->c;
$active = (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE is_active=1"))->c;
$inactive = $total - $active;

// All tiers
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers ORDER BY price ASC";
$res = $db->query($sql);
$tiers = [];
if ($res) while ($o = $db->fetch_object($res)) $tiers[] = $o;

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Subscription Tiers');
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

.fb-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
.fb-page-header h1 { margin: 0; font-size: 24px; font-weight: 800; color: #1e293b; }
.fb-page-header p  { margin: 4px 0 0; color: #64748b; font-size: 14px; }

.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: background .2s; }
.btn-primary:hover { background: var(--accent-dark); }
.btn-ghost   { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; }

/* Stats */
.stats-strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
.stat-tile { background: #fff; border-radius: var(--radius); padding: 20px 24px; box-shadow: var(--shadow); border-top: 4px solid var(--accent); }
.stat-tile.green  { border-color: #10b981; }
.stat-tile.red    { border-color: #ef4444; }
.stat-tile .val { font-size: 30px; font-weight: 800; color: #1e293b; line-height: 1; }
.stat-tile .lbl { font-size: 12px; color: #64748b; margin-top: 6px; text-transform: uppercase; letter-spacing: .5px; }

/* Notice */
.fb-notice { padding: 13px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
.fb-notice.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.fb-notice.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

/* Table */
.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
.fb-table { width: 100%; border-collapse: collapse; }
.fb-table thead th { text-align: left; padding: 13px 20px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
.fb-table tbody td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #374151; vertical-align: middle; }
.fb-table tbody tr:last-child td { border-bottom: none; }
.fb-table tbody tr:hover { background: #fafbff; }

/* Tier name cell */
.tier-name { font-weight: 700; color: #1e293b; font-size: 14px; }
.tier-type { display: inline-block; background: var(--accent-light); color: var(--accent-dark); padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-top: 3px; }

.benefits-text { font-size: 12px; color: #64748b; max-width: 280px; }

/* Status badges */
.badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.badge-active   { background: #dcfce7; color: #15803d; }
.badge-inactive { background: #f1f5f9; color: #475569; }

/* Action buttons */
.act-btn { display: inline-flex; align-items: center; gap: 4px; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; text-decoration: none; border: 1px solid #e2e8f0; color: #475569 !important; background: #fff; margin-left: 4px; transition: all .15s; }
.act-btn:hover  { background: #f1f5f9; }
.act-btn.toggle-on  { color: #15803d !important; border-color: #bbf7d0; background: #f0fdf4; }
.act-btn.toggle-on:hover { background: #dcfce7; }
.act-btn.toggle-off { color: #92400e !important; border-color: #fde68a; background: #fffbeb; }
.act-btn.toggle-off:hover { background: #fef3c7; }
.act-btn.danger { color: #ef4444 !important; border-color: #fecaca; }
.act-btn.danger:hover { background: #fef2f2; }

/* Info box */
.info-box { background: #eff6ff; border-radius: var(--radius); border-left: 4px solid var(--accent); padding: 20px 24px; margin-top: 24px; }
.info-box h3 { margin: 0 0 10px; font-size: 15px; color: #1e40af; }
.info-box ul { margin: 0; padding-left: 20px; color: #1e40af; font-size: 13px; line-height: 1.8; }

.empty-state { text-align: center; padding: 60px 40px; color: #94a3b8; }
.empty-state h3 { font-size: 18px; color: #64748b; margin: 0 0 8px; }
</style>

<div class="fb-wrap">

    <!-- Header -->
    <div class="fb-page-header">
        <div>
            <h1>💳 Subscription Tiers</h1>
            <p>Define membership plans available to beneficiaries</p>
        </div>
        <div>
            <a href="dashboard_admin.php" class="btn-ghost">← Dashboard</a>
            <a href="create_subscription_tier.php" class="btn-primary">+ Create Tier</a>
        </div>
    </div>

    <?php if ($notice) : ?>
    <div class="fb-notice <?php echo $notice[0]; ?>"><?php echo $notice[1]; ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-strip">
        <div class="stat-tile">
            <div class="val"><?php echo $total; ?></div>
            <div class="lbl">Total Tiers</div>
        </div>
        <div class="stat-tile green">
            <div class="val"><?php echo $active; ?></div>
            <div class="lbl">Active</div>
        </div>
        <div class="stat-tile red">
            <div class="val"><?php echo $inactive; ?></div>
            <div class="lbl">Inactive</div>
        </div>
    </div>

    <!-- Table -->
    <div class="fb-card">
    <?php if (!empty($tiers)) : ?>
    <table class="fb-table">
        <thead>
            <tr>
                <th>Tier</th>
                <th>Duration</th>
                <th>Price</th>
                <th>Benefits</th>
                <th>Status</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tiers as $obj) :
            $is_active   = (bool)$obj->is_active;
            $badge_class = $is_active ? 'badge-active' : 'badge-inactive';
        ?>
            <tr>
                <td>
                    <div class="tier-name"><?php echo dol_escape_htmltag($obj->tier_name); ?></div>
                    <span class="tier-type"><?php echo dol_escape_htmltag($obj->tier_type); ?></span>
                </td>
                <td><?php echo (int)$obj->duration_months; ?> month<?php echo $obj->duration_months != 1 ? 's' : ''; ?></td>
                <td><strong>₦<?php echo number_format($obj->price, 2); ?></strong></td>
                <td><div class="benefits-text"><?php echo dol_escape_htmltag(dol_trunc($obj->benefits, 60)); ?></div></td>
                <td><span class="badge <?php echo $badge_class; ?>"><?php echo $is_active ? 'Active' : 'Inactive'; ?></span></td>
                <td style="text-align:right; white-space:nowrap;">
                    <a href="edit_subscription_tier.php?id=<?php echo $obj->rowid; ?>" class="act-btn">✏️ Edit</a>
                    <a href="subscription_tiers.php?action=toggle&id=<?php echo $obj->rowid; ?>&token=<?php echo newToken(); ?>"
                       class="act-btn <?php echo $is_active ? 'toggle-off' : 'toggle-on'; ?>">
                       <?php echo $is_active ? '⏸ Deactivate' : '▶ Activate'; ?>
                    </a>
                    <a href="delete_subscription_tier.php?id=<?php echo $obj->rowid; ?>" class="act-btn danger"
                       onclick="return confirm('Delete tier <?php echo dol_escape_js($obj->tier_name); ?>?')">🗑️</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else : ?>
    <div class="empty-state">
        <h3>No subscription tiers yet</h3>
        <p style="font-size:13px;">Create your first tier to define membership plans for beneficiaries.</p>
        <a href="create_subscription_tier.php" class="btn-primary" style="margin-top:8px;">+ Create Tier</a>
    </div>
    <?php endif; ?>
    </div>

    <!-- Info Box -->
    <div class="info-box">
        <h3>💡 About Subscription Tiers</h3>
        <ul>
            <li><strong>Annual</strong> — Full-year membership, best value for regular beneficiaries</li>
            <li><strong>Donor</strong> — Premium tier that supports the foodbank's mission</li>
            <li><strong>Guest</strong> — Short-term or trial access for new subscribers</li>
        </ul>
        <p style="margin: 10px 0 0; font-size: 13px; color: #1e40af;">
            Active tiers are shown to beneficiaries during registration and renewal. Deactivated tiers remain visible to admins but cannot be selected by subscribers.
        </p>
    </div>

</div>

<?php
llxFooter();
?>
