<?php
/**
 * Create Inventory/Supply Log — accessible by Admins and Vendors
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/donation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

$is_admin  = FoodbankPermissions::isAdmin($user);
$is_vendor = FoodbankPermissions::isVendor($user, $db);

if (!$is_admin && !$is_vendor) {
    accessforbidden('You do not have access to this page.');
}

// Resolve current vendor (if vendor role)
$current_vendor_id   = 0;
$current_vendor_name = '';
if ($is_vendor) {
    $sql = "SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
    $res = $db->query($sql);
    if ($res && $obj = $db->fetch_object($res)) {
        $current_vendor_id   = $obj->rowid;
        $current_vendor_name = $obj->name;
    }
}

$notice    = [];
$hide_form = false;
$new_ref   = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed.'];
    } else {
        $d               = new DonationFB($db);
        $d->ref          = GETPOST('ref',          'alphanohtml');
        $d->product_name = GETPOST('product_name', 'alphanohtml');
        $d->category     = GETPOST('category',     'alphanohtml');
        $d->quantity     = GETPOST('quantity',      'int');
        $d->unit         = GETPOST('unit',          'alphanohtml');
        $d->fk_warehouse = GETPOST('fk_warehouse',  'int');
        $d->note         = GETPOST('note',          'restricthtml');
        $d->date_donation = dol_now();

        if ($is_admin) {
            $d->fk_vendor = GETPOST('fk_vendor', 'int');
            $d->status    = GETPOST('status',     'alphanohtml');
        } else {
            $d->fk_vendor = $current_vendor_id;
            $d->status    = 'Pending';
        }

        if ($d->create($user) > 0) {
            $hide_form = true;
            $new_ref   = $d->ref;
        } else {
            $notice = ['error', 'Error saving inventory: '.dol_escape_htmltag($d->error)];
        }
    }
}

// Dropdowns
$vendors = [];
if ($is_admin) {
    $res = $db->query("SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE status='Active' ORDER BY name");
    while ($o = $db->fetch_object($res)) $vendors[] = $o;
}

$warehouses = [];
$res = $db->query("SELECT rowid, label FROM ".MAIN_DB_PREFIX."foodbank_warehouses ORDER BY label");
while ($o = $db->fetch_object($res)) $warehouses[] = $o;

$_SESSION["mainmenu"] = "foodbankcrm";
$_fb_admin_head = '<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">'
              . '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/css/admin_mobile.css">';
llxHeader($_fb_admin_head, 'Add Inventory');
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

<?php if ($is_vendor && !$is_admin) : ?>
/* Vendor: full-width portal — hide side nav */
#id-top, .side-nav, .side-nav-vert, #id-left { display: none !important; }
html, body { background: var(--surface) !important; }
#id-right { margin: 0 !important; padding: 0 !important; width: 100vw !important; max-width: 100vw !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
<?php else : ?>
/* Admin: standard side-nav layout */
#id-top { display: none !important; }
.side-nav, .side-nav-vert { top: 0 !important; height: 100vh !important; }
#id-right { padding-top: 30px !important; background: var(--surface) !important; min-height: 100vh; }
.fiche { max-width: 100% !important; margin: 0 !important; }
<?php endif; ?>

.fb-wrap { max-width: 860px; margin: 0 auto; padding: 24px 28px; font-family: var(--font); }

