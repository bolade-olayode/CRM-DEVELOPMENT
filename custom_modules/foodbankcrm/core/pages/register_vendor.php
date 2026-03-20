<?php
/**
 * VENDOR REGISTRATION (FIXED & FINAL)
 * Fixes: SQL Column 'date_creation' mismatch
 */
define('NOCSRFCHECK', 1);
define('NOTOKENRENEWAL', 1);
define('NOLOGIN', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

$error = '';
$action = GETPOST('action', 'alpha');
$ip_address = $_SERVER['REMOTE_ADDR'];
$from_app = GETPOST('from_app', 'alpha') === '1' ? '1' : '';

// --- 1. RATE LIMITER ---
if ($action == 'register') {
    $time_window = 600;
    $max_attempts = 10;

    $res_check = $db->query("SHOW TABLES LIKE '".MAIN_DB_PREFIX."foodbank_rate_limit'");
    if ($res_check && $db->num_rows($res_check) > 0) {
        $db->query("DELETE FROM " . MAIN_DB_PREFIX . "foodbank_rate_limit WHERE attempt_time < (NOW() - INTERVAL $time_window SECOND)");
        $sql_limit = "SELECT COUNT(*) as cnt FROM " . MAIN_DB_PREFIX . "foodbank_rate_limit WHERE ip_address = '".$db->escape($ip_address)."' AND action_type = 'vendor_register'";
        $res_limit = $db->query($sql_limit);
        $obj_limit = $db->fetch_object($res_limit);

        if ($obj_limit->cnt >= $max_attempts) {
            $error = "Too many attempts. Please wait 10 minutes.";
        } else {
            $db->query("INSERT INTO " . MAIN_DB_PREFIX . "foodbank_rate_limit (ip_address, action_type) VALUES ('".$db->escape($ip_address)."', 'vendor_register')");
        }
    }
}

// --- 2. REGISTRATION LOGIC ---
if ($action == 'register' && empty($error)) {
    $db->begin();

    $username       = GETPOST('username', 'alpha');
    $pass           = GETPOST('password', 'none');
    $contact_person = GETPOST('contact_person', 'alpha');
    $email          = GETPOST('email', 'alpha');
    $phone          = GETPOST('phone', 'alpha');
    $business_name  = GETPOST('business_name', 'alpha');
    $rc_number      = GETPOST('rc_number', 'alpha');
    $tax_id         = GETPOST('tax_id', 'alpha');
    $category       = GETPOST('category', 'alpha');
    $website        = GETPOST('website', 'alpha');
    $address        = GETPOST('address', 'restricthtml');
    $bank_name      = GETPOST('bank_name', 'alpha');
    $account_no     = GETPOST('account_no', 'alpha');

    if (empty($username) || empty($email) || empty($pass) || empty($business_name) || empty($category)) {
        $error = "Please fill in all required fields (including Category).";
        $db->rollback();
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        $error = "Username invalid. Use letters and numbers only.";
        $db->rollback();
    } else {
        $newuser = new User($db);
        $newuser->login = $username;
        $newuser->email = $email;
        $newuser->firstname = $contact_person;
        $newuser->lastname  = "(Vendor)";
        $newuser->pass = $pass;
        $newuser->statut = 0;

        $check_login = new User($db);
        if ($check_login->fetch('', $username) > 0) {
            $error = "Username '$username' is already taken.";
            $db->rollback();
        } else {
            $sql_email = "SELECT rowid FROM ".MAIN_DB_PREFIX."user WHERE email = '".$db->escape($email)."'";
            $res_email = $db->query($sql_email);
            if ($res_email && $db->num_rows($res_email) > 0) {
                $error = "Email address already registered.";
                $db->rollback();
            } else {
                $uid = $newuser->create($user);

                if ($uid > 0) {
                    $ref = 'VEND-' . date('ym') . '-' . str_pad($uid, 4, '0', STR_PAD_LEFT);

                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "foodbank_vendors
                            (fk_user, ref, name, category, contact_person, contact_email, contact_phone,
                             address, registration_number, tax_id, website, bank_name, bank_account_number, date_creation, status)
                            VALUES
                            (" . (int)$uid . ", '$ref',
                            '" . $db->escape($business_name) . "', '" . $db->escape($category) . "',
                            '" . $db->escape($contact_person) . "', '" . $db->escape($email) . "',
                            '" . $db->escape($phone) . "', '" . $db->escape($address) . "',
                            '" . $db->escape($rc_number) . "', '" . $db->escape($tax_id) . "',
                            '" . $db->escape($website) . "', '" . $db->escape($bank_name) . "',
                            '" . $db->escape($account_no) . "', NOW(), 'Pending')";

                    if ($db->query($sql)) {
                        foreach (array(100001, 100011, 100012, 100013) as $right_id) {
                            $db->query("INSERT IGNORE INTO ".MAIN_DB_PREFIX."user_rights (entity, fk_user, fk_id) VALUES (1, ".(int)$uid.", ".(int)$right_id.")");
                        }

                        $otp = random_int(100000, 999999);
                        $db->query("CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."foodbank_email_verification (email VARCHAR(255) PRIMARY KEY, code VARCHAR(10), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
                        $db->query("DELETE FROM " . MAIN_DB_PREFIX . "foodbank_email_verification WHERE email = '".$db->escape($email)."'");
                        $db->query("INSERT INTO " . MAIN_DB_PREFIX . "foodbank_email_verification (email, code) VALUES ('".$db->escape($email)."', '$otp')");

                        $db->commit();

                        $subject = "Verify Vendor Account - Foodbank CRM";
                        $msg = "Hello $contact_person,\n\nYour Vendor verification code is: $otp\n\nThank you.";
                        $from = !empty($conf->global->MAIN_MAIL_EMAIL_FROM) ? $conf->global->MAIN_MAIL_EMAIL_FROM : 'no-reply@foodbank.com';
                        $mail = new CMailFile($subject, $email, $from, $msg);
                        $mail->sendfile();

                        $app_params = $from_app === '1' ? '&from_app=1&role=vendor' : '';
                        header("Location: verify_otp.php?email=".urlencode($email).$app_params);
                        exit;
                    } else {
                        $db->rollback();
                        dol_syslog("Vendor Registration DB Error: " . $db->lasterror(), LOG_ERR);
                        $error = "We encountered a technical error. Please contact support.";
                    }
                } else {
                    $db->rollback();
                    dol_syslog("Vendor User Creation Error: " . $newuser->error, LOG_ERR);
                    $error = "Account creation failed. Please ensure your password meets the complexity requirements.";
                }
            }
        }
    }
}

$base       = DOL_URL_ROOT . '/custom/foodbankcrm';
$logo       = $base . '/img/logo.png';
$favicon    = $base . '/img/favicon.png';
$land_url   = $base . '/index.php';
$sub_url    = 'register.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Partner Registration | Foodbank CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo $favicon; ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --amber:       #d97706;
            --amber-dark:  #b45309;
            --amber-deep:  #78350f;
            --amber-faint: #fffbeb;
            --amber-mid:   #fde68a;
            --teal:        #0d9488;
            --text:        #111827;
            --text-2:      #374151;
            --muted:       #6b7280;
            --border:      #e5e7eb;
            --surface:     #f9fafb;
        }
        body {
            background: var(--surface);
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ── TOP BAR ── */
        .topbar {
            background: #fff; border-bottom: 1px solid var(--border);
            padding: 0 5%; height: 64px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar-logo img { height: 40px; display: block; }
        .topbar-links { display: flex; align-items: center; gap: 16px; font-size: 14px; }
        .topbar-links a { color: var(--muted); text-decoration: none; }
        .topbar-links a:hover { color: var(--amber); }
        .topbar-links .sep { color: var(--border); }

        /* ── HERO ── */
        .page-hero {
            background: linear-gradient(135deg, var(--amber-deep) 0%, var(--amber) 100%);
            padding: 52px 5% 72px; text-align: center;
        }
        .page-hero h1 {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: clamp(32px, 5vw, 52px);
            font-weight: 700; color: #fff;
            letter-spacing: -1.5px; line-height: 1.1; margin-bottom: 14px;
        }
        .page-hero h1 em { font-style: italic; color: var(--amber-mid); }
        .page-hero p { font-size: 16px; color: rgba(255,255,255,0.65); max-width: 480px; margin: 0 auto; line-height: 1.65; }
        .pending-notice {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
            color: rgba(255,255,255,0.9); border-radius: 30px;
            padding: 7px 18px; font-size: 13px; font-weight: 600; margin-bottom: 20px;
        }

        /* ── MAIN WRAPPER ── */
        .reg-wrap { max-width: 820px; margin: -32px auto 60px; padding: 0 20px; position: relative; z-index: 10; }

        /* ── FORM CARD ── */
        .form-card {
            background: #fff; border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.07);
            border: 1px solid var(--border); overflow: hidden;
        }

        .section-header {
            padding: 14px 32px;
            background: var(--amber-faint); border-bottom: 1px solid var(--amber-mid);
            display: flex; align-items: center; gap: 10px;
        }
        .section-header-num {
            width: 24px; height: 24px; border-radius: 50%;
            background: var(--amber); color: #fff;
            font-size: 12px; font-weight: 700;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .section-header h3 {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: 18px; font-weight: 600; color: var(--amber-deep); letter-spacing: -.2px;
        }

        .form-body { padding: 28px 32px; border-bottom: 1px solid var(--surface); }
        .form-body:last-of-type { border-bottom: none; }

        .error-banner {
            background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;
            border-radius: 10px; padding: 14px 18px; margin: 24px 32px 0;
            display: flex; align-items: flex-start; gap: 10px; font-size: 14px;
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group:last-child { margin-bottom: 0; }
        .form-group label {
            display: block; margin-bottom: 7px;
            font-size: 13px; font-weight: 600; color: var(--text-2);
        }
        .form-group label .req { color: var(--amber); margin-left: 2px; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 11px 14px;
            border: 1.5px solid var(--border); border-radius: 9px;
            font-size: 15px; font-family: 'Inter', sans-serif;
            color: var(--text); background: #fff;
            transition: border-color .15s, box-shadow .15s; outline: none;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus { border-color: var(--amber); box-shadow: 0 0 0 3px rgba(217,119,6,0.1); }
        .form-group input.invalid { border-color: #ef4444; background: #fef2f2; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .field-error { font-size: 12px; color: #ef4444; margin-top: 5px; display: none; }

        /* Password */
        .password-wrap { position: relative; }
        .pw-toggle {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: var(--muted); user-select: none;
            background: none; border: none; padding: 0; font-size: 16px;
        }
        .strength-track { height: 3px; background: var(--border); border-radius: 2px; margin-top: 8px; overflow: hidden; }
        .strength-fill  { height: 100%; width: 0; border-radius: 2px; transition: width .3s, background-color .3s; }
        .strength-lbl   { font-size: 11px; color: var(--muted); margin-top: 4px; text-align: right; height: 14px; }

        /* Submit zone */
        .submit-zone { padding: 28px 32px; }
        .btn-register {
            width: 100%; padding: 15px;
            background: var(--amber); color: #fff;
            border: none; border-radius: 10px;
            font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 700;
            cursor: pointer; transition: background .2s, transform .1s;
        }
        .btn-register:hover { background: var(--amber-dark); }
        .btn-register:active { transform: scale(.98); }
        .btn-register:disabled { opacity: .5; cursor: not-allowed; }
        .form-footer { text-align: center; margin-top: 14px; font-size: 14px; color: var(--muted); }
        .form-footer a { color: var(--amber); text-decoration: none; font-weight: 600; }
        .form-footer a:hover { text-decoration: underline; }

        .sub-cta {
            margin-top: 14px; padding: 18px 24px;
            background: #f0fdfa; border: 1px solid #ccfbf1; border-radius: 12px;
            text-align: center; font-size: 14px; color: #134e4a;
        }
        .sub-cta a { color: var(--teal); font-weight: 700; text-decoration: none; }
        .sub-cta a:hover { text-decoration: underline; }

        @media (max-width: 640px) {
            .page-hero { padding: 40px 5% 64px; }
            .reg-wrap { margin-top: -24px; padding: 0 14px; }
            .form-row { grid-template-columns: 1fr; }
            .form-body { padding: 20px 18px; }
            .section-header { padding: 12px 18px; }
            .submit-zone { padding: 20px 18px; }
            .error-banner { margin: 20px 18px 0; }
        }
    </style>
</head>
<body>

<!-- Top bar -->
<div class="topbar">
    <a href="<?php echo $land_url; ?>" class="topbar-logo">
        <img src="<?php echo $logo; ?>" alt="Foodbank CRM">
    </a>
    <div class="topbar-links">
        <a href="<?php echo $land_url; ?>">← Back to Home</a>
        <span class="sep">·</span>
        <a href="<?php echo $sub_url; ?>" style="color:var(--teal)">Register as Subscriber</a>
    </div>
</div>

<!-- Hero -->
<div class="page-hero">
    <div class="pending-notice">⏳ Applications are reviewed within 24–48 hours</div>
    <h1>Become a<br><em>Vendor Partner</em></h1>
    <p>Join our network of trusted food suppliers. List your products, receive orders, and grow your reach within the community.</p>
</div>

<!-- Main content -->
<div class="reg-wrap">

    <?php if ($error): ?>
    <div class="error-banner" id="errorBanner" style="margin: 0 0 16px;">
        <span>⚠️</span>
        <div><?php echo $error; ?></div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('errorBanner').scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    </script>
    <?php endif; ?>

    <div class="form-card">

        <form method="POST" id="vendorForm">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="from_app" value="<?php echo htmlspecialchars($from_app); ?>">

            <!-- Section 1: Business -->
            <div class="section-header">
                <div class="section-header-num">1</div>
                <h3>Business Information</h3>
            </div>
            <div class="form-body">
                <div class="form-group">
                    <label>Registered Business Name <span class="req">*</span></label>
                    <input type="text" name="business_name" placeholder="e.g. Global Foods Ltd." value="<?php echo dol_escape_htmltag(GETPOST('business_name')); ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>RC / Registration Number <span class="req">*</span></label>
                        <input type="text" name="rc_number" value="<?php echo dol_escape_htmltag(GETPOST('rc_number')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tax ID (TIN)</label>
                        <input type="text" name="tax_id" value="<?php echo dol_escape_htmltag(GETPOST('tax_id')); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Primary Supply Category <span class="req">*</span></label>
                        <select name="category" required>
                            <option value="">Select category...</option>
                            <option value="Grains"         <?php if (GETPOST('category')=='Grains') echo 'selected'; ?>>Grains &amp; Staples</option>
                            <option value="Fresh Produce"  <?php if (GETPOST('category')=='Fresh Produce') echo 'selected'; ?>>Fresh Produce</option>
                            <option value="Proteins"       <?php if (GETPOST('category')=='Proteins') echo 'selected'; ?>>Meat &amp; Poultry</option>
                            <option value="Packaged"       <?php if (GETPOST('category')=='Packaged') echo 'selected'; ?>>Packaged Goods</option>
                            <option value="Logistics"      <?php if (GETPOST('category')=='Logistics') echo 'selected'; ?>>Logistics Provider</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Website (Optional)</label>
                        <input type="url" name="website" placeholder="https://..." value="<?php echo dol_escape_htmltag(GETPOST('website')); ?>">
                    </div>
                </div>
            </div>

            <!-- Section 2: Contact -->
            <div class="section-header">
                <div class="section-header-num">2</div>
                <h3>Contact Details</h3>
            </div>
            <div class="form-body">
                <div class="form-group">
                    <label>Contact Person <span class="req">*</span></label>
                    <input type="text" name="contact_person" value="<?php echo dol_escape_htmltag(GETPOST('contact_person')); ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Business Email <span class="req">*</span></label>
                        <input type="email" name="email" value="<?php echo dol_escape_htmltag(GETPOST('email')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Direct Phone <span class="req">*</span></label>
                        <input type="tel" name="phone" value="<?php echo dol_escape_htmltag(GETPOST('phone')); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Office Address <span class="req">*</span></label>
                    <textarea name="address" rows="2" required><?php echo dol_escape_htmltag(GETPOST('address')); ?></textarea>
                </div>
            </div>

            <!-- Section 3: Banking -->
            <div class="section-header">
                <div class="section-header-num">3</div>
                <h3>Banking Information</h3>
            </div>
            <div class="form-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Bank Name</label>
                        <input type="text" name="bank_name" placeholder="e.g. Zenith Bank" value="<?php echo dol_escape_htmltag(GETPOST('bank_name')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Account Number</label>
                        <input type="text" name="account_no" value="<?php echo dol_escape_htmltag(GETPOST('account_no')); ?>">
                    </div>
                </div>
            </div>

            <!-- Section 4: Account Security -->
            <div class="section-header">
                <div class="section-header-num">4</div>
                <h3>Account &amp; Login</h3>
            </div>
            <div class="form-body">
                <div class="form-group">
                    <label>Username <span class="req">*</span></label>
                    <input type="text" name="username" placeholder="e.g. GlobalFoodsLtd"
                           value="<?php echo dol_escape_htmltag(GETPOST('username')); ?>"
                           required autocomplete="username" onkeyup="validateUsername(this)">
                    <div class="field-error" id="userMsg">Only letters, numbers, underscores, and dashes allowed.</div>
                </div>
                <div class="form-group">
                    <label>Password <span class="req">*</span></label>
                    <div class="password-wrap">
                        <input type="password" id="passwordInput" name="password" required minlength="6"
                               autocomplete="new-password" onkeyup="checkStrength(this.value)">
                        <button type="button" class="pw-toggle" onclick="togglePassword()">👁</button>
                    </div>
                    <div class="strength-track"><div id="strengthBar" class="strength-fill"></div></div>
                    <div id="strengthText" class="strength-lbl"></div>
                </div>
            </div>

            <!-- Submit -->
            <div class="submit-zone">
                <button type="submit" id="submitBtn" class="btn-register">Submit Vendor Application →</button>
                <div class="form-footer">
                    Already have an account? <a href="<?php echo DOL_URL_ROOT; ?>/index.php">Sign in here</a>
                </div>
            </div>

        </form>
    </div>

    <div class="sub-cta">
        Looking to receive food as a beneficiary? <a href="<?php echo $sub_url; ?>">Subscribe as a Beneficiary instead →</a>
    </div>

</div>

<script>
function validateUsername(input) {
    const msg = document.getElementById('userMsg');
    const btn = document.getElementById('submitBtn');
    const ok  = /^[a-zA-Z0-9_-]*$/.test(input.value);
    input.classList.toggle('invalid', !ok && input.value.length > 0);
    msg.style.display = (!ok && input.value.length > 0) ? 'block' : 'none';
    btn.disabled = !ok && input.value.length > 0;
    btn.style.opacity = btn.disabled ? '0.5' : '1';
}

function togglePassword() {
    const input = document.getElementById('passwordInput');
    const btn   = document.querySelector('.pw-toggle');
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.textContent = isHidden ? '🙈' : '👁';
}

function checkStrength(password) {
    const bar = document.getElementById('strengthBar');
    const txt = document.getElementById('strengthText');
    let s = 0;
    if (password.length >= 6) s++;
    if (password.length >= 8) s++;
    if (/[A-Z]/.test(password)) s++;
    if (/[0-9]/.test(password)) s++;
    if (/[^A-Za-z0-9]/.test(password)) s++;
    const map = [null,
        ['#ef4444','20%','Too weak'],['#f97316','40%','Weak'],
        ['#eab308','60%','Medium'], ['#84cc16','80%','Strong'],
        ['#22c55e','100%','Very strong']
    ];
    const e = map[s] || ['#e2e8f0','0%',''];
    bar.style.backgroundColor = password.length ? e[0] : '#e2e8f0';
    bar.style.width = password.length ? e[1] : '0%';
    txt.textContent = password.length ? e[2] : '';
    txt.style.color = e[0];
}
</script>

</body>
</html>
