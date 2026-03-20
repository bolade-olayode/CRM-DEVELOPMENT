<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");

$id = GETPOST('id', 'int');
if (!$id) { header("Location: beneficiaries.php"); exit; }

$b = new Beneficiary($db);
if ($b->fetch($id) < 0) { header("Location: beneficiaries.php"); exit; }

$notice = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security token expired. Please refresh.'];
    } else {
        $errs = [];

        $raw_dob = GETPOST('dob', 'alpha');
        if (!empty($raw_dob)) {
            $parts = explode('-', $raw_dob);
            if (count($parts) != 3 || (int)$parts[0] < 1900 || (int)$parts[0] > (int)date('Y')) {
                $errs[] = 'Invalid birth year (must be between 1900 and '.date('Y').')';
            }
        }

        if (empty($errs)) {
            $b->firstname           = GETPOST('firstname',           'alphanohtml');
            $b->lastname            = GETPOST('lastname',            'alphanohtml');
            $b->email               = GETPOST('email',               'alphanohtml');
            $b->phone               = GETPOST('phone',               'alphanohtml');
            $b->gender              = GETPOST('gender',              'alphanohtml');
            $b->dob                 = $raw_dob;
            $b->identification_number = GETPOST('identification_number', 'alphanohtml');
            $b->address             = GETPOST('address',             'restricthtml');
            $b->city                = GETPOST('city',                'alphanohtml');
            $b->state               = GETPOST('state',               'alphanohtml');
            $b->family_size         = GETPOST('family_size',         'int');
            $b->employment_status   = GETPOST('employment_status',   'alphanohtml');
            $b->subscription_status = GETPOST('subscription_status', 'alphanohtml');
            $b->subscription_type   = GETPOST('subscription_type',   'alphanohtml');
            $b->note                = GETPOST('note',                'restricthtml');

            if ($b->update($user) > 0) {
                $notice = ['success', 'Profile updated successfully.'];
            } else {
                $notice = ['error', 'Database error: '.dol_escape_htmltag($b->error)];
            }
        } else {
            $notice = ['error', implode('<br>', $errs)];
        }
    }
}

// Live subscription tiers
$tiers = [];
$res = $db->query("SELECT tier_type, tier_name, price FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE is_active=1 ORDER BY price ASC");
if ($res) while ($t = $db->fetch_object($res)) $tiers[] = $t;

$_SESSION["mainmenu"] = "foodbankcrm";
$_fb_admin_head = '<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">'
              . '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/css/admin_mobile.css">';
llxHeader($_fb_admin_head, 'Edit Subscriber');
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

.section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #94a3b8; margin: 0 0 18px; }
.section-divider { border: none; border-top: 1px solid #f1f5f9; margin: 4px 0 22px; }

.plan-accent { color: var(--accent); font-size: 12px; }
</style>

<div class="fb-wrap">

    <div class="fb-page-header">
        <div>
            <h1>Edit Subscriber</h1>
            <p>Ref: <strong><?php echo dol_escape_htmltag($b->ref); ?></strong> &middot; <?php echo dol_escape_htmltag(trim($b->firstname.' '.$b->lastname)); ?></p>
        </div>
        <div>
            <a href="view_beneficiary.php?id=<?php echo $b->id; ?>" class="btn-ghost">← View Profile</a>
            <a href="beneficiaries.php" class="btn-ghost">All Subscribers</a>
        </div>
    </div>

    <?php if ($notice) : ?>
    <div class="fb-notice <?php echo $notice[0]; ?>"><?php echo $notice[1]; ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo basename(__FILE__).'?id='.(int)$b->id; ?>">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">

        <!-- Personal Information -->
        <div class="fb-card">
            <div class="fb-card-head"><h3>Personal Information</h3></div>
            <div class="fb-card-body">

                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="firstname" required value="<?php echo dol_escape_htmltag($b->firstname); ?>">
                    </div>
                    <div class="form-group">
                        <label>Last Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lastname" required value="<?php echo dol_escape_htmltag($b->lastname); ?>">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo dol_escape_htmltag($b->email); ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" value="<?php echo dol_escape_htmltag($b->phone); ?>">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">— Select —</option>
                            <option value="Male"   <?php echo $b->gender=='Male'  ?'selected':''; ?>>Male</option>
                            <option value="Female" <?php echo $b->gender=='Female'?'selected':''; ?>>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo dol_escape_htmltag($b->dob); ?>">
                    </div>
                </div>

                <div class="form-group" style="max-width:400px;">
                    <label>NIN / ID Number</label>
                    <input type="text" name="identification_number" placeholder="National Identity Number"
                           value="<?php echo dol_escape_htmltag($b->identification_number); ?>">
                </div>
            </div>
        </div>

        <!-- Address & Household -->
        <div class="fb-card">
            <div class="fb-card-head"><h3>Address &amp; Household</h3></div>
            <div class="fb-card-body">

                <div class="form-group">
                    <label>Street Address</label>
                    <textarea name="address" rows="2"><?php echo dol_escape_htmltag($b->address); ?></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>City / LGA</label>
                        <input type="text" name="city" value="<?php echo dol_escape_htmltag($b->city); ?>">
                    </div>
                    <div class="form-group">
                        <label>State</label>
                        <input type="text" name="state" value="<?php echo dol_escape_htmltag($b->state); ?>">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Family Size</label>
                        <input type="number" name="family_size" min="1" value="<?php echo (int)$b->family_size ?: 1; ?>">
                    </div>
                    <div class="form-group">
                        <label>Employment Status</label>
                        <select name="employment_status">
                            <option value="">— Select —</option>
                            <?php foreach (['Employed','Unemployed','Self-Employed','Student','Retired'] as $opt) : ?>
                            <option value="<?php echo $opt; ?>" <?php echo $b->employment_status==$opt?'selected':''; ?>><?php echo $opt; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscription Management -->
        <div class="fb-card">
            <div class="fb-card-head"><h3>Subscription Management</h3></div>
            <div class="fb-card-body">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Subscription Status</label>
                        <select name="subscription_status">
                            <?php foreach (['Pending','Active','Inactive','Expired'] as $st) : ?>
                            <option value="<?php echo $st; ?>" <?php echo $b->subscription_status==$st?'selected':''; ?>><?php echo $st; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Active Plan</label>
                        <select name="subscription_type">
                            <option value="">— No Plan —</option>
                            <?php foreach ($tiers as $tier) : ?>
                            <option value="<?php echo dol_escape_htmltag($tier->tier_type); ?>"
                                    <?php echo $b->subscription_type==$tier->tier_type?'selected':''; ?>>
                                <?php echo dol_escape_htmltag($tier->tier_name); ?> (<?php echo price($tier->price); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Admin Internal Notes</label>
                    <textarea name="note" rows="3" placeholder="Private notes visible only to admins..."><?php echo dol_escape_htmltag($b->note); ?></textarea>
                </div>

                <hr class="section-divider">
                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <a href="view_beneficiary.php?id=<?php echo $b->id; ?>" class="btn-ghost">Cancel</a>
                </div>
            </div>
        </div>

    </form>

</div>

<?php llxFooter(); ?>
