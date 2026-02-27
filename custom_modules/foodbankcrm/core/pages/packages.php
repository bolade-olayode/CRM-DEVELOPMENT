<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/package.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader('', 'Manage Packages');
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
.stats-strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
.stat-tile { background: #fff; border-radius: var(--radius); padding: 20px 24px; box-shadow: var(--shadow); border-top: 4px solid var(--accent); }
.stat-tile.green { border-color: #10b981; }
.stat-tile .val { font-size: 30px; font-weight: 800; color: #1e293b; line-height: 1; }
.stat-tile .lbl { font-size: 12px; color: #64748b; margin-top: 6px; text-transform: uppercase; letter-spacing: .5px; }

/* Package cards grid */
.pkg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
.pkg-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; transition: transform .2s, box-shadow .2s; }
.pkg-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
.pkg-card-top { background: var(--accent); padding: 20px 24px; }
.pkg-card-top .pkg-ref { font-size: 11px; color: rgba(255,255,255,.7); text-transform: uppercase; letter-spacing: .6px; margin-bottom: 6px; }
.pkg-card-top .pkg-name { font-size: 18px; font-weight: 800; color: #fff; margin: 0; }
.pkg-card-body { padding: 18px 24px; }
.pkg-card-body .pkg-desc { font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 16px; min-height: 38px; }
.pkg-meta { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
.item-bubble { background: var(--accent-light); color: var(--accent-dark); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.badge-active   { background: #dcfce7; color: #15803d; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
.badge-inactive { background: #f1f5f9; color: #475569; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
.usage-stat { font-size: 12px; color: #94a3b8; }
.pkg-card-foot { padding: 14px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 8px; }
.act-btn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; text-decoration: none; border: 1px solid #e2e8f0; color: #475569 !important; background: #fff; transition: all .15s; }
.act-btn:hover { background: #f1f5f9; }
.act-btn.danger { color: #ef4444 !important; border-color: #fecaca; }
.act-btn.danger:hover { background: #fef2f2; }

.empty-state { text-align: center; padding: 70px 40px; color: #94a3b8; }
.empty-state h3 { font-size: 18px; color: #64748b; margin: 0 0 8px; }
.empty-state p { margin: 0 0 24px; font-size: 14px; }
</style>

<?php
$total  = (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_packages"))->c;
$active = (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_packages WHERE status='Active'"))->c;
$used   = (int)$db->fetch_object($db->query("SELECT COUNT(DISTINCT fk_package) as c FROM ".MAIN_DB_PREFIX."foodbank_distributions"))->c;

$sql = "SELECT p.*,
        (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."foodbank_package_items WHERE fk_package = p.rowid) as item_count,
        (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE fk_package = p.rowid) as usage_count
        FROM ".MAIN_DB_PREFIX."foodbank_packages p
        ORDER BY p.rowid DESC";
$res = $db->query($sql);
$packages = array();
if ($res) while ($o = $db->fetch_object($res)) $packages[] = $o;
?>

<div class="fb-wrap">

    <div class="fb-page-header">
        <div>
            <h1>📦 Packages</h1>
            <p>Manage food box templates and configurations</p>
        </div>
        <div>
            <a href="dashboard_admin.php" class="btn-ghost">← Dashboard</a>
            <a href="create_package.php" class="btn-primary">+ Create Package</a>
        </div>
    </div>

    <div class="stats-strip">
        <div class="stat-tile">
            <div class="val"><?php echo $total; ?></div>
            <div class="lbl">Total Packages</div>
        </div>
        <div class="stat-tile green">
            <div class="val"><?php echo $active; ?></div>
            <div class="lbl">Active Packages</div>
        </div>
        <div class="stat-tile">
            <div class="val"><?php echo $used; ?></div>
            <div class="lbl">Used in Distributions</div>
        </div>
    </div>

    <?php if (!empty($packages)) : ?>
    <div class="pkg-grid">
        <?php foreach ($packages as $obj) :
            $is_active = ($obj->status == 'Active');
            $badge_class = $is_active ? 'badge-active' : 'badge-inactive';
        ?>
        <div class="pkg-card">
            <div class="pkg-card-top">
                <div class="pkg-ref"><?php echo dol_escape_htmltag($obj->ref); ?></div>
                <div class="pkg-name"><?php echo dol_escape_htmltag($obj->name); ?></div>
            </div>
            <div class="pkg-card-body">
                <div class="pkg-desc"><?php echo $obj->description ? dol_escape_htmltag(dol_trunc($obj->description, 80)) : '<em style="color:#cbd5e1">No description</em>'; ?></div>
                <div class="pkg-meta">
                    <span class="item-bubble"><?php echo $obj->item_count; ?> items</span>
                    <span class="<?php echo $badge_class; ?>"><?php echo $obj->status ?: 'Draft'; ?></span>
                    <span class="usage-stat">Used <?php echo $obj->usage_count; ?>× in distributions</span>
                </div>
            </div>
            <div class="pkg-card-foot">
                <a href="view_package.php?id=<?php echo $obj->rowid; ?>" class="act-btn">View</a>
                <a href="edit_package.php?id=<?php echo $obj->rowid; ?>" class="act-btn">✏️ Edit</a>
                <a href="delete_package.php?id=<?php echo $obj->rowid; ?>" class="act-btn danger"
                   onclick="return confirm('Delete package <?php echo dol_escape_js($obj->name); ?>?')">🗑️</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <div class="empty-state">
        <h3>No packages created</h3>
        <p>Create a package template to start defining food boxes.</p>
        <a href="create_package.php" class="btn-primary">+ Create Package</a>
    </div>
    <?php endif; ?>

</div>

<?php
llxFooter();
$db->close();