.fb-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
.fb-page-header h1 { margin: 0; font-size: 22px; font-weight: 800; color: #1e293b; }
.fb-page-header p  { margin: 4px 0 0; color: #64748b; font-size: 14px; }

.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none !important; border: none; cursor: pointer; font-family: var(--font); transition: background .2s; }
.btn-primary:hover { background: var(--accent-dark); text-decoration: none !important; }
.btn-ghost { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none !important; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; text-decoration: none !important; }
.btn-danger { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #dc2626 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none !important; border: 1px solid #fecaca; transition: background .15s; }
.btn-danger:hover { background: #fef2f2; text-decoration: none !important; }

.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; margin-bottom: 20px; }
.fb-card-head { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; }
.fb-card-head h3 { margin: 0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #94a3b8; }
.fb-card-body { padding: 24px; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
.form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px; }
.form-group label { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
.form-group input,
.form-group select,
.form-group textarea { border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; font-size: 14px; color: #1e293b; background: #fff; width: 100%; box-sizing: border-box; font-family: var(--font); transition: border-color .15s, box-shadow .15s; }
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(79,70,229,.1); outline: none; }
.form-group input[disabled] { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }

.fb-notice { padding: 13px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
.fb-notice.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

.success-card { background: #f0fdf4; border: 2px solid #16a34a; border-radius: var(--radius); padding: 40px; text-align: center; }
.success-card .s-icon { font-size: 48px; margin-bottom: 14px; }
.success-card h2 { color: #15803d; margin: 0 0 8px; font-size: 20px; font-weight: 800; }
.success-card p { color: #166534; margin: 0 0 24px; font-size: 14px; }

.vendor-lock { background: var(--accent-light); border: 1.5px solid #c7d2fe; border-radius: 8px; padding: 10px 14px; font-size: 14px; color: var(--accent-dark); font-weight: 600; }

.section-divider { border: none; border-top: 1px solid #f1f5f9; margin: 4px 0 22px; }
</style>

<div class="fb-wrap">

    <div class="fb-page-header">
        <div>
            <h1>Add Inventory</h1>
            <p>Record a new supply log into the system<?php echo $is_vendor ? ' as <strong>'.dol_escape_htmltag($current_vendor_name).'</strong>' : ''; ?></p>
        </div>
        <div>
            <?php if ($is_admin) : ?>
            <a href="donations.php" class="btn-ghost">← Back to Inventory</a>
            <?php else : ?>
            <a href="dashboard_vendor.php" class="btn-ghost">← Dashboard</a>
            <a href="<?php echo DOL_URL_ROOT; ?>/user/logout.php" class="btn-danger">Logout</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($notice) : ?>
    <div class="fb-notice <?php echo $notice[0]; ?>"><?php echo $notice[1]; ?></div>
    <?php endif; ?>

    <?php if ($hide_form) : ?>
    <div class="success-card">
        <div class="s-icon">📦</div>
        <h2>Inventory Logged!</h2>
        <p>Ref: <strong><?php echo dol_escape_htmltag($new_ref); ?></strong> has been saved successfully.</p>
        <?php if ($is_admin) : ?>
        <a href="donations.php" class="btn-primary" style="margin-right:10px;">View All Inventory</a>
        <?php else : ?>
        <a href="dashboard_vendor.php" class="btn-primary" style="margin-right:10px;">Back to Dashboard</a>
        <?php endif; ?>
        <a href="create_donation.php" class="btn-ghost">+ Add Another</a>
    </div>

    <?php else : ?>
    <div class="fb-card">
        <div class="fb-card-head"><h3>Product Details</h3></div>
        <div class="fb-card-body">
            <form method="POST" action="<?php echo basename(__FILE__); ?>">
                <input type="hidden" name="token" value="<?php echo newToken(); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Product Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="product_name" required placeholder="e.g. Premium White Rice 50kg"
                               value="<?php echo dol_escape_htmltag(GETPOST('product_name', 'alphanohtml')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="">— Select Category —</option>
                            <?php foreach (['Grains','Vegetables','Proteins','Dairy','Beverages','Packaged Foods','Other'] as $c) : ?>
                            <option value="<?php echo $c; ?>" <?php echo GETPOST('category','alphanohtml')==$c?'selected':''; ?>><?php echo $c; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid-3">
                    <div class="form-group">
                        <label>Quantity <span style="color:#ef4444;">*</span></label>
                        <input type="number" name="quantity" required min="1"
                               value="<?php echo (int)GETPOST('quantity','int') ?: 1; ?>">
                    </div>
                    <div class="form-group">
                        <label>Unit</label>
                        <select name="unit">
                            <?php foreach (['kg'=>'Kilograms','bags'=>'Bags','cartons'=>'Cartons','liters'=>'Liters','units'=>'Units','crates'=>'Crates'] as $val=>$lbl) : ?>
                            <option value="<?php echo $val; ?>" <?php echo GETPOST('unit','alphanohtml')==$val?'selected':''; ?>><?php echo $lbl; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($is_admin) : ?>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="Received" selected>Received (In Stock)</option>
                            <option value="Pending">Pending (Expected)</option>
                        </select>
                    </div>
                    <?php else : ?>
                    <input type="hidden" name="status" value="Pending">
                    <?php endif; ?>
                </div>

                <hr class="section-divider">
                <p style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#94a3b8; margin:0 0 18px;">Source &amp; Storage</p>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Supply Source (Vendor)</label>
                        <?php if ($is_admin) : ?>
                        <select name="fk_vendor" required>
                            <option value="">— Select Vendor —</option>
                            <?php foreach ($vendors as $vnd) : ?>
                            <option value="<?php echo $vnd->rowid; ?>" <?php echo GETPOST('fk_vendor','int')==$vnd->rowid?'selected':''; ?>><?php echo dol_escape_htmltag($vnd->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else : ?>
                        <div class="vendor-lock"><?php echo dol_escape_htmltag($current_vendor_name); ?></div>
                        <input type="hidden" name="fk_vendor" value="<?php echo $current_vendor_id; ?>">
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Target Warehouse</label>
                        <select name="fk_warehouse">
                            <option value="">— Select Warehouse —</option>
                            <?php foreach ($warehouses as $wh) : ?>
                            <option value="<?php echo $wh->rowid; ?>" <?php echo GETPOST('fk_warehouse','int')==$wh->rowid?'selected':''; ?>><?php echo dol_escape_htmltag($wh->label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes / Condition</label>
                    <textarea name="note" rows="2" placeholder="Expiry date, batch number, condition..."><?php echo dol_escape_htmltag(GETPOST('note', 'restricthtml')); ?></textarea>
                </div>

                <div class="form-group" style="max-width:260px;">
                    <label>Reference ID</label>
                    <input type="text" name="ref" placeholder="Auto-generated if empty"
                           value="<?php echo dol_escape_htmltag(GETPOST('ref', 'alphanohtml')); ?>">
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="submit" class="btn-primary">Save Inventory Log</button>
                    <?php if ($is_admin) : ?>
                    <a href="donations.php" class="btn-ghost">Cancel</a>
                    <?php else : ?>
                    <a href="dashboard_vendor.php" class="btn-ghost">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php llxFooter(); ?>
