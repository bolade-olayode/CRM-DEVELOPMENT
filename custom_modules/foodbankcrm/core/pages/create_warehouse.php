<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/warehouse.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");

$notice    = [];
$hide_form = false;
$new_ref   = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed: invalid CSRF token.'];
    } else {
        $wh           = new Warehouse($db);
        $wh->label    = GETPOST('label', 'alphanohtml');
        $wh->ref      = GETPOST('ref',   'alphanohtml');
        $wh->address  = GETPOST('address', 'restricthtml');
        $wh->capacity = GETPOST('capacity', 'int');

        $result = $wh->create($user);

        if ($result > 0) {
            $hide_form = true;
            $new_ref   = $wh->ref;
        } else {
            $notice = ['error', 'Error creating warehouse: '.dol_escape_htmltag($wh->error)];
        }
    }
}

$_SESSION["mainmenu"] = "foodbankcrm";
$_fb_admin_head = '<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">'
              . '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/css/admin_mobile.css">';
llxHeader($_fb_admin_head, 'Create Warehouse');
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

.fb-wrap { max-width: 760px; margin: 0 auto; padding: 24px 28px; font-family: var(--font); }

.fb-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
.fb-page-header h1 { margin: 0; font-size: 22px; font-weight: 800; color: #1e293b; }
.fb-page-header p  { margin: 4px 0 0; color: #64748b; font-size: 14px; }

.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none !important; border: none; cursor: pointer; font-family: var(--font); transition: background .2s; }
.btn-primary:hover { background: var(--accent-dark); text-decoration: none !important; }
.btn-ghost { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none !important; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; text-decoration: none !important; }

.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; margin-bottom: 20px; }
.fb-card-head { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; }
.fb-card-head h3 { margin: 0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #94a3b8; }
.fb-card-body { padding: 24px; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px; }
.form-group label { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
.form-group input,
.form-group select,
.form-group textarea { border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; font-size: 14px; color: #1e293b; background: #fff; width: 100%; box-sizing: border-box; font-family: var(--font); transition: border-color .15s, box-shadow .15s; }
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(79,70,229,.1); outline: none; }
.form-group .hint { font-size: 12px; color: #94a3b8; }

.fb-notice { padding: 13px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
.fb-notice.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

.success-card { background: #f0fdf4; border: 2px solid #16a34a; border-radius: var(--radius); padding: 40px; text-align: center; }
.success-card .s-icon { font-size: 48px; margin-bottom: 14px; }
.success-card h2 { color: #15803d; margin: 0 0 8px; font-size: 20px; font-weight: 800; }
.success-card p { color: #166534; margin: 0 0 24px; font-size: 14px; }
</style>

<div class="fb-wrap">

    <div class="fb-page-header">
        <div>
            <h1>Create Warehouse</h1>
            <p>Add a new storage location to the system</p>
        </div>
        <div>
            <a href="warehouses.php" class="btn-ghost">← Back to Warehouses</a>
        </div>
    </div>

    <?php if ($notice) : ?>
    <div class="fb-notice <?php echo $notice[0]; ?>"><?php echo $notice[1]; ?></div>
    <?php endif; ?>

    <?php if ($hide_form) : ?>
    <div class="success-card">
        <div class="s-icon">🏭</div>
        <h2>Warehouse Created!</h2>
        <p>Ref: <strong><?php echo dol_escape_htmltag($new_ref); ?></strong> has been added to the system.</p>
        <a href="warehouses.php" class="btn-primary" style="margin-right:10px;">View All Warehouses</a>
        <a href="create_warehouse.php" class="btn-ghost">Add Another</a>
    </div>

    <?php else : ?>
    <div class="fb-card">
        <div class="fb-card-head"><h3>Warehouse Details</h3></div>
        <div class="fb-card-body">
            <form method="POST" action="<?php echo basename(__FILE__); ?>">
                <input type="hidden" name="token" value="<?php echo newToken(); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Label <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="label" required placeholder="e.g. Main Storage Hub"
                               value="<?php echo dol_escape_htmltag(GETPOST('label', 'alphanohtml')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Reference ID</label>
                        <input type="text" name="ref" placeholder="Auto-generated if empty"
                               value="<?php echo dol_escape_htmltag(GETPOST('ref', 'alphanohtml')); ?>">
                        <span class="hint">e.g. WAR2025-0001</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3" placeholder="Physical address of the warehouse..."><?php echo dol_escape_htmltag(GETPOST('address', 'restricthtml')); ?></textarea>
                </div>

                <div class="form-group" style="max-width:220px;">
                    <label>Capacity (units)</label>
                    <input type="number" name="capacity" min="0"
                           value="<?php echo (int)GETPOST('capacity', 'int'); ?>">
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="submit" class="btn-primary">Create Warehouse</button>
                    <a href="warehouses.php" class="btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php llxFooter(); ?>
