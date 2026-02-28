<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendor.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");

$id = GETPOST('id', 'int');
if (!$id) { header("Location: vendors.php"); exit; }

$v = new VendorFB($db);
if ($v->fetch($id) <= 0) { header("Location: vendors.php"); exit; }

$notice    = [];
$hide_form = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed.'];
    } else {
        $v->name                = GETPOST('name',                'alphanohtml');
        $v->category            = GETPOST('category',            'alphanohtml');
        $v->email               = GETPOST('email',               'alphanohtml');
        $v->phone               = GETPOST('phone',               'alphanohtml');
        $v->contact_person      = GETPOST('contact_person',      'alphanohtml');
        $v->contact_email       = GETPOST('contact_email',       'alphanohtml');
        $v->contact_phone       = GETPOST('contact_phone',       'alphanohtml');
        $v->address             = GETPOST('address',             'restricthtml');
        $v->registration_number = GETPOST('rc_number',           'alphanohtml');
        $v->tax_id              = GETPOST('tax_id',              'alphanohtml');
        $v->website             = GETPOST('website',             'alphanohtml');
        $v->bank_name           = GETPOST('bank_name',           'alphanohtml');
        $v->bank_account_number = GETPOST('bank_account_number', 'alphanohtml');
        $v->description         = GETPOST('description',         'restricthtml');
        $v->status              = GETPOST('status',              'alphanohtml');

        if ($v->update($user) > 0) {
            $notice    = ['success', 'Vendor updated successfully.'];
            $hide_form = false; // stay on form so user can keep editing
        } else {
            $notice = ['error', 'Update failed: '.dol_escape_htmltag($v->error)];
        }
    }
}

llxHeader('', 'Edit Vendor');
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

.fb-wrap { max-width: 900px; margin: 0 auto; padding: 24px 28px; font-family: var(--font); }

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

.fb-notice { padding: 13px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
.fb-notice.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.fb-notice.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

.section-divider { border: none; border-top: 1px solid #f1f5f9; margin: 4px 0 22px; }

.badge-active   { display:inline-block; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; background:#dcfce7; color:#15803d; }
.badge-pending  { display:inline-block; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; background:#fef3c7; color:#92400e; }
.badge-inactive { display:inline-block; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; background:#f1f5f9; color:#475569; }
</style>

<div class="fb-wrap">

    <div class="fb-page-header">
        <div>
            <h1>Edit Vendor</h1>
            <p><?php echo dol_escape_htmltag($v->name); ?> &middot; Ref: <strong><?php echo dol_escape_htmltag($v->ref); ?></strong></p>
        </div>
        <div>
            <a href="view_vendor.php?id=<?php echo $v->id; ?>" class="btn-ghost">← Back to Profile</a>
            <a href="vendors.php" class="btn-ghost">All Vendors</a>
        </div>
    </div>

    <?php if ($notice) : ?>
    <div class="fb-notice <?php echo $notice[0]; ?>"><?php echo $notice[1]; ?></div>
    <?php endif; ?>

    <div class="fb-card">
        <div class="fb-card-head"><h3>Business Details</h3></div>
        <div class="fb-card-body">
            <form method="POST" action="<?php echo basename(__FILE__).'?id='.(int)$v->id; ?>">
                <input type="hidden" name="token" value="<?php echo newToken(); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Business Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="name" required value="<?php echo dol_escape_htmltag($v->name); ?>">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="">— Select Category —</option>
                            <?php foreach (['Grains','Fresh Produce','Proteins','Packaged','Dairy','Beverages','Logistics','Other'] as $c) : ?>
                            <option value="<?php echo $c; ?>" <?php echo $v->category==$c?'selected':''; ?>><?php echo $c; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>RC Number</label>
                        <input type="text" name="rc_number" value="<?php echo dol_escape_htmltag($v->registration_number); ?>">
                    </div>
                    <div class="form-group">
                        <label>Tax ID (TIN)</label>
                        <input type="text" name="tax_id" value="<?php echo dol_escape_htmltag($v->tax_id); ?>">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Business Email</label>
                        <input type="email" name="email" value="<?php echo dol_escape_htmltag($v->email); ?>">
                    </div>
                    <div class="form-group">
                        <label>Business Phone</label>
                        <input type="text" name="phone" value="<?php echo dol_escape_htmltag($v->phone); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Website</label>
                    <input type="url" name="website" value="<?php echo dol_escape_htmltag($v->website); ?>">
                </div>

                <div class="form-group">
                    <label>Office Address</label>
                    <textarea name="address" rows="2"><?php echo dol_escape_htmltag($v->address); ?></textarea>
                </div>

                <hr class="section-divider">
                <p style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#94a3b8; margin:0 0 18px;">Contact Manager</p>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" value="<?php echo dol_escape_htmltag($v->contact_person); ?>">
                    </div>
                    <div class="form-group">
                        <label>Direct Phone</label>
                        <input type="text" name="contact_phone" value="<?php echo dol_escape_htmltag($v->contact_phone); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Direct Email</label>
                    <input type="email" name="contact_email" value="<?php echo dol_escape_htmltag($v->contact_email); ?>">
                </div>

                <hr class="section-divider">
                <p style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#94a3b8; margin:0 0 18px;">Banking Details</p>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Bank Name</label>
                        <input type="text" name="bank_name" value="<?php echo dol_escape_htmltag($v->bank_name); ?>">
                    </div>
                    <div class="form-group">
                        <label>Account Number</label>
                        <input type="text" name="bank_account_number" value="<?php echo dol_escape_htmltag($v->bank_account_number); ?>">
                    </div>
                </div>

                <hr class="section-divider">
                <p style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#94a3b8; margin:0 0 18px;">Admin Controls</p>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Vendor Status</label>
                        <select name="status">
                            <option value="Pending"  <?php echo $v->status=='Pending' ?'selected':''; ?>>Pending</option>
                            <option value="Active"   <?php echo $v->status=='Active'  ?'selected':''; ?>>Active</option>
                            <option value="Inactive" <?php echo $v->status=='Inactive'?'selected':''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Internal Notes</label>
                    <textarea name="description" rows="3"><?php echo dol_escape_htmltag($v->description); ?></textarea>
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <a href="view_vendor.php?id=<?php echo $v->id; ?>" class="btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</div>

<?php llxFooter(); ?>
