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

// --- 1. RATE LIMITER ---
if ($action == 'register') {
    $time_window = 600; 
    $max_attempts = 10; 
    
    // Check table exists to prevent crash on first run
    $res_check = $db->query("SHOW TABLES LIKE '".MAIN_DB_PREFIX."foodbank_rate_limit'");
    if ($res_check && $db->num_rows($res_check) > 0) {
        $db->query("DELETE FROM " . MAIN_DB_PREFIX . "foodbank_rate_limit WHERE attempt_time < (NOW() - INTERVAL $time_window SECOND)");
        $sql_limit = "SELECT COUNT(*) as cnt FROM " . MAIN_DB_PREFIX . "foodbank_rate_limit WHERE ip_address = '".$db->escape($ip_address)."' AND action_type = 'register'";
        $res_limit = $db->query($sql_limit);
        $obj_limit = $db->fetch_object($res_limit);

        if ($obj_limit->cnt >= $max_attempts) {
            $error = "⚠️ Too many attempts. Please wait 10 minutes.";
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

        // Check 1: Is Username taken?
        $check_login = new User($db);
        if ($check_login->fetch('', $username) > 0) {
            $error = "Username is already taken. Please choose another.";
            $db->rollback();
        } 
        // Check 2: Is Email taken?
        else {
            $sql_email = "SELECT rowid FROM ".MAIN_DB_PREFIX."user WHERE email = '".$db->escape($email)."'";
            $res_email = $db->query($sql_email);
            if ($res_email && $db->num_rows($res_email) > 0) {
                $error = "Email address already registered.";
                $db->rollback();
            } else {
                // Create User
                $uid = $newuser->create($user);

                if ($uid > 0) {
                    $ref = 'SUB-' . date('ym') . '-' . str_pad($uid, 4, '0', STR_PAD_LEFT);
                    $start_date = date('Y-m-d');
                    $end_date   = date('Y-m-d', strtotime('+1 year'));

                    // --- FIX: Changed 'date_creation' to 'datec' ---
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
                        $otp = rand(100000, 999999);
                        $db->query("CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."foodbank_email_verification (email VARCHAR(255) PRIMARY KEY, code VARCHAR(10), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
                        $db->query("DELETE FROM " . MAIN_DB_PREFIX . "foodbank_email_verification WHERE email = '".$db->escape($email)."'");
                        $db->query("INSERT INTO " . MAIN_DB_PREFIX . "foodbank_email_verification (email, code) VALUES ('".$db->escape($email)."', '$otp')");
                        
                        $db->commit();

                        $subject = "Verify your Foodbank Account";
                        $msg = "Hello $fname,\n\nYour verification code is: $otp\n\nThank you.";
                        $mail = new CMailFile($subject, $email, 'no-reply@foodbank.com', $msg);
                        $mail->sendfile();

                        header("Location: verify_otp.php?email=".urlencode($email));
                        exit;
                    } else {
                        $db->rollback();
                        // --- FIX: Hidden Error Message ---
                        dol_syslog("Registration DB Error: " . $db->lasterror(), LOG_ERR); // Log it internally
                        $error = "We encountered a technical error while creating your profile. Please contact support.";
                    }
                } else {
                    $db->rollback();
                    // --- FIX: Hidden Error Message ---
                    dol_syslog("Registration User Error: " . $newuser->error, LOG_ERR);
                    $error = "Account creation failed. Please ensure your password meets the complexity requirements.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subscribe | Foodbank CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; color: #334155; }
        .container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
        .header { text-align: center; margin-bottom: 50px; }
        .header h1 { font-size: 32px; color: #1e293b; margin-bottom: 10px; }
        .header p { color: #64748b; font-size: 18px; }
        
        .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-bottom: 50px; }
        .plan-card { background: white; border-radius: 12px; padding: 30px; border: 2px solid #e2e8f0; cursor: pointer; transition: all 0.3s ease; position: relative; }
        .plan-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); border-color: #cbd5e1; }
        .plan-card.selected { border-color: #2563eb; background: #eff6ff; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2); }
        .plan-name { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 15px; }
        .plan-price { font-size: 36px; font-weight: 800; color: #0f172a; margin-bottom: 20px; }
        .plan-price span { font-size: 16px; color: #64748b; font-weight: normal; }
        .check-icon { position: absolute; top: 20px; right: 20px; color: #2563eb; display: none; font-size: 24px; }
        .plan-card.selected .check-icon { display: block; }

        .reg-form { background: white; max-width: 600px; margin: 0 auto; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); display: none; }
        .reg-form.active { display: block; animation: fadeIn 0.5s; }
        .form-title { font-size: 20px; font-weight: bold; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        
        input { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 16px; transition: 0.2s; }
        input:focus { border-color: #2563eb; outline: none; }
        
        /* VALIDATION STYLES */
        input.invalid { border-color: #ef4444; background: #fef2f2; }
        .validation-msg { font-size: 12px; color: #ef4444; margin-top: 5px; display: none; }
        
        .btn-submit { width: 100%; background: #2563eb; color: white; padding: 15px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background: #1d4ed8; }
        .error-box { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 30px; text-align: center; border: 1px solid #fca5a5; }

        /* PASSWORD UX */
        .password-wrapper { position: relative; }
        .password-toggle { position: absolute; right: 15px; top: 12px; cursor: pointer; font-size: 18px; color: #64748b; user-select: none; }
        .strength-meter { height: 4px; background: #e2e8f0; border-radius: 2px; margin-top: 8px; overflow: hidden; width: 100%; }
        .strength-bar { height: 100%; width: 0%; transition: width 0.3s ease, background-color 0.3s ease; }
        .strength-text { font-size: 12px; color: #64748b; margin-top: 4px; text-align: right; display: block; height: 16px; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Select Your Subscription</h1>
        <p>Choose a plan to get started. You'll create your account in the next step.</p>
    </div>

    <?php if ($error): ?>
        <div class="error-box">
            <strong>⚠️ Registration Failed</strong><br>
            <?php echo $error; ?>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                document.getElementById('registrationSection').classList.add('active');
                document.getElementById('registrationSection').scrollIntoView({ block: 'center' });
            });
        </script>
    <?php endif; ?>

    <div class="pricing-grid">
        <?php
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "foodbank_subscription_tiers WHERE is_active = 1";
        $res = $db->query($sql);
        if ($res && $db->num_rows($res) > 0) {
            while ($tier = $db->fetch_object($res)) {
                $isSelected = (GETPOST('selected_plan') == $tier->tier_type) ? 'selected' : '';
                print '<div class="plan-card '.$isSelected.'" onclick="selectPlan(this, \''.$tier->tier_type.'\')">';
                print '<div class="check-icon">✓</div>';
                print '<div class="plan-name">'.dol_escape_htmltag($tier->tier_name).'</div>';
                print '<div class="plan-price">₦'.number_format($tier->price).' <span>/ year</span></div>';
                print '</div>';
            }
        }
        ?>
    </div>

    <div id="registrationSection" class="reg-form <?php echo ($error || GETPOST('selected_plan')) ? 'active' : ''; ?>">
        <div class="form-title">Create Your Account</div>
        <form method="POST">
            <input type="hidden" name="action" value="register">
            <input type="hidden" id="selectedPlanInput" name="selected_plan" value="<?php echo dol_escape_htmltag(GETPOST('selected_plan')); ?>" required>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="firstname" value="<?php echo dol_escape_htmltag(GETPOST('firstname')); ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="lastname" value="<?php echo dol_escape_htmltag(GETPOST('lastname')); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Username (Login ID)</label>
                <input type="text" id="usernameInput" name="username" value="<?php echo dol_escape_htmltag(GETPOST('username')); ?>" 
                       required placeholder="e.g. bolade2025" onkeyup="validateUsername(this)">
                <div id="userMsg" class="validation-msg">❌ Invalid characters. Only letters, numbers, and underscores allowed.</div>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo dol_escape_htmltag(GETPOST('email')); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" value="<?php echo dol_escape_htmltag(GETPOST('phone')); ?>" required>
            </div>

            <div class="form-group">
                <label>Create Password</label>
                <div class="password-wrapper">
                    <input type="password" id="passwordInput" name="password" minlength="6" required onkeyup="checkStrength(this.value)">
                    <span class="password-toggle" onclick="togglePassword()">👁️</span>
                </div>
                <div class="strength-meter"><div id="strengthBar" class="strength-bar"></div></div>
                <span id="strengthText" class="strength-text"></span>
            </div>

            <button type="submit" id="submitBtn" class="btn-submit">Complete Registration →</button>
            <p style="text-align:center; margin-top:15px; font-size:14px; color:#64748b;">
                Already have an account? <a href="../../index.php">Login here</a>
            </p>
        </form>
    </div>
</div>

<script>
    function selectPlan(card, planType) {
        document.querySelectorAll('.plan-card').forEach(el => el.classList.remove('selected'));
        card.classList.add('selected');
        document.getElementById('selectedPlanInput').value = planType;
        document.getElementById('registrationSection').classList.add('active');
        document.getElementById('registrationSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // USERNAME VALIDATION
    function validateUsername(input) {
        const msg = document.getElementById('userMsg');
        const btn = document.getElementById('submitBtn');
        const regex = /^[a-zA-Z0-9_-]+$/; // Only Letters, Numbers, Underscore, Dash
        
        if (input.value.length > 0 && !regex.test(input.value)) {
            input.classList.add('invalid');
            msg.style.display = 'block';
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
        } else {
            input.classList.remove('invalid');
            msg.style.display = 'none';
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
        }
    }

    function togglePassword() {
        const input = document.getElementById('passwordInput');
        const icon = document.querySelector('.password-toggle');
        if (input.type === "password") {
            input.type = "text";
            icon.textContent = "🙈";
        } else {
            input.type = "password";
            icon.textContent = "👁️";
        }
    }

    function checkStrength(password) {
        const bar = document.getElementById('strengthBar');
        const text = document.getElementById('strengthText');
        let strength = 0;

        if (password.length >= 6) strength += 1;
        if (password.length >= 8) strength += 1;
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;

        let color = '#e2e8f0';
        let width = '0%';
        let message = '';

        switch (strength) {
            case 0:
            case 1: color = '#ef4444'; width = '20%'; message = 'Too Weak'; break;
            case 2: color = '#f97316'; width = '40%'; message = 'Weak'; break;
            case 3: color = '#eab308'; width = '60%'; message = 'Medium'; break;
            case 4: color = '#84cc16'; width = '80%'; message = 'Strong'; break;
            case 5: color = '#22c55e'; width = '100%'; message = 'Very Strong'; break;
        }

        if (password.length === 0) { width = '0%'; message = ''; }
        bar.style.backgroundColor = color;
        bar.style.width = width;
        text.textContent = message;
        text.style.color = color;
    }
</script>

</body>
</html>