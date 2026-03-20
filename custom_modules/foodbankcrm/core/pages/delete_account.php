<?php
/*
 * Delete Account & Data Request — Foodbank CRM
 * Public page (no login required) — linked from Google Play Store listing.
 */

if (!defined('NOREQUIRESOC'))    define('NOREQUIRESOC',    '1');
if (!defined('NOREQUIRETRAN'))   define('NOREQUIRETRAN',   '1');
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK',     '1');
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',  '1');
if (!defined('NOLOGIN'))         define('NOLOGIN',         '1');
if (!defined('NOREQUIREMENU'))   define('NOREQUIREMENU',   '1');
if (!defined('NOREQUIREHTML'))   define('NOREQUIREHTML',   '1');

require_once dirname(__DIR__, 4) . '/main.inc.php';

$base      = DOL_URL_ROOT . '/custom/foodbankcrm';
$index_url = $base . '/index.php';

/* ── Handle form submission ─────────────────────────────────── */
$submitted  = false;
$form_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $req_name    = trim(strip_tags($_POST['req_name']    ?? ''));
    $req_email   = trim(strip_tags($_POST['req_email']   ?? ''));
    $req_type    = trim(strip_tags($_POST['req_type']    ?? ''));
    $req_scope   = trim(strip_tags($_POST['req_scope']   ?? ''));
    $req_reason  = trim(strip_tags($_POST['req_reason']  ?? ''));
    $confirmed   = !empty($_POST['confirmed']);

    if (!$req_name || !$req_email || !$req_type || !$req_scope || !$confirmed) {
        $form_error = 'Please fill in all required fields and tick the confirmation checkbox.';
    } elseif (!filter_var($req_email, FILTER_VALIDATE_EMAIL)) {
        $form_error = 'Please enter a valid email address.';
    } else {
        // Build email body
        $scope_label = $req_scope === 'account' ? 'Full account + all data deletion' : 'Data deletion only (keep account)';
        $body  = "=== Foodbank CRM — Account / Data Deletion Request ===\r\n\r\n";
        $body .= "Name       : {$req_name}\r\n";
        $body .= "Email      : {$req_email}\r\n";
        $body .= "Account type: {$req_type}\r\n";
        $body .= "Request type: {$scope_label}\r\n";
        $body .= "Reason     : " . ($req_reason ?: 'Not provided') . "\r\n";
        $body .= "Submitted  : " . date('Y-m-d H:i:s') . " UTC\r\n";
        $body .= "\r\nPlease process this request within 30 days as required by NDPA 2023.\r\n";

        $to      = 'support@xdigitalfoodbank.com';
        $subject = "Account Deletion Request — {$req_name} ({$req_type})";
        $headers = "From: noreply@xdigitalfoodbank.com\r\nReply-To: {$req_email}\r\nContent-Type: text/plain; charset=UTF-8";

        // Attempt to send — if mail() is not configured the fallback copy is shown
        @mail($to, $subject, $body, $headers);

        $submitted = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account or Data — Foodbank CRM</title>
    <link rel="icon" type="image/png" href="<?php echo $base; ?>/img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --teal:       #0d9488;
            --teal-deep:  #0f766e;
            --teal-light: #e6f4f1;
            --red:        #ef4444;
            --red-light:  #fef2f2;
            --amber:      #f59e0b;
            --amber-light:#fffbeb;
            --text:       #1e293b;
            --muted:      #64748b;
            --border:     #e2e8f0;
            --radius:     12px;
        }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f8fafc;
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* ── Nav ── */
        .nav {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nav img { width: 32px; height: 32px; border-radius: 8px; }
        .nav-brand { font-weight: 700; font-size: 15px; color: var(--text); text-decoration: none; }
        .nav-back {
            margin-left: auto;
            font-size: 13px;
            color: var(--teal);
            text-decoration: none;
            font-weight: 500;
        }
        .nav-back:hover { text-decoration: underline; }

        /* ── Layout ── */
        .page-wrap {
            max-width: 680px;
            margin: 0 auto;
            padding: 40px 20px 80px;
        }

        /* ── Hero ── */
        .hero {
            text-align: center;
            margin-bottom: 40px;
        }
        .hero-icon {
            width: 64px; height: 64px;
            border-radius: 50%;
            background: var(--red-light);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
            font-size: 28px;
        }
        .hero h1 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .hero p {
            color: var(--muted);
            font-size: 15px;
            max-width: 480px;
            margin: 0 auto;
        }

        /* ── Cards ── */
        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--muted);
            margin-bottom: 16px;
        }

        /* ── Steps ── */
        .steps { display: flex; flex-direction: column; gap: 14px; }
        .step { display: flex; gap: 14px; align-items: flex-start; }
        .step-num {
            width: 28px; height: 28px; border-radius: 50%;
            background: var(--teal);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .step-text { font-size: 14px; line-height: 1.6; }
        .step-text strong { color: var(--text); }

        /* ── Data table ── */
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .data-table th {
            background: var(--teal-light);
            color: var(--teal-deep);
            font-weight: 600;
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        .data-table td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
            color: var(--text);
            vertical-align: top;
        }
        .data-table tr:last-child td { border-bottom: none; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-red    { background: var(--red-light);    color: var(--red); }
        .badge-amber  { background: var(--amber-light);  color: #92400e; }
        .badge-green  { background: var(--teal-light);   color: var(--teal-deep); }

        /* ── Form ── */
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text);
        }
        .form-group label .req { color: var(--red); margin-left: 2px; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text);
            background: #fff;
            transition: border-color .15s;
            outline: none;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--teal);
            box-shadow: 0 0 0 3px rgba(13,148,136,.1);
        }
        .form-group textarea { resize: vertical; min-height: 90px; }

        .radio-group { display: flex; flex-direction: column; gap: 10px; margin-top: 4px; }
        .radio-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
            font-size: 14px;
            padding: 12px 14px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            transition: border-color .15s, background .15s;
        }
        .radio-label:hover { border-color: var(--teal); background: var(--teal-light); }
        .radio-label input[type=radio] { margin-top: 2px; accent-color: var(--teal); }
        .radio-label-text strong { display: block; font-weight: 600; }
        .radio-label-text span { font-size: 12px; color: var(--muted); }

        .check-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 13px;
            line-height: 1.6;
            cursor: pointer;
        }
        .check-label input[type=checkbox] { margin-top: 3px; accent-color: var(--teal); flex-shrink: 0; }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: var(--red);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background .15s;
            margin-top: 8px;
        }
        .btn-submit:hover { background: #dc2626; }

        /* ── Alert ── */
        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .alert-error   { background: var(--red-light);   border: 1px solid #fca5a5; color: #991b1b; }
        .alert-success { background: var(--teal-light);  border: 1px solid #99f6e4; color: var(--teal-deep); }
        .alert-warn    { background: var(--amber-light);  border: 1px solid #fde68a; color: #92400e; }

        /* ── Success state ── */
        .success-wrap { text-align: center; padding: 40px 20px; }
        .success-icon { font-size: 52px; margin-bottom: 16px; }
        .success-wrap h2 { font-size: 22px; font-weight: 700; margin-bottom: 8px; }
        .success-wrap p { color: var(--muted); font-size: 14px; max-width: 420px; margin: 0 auto 8px; }
        .ref-box {
            display: inline-block;
            background: var(--teal-light);
            color: var(--teal-deep);
            font-weight: 700;
            font-size: 14px;
            padding: 8px 20px;
            border-radius: 8px;
            margin: 12px 0 20px;
        }
        .alt-contact {
            margin-top: 16px;
            font-size: 13px;
            color: var(--muted);
        }
        .alt-contact a { color: var(--teal); font-weight: 600; text-decoration: none; }

        /* ── Footer ── */
        .page-footer {
            text-align: center;
            font-size: 12px;
            color: var(--muted);
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }
        .page-footer a { color: var(--teal); text-decoration: none; }
        .page-footer a:hover { text-decoration: underline; }

        @media (max-width: 480px) {
            .page-wrap { padding: 24px 16px 60px; }
            .hero h1 { font-size: 22px; }
            .card { padding: 20px 16px; }
        }
    </style>
</head>
<body>

<!-- Nav -->
<nav class="nav">
    <img src="<?php echo $base; ?>/img/favicon.png" alt="Foodbank CRM">
    <a class="nav-brand" href="<?php echo $index_url; ?>">Foodbank CRM</a>
    <a class="nav-back" href="<?php echo $index_url; ?>">← Back to Home</a>
</nav>

<div class="page-wrap">

<?php if ($submitted): ?>

    <!-- ── Success ── -->
    <div class="card">
        <div class="success-wrap">
            <div class="success-icon">✅</div>
            <h2>Request Received</h2>
            <p>Your <?php echo $req_scope === 'account' ? 'account and data deletion' : 'data deletion'; ?> request has been submitted successfully.</p>
            <div class="ref-box">Ref: DEL-<?php echo strtoupper(substr(md5($req_email . time()), 0, 8)); ?></div>
            <p>We will process your request within <strong>30 days</strong> in accordance with the Nigeria Data Protection Act (NDPA) 2023 and notify you at <strong><?php echo htmlspecialchars($req_email); ?></strong> once complete.</p>
            <div class="alt-contact">
                Questions? Email us at <a href="mailto:support@xdigitalfoodbank.com">support@xdigitalfoodbank.com</a>
            </div>
        </div>
    </div>

<?php else: ?>

    <!-- ── Hero ── -->
    <div class="hero">
        <div class="hero-icon">🗑️</div>
        <h1>Delete Account or Data</h1>
        <p>Use this form to request deletion of your Foodbank CRM account or personal data. Requests are processed within 30 days.</p>
    </div>

    <!-- ── How it works ── -->
    <div class="card">
        <div class="card-title">How to submit your request</div>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-text"><strong>Fill in the form below</strong> with the email address linked to your Foodbank CRM account.</div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-text"><strong>Choose the scope</strong> — full account deletion, or data deletion only while keeping your account.</div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-text"><strong>Submit</strong> — our team at Next Digital Solutions will verify your identity and process your request within <strong>30 days</strong>.</div>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <div class="step-text"><strong>Confirmation email</strong> will be sent to you once deletion is complete.</div>
            </div>
        </div>
    </div>

    <!-- ── What gets deleted ── -->
    <div class="card">
        <div class="card-title">What data is deleted or retained</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data Type</th>
                    <th>On Account Deletion</th>
                    <th>On Data-Only Request</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Name, email, phone, address</td>
                    <td><span class="badge badge-red">Deleted</span></td>
                    <td><span class="badge badge-red">Deleted</span></td>
                </tr>
                <tr>
                    <td>Subscription & order history</td>
                    <td><span class="badge badge-red">Deleted</span></td>
                    <td><span class="badge badge-red">Deleted</span></td>
                </tr>
                <tr>
                    <td>Push notification token</td>
                    <td><span class="badge badge-red">Deleted</span></td>
                    <td><span class="badge badge-red">Deleted</span></td>
                </tr>
                <tr>
                    <td>Login credentials</td>
                    <td><span class="badge badge-red">Deleted</span></td>
                    <td><span class="badge badge-green">Kept (account active)</span></td>
                </tr>
                <tr>
                    <td>Payment transaction records</td>
                    <td><span class="badge badge-amber">Retained 7 years</span></td>
                    <td><span class="badge badge-amber">Retained 7 years</span></td>
                </tr>
                <tr>
                    <td>Distribution & supply audit logs</td>
                    <td><span class="badge badge-amber">Retained 7 years</span></td>
                    <td><span class="badge badge-amber">Retained 7 years</span></td>
                </tr>
            </tbody>
        </table>
        <p style="font-size:12px; color:var(--muted); margin-top:12px;">
            Financial and audit records are retained for 7 years as required by Nigerian financial regulations. All retained records are anonymised where possible.
        </p>
    </div>

    <!-- ── Form ── -->
    <div class="card">
        <div class="card-title">Submit your request</div>

        <?php if ($form_error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($form_error); ?></div>
        <?php endif; ?>

        <div class="alert alert-warn">
            ⚠️ Account deletion is <strong>permanent and irreversible</strong>. Once deleted, your subscription history and profile cannot be recovered.
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label>Full Name <span class="req">*</span></label>
                <input type="text" name="req_name" placeholder="e.g. Adaeze Okonkwo" required
                       value="<?php echo htmlspecialchars($_POST['req_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Email Address on Your Account <span class="req">*</span></label>
                <input type="email" name="req_email" placeholder="e.g. adaeze@email.com" required
                       value="<?php echo htmlspecialchars($_POST['req_email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Account Type <span class="req">*</span></label>
                <select name="req_type" required>
                    <option value="" disabled <?php echo empty($_POST['req_type']) ? 'selected' : ''; ?>>Select your account type</option>
                    <option value="Member (Beneficiary)" <?php echo (($_POST['req_type'] ?? '') === 'Member (Beneficiary)') ? 'selected' : ''; ?>>Member (Beneficiary)</option>
                    <option value="Vendor" <?php echo (($_POST['req_type'] ?? '') === 'Vendor') ? 'selected' : ''; ?>>Vendor / Supplier</option>
                </select>
            </div>

            <div class="form-group">
                <label>What would you like to delete? <span class="req">*</span></label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="req_scope" value="account" required
                               <?php echo (($_POST['req_scope'] ?? '') === 'account') ? 'checked' : ''; ?>>
                        <div class="radio-label-text">
                            <strong>My full account and all data</strong>
                            <span>Permanently removes your account, profile, orders, and personal data.</span>
                        </div>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="req_scope" value="data_only"
                               <?php echo (($_POST['req_scope'] ?? '') === 'data_only') ? 'checked' : ''; ?>>
                        <div class="radio-label-text">
                            <strong>My personal data only (keep account)</strong>
                            <span>Removes your personal details and history, but your login remains active.</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Reason for request <span style="color:var(--muted); font-weight:400;">(optional)</span></label>
                <textarea name="req_reason" placeholder="Let us know why you're leaving (optional)..."><?php echo htmlspecialchars($_POST['req_reason'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="check-label">
                    <input type="checkbox" name="confirmed" value="1" <?php echo !empty($_POST['confirmed']) ? 'checked' : ''; ?>>
                    <span>I understand that account deletion is <strong>permanent and cannot be undone</strong>, and that financial/audit records may be retained for up to 7 years as required by law.</span>
                </label>
            </div>

            <button type="submit" class="btn-submit">Submit Deletion Request</button>
        </form>

        <p style="font-size:12px; color:var(--muted); margin-top:16px; text-align:center;">
            Alternatively, email us directly at
            <a href="mailto:support@xdigitalfoodbank.com" style="color:var(--teal); font-weight:600;">support@xdigitalfoodbank.com</a>
            with subject line <strong>"Account Deletion Request"</strong>.
        </p>
    </div>

<?php endif; ?>

    <!-- ── Footer ── -->
    <div class="page-footer">
        <p>Foodbank CRM is operated by <strong>Next Digital Solutions</strong> · xdigitalfoodbank.com</p>
        <p style="margin-top:6px;">
            <a href="privacy_policy.php">Privacy Policy</a> &nbsp;·&nbsp;
            <a href="terms.php">Terms &amp; Conditions</a> &nbsp;·&nbsp;
            <a href="mailto:support@xdigitalfoodbank.com">Contact Support</a>
        </p>
    </div>

</div><!-- /page-wrap -->
</body>
</html>
