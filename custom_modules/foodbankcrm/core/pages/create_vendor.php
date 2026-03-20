<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendor.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");

$notice           = [];
$hide_form        = false;
$created_login    = '';
$created_password = '';
$new_ref          = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed.'];
    } else {
        $db->begin();

        $vendor_name = GETPOST('name', 'alphanohtml');

        // --- Step 1: Create Dolibarr user account for the vendor ---
        $newuser = new User($db);

        $base_login = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $vendor_name));
        if (empty($base_login)) $base_login = 'vendor';
        $test_login = $base_login;
        $suffix     = 1;
        while (true) {
            $chk = new User($db);
            if ($chk->fetch('', $test_login) <= 0) break;
            $test_login = $base_login.$suffix++;
        }
        $created_login = $test_login;

        // Generate 12-char password with guaranteed complexity
        $lowers   = 'abcdefghjkmnpqrstuvwxyz';
        $uppers   = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        $digits   = '23456789';
        $specials = '!@#$%';
        $all      = $lowers.$uppers.$digits.$specials;
        $created_password  = $lowers[random_int(0, strlen($lowers)-1)];
        $created_password .= $uppers[random_int(0, strlen($uppers)-1)];
        $created_password .= $digits[random_int(0, strlen($digits)-1)];
        $created_password .= $specials[random_int(0, strlen($specials)-1)];
        for ($i = 0; $i < 8; $i++) $created_password .= $all[random_int(0, strlen($all)-1)];
        $created_password = str_shuffle($created_password);

        $contact_email = GETPOST('contact_email', 'alphanohtml');
        $newuser->login     = $created_login;
        $newuser->email     = $contact_email;
        $newuser->firstname = $vendor_name;
        $newuser->lastname  = '';
        $newuser->pass      = $created_password;
        $newuser->statut    = 0; // disabled until admin approves

        $uid = $newuser->create($user);

        if ($uid <= 0) {
            $db->rollback();
            $notice = ['error', 'Could not create user account: '.dol_escape_htmltag($newuser->error)];
        } else {
            // Grant vendor permissions
            foreach ([100011, 100031, 100032] as $right_id) {
                $db->query("INSERT IGNORE INTO ".MAIN_DB_PREFIX."user_rights (entity, fk_user, fk_id) VALUES (1, ".(int)$uid.", ".(int)$right_id.")");
            }

            // --- Step 2: Create vendor record ---
            $v = new VendorFB($db);
            $v->name                = $vendor_name;
            $v->category            = GETPOST('category',            'alphanohtml');
            $v->email               = GETPOST('email',               'alphanohtml');
            $v->phone               = GETPOST('phone',               'alphanohtml');
            $v->contact_person      = GETPOST('contact_person',      'alphanohtml');
            $v->contact_email       = $contact_email;
            $v->contact_phone       = GETPOST('contact_phone',       'alphanohtml');
            $v->address             = GETPOST('address',             'restricthtml');
            $v->registration_number = GETPOST('rc_number',           'alphanohtml');
            $v->tax_id              = GETPOST('tax_id',              'alphanohtml');
            $v->website             = GETPOST('website',             'alphanohtml');
            $v->bank_name           = GETPOST('bank_name',           'alphanohtml');
            $v->bank_account_number = GETPOST('bank_account_number', 'alphanohtml');
            $v->description         = GETPOST('description',         'restricthtml');
            $v->ref                 = GETPOST('ref',                 'alphanohtml');
            $v->status              = GETPOST('status',              'alphanohtml');

            $res = $v->create($user);

            if ($res > 0) {
                // Link user to vendor
                $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_vendors SET fk_user = ".(int)$uid." WHERE rowid = ".(int)$res);

                $db->commit();
                $hide_form = true;
                $new_ref   = $v->ref;
            } else {
                $db->rollback();
                $notice = ['error', 'Error creating vendor: '.dol_escape_htmltag($v->error)];
            }
        }
    }
}

$_SESSION["mainmenu"] = "foodbankcrm";
$_fb_admin_head = '<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">'
              . '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/css/admin_mobile.css">';
