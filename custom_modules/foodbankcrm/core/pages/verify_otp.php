<?php
/**
 * OTP VERIFICATION (SMART ROUTER)
 * Verifies Email -> Identifies User Type -> Redirects to Correct Dashboard
 */
define('NOCSRFCHECK', 1);
define('NOTOKENRENEWAL', 1);
define('NOLOGIN', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

$email = GETPOST('email', 'alpha');
$action = GETPOST('action', 'alpha');
$error = '';
$success_msg = '';

$ip_address = $_SERVER['REMOTE_ADDR'];

// --- Helper: Check brute-force lockout ---
function isOtpLocked($db, $email, $ip_address) {
    $max_attempts = 5;
    $lockout_window = 900; // 15 minutes
    $sql = "SELECT COUNT(*) as cnt FROM ".MAIN_DB_PREFIX."foodbank_rate_limit
            WHERE (ip_address = '".$db->escape($ip_address)."' OR ip_address = '".$db->escape($email)."')
            AND action_type = 'otp_verify'
            AND attempt_time > (NOW() - INTERVAL ".$lockout_window." SECOND)";
    $res = $db->query($sql);
    if ($res) {
        $obj = $db->fetch_object($res);
        return ($obj->cnt >= $max_attempts);
    }
    return false;
}

function recordOtpAttempt($db, $email, $ip_address) {
    $db->query("INSERT INTO ".MAIN_DB_PREFIX."foodbank_rate_limit (ip_address, action_type) VALUES ('".$db->escape($email)."', 'otp_verify')");
    $db->query("INSERT INTO ".MAIN_DB_PREFIX."foodbank_rate_limit (ip_address, action_type) VALUES ('".$db->escape($ip_address)."', 'otp_verify')");
}

// --- RESEND LOGIC (Rate limited: max 3 per 10 min) ---
if ($action == 'resend') {
    // Check resend rate limit
    $sql_resend = "SELECT COUNT(*) as cnt FROM ".MAIN_DB_PREFIX."foodbank_rate_limit
                   WHERE ip_address = '".$db->escape($email)."' AND action_type = 'otp_resend'
                   AND attempt_time > (NOW() - INTERVAL 600 SECOND)";
    $res_resend = $db->query($sql_resend);
    $resend_count = $res_resend ? (int)$db->fetch_object($res_resend)->cnt : 0;

    if ($resend_count >= 3) {
        $error = "Too many resend requests. Please wait 10 minutes.";
    } else {
        $otp = random_int(100000, 999999);
        $db->query("UPDATE " . MAIN_DB_PREFIX . "foodbank_email_verification SET code = '".$db->escape($otp)."', created_at = NOW() WHERE email = '" . $db->escape($email) . "'");
        if ($db->affected_rows() == 0) {
            $db->query("INSERT INTO " . MAIN_DB_PREFIX . "foodbank_email_verification (email, code) VALUES ('".$db->escape($email)."', '".$db->escape($otp)."')");
        }

        // Track resend
        $db->query("INSERT INTO ".MAIN_DB_PREFIX."foodbank_rate_limit (ip_address, action_type) VALUES ('".$db->escape($email)."', 'otp_resend')");

        // Reset failed verification attempts on resend
        $db->query("DELETE FROM ".MAIN_DB_PREFIX."foodbank_rate_limit WHERE ip_address = '".$db->escape($email)."' AND action_type = 'otp_verify'");

        $subject = "New Verification Code";
        $msg = "Your new code is: $otp";
        $mail = new CMailFile($subject, $email, 'no-reply@foodbank.com', $msg);
        $mail->sendfile();
        $success_msg = "A new code has been sent to your email.";
    }
}

// --- VERIFY LOGIC (Brute-force protected) ---
if ($action == 'verify') {
    if (isOtpLocked($db, $email, $ip_address)) {
        $error = "Too many failed attempts. Please wait 15 minutes or request a new code.";
    } else {
        $code_input = GETPOST('code', 'alpha');

        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "foodbank_email_verification
                WHERE email = '" . $db->escape($email) . "'
                AND code = '" . $db->escape($code_input) . "'";

        $res = $db->query($sql);

        if ($res && $db->num_rows($res) > 0) {
            $obj = $db->fetch_object($res);

            // Expiration Check (5 Mins)
            if ((time() - strtotime($obj->created_at)) > 300) {
                $error = "Code expired. Please request a new one.";
            } else {
                $db->begin();

                // Find User ID
                $sql_user = "SELECT rowid FROM ".MAIN_DB_PREFIX."user WHERE email = '".$db->escape($email)."' LIMIT 1";
                $res_user = $db->query($sql_user);
                $obj_user = $db->fetch_object($res_user);

                if ($obj_user) {
                    $u = new User($db);
                    $u->fetch($obj_user->rowid);
                    $u->statut = 1; // Enable Account
                    $u->update($user);

                    // Clean up: delete OTP and failed attempt records
                    $db->query("DELETE FROM " . MAIN_DB_PREFIX . "foodbank_email_verification WHERE email = '" . $db->escape($email) . "'");
                    $db->query("DELETE FROM ".MAIN_DB_PREFIX."foodbank_rate_limit WHERE ip_address = '".$db->escape($email)."' AND action_type IN ('otp_verify','otp_resend')");
                    $db->commit();

                    // Regenerate session to prevent session fixation
                    session_regenerate_id(true);

                    // Auto Login
                    $_SESSION["dol_login"] = $u->login;
                    $_SESSION["dol_entity"] = 1;
                    $_SESSION["dol_authtype"] = 'form';
                    $_SESSION["foodbank_checked"] = true;

                    // --- SMART ROUTING LOGIC ---
                    // 1. Check if Vendor
                    $sql_vend = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = " . $u->id;
                    $res_vend = $db->query($sql_vend);
                    if ($res_vend && $db->num_rows($res_vend) > 0) {
                        header("Location: dashboard_vendor.php");
                        exit;
                    }

                    // 2. Check if Subscriber
                    $sql_sub = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = " . $u->id;
                    $res_sub = $db->query($sql_sub);
                    if ($res_sub && $db->num_rows($res_sub) > 0) {
                        header("Location: dashboard_beneficiary.php");
                        exit;
                    }

                    // 3. Fallback
                    header("Location: ../../index.php");
                    exit;

                } else {
                    $error = "User account not found.";
                }
            }
        } else {
            // Record failed attempt
            recordOtpAttempt($db, $email, $ip_address);
            $error = "Invalid verification code.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Email | Foodbank CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; border: 1px solid #e2e8f0; }
        h2 { color: #1e293b; margin-bottom: 10px; }
        p { color: #64748b; margin-bottom: 25px; }
        input { width: 100%; padding: 15px; margin: 20px 0; border: 2px solid #cbd5e1; border-radius: 8px; font-size: 24px; text-align: center; letter-spacing: 5px; box-sizing: border-box; transition: 0.2s; }
        input:focus { border-color: #2563eb; outline: none; }
        button { width: 100%; padding: 15px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        button:hover { background: #1d4ed8; }
        .error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .resend-link { display: inline-block; margin-top: 20px; color: #64748b; text-decoration: none; font-size: 14px; cursor: pointer; background: none; border: none; padding: 0; }
        .resend-link:hover { color: #2563eb; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Verify Your Email</h2>
        <p>We sent a 6-digit code to<br><strong><?php echo htmlspecialchars($email); ?></strong></p>
        <?php if($error) echo '<div class="error">'.$error.'</div>'; ?>
        <?php if($success_msg) echo '<div class="success">'.$success_msg.'</div>'; ?>
        <form method="POST">
            <input type="hidden" name="action" value="verify">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <input type="text" name="code" placeholder="000000" maxlength="6" required autofocus>
            <button type="submit">Verify & Continue</button>
        </form>
        <form method="POST">
            <input type="hidden" name="action" value="resend">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <button type="submit" class="resend-link">Code expired? Request a new one</button>
        </form>
    </div>
</body>
</html>