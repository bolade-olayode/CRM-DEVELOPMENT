<?php
/**
 * SAAS REGISTRATION PAGE (FINAL PRODUCTION)
 * Fixes: 'date_creation' bug + Hides Raw SQL Errors
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
        $sql_limit = "SELECT COUNT(*) as cnt FROM " . MAIN_DB_PREFIX . "foodbank_rate_limit WHERE ip_address = '".$db->escape($ip_address)."' AND action_type = 'register'";
        $res_limit = $db->query($sql_limit);
        $obj_limit = $db->fetch_object($res_limit);

        if ($obj_limit->cnt >= $max_attempts) {
            $error = "Too many attempts. Please wait 10 minutes.";
        } else {
            $db->query("INSERT INTO " . MAIN_DB_PREFIX . "foodbank_rate_limit (ip_address, action_type) VALUES ('".$db->escape($ip_address)."', 'register')");
        }
    }
}

// --- 2. REGISTRATION LOGIC ---
if ($action == 'register' && empty($error)) {
    $db->begin();

    $username = GETPOST('username', 'alpha');
    $email    = GETPOST('email', 'alpha');
    $pass     = GETPOST('password', 'none');
    $fname    = GETPOST('firstname', 'alpha');
    $lname    = GETPOST('lastname', 'alpha');
    $phone    = GETPOST('phone', 'alpha');
    $plan     = GETPOST('selected_plan', 'alpha');

    if (empty($username) || empty($email) || empty($pass) || empty($plan)) {
        $error = "Please fill in all required fields.";
        $db->rollback();
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        $error = "Username contains invalid characters. Use letters and numbers only.";
        $db->rollback();
    } else {
        $newuser = new User($db);
        $newuser->login = $username;
        $newuser->email = $email;
        $newuser->firstname = $fname;
        $newuser->lastname  = $lname;
        $newuser->pass = $pass;
        $newuser->statut = 0;

        $check_login = new User($db);
        if ($check_login->fetch('', $username) > 0) {
            $error = "Username is already taken. Please choose another.";
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
                    $ref = 'SUB-' . date('ym') . '-' . str_pad($uid, 4, '0', STR_PAD_LEFT);
                    $start_date = date('Y-m-d');
                    $end_date   = date('Y-m-d', strtotime('+1 year'));

                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "foodbank_beneficiaries
                            (fk_user, ref, firstname, lastname, email, phone,
                             subscription_type, subscription_status, subscription_start_date, subscription_end_date,
                             datec)
                            VALUES
                            (" . (int)$uid . ", '$ref',
                            '" . $db->escape($fname) . "', '" . $db->escape($lname) . "',
                            '" . $db->escape($email) . "', '" . $db->escape($phone) . "',
                            '" . $db->escape($plan) . "', 'Pending', '$start_date', '$end_date',
                            NOW())";

                    if ($db->query($sql)) {
                        foreach (array(100001, 100021, 100022) as $right_id) {
                            $db->query("INSERT IGNORE INTO ".MAIN_DB_PREFIX."user_rights (entity, fk_user, fk_id) VALUES (1, ".(int)$uid.", ".(int)$right_id.")");
                        }

                        $otp = random_int(100000, 999999);
                        $db->query("CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."foodbank_email_verification (email VARCHAR(255) PRIMARY KEY, code VARCHAR(10), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
                        $db->query("DELETE FROM " . MAIN_DB_PREFIX . "foodbank_email_verification WHERE email = '".$db->escape($email)."'");
                        $db->query("INSERT INTO " . MAIN_DB_PREFIX . "foodbank_email_verification (email, code) VALUES ('".$db->escape($email)."', '$otp')");

                        $db->commit();

                        require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/foodbank_mailer.class.php';
                        FoodbankMailer::sendOtpEmail($email, $fname, $otp);
                        FoodbankMailer::sendAdminNewRegistration('subscriber', $fname.' '.$lname, $email);

                        $app_params = $from_app === '1' ? '&from_app=1&role=beneficiary' : '';
                        header("Location: verify_otp.php?email=".urlencode($email).$app_params);
                        exit;
                    } else {
                        $db->rollback();
                        dol_syslog("Registration DB Error: " . $db->lasterror(), LOG_ERR);
                        $error = "We encountered a technical error while creating your profile. Please contact support.";
                    }
                } else {
                    $db->rollback();
                    dol_syslog("Registration User Error: " . $newuser->error, LOG_ERR);
                    $error = "Account creation failed. Please ensure your password meets the complexity requirements.";
                }
            }
        }
    }
}

$base      = DOL_URL_ROOT . '/custom/foodbankcrm';
$logo      = $base . '/img/logo.png';
$favicon   = $base . '/img/favicon.png';
$land_url  = $base . '/index.php';
$vendor_url = 'register_vendor.php';

// Pre-load tiers for plan cards
$tiers = [];
$res_t = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE is_active=1 ORDER BY price ASC");
while ($res_t && ($t = $db->fetch_object($res_t))) { $tiers[] = $t; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subscribe | Foodbank CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo $favicon; ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --teal:       #0d9488;
            --teal-dark:  #0f766e;
            --teal-deep:  #134e4a;
            --teal-faint: #f0fdfa;
            --teal-mid:   #ccfbf1;
            --text:       #111827;
            --text-2:     #374151;
            --muted:      #6b7280;
            --border:     #e5e7eb;
            --surface:    #f9fafb;
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
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 0 5%;
            height: 64px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar-logo img { height: 40px; display: block; }
        .topbar-links { display: flex; align-items: center; gap: 16px; font-size: 14px; }
        .topbar-links a { color: var(--muted); text-decoration: none; }
        .topbar-links a:hover { color: var(--teal); }
        .topbar-links .sep { color: var(--border); }

        /* ── HERO ── */
        .page-hero {
            background: linear-gradient(135deg, var(--teal-deep) 0%, var(--teal) 100%);
            padding: 52px 5% 64px;
            text-align: center;
        }
        .page-hero h1 {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: clamp(32px, 5vw, 52px);
            font-weight: 700; color: #fff;
            letter-spacing: -1.5px; line-height: 1.1;
            margin-bottom: 14px;
        }
        .page-hero h1 em { font-style: italic; color: var(--teal-mid); }
        .page-hero p { font-size: 16px; color: rgba(255,255,255,0.65); max-width: 480px; margin: 0 auto; line-height: 1.65; }

        /* ── MAIN WRAPPER ── */
        .reg-wrap { max-width: 1060px; margin: -32px auto 60px; padding: 0 20px; position: relative; z-index: 10; }

        /* ── PLAN CARDS ── */
        .plans-label {
            font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 16px; padding: 0 4px;
        }
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px; margin-bottom: 28px;
        }
        .plan-card {
            background: #fff; border-radius: 16px; padding: 28px 24px;
            border: 2px solid var(--border); cursor: pointer;
            transition: all .2s; position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .plan-card:hover { border-color: var(--teal); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(13,148,136,0.12); }
        .plan-card.selected { border-color: var(--teal); background: var(--teal-faint); box-shadow: 0 0 0 4px rgba(13,148,136,0.15), 0 8px 24px rgba(13,148,136,0.1); }
        .plan-check { position: absolute; top: 16px; right: 16px; display: none; width: 22px; height: 22px; background: var(--teal); border-radius: 50%; align-items: center; justify-content: center; }
        .plan-check svg { width: 12px; height: 12px; stroke: #fff; stroke-width: 2.5; fill: none; }
        .plan-card.selected .plan-check { display: flex; }
        .plan-name {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: 22px; font-weight: 600; color: var(--text);
            margin-bottom: 10px; letter-spacing: -.3px;
        }
        .plan-price { font-size: 32px; font-weight: 800; color: var(--text); line-height: 1; margin-bottom: 4px; }
        .plan-price-period { font-size: 13px; color: var(--muted); font-weight: 400; }
        .plan-limit { font-size: 12px; color: var(--teal-dark); font-weight: 600; margin-top: 10px; background: var(--teal-mid); display: inline-block; padding: 3px 10px; border-radius: 20px; }

        /* ── FORM CARD ── */
        .form-card {
            background: #fff; border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
            border: 1px solid var(--border);
            overflow: hidden;
            display: none;
            margin-top: 4px;
        }
        .form-card.active { display: block; animation: slideUp .35s ease; }
        @keyframes slideUp { from { opacity:0; transform: translateY(12px); } to { opacity:1; transform: translateY(0); } }

        .form-card-head {
            background: var(--teal-faint); border-bottom: 1px solid var(--teal-mid);
            padding: 20px 32px;
            display: flex; align-items: center; gap: 12px;
        }
        .form-card-head h2 {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: 22px; font-weight: 600; color: var(--teal-deep);
            letter-spacing: -.3px;
        }
        .form-card-head .selected-plan-badge {
            background: var(--teal); color: #fff;
            font-size: 12px; font-weight: 600; padding: 4px 12px;
            border-radius: 20px; display: none;
        }
        .form-card-head .selected-plan-badge.visible { display: inline-block; }

        .form-body { padding: 32px; }

        .error-banner {
            background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;
            border-radius: 10px; padding: 14px 18px; margin-bottom: 28px;
            display: flex; align-items: flex-start; gap: 10px; font-size: 14px;
        }
        .error-banner-icon { font-size: 16px; margin-top: 1px; flex-shrink: 0; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; margin-bottom: 7px;
            font-size: 13px; font-weight: 600; color: var(--text-2);
        }
        .form-group label .req { color: var(--teal); margin-left: 2px; }
        .form-group input,
        .form-group select {
            width: 100%; padding: 11px 14px;
            border: 1.5px solid var(--border); border-radius: 9px;
            font-size: 15px; font-family: 'Inter', sans-serif;
            color: var(--text); background: #fff;
            transition: border-color .15s, box-shadow .15s;
            outline: none;
        }
        .form-group input:focus,
        .form-group select:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(13,148,136,0.1); }
        .form-group input.invalid { border-color: #ef4444; background: #fef2f2; }
        .field-hint { font-size: 12px; color: var(--muted); margin-top: 5px; display: none; }
        .field-hint.show { display: block; }
        .field-error { font-size: 12px; color: #ef4444; margin-top: 5px; display: none; }

        /* Password */
        .password-wrap { position: relative; }
        .pw-toggle {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: var(--muted); user-select: none; font-size: 16px;
            background: none; border: none; padding: 0; line-height: 1;
        }
        .strength-track { height: 3px; background: var(--border); border-radius: 2px; margin-top: 8px; overflow: hidden; }
        .strength-fill  { height: 100%; width: 0; border-radius: 2px; transition: width .3s, background-color .3s; }
        .strength-lbl   { font-size: 11px; color: var(--muted); margin-top: 4px; text-align: right; height: 14px; }

        /* Submit */
        .btn-register {
            width: 100%; padding: 15px;
            background: var(--teal); color: #fff;
            border: none; border-radius: 10px;
            font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 700;
            cursor: pointer; transition: background .2s, transform .1s;
            margin-top: 8px;
        }
        .btn-register:hover { background: var(--teal-dark); }
        .btn-register:active { transform: scale(.98); }
        .btn-register:disabled { opacity: .5; cursor: not-allowed; }

        .form-footer { text-align: center; margin-top: 20px; font-size: 14px; color: var(--muted); }
        .form-footer a { color: var(--teal); text-decoration: none; font-weight: 600; }
        .form-footer a:hover { text-decoration: underline; }

        .vendor-cta {
            margin-top: 12px; padding: 18px 24px;
            background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px;
            text-align: center; font-size: 14px; color: #92400e;
        }
        .vendor-cta a { color: #d97706; font-weight: 700; text-decoration: none; }
        .vendor-cta a:hover { text-decoration: underline; }

        @media (max-width: 640px) {
            .page-hero { padding: 40px 5% 56px; }
            .reg-wrap { margin-top: -24px; padding: 0 14px; }
            .form-row { grid-template-columns: 1fr; }
            .form-body { padding: 24px 20px; }
            .form-card-head { padding: 16px 20px; }
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
        <a href="<?php echo $vendor_url; ?>">Register as Vendor</a>
    </div>
</div>

<!-- Hero -->
<div class="page-hero">
    <h1>Start your<br><em>free subscription</em></h1>
    <p>Choose a plan and create your account to access food packages, track orders, and manage your household delivery.</p>
</div>

<!-- Main content -->
<div class="reg-wrap">

    <div class="plans-label">Step 1 — Choose your plan</div>

    <div class="plans-grid">
        <?php
        $plan_limit_map = [
            'Guest'    => 'Browse only',
            'Basic'    => '4 orders / month',
            'Standard' => '8 orders / month',
            'Premium'  => 'Unlimited orders',
        ];
        foreach ($tiers as $tier):
            $isSelected = (GETPOST('selected_plan') == $tier->tier_type) ? 'selected' : '';
            $limit_label = $plan_limit_map[$tier->tier_type] ?? '';
        ?>
        <div class="plan-card <?php echo $isSelected; ?>" onclick="selectPlan(this, '<?php echo dol_escape_js($tier->tier_type); ?>')">
            <div class="plan-check">
                <svg viewBox="0 0 12 12"><polyline points="2,6 5,9 10,3"/></svg>
            </div>
            <div class="plan-name"><?php echo dol_escape_htmltag($tier->tier_name); ?></div>
            <div class="plan-price">₦<?php echo number_format($tier->price); ?><span class="plan-price-period"> / yr</span></div>
            <?php if ($limit_label): ?>
            <div class="plan-limit"><?php echo dol_escape_htmltag($limit_label); ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Registration form — reveals when plan selected -->
    <div id="formCard" class="form-card <?php echo ($error || GETPOST('selected_plan')) ? 'active' : ''; ?>">
        <div class="form-card-head">
            <h2>Step 2 — Create your account</h2>
            <span class="selected-plan-badge <?php echo GETPOST('selected_plan') ? 'visible' : ''; ?>" id="planBadge">
                <?php echo dol_escape_htmltag(GETPOST('selected_plan')); ?>
            </span>
        </div>
        <div class="form-body">

            <?php if ($error): ?>
            <div class="error-banner" id="errorBanner">
                <span class="error-banner-icon">⚠️</span>
                <div><?php echo $error; ?></div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('errorBanner').scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            </script>
            <?php endif; ?>

            <form method="POST" id="regForm">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="from_app" value="<?php echo htmlspecialchars($from_app); ?>">
                <input type="hidden" id="selectedPlanInput" name="selected_plan" value="<?php echo dol_escape_htmltag(GETPOST('selected_plan')); ?>" required>

                <div class="form-row">
                    <div class="form-group">
                        <label>First Name <span class="req">*</span></label>
                        <input type="text" name="firstname" value="<?php echo dol_escape_htmltag(GETPOST('firstname')); ?>" required autocomplete="given-name">
                    </div>
                    <div class="form-group">
                        <label>Last Name <span class="req">*</span></label>
                        <input type="text" name="lastname" value="<?php echo dol_escape_htmltag(GETPOST('lastname')); ?>" required autocomplete="family-name">
                    </div>
                </div>

                <div class="form-group">
                    <label>Username <span class="req">*</span></label>
                    <input type="text" id="usernameInput" name="username"
                           value="<?php echo dol_escape_htmltag(GETPOST('username')); ?>"
                           required placeholder="e.g. bolade2025" autocomplete="username"
                           onkeyup="validateUsername(this)">
                    <div class="field-error" id="userMsg">Only letters, numbers, underscores, and dashes allowed.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address <span class="req">*</span></label>
                        <input type="email" name="email" value="<?php echo dol_escape_htmltag(GETPOST('email')); ?>" required autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label>Phone Number <span class="req">*</span></label>
                        <input type="tel" name="phone" value="<?php echo dol_escape_htmltag(GETPOST('phone')); ?>" required autocomplete="tel">
                    </div>
                </div>

                <div class="form-group">
                    <label>Password <span class="req">*</span></label>
                    <div class="password-wrap">
                        <input type="password" id="passwordInput" name="password" minlength="6" required autocomplete="new-password" onkeyup="checkStrength(this.value)">
                        <button type="button" class="pw-toggle" onclick="togglePassword()" aria-label="Show password">👁</button>
                    </div>
                    <div class="strength-track"><div id="strengthBar" class="strength-fill"></div></div>
                    <div id="strengthText" class="strength-lbl"></div>
                </div>

                <button type="submit" id="submitBtn" class="btn-register">Complete Registration →</button>
            </form>

            <div class="form-footer">
                Already have an account? <a href="<?php echo DOL_URL_ROOT; ?>/index.php">Sign in here</a>
            </div>

        </div>
    </div>

    <div class="vendor-cta">
        Are you a food supplier? <a href="<?php echo $vendor_url; ?>">Apply as a Vendor Partner instead →</a>
    </div>

</div>

<script>
function selectPlan(card, planType) {
    document.querySelectorAll('.plan-card').forEach(el => el.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('selectedPlanInput').value = planType;
    const fc = document.getElementById('formCard');
    fc.classList.add('active');
    const badge = document.getElementById('planBadge');
    badge.textContent = planType;
    badge.classList.add('visible');
    fc.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function validateUsername(input) {
    const msg = document.getElementById('userMsg');
    const btn = document.getElementById('submitBtn');
    const ok  = /^[a-zA-Z0-9_-]*$/.test(input.value);
    input.classList.toggle('invalid', !ok && input.value.length > 0);
    msg.style.display = (!ok && input.value.length > 0) ? 'block' : 'none';
    btn.disabled = !ok && input.value.length > 0;
    btn.style.opacity = (btn.disabled) ? '0.5' : '1';
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
    const map = [
        null,
        ['#ef4444', '20%', 'Too weak'],
        ['#f97316', '40%', 'Weak'],
        ['#eab308', '60%', 'Medium'],
        ['#84cc16', '80%', 'Strong'],
        ['#22c55e', '100%', 'Very strong'],
    ];
    const entry = map[s] || ['#e2e8f0', '0%', ''];
    bar.style.backgroundColor = password.length ? entry[0] : '#e2e8f0';
    bar.style.width = password.length ? entry[1] : '0%';
    txt.textContent = password.length ? entry[2] : '';
    txt.style.color = entry[0];
}
</script>

</body>
</html>
