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

// --- 1. RATE LIMITER ---
if ($action == 'register') {
    $time_window = 600; 
    $max_attempts = 10;
    
    // Check if table exists to prevent crash
    $res_check = $db->query("SHOW TABLES LIKE '".MAIN_DB_PREFIX."foodbank_rate_limit'");
    if ($res_check && $db->num_rows($res_check) > 0) {
        $db->query("DELETE FROM " . MAIN_DB_PREFIX . "foodbank_rate_limit WHERE attempt_time < (NOW() - INTERVAL $time_window SECOND)");
        $sql_limit = "SELECT COUNT(*) as cnt FROM " . MAIN_DB_PREFIX . "foodbank_rate_limit WHERE ip_address = '".$db->escape($ip_address)."' AND action_type = 'vendor_register'";
        $res_limit = $db->query($sql_limit);
        $obj_limit = $db->fetch_object($res_limit);

        if ($obj_limit->cnt >= $max_attempts) {
            $error = "⚠️ Too many attempts. Please wait 10 minutes.";
        } else {
            $db->query("INSERT INTO " . MAIN_DB_PREFIX . "foodbank_rate_limit (ip_address, action_type) VALUES ('".$db->escape($ip_address)."', 'vendor_register')");
        }
    }
}

// --- 2. REGISTRATION LOGIC ---
if ($action == 'register' && empty($error)) {
    $db->begin();

    // Inputs
    $username       = GETPOST('username', 'alpha');
    $pass           = GETPOST('password', 'none');
    
    // Contact Info
    $contact_person = GETPOST('contact_person', 'alpha');
    $email          = GETPOST('email', 'alpha'); 
    $phone          = GETPOST('phone', 'alpha');
    
    // Business Info
    $business_name  = GETPOST('business_name', 'alpha');
    $rc_number      = GETPOST('rc_number', 'alpha');
    $tax_id         = GETPOST('tax_id', 'alpha');
    $category       = GETPOST('category', 'alpha');
    $website        = GETPOST('website', 'alpha');
    $address        = GETPOST('address', 'restricthtml');
    $bank_name      = GETPOST('bank_name', 'alpha');
    $account_no     = GETPOST('account_no', 'alpha');

    // Validation
    if (empty($username) || empty($email) || empty($pass) || empty($business_name) || empty($category)) {
        $error = "Please fill in all required fields (including Category).";
        $db->rollback();
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        $error = "Username invalid. Use letters and numbers only.";
        $db->rollback();
    } else {
        // Create User
        $newuser = new User($db);
        $newuser->login = $username;
        $newuser->email = $email;
        $newuser->firstname = $contact_person;
        $newuser->lastname  = "(Vendor)";
        $newuser->pass = $pass;
        $newuser->statut = 0; 

        // Check Login Uniqueness
        $check_login = new User($db);
        if ($check_login->fetch('', $username) > 0) {
            $error = "Username '$username' is already taken.";
            $db->rollback();
        } else {
            // Check Email Uniqueness
            $sql_email = "SELECT rowid FROM ".MAIN_DB_PREFIX."user WHERE email = '".$db->escape($email)."'";
            $res_email = $db->query($sql_email);
            if ($res_email && $db->num_rows($res_email) > 0) {
                $error = "Email address already registered.";
                $db->rollback();
            } else {
                $uid = $newuser->create($user);

                if ($uid > 0) {
                    // Create Vendor Profile
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
                        // Auto-grant FoodbankCRM permissions so menu is visible and trigger can route
                        foreach (array(100001, 100011, 100012, 100013) as $right_id) {
                            $db->query("INSERT IGNORE INTO ".MAIN_DB_PREFIX."user_rights (entity, fk_user, fk_id) VALUES (1, ".(int)$uid.", ".(int)$right_id.")");
                        }

                        // --- OTP GENERATION ---
                        $otp = random_int(100000, 999999);
                        $db->query("CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."foodbank_email_verification (email VARCHAR(255) PRIMARY KEY, code VARCHAR(10), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
                        $db->query("DELETE FROM " . MAIN_DB_PREFIX . "foodbank_email_verification WHERE email = '".$db->escape($email)."'");
                        $db->query("INSERT INTO " . MAIN_DB_PREFIX . "foodbank_email_verification (email, code) VALUES ('".$db->escape($email)."', '$otp')");
                        
                        $db->commit();

                        // Send Email
                        $subject = "Verify Vendor Account - Foodbank CRM";
                        $msg = "Hello $contact_person,\n\nYour Vendor verification code is: $otp\n\nThank you.";
                        $from = !empty($conf->global->MAIN_MAIL_EMAIL_FROM) ? $conf->global->MAIN_MAIL_EMAIL_FROM : 'no-reply@foodbank.com';
                        $mail = new CMailFile($subject, $email, $from, $msg);
                        $mail->sendfile();

                        // Redirect to Verify Page
                        header("Location: verify_otp.php?email=".urlencode($email));
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Partner Registration | Foodbank CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 40px 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { text-align: center; margin-bottom: 40px; }
        .header h2 { color: #2d3748; margin: 10px 0; }
        .section-header { background: #f7fafc; padding: 10px 15px; border-left: 4px solid #2c3e50; color: #2d3748; font-weight: bold; margin: 30px 0 20px 0; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
        input:focus { border-color: #2c3e50; outline: none; }
        
        input.invalid { border-color: #ef4444; background: #fef2f2; }
        
        .btn-register { width: 100%; background: #2c3e50; color: white; border: none; padding: 16px; border-radius: 6px; font-weight: bold; font-size: 16px; cursor: pointer; transition: 0.2s; }
        .btn-register:hover { background: #1a202c; }
        
        .password-wrapper { position: relative; }
        .password-toggle { position: absolute; right: 15px; top: 12px; cursor: pointer; font-size: 18px; color: #64748b; user-select: none; }
        .strength-meter { height: 4px; background: #e2e8f0; border-radius: 2px; margin-top: 8px; overflow: hidden; width: 100%; }
        .strength-bar { height: 100%; width: 0%; transition: width 0.3s ease, background-color 0.3s ease; }
        .strength-text { font-size: 12px; color: #64748b; margin-top: 4px; text-align: right; display: block; height: 16px; }

        .error-box { background: #fee2e2; color: #c53030; padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>Vendor Partnership Application</h2>
        <p style="color: #718096;">Join our network of trusted food suppliers.</p>
    </div>

    <?php if ($error) print '<div class="error-box">'.$error.'</div>'; ?>

    <form method="POST">
        <input type="hidden" name="action" value="register">

        <div class="section-header">1. Business Information</div>
        <div class="form-group">
            <label>Registered Business Name</label>
            <input type="text" name="business_name" placeholder="e.g. Global Foods Ltd." value="<?php echo dol_escape_htmltag(GETPOST('business_name')); ?>" required>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>RC / Registration Number</label>
                <input type="text" name="rc_number" value="<?php echo dol_escape_htmltag(GETPOST('rc_number')); ?>" required>
            </div>
            <div class="form-group">
                <label>Tax ID (TIN)</label>
                <input type="text" name="tax_id" value="<?php echo dol_escape_htmltag(GETPOST('tax_id')); ?>">
            </div>
        </div>
        
        <div class="grid-2">
            <div class="form-group">
                <label>Website (Optional)</label>
                <input type="url" name="website" placeholder="https://..." value="<?php echo dol_escape_htmltag(GETPOST('website')); ?>">
            </div>
            <div class="form-group">
                <label>Primary Supply Category</label>
                <select name="category" required>
                    <option value="">Select...</option>
                    <option value="Grains">Grains & Staples</option>
                    <option value="Fresh Produce">Fresh Produce</option>
                    <option value="Proteins">Meat & Poultry</option>
                    <option value="Packaged">Packaged Goods</option>
                    <option value="Logistics">Logistics Provider</option>
                </select>
            </div>
        </div>

        <div class="section-header">2. Contact Details</div>
        <div class="form-group">
            <label>Contact Person</label>
            <input type="text" name="contact_person" value="<?php echo dol_escape_htmltag(GETPOST('contact_person')); ?>" required>
        </div>
        
        <div class="grid-2">
            <div class="form-group">
                <label>Business Email (Verification Code will be sent here)</label>
                <input type="email" name="email" value="<?php echo dol_escape_htmltag(GETPOST('email')); ?>" required>
            </div>
            <div class="form-group">
                <label>Direct Phone</label>
                <input type="tel" name="phone" value="<?php echo dol_escape_htmltag(GETPOST('phone')); ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Office Address</label>
            <textarea name="address" rows="2" required><?php echo dol_escape_htmltag(GETPOST('address')); ?></textarea>
        </div>

        <div class="section-header">3. Banking Info</div>
        <div class="grid-2">
            <div class="form-group">
                <label>Bank Name</label>
                <input type="text" name="bank_name" placeholder="e.g. Zenith Bank" value="<?php echo dol_escape_htmltag(GETPOST('bank_name')); ?>">
            </div>
            <div class="form-group">
                <label>Account Number</label>
                <input type="text" name="account_no" value="<?php echo dol_escape_htmltag(GETPOST('account_no')); ?>">
            </div>
        </div>

        <div class="section-header">4. Account Security</div>
        
        <div class="form-group">
            <label>Create Username (For Login)</label>
            <input type="text" name="username" placeholder="CompanyID" value="<?php echo dol_escape_htmltag(GETPOST('username')); ?>" required onkeyup="validateUsername(this)">
            <small id="userMsg" style="color:#ef4444; display:none;">Only letters and numbers allowed.</small>
        </div>

        <div class="form-group">
            <label>Create Password</label>
            <div class="password-wrapper">
                <input type="password" id="passwordInput" name="password" required onkeyup="checkStrength(this.value)">
                <span class="password-toggle" onclick="togglePassword()">👁️</span>
            </div>
            <div class="strength-meter"><div id="strengthBar" class="strength-bar"></div></div>
            <span id="strengthText" class="strength-text"></span>
        </div>

        <button type="submit" id="submitBtn" class="btn-register">Submit Application</button>
    </form>
    <div style="text-align:center; margin-top:20px;">
        <a href="../../index.php" style="color:#718096; text-decoration:none;">Cancel</a>
    </div>
</div>

<script>
    function validateUsername(input) {
        const regex = /^[a-zA-Z0-9_-]+$/;
        const msg = document.getElementById('userMsg');
        const btn = document.getElementById('submitBtn');
        if (input.value.length > 0 && !regex.test(input.value)) {
            input.classList.add('invalid');
            msg.style.display = 'block';
            btn.disabled = true;
            btn.style.opacity = '0.5';
        } else {
            input.classList.remove('invalid');
            msg.style.display = 'none';
            btn.disabled = false;
            btn.style.opacity = '1';
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

        let color = '#e2e8f0'; let width = '0%'; let message = '';
        switch (strength) {
            case 0: case 1: color = '#ef4444'; width = '20%'; message = 'Too Weak'; break;
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