<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");

$notice           = [];
$hide_form        = false;
$created_login    = '';
$created_password = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed: invalid CSRF token.'];
    } else {
        $db->begin();

        $firstname = GETPOST('firstname', 'alphanohtml');
        $lastname  = GETPOST('lastname',  'alphanohtml');
        $email     = GETPOST('email',     'alphanohtml');
        $phone     = GETPOST('phone',     'alphanohtml');

        // --- Step 1: Create Dolibarr user account ---
        require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
        $newuser = new User($db);

        $base_login = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstname.$lastname));
        if (empty($base_login)) $base_login = 'subscriber';
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

        $newuser->login     = $created_login;
        $newuser->email     = $email;
        $newuser->firstname = $firstname;
        $newuser->lastname  = $lastname;
        $newuser->pass      = $created_password;
        $newuser->statut    = 1;

        $uid = $newuser->create($user);

        if ($uid <= 0) {
            $db->rollback();
            $notice = ['error', 'Could not create user account: '.dol_escape_htmltag($newuser->error)];
        } else {
            // Grant beneficiary permissions
            foreach ([100001, 100021, 100022] as $right_id) {
                $db->query("INSERT IGNORE INTO ".MAIN_DB_PREFIX."user_rights (entity, fk_user, fk_id) VALUES (1, ".(int)$uid.", ".(int)$right_id.")");
            }

            // --- Step 2: Create beneficiary profile ---
            $b                       = new Beneficiary($db);
            $b->ref                  = GETPOST('ref',          'alphanohtml');
            $b->firstname            = $firstname;
            $b->lastname             = $lastname;
            $b->email                = $email;
            $b->phone                = $phone;
            $b->address              = GETPOST('address',       'restricthtml');
            $b->household_size       = (int)GETPOST('household_size', 'int');
            $b->note                 = GETPOST('note',          'restricthtml');
            $b->subscription_status  = GETPOST('subscription_status', 'alphanohtml') ?: 'Pending';

            $bid = $b->create($user);

            if ($bid > 0) {
                // Link user to beneficiary
                $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET fk_user = ".(int)$uid." WHERE rowid = ".(int)$bid);

                // Apply selected subscription tier
                $tier_id = GETPOST('subscription_tier', 'int');
                if ($tier_id > 0) {
                    $tier_res = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE rowid = ".(int)$tier_id);
                    if ($tier_res && $tier = $db->fetch_object($tier_res)) {
                        $start_date = date('Y-m-d');
                        $end_date   = date('Y-m-d', strtotime('+'.$tier->duration_months.' months'));
                        $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET
                                    subscription_type = '".$db->escape($tier->tier_type)."',
                                    subscription_status = '".$db->escape($b->subscription_status)."',
                                    subscription_start_date = '".$start_date."',
                                    subscription_end_date = '".$end_date."',
                                    subscription_fee = ".(float)$tier->price."
                                    WHERE rowid = ".(int)$bid);
                    }
                }

                $db->commit();
                $hide_form = true;
            } else {
                $db->rollback();
                $notice = ['error', 'Error creating subscriber profile: '.dol_escape_htmltag($b->error)];
            }
        }
    }
}

// Load active subscription tiers
$subscription_tiers = [];
$res = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE is_active=1 ORDER BY price ASC");
if ($res) while ($obj = $db->fetch_object($res)) $subscription_tiers[] = $obj;

llxHeader('', 'Create Subscriber');
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