llxHeader($_fb_admin_head, 'Register Vendor');
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
.form-group .hint { font-size: 12px; color: #94a3b8; }

.fb-notice { padding: 13px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
.fb-notice.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

.section-divider { border: none; border-top: 1px solid #f1f5f9; margin: 4px 0 22px; }

.success-card { background: #f0fdf4; border: 2px solid #16a34a; border-radius: var(--radius); padding: 40px; text-align: center; margin-bottom: 20px; }
.success-card .s-icon { font-size: 48px; margin-bottom: 14px; }
.success-card h2 { color: #15803d; margin: 0 0 8px; font-size: 20px; font-weight: 800; }
.success-card p { color: #166534; margin: 0 0 20px; font-size: 14px; }
.cred-box { background: #fff; border: 1px solid #bbf7d0; border-radius: 8px; padding: 22px 28px; display: inline-block; min-width: 280px; text-align: left; margin-bottom: 22px; }
.cred-lbl { font-size: 11px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
.cred-val { font-size: 22px; font-weight: 800; color: #1e293b; font-family: monospace; word-break: break-all; }
.warn-copy { color: #dc2626; font-size: 13px; font-weight: 600; margin: 0 0 20px; }
.pending-note { background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: #92400e; margin-bottom: 20px; }
</style>

<div class="fb-wrap">

    <div class="fb-page-header">
        <div>
            <h1>Register Vendor</h1>
            <p>Add a new food supplier with a login account</p>
        </div>
        <div>
            <a href="vendors.php" class="btn-ghost">← Back to Vendors</a>
        </div>
    </div>

    <?php if ($notice) : ?>
    <div class="fb-notice <?php echo $notice[0]; ?>"><?php echo $notice[1]; ?></div>
    <?php endif; ?>

    <?php if ($hide_form) : ?>

    <div class="success-card">
        <div class="s-icon">🏢</div>
        <h2>Vendor Registered!</h2>
        <p>Ref: <strong><?php echo dol_escape_htmltag($new_ref); ?></strong> — A login account has been created. Share these credentials with the vendor.</p>
        <div class="cred-box">
            <div class="cred-lbl" style="margin-bottom:16px;">Vendor Login Credentials</div>
            <div style="margin-bottom:12px;">
                <div class="cred-lbl">Username</div>
                <div class="cred-val"><?php echo dol_escape_htmltag($created_login); ?></div>
            </div>
            <div>
                <div class="cred-lbl">Temporary Password</div>
                <div class="cred-val"><?php echo dol_escape_htmltag($created_password); ?></div>
            </div>
        </div>
        <p class="warn-copy">⚠️ Copy these now — the password cannot be retrieved again.</p>
        <div class="pending-note">The vendor account is <strong>disabled</strong> until approved. Go to the vendor's profile and click <strong>Approve Access</strong> to activate their login.</div>
        <a href="vendors.php" class="btn-primary" style="margin-right:10px;">View All Vendors</a>
        <a href="create_vendor.php" class="btn-ghost">Add Another</a>
    </div>

    <?php else : ?>

    <div class="fb-card">
        <div class="fb-card-head"><h3>Business Details</h3></div>
        <div class="fb-card-body">
            <form method="POST" action="<?php echo basename(__FILE__); ?>">
                <input type="hidden" name="token" value="<?php echo newToken(); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Business Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="name" required placeholder="e.g. ABC Foods Ltd"
                               value="<?php echo dol_escape_htmltag(GETPOST('name', 'alphanohtml')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="">— Select Category —</option>
                            <?php foreach (['Grains','Fresh Produce','Proteins','Packaged','Dairy','Beverages','Logistics','Other'] as $c) : ?>
                            <option value="<?php echo $c; ?>" <?php echo GETPOST('category','alphanohtml')==$c?'selected':''; ?>><?php echo $c; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>RC Number</label>
                        <input type="text" name="rc_number" placeholder="e.g. RC123456"
                               value="<?php echo dol_escape_htmltag(GETPOST('rc_number', 'alphanohtml')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Tax ID (TIN)</label>
                        <input type="text" name="tax_id" placeholder="e.g. 12345678-0001"
                               value="<?php echo dol_escape_htmltag(GETPOST('tax_id', 'alphanohtml')); ?>">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Business Email</label>
                        <input type="email" name="email" placeholder="info@abcfoods.com"
                               value="<?php echo dol_escape_htmltag(GETPOST('email', 'alphanohtml')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Business Phone</label>
                        <input type="text" name="phone" placeholder="01-123456"
                               value="<?php echo dol_escape_htmltag(GETPOST('phone', 'alphanohtml')); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Website</label>
                    <input type="url" name="website" placeholder="https://abcfoods.com"
                           value="<?php echo dol_escape_htmltag(GETPOST('website', 'alphanohtml')); ?>">
                </div>

                <div class="form-group">
                    <label>Office Address</label>
                    <textarea name="address" rows="2" placeholder="Office or warehouse address"><?php echo dol_escape_htmltag(GETPOST('address', 'restricthtml')); ?></textarea>
                </div>

                <hr class="section-divider">
                <p style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#94a3b8; margin:0 0 18px;">Contact Manager</p>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" placeholder="e.g. Mr. John Doe"
                               value="<?php echo dol_escape_htmltag(GETPOST('contact_person', 'alphanohtml')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Direct Phone</label>
                        <input type="text" name="contact_phone" placeholder="080..."
                               value="<?php echo dol_escape_htmltag(GETPOST('contact_phone', 'alphanohtml')); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Contact Email <span style="color:#ef4444;">*</span></label>
                    <input type="email" name="contact_email" required placeholder="john.doe@abcfoods.com"
                           value="<?php echo dol_escape_htmltag(GETPOST('contact_email', 'alphanohtml')); ?>">
                    <span class="hint">Used as the vendor's login email address.</span>
                </div>

                <hr class="section-divider">
                <p style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#94a3b8; margin:0 0 18px;">Banking Details</p>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Bank Name</label>
                        <input type="text" name="bank_name" placeholder="e.g. First Bank"
                               value="<?php echo dol_escape_htmltag(GETPOST('bank_name', 'alphanohtml')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Account Number</label>
                        <input type="text" name="bank_account_number" placeholder="10-digit account number"
                               value="<?php echo dol_escape_htmltag(GETPOST('bank_account_number', 'alphanohtml')); ?>">
                    </div>
                </div>

                <hr class="section-divider">
                <p style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#94a3b8; margin:0 0 18px;">Admin Controls</p>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Initial Status</label>
                        <select name="status">
                            <option value="Pending" selected>Pending (Awaiting Approval)</option>
                            <option value="Active">Active (Approve Immediately)</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                        <span class="hint">Login stays disabled until you approve from the profile page.</span>
                    </div>
                    <div class="form-group">
                        <label>Reference ID</label>
                        <input type="text" name="ref" placeholder="Auto-generated if empty"
                               value="<?php echo dol_escape_htmltag(GETPOST('ref', 'alphanohtml')); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Internal Notes</label>
                    <textarea name="description" rows="3" placeholder="Additional details about this vendor..."><?php echo dol_escape_htmltag(GETPOST('description', 'restricthtml')); ?></textarea>
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="submit" class="btn-primary">Register Vendor</button>
                    <a href="vendors.php" class="btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php endif; ?>

</div>

<?php llxFooter(); ?>