/* Plan selection cards */
.plan-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 14px; margin-top: 10px; }
.plan-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
.plan-card { display: block; background: #fff; border: 2px solid #e2e8f0; border-radius: 10px; padding: 22px 20px; text-align: center; cursor: pointer; transition: all .2s; position: relative; }
.plan-card:hover { border-color: var(--accent); transform: translateY(-2px); box-shadow: var(--shadow); }
.plan-option input[type="radio"]:checked + .plan-card { border-color: var(--accent); background: var(--accent-light); box-shadow: 0 0 0 1px var(--accent); }
.plan-check { position: absolute; top: 10px; right: 10px; width: 20px; height: 20px; background: var(--accent); color: #fff; border-radius: 50%; display: none; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; }
.plan-option input[type="radio"]:checked + .plan-card .plan-check { display: flex; }
.plan-name     { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 8px; display: block; font-weight: 700; }
.plan-price    { font-size: 26px; font-weight: 800; color: #1e293b; margin-bottom: 4px; display: block; }
.plan-duration { font-size: 12px; color: #94a3b8; margin-bottom: 12px; display: block; }
.plan-feature  { font-size: 12px; color: #475569; border-top: 1px solid #e2e8f0; padding-top: 10px; margin-top: 8px; display: block; }

/* Success / credentials */
.success-card { background: #f0fdf4; border: 2px solid #16a34a; border-radius: var(--radius); padding: 40px; text-align: center; margin-bottom: 20px; }
.success-card .s-icon { font-size: 48px; margin-bottom: 14px; }
.success-card h2 { color: #15803d; margin: 0 0 8px; font-size: 20px; font-weight: 800; }
.success-card p { color: #166534; margin: 0 0 20px; font-size: 14px; }
.cred-box { background: #fff; border: 1px solid #bbf7d0; border-radius: 8px; padding: 22px 28px; display: inline-block; min-width: 280px; text-align: left; margin-bottom: 22px; }
.cred-label { font-size: 11px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
.cred-value { font-size: 22px; font-weight: 800; color: #1e293b; font-family: monospace; }
.warn-copy { color: #dc2626; font-size: 13px; font-weight: 600; margin: 0 0 20px; }

.info-box { background: #eff6ff; border-radius: var(--radius); border-left: 4px solid var(--accent); padding: 18px 22px; margin-top: 20px; }
.info-box h4 { margin: 0 0 8px; font-size: 14px; color: #1e40af; }
.info-box ul { margin: 0; padding-left: 18px; color: #1e40af; font-size: 13px; line-height: 1.8; }

.section-divider { border: none; border-top: 1px solid #f1f5f9; margin: 4px 0 22px; }
</style>

<div class="fb-wrap">

    <div class="fb-page-header">
        <div>
            <h1>New Subscriber</h1>
            <p>Create a beneficiary account with login credentials</p>
        </div>
        <div>
            <a href="beneficiaries.php" class="btn-ghost">← Back to Subscribers</a>
        </div>
    </div>

    <?php if ($notice) : ?>
    <div class="fb-notice <?php echo $notice[0]; ?>"><?php echo $notice[1]; ?></div>
    <?php endif; ?>

    <?php if ($hide_form && $created_login) : ?>

    <!-- Success + Credentials -->
    <div class="success-card">
        <div class="s-icon">✅</div>
        <h2>Subscriber Created Successfully!</h2>
        <p>A Dolibarr login account has been created. Share these credentials with the subscriber.</p>
        <div class="cred-box">
            <div class="cred-label" style="margin-bottom:14px;">Login Credentials</div>
            <div style="margin-bottom:12px;">
                <div class="cred-label">Username</div>
                <div class="cred-value"><?php echo dol_escape_htmltag($created_login); ?></div>
            </div>
            <div>
                <div class="cred-label">Temporary Password</div>
                <div class="cred-value"><?php echo dol_escape_htmltag($created_password); ?></div>
            </div>
        </div>
        <p class="warn-copy">⚠️ Copy these credentials now — the password cannot be retrieved again.</p>
        <a href="beneficiaries.php" class="btn-primary" style="margin-right:10px;">View All Subscribers</a>
        <a href="create_beneficiary.php" class="btn-ghost">Add Another</a>
    </div>

    <?php else : ?>

    <!-- Personal Information -->
    <div class="fb-card">
        <div class="fb-card-head"><h3>Personal Information</h3></div>
        <div class="fb-card-body">
            <form method="POST" action="<?php echo basename(__FILE__); ?>">
                <input type="hidden" name="token" value="<?php echo newToken(); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="firstname" required placeholder="e.g. John"
                               value="<?php echo dol_escape_htmltag(GETPOST('firstname', 'alphanohtml')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Last Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lastname" required placeholder="e.g. Doe"
                               value="<?php echo dol_escape_htmltag(GETPOST('lastname', 'alphanohtml')); ?>">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="john@example.com"
                               value="<?php echo dol_escape_htmltag(GETPOST('email', 'alphanohtml')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" placeholder="080..."
                               value="<?php echo dol_escape_htmltag(GETPOST('phone', 'alphanohtml')); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="2" placeholder="Residential address"><?php echo dol_escape_htmltag(GETPOST('address', 'restricthtml')); ?></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Household Size</label>
                        <input type="number" name="household_size" min="1"
                               value="<?php echo (int)GETPOST('household_size','int') ?: 1; ?>">
                    </div>
                    <div class="form-group">
                        <label>Reference ID</label>
                        <input type="text" name="ref" placeholder="Auto-generated if empty"
                               value="<?php echo dol_escape_htmltag(GETPOST('ref', 'alphanohtml')); ?>">
                        <span class="hint">e.g. BEN2025-0001</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Internal Notes</label>
                    <textarea name="note" rows="2" placeholder="Admin notes (not visible to subscriber)..."><?php echo dol_escape_htmltag(GETPOST('note', 'restricthtml')); ?></textarea>
                </div>

                <hr class="section-divider">
                <p style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#94a3b8; margin:0 0 18px;">Subscription Plan</p>

                <div class="form-group" style="max-width:280px;">
                    <label>Initial Status</label>
                    <select name="subscription_status">
                        <option value="Pending" selected>Pending (Awaiting Payment)</option>
                        <option value="Active">Active (Payment Received)</option>
                    </select>
                </div>

                <?php if (!empty($subscription_tiers)) : ?>
                <div class="plan-grid">
                    <?php foreach ($subscription_tiers as $tier) : ?>
                    <label class="plan-option">
                        <input type="radio" name="subscription_tier" value="<?php echo $tier->rowid; ?>" required>
                        <div class="plan-card">
                            <div class="plan-check">✓</div>
                            <span class="plan-name"><?php echo dol_escape_htmltag($tier->tier_name); ?></span>
                            <span class="plan-price">₦<?php echo number_format($tier->price); ?></span>
                            <span class="plan-duration">per <?php echo $tier->duration_months; ?> month<?php echo $tier->duration_months != 1 ? 's' : ''; ?></span>
                            <?php if (!empty($tier->description)) : ?>
                            <span class="plan-feature"><?php echo dol_escape_htmltag($tier->description); ?></span>
                            <?php endif; ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <div style="padding:14px 18px; background:#fef3c7; color:#92400e; border-radius:8px; font-size:13px; border:1px solid #fde68a;">
                    No subscription tiers found. <a href="create_subscription_tier.php" style="color:var(--accent); font-weight:600;">Create one first.</a>
                </div>
                <?php endif; ?>

                <div style="display:flex; gap:10px; margin-top:28px;">
                    <button type="submit" class="btn-primary">Create Subscriber</button>
                    <a href="beneficiaries.php" class="btn-ghost">Cancel</a>
                </div>

            </form>
        </div>
    </div>

    <div class="info-box">
        <h4>💡 Status Guide</h4>
        <ul>
            <li><strong>Pending</strong> — Waiting for payment. Subscriber can log in but access may be limited.</li>
            <li><strong>Active</strong> — Payment received. Full access granted immediately.</li>
        </ul>
    </div>

    <?php endif; ?>

</div>

<?php llxFooter(); ?>
