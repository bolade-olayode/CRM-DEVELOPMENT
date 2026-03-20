<?php
/*
 * Privacy Policy — Foodbank CRM
 */

if (!defined('NOREQUIRESOC'))    define('NOREQUIRESOC',    '1');
if (!defined('NOREQUIRETRAN'))   define('NOREQUIRETRAN',   '1');
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK',     '1');
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',  '1');
if (!defined('NOLOGIN'))         define('NOLOGIN',         '1');
if (!defined('NOREQUIREMENU'))   define('NOREQUIREMENU',   '1');
if (!defined('NOREQUIREHTML'))   define('NOREQUIREHTML',   '1');

require_once dirname(__DIR__, 4) . '/main.inc.php';

$base = DOL_URL_ROOT . '/custom/foodbankcrm';
$logo_colored = $base . '/img/food bank crm favicon.png';
$index_url    = $base . '/index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — Foodbank CRM</title>
    <link rel="icon" type="image/png" href="<?php echo $base; ?>/img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --teal:       #0d9488;
            --teal-deep:  #0f766e;
            --teal-light: #e6f4f1;
            --text:       #1e293b;
            --muted:      #64748b;
            --border:     #e2e8f0;
        }
        body {
            font-family: 'Inter', sans-serif;
            color: var(--text);
            background: #f8fafc;
            line-height: 1.75;
        }
        nav {
            position: sticky; top: 0; z-index: 100;
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            height: 64px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .nav-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .nav-brand img { height: 36px; width: 36px; border-radius: 8px; }
        .nav-brand span { font-size: 16px; font-weight: 600; color: var(--text); }
        .btn-back {
            font-size: 14px; font-weight: 500; color: var(--teal);
            text-decoration: none; padding: 8px 18px;
            border: 1px solid var(--teal); border-radius: 8px;
            transition: background .15s, color .15s;
        }
        .btn-back:hover { background: var(--teal); color: #fff; }
        .legal-hero {
            background: linear-gradient(135deg, var(--teal-deep) 0%, #0891b2 100%);
            color: #fff;
            padding: 64px 24px 48px;
            text-align: center;
        }
        .legal-hero h1 {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 500;
            margin-bottom: 12px;
        }
        .legal-hero p { font-size: 15px; opacity: .85; }
        .effective-badge {
            display: inline-block;
            margin-top: 18px;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.3);
            border-radius: 20px;
            padding: 6px 18px;
            font-size: 13px;
        }
        .ndpa-badge {
            display: inline-flex; align-items: center; gap: 8px;
            margin-top: 12px; margin-left: 10px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 20px;
            padding: 6px 16px;
            font-size: 12px; font-weight: 600;
            letter-spacing: .4px;
        }
        .legal-wrap {
            max-width: 820px;
            margin: 0 auto;
            padding: 56px 24px 80px;
        }
        .toc {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 28px 32px;
            margin-bottom: 48px;
        }
        .toc h3 {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: 18px; font-weight: 500;
            margin-bottom: 16px; color: var(--teal-deep);
        }
        .toc ol {
            padding-left: 20px;
            columns: 2;
            column-gap: 32px;
        }
        @media (max-width: 560px) { .toc ol { columns: 1; } }
        .toc ol li { margin-bottom: 6px; font-size: 14px; }
        .toc ol li a { color: var(--teal); text-decoration: none; }
        .toc ol li a:hover { text-decoration: underline; }
        .summary-box {
            background: var(--teal-light);
            border-left: 4px solid var(--teal);
            border-radius: 0 10px 10px 0;
            padding: 20px 24px;
            margin-bottom: 48px;
            font-size: 14px;
            color: var(--teal-deep);
        }
        .summary-box strong { display: block; font-size: 15px; margin-bottom: 6px; }
        .section {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 36px 40px;
            margin-bottom: 24px;
        }
        @media (max-width: 640px) { .section { padding: 28px 22px; } }
        .section-num {
            display: inline-block;
            background: var(--teal-light);
            color: var(--teal-deep);
            font-size: 12px; font-weight: 600;
            border-radius: 6px;
            padding: 3px 10px;
            margin-bottom: 10px;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .section h2 {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: 1.35rem; font-weight: 500;
            margin-bottom: 16px; color: var(--text);
        }
        .section p { font-size: 15px; color: #374151; margin-bottom: 12px; }
        .section p:last-child { margin-bottom: 0; }
        .section ul, .section ol {
            padding-left: 22px;
            margin-bottom: 12px;
        }
        .section ul li, .section ol li {
            font-size: 15px; color: #374151;
            margin-bottom: 6px;
        }
        .section strong { font-weight: 600; color: var(--text); }
        .section table {
            width: 100%; border-collapse: collapse;
            margin-top: 16px; font-size: 14px;
        }
        .section table th {
            background: var(--teal-light);
            color: var(--teal-deep);
            text-align: left; padding: 10px 14px;
            font-weight: 600; font-size: 13px;
        }
        .section table td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }
        .rights-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 16px;
        }
        @media (max-width: 580px) { .rights-grid { grid-template-columns: 1fr; } }
        .rights-card {
            background: var(--teal-light);
            border-radius: 8px;
            padding: 16px 18px;
        }
        .rights-card h4 { font-size: 14px; font-weight: 600; color: var(--teal-deep); margin-bottom: 6px; }
        .rights-card p { font-size: 13px; color: #374151; margin-bottom: 0; }
        .legal-footer {
            background: var(--teal-deep);
            color: rgba(255,255,255,.75);
            text-align: center;
            padding: 28px 24px;
            font-size: 13px;
        }
        .legal-footer a { color: rgba(255,255,255,.9); }
        .legal-footer a:hover { color: #fff; }
    </style>
</head>
<body>

<nav>
    <a href="<?php echo $index_url; ?>" class="nav-brand">
        <img src="<?php echo $logo_colored; ?>" alt="Foodbank CRM">
        <span>Foodbank CRM</span>
    </a>
    <a href="<?php echo $index_url; ?>" class="btn-back">← Back to Home</a>
</nav>

<div class="legal-hero">
    <h1>Privacy Policy</h1>
    <p>How we collect, use, and protect your personal information.</p>
    <br>
    <span class="effective-badge">Effective Date: 1 January 2025</span>
    <span class="ndpa-badge">NDPA 2023 Compliant</span>
</div>

<div class="legal-wrap">

    <div class="summary-box">
        <strong>Summary (plain language)</strong>
        We collect information you provide when registering or transacting on the Platform. We use it to deliver our services, process payments, and improve the Platform. We do not sell your data. You have the right to access, correct, or delete your data at any time. For full details, read the sections below.
    </div>

    <div class="toc">
        <h3>Table of Contents</h3>
        <ol>
            <li><a href="#p1">Who We Are</a></li>
            <li><a href="#p2">Data We Collect</a></li>
            <li><a href="#p3">How We Collect Data</a></li>
            <li><a href="#p4">Why We Use Your Data</a></li>
            <li><a href="#p5">Legal Basis for Processing</a></li>
            <li><a href="#p6">Data Sharing</a></li>
            <li><a href="#p7">International Transfers</a></li>
            <li><a href="#p8">Data Retention</a></li>
            <li><a href="#p9">Your Rights</a></li>
            <li><a href="#p10">Cookies &amp; Tracking</a></li>
            <li><a href="#p11">Children's Privacy</a></li>
            <li><a href="#p12">Security Measures</a></li>
            <li><a href="#p13">Changes to This Policy</a></li>
            <li><a href="#p14">Contact &amp; Complaints</a></li>
        </ol>
    </div>

    <div class="section" id="p1">
        <span class="section-num">Section 01</span>
        <h2>Who We Are</h2>
        <p><strong>Next Digital Solutions (FoodBank)</strong> ("we", "us", or "our") is the data controller responsible for your personal data collected through the Foodbank CRM platform ("Platform").</p>
        <p>We operate in compliance with the <strong>Nigeria Data Protection Act 2023 (NDPA)</strong>, the Nigeria Data Protection Regulation (NDPR), and all applicable data protection legislation.</p>
        <p><strong>Contact details:</strong></p>
        <ul>
            <li>Address: Emeka Anyaoku Street, Garki, Area 11, FCT, Abuja, Nigeria</li>
            <li>Email: <a href="mailto:support@xdigitalfoodbank.com">support@xdigitalfoodbank.com</a></li>
            <li>Phone: +234 701 389 2929</li>
        </ul>
    </div>

    <div class="section" id="p2">
        <span class="section-num">Section 02</span>
        <h2>Data We Collect</h2>
        <p>Depending on how you use the Platform, we may collect the following categories of personal data:</p>
        <table>
            <tr>
                <th>Category</th>
                <th>Examples</th>
            </tr>
            <tr>
                <td><strong>Identity Data</strong></td>
                <td>Full name, date of birth, gender, national ID details</td>
            </tr>
            <tr>
                <td><strong>Contact Data</strong></td>
                <td>Email address, phone number, physical address</td>
            </tr>
            <tr>
                <td><strong>Account Data</strong></td>
                <td>Username, password (hashed), account type (beneficiary/vendor), subscription tier</td>
            </tr>
            <tr>
                <td><strong>Financial Data</strong></td>
                <td>Bank account number, bank name, payment transaction records (processed via Paystack)</td>
            </tr>
            <tr>
                <td><strong>Business Data</strong></td>
                <td>Business name, CAC registration number, product categories (vendors only)</td>
            </tr>
            <tr>
                <td><strong>Usage Data</strong></td>
                <td>Login timestamps, pages visited, actions performed, device type, browser type, IP address</td>
            </tr>
            <tr>
                <td><strong>Communication Data</strong></td>
                <td>Emails, support messages, feedback forms</td>
            </tr>
            <tr>
                <td><strong>Content Data</strong></td>
                <td>Documents, images, or files you upload to the Platform</td>
            </tr>
        </table>
        <p style="margin-top:16px;">We do not intentionally collect sensitive personal data (e.g., health, biometric, or political data) unless strictly necessary for service delivery and with your explicit consent.</p>
    </div>

    <div class="section" id="p3">
        <span class="section-num">Section 03</span>
        <h2>How We Collect Your Data</h2>
        <p>We collect personal data through:</p>
        <ul>
            <li><strong>Direct interactions</strong> — when you register, subscribe, submit forms, or contact us;</li>
            <li><strong>Automated technologies</strong> — cookies, log files, and analytics tools that record your usage of the Platform;</li>
            <li><strong>Third-party sources</strong> — payment processors (e.g., Paystack) that share transaction data with us to fulfil your request;</li>
            <li><strong>Mobile application</strong> — when you use the Foodbank CRM mobile app, device identifiers and push notification tokens may be collected.</li>
        </ul>
    </div>

    <div class="section" id="p4">
        <span class="section-num">Section 04</span>
        <h2>Why We Use Your Data</h2>
        <p>We use your personal data for the following purposes:</p>
        <ul>
            <li><strong>Account management</strong> — creating and managing your account, authenticating logins, and maintaining your subscription;</li>
            <li><strong>Service delivery</strong> — processing orders, managing food distribution logistics, and facilitating vendor-beneficiary matching;</li>
            <li><strong>Payment processing</strong> — verifying payment details and processing transactions securely through Paystack;</li>
            <li><strong>Communications</strong> — sending service notifications, transaction confirmations, support responses, and (where opted in) marketing updates;</li>
            <li><strong>Platform improvement</strong> — analysing usage patterns to improve features, performance, and user experience;</li>
            <li><strong>Legal compliance</strong> — maintaining records as required by Nigerian law and responding to lawful regulatory requests;</li>
            <li><strong>Security</strong> — detecting and preventing fraud, abuse, and unauthorised access.</li>
        </ul>
    </div>

    <div class="section" id="p5">
        <span class="section-num">Section 05</span>
        <h2>Legal Basis for Processing</h2>
        <p>Under the NDPA 2023, we process your personal data on the following lawful bases:</p>
        <ul>
            <li><strong>Contract performance</strong> — processing necessary to provide the services you have subscribed to;</li>
            <li><strong>Consent</strong> — where you have freely given explicit consent (e.g., marketing emails, push notifications);</li>
            <li><strong>Legitimate interests</strong> — for fraud prevention, security, and platform analytics, where these interests are not overridden by your rights;</li>
            <li><strong>Legal obligation</strong> — where processing is required to comply with applicable Nigerian law or regulatory requirements.</li>
        </ul>
        <p>Where we rely on consent, you may withdraw it at any time without affecting the lawfulness of processing before withdrawal.</p>
    </div>

    <div class="section" id="p6">
        <span class="section-num">Section 06</span>
        <h2>Data Sharing &amp; Disclosure</h2>
        <p>We do not sell, rent, or trade your personal data. We may share your data with:</p>
        <ul>
            <li><strong>Payment processors</strong> (Paystack) — to process financial transactions securely;</li>
            <li><strong>Cloud hosting providers</strong> — that host our servers and databases under confidentiality agreements;</li>
            <li><strong>Authorised staff</strong> — employees or contractors who need access to deliver our services, bound by confidentiality obligations;</li>
            <li><strong>Regulatory authorities</strong> — where disclosure is required by law, court order, or lawful regulatory request;</li>
            <li><strong>Business successors</strong> — in the event of a merger, acquisition, or asset sale, where the successor commits to protect your data under equivalent terms.</li>
        </ul>
        <p>Any third-party processors we engage are bound by data processing agreements that require them to protect your data in accordance with applicable law.</p>
    </div>

    <div class="section" id="p7">
        <span class="section-num">Section 07</span>
        <h2>International Data Transfers</h2>
        <p>Your data is primarily stored and processed within Nigeria. Where data is transferred outside Nigeria (for example, to cloud infrastructure providers), we ensure adequate safeguards are in place, including:</p>
        <ul>
            <li>Data processing agreements incorporating NDPA-compliant standard contractual clauses;</li>
            <li>Transfers only to countries or organisations providing equivalent data protection standards;</li>
            <li>Your explicit consent where required by law.</li>
        </ul>
    </div>

    <div class="section" id="p8">
        <span class="section-num">Section 08</span>
        <h2>Data Retention</h2>
        <p>We retain your personal data only as long as necessary for the purposes it was collected, or as required by law. Retention periods are as follows:</p>
        <ul>
            <li><strong>Account data</strong> — retained for the duration of your active account plus 12 months after closure;</li>
            <li><strong>Transaction records</strong> — retained for 7 years to comply with Nigerian financial regulations;</li>
            <li><strong>Usage and log data</strong> — retained for up to 12 months;</li>
            <li><strong>Support communications</strong> — retained for 3 years from the date of resolution.</li>
        </ul>
        <p>After the applicable retention period, data is securely deleted or anonymised.</p>
    </div>

    <div class="section" id="p9">
        <span class="section-num">Section 09</span>
        <h2>Your Data Rights</h2>
        <p>Under the NDPA 2023, you have the following rights regarding your personal data:</p>
        <div class="rights-grid">
            <div class="rights-card">
                <h4>Right of Access</h4>
                <p>Request a copy of the personal data we hold about you.</p>
            </div>
            <div class="rights-card">
                <h4>Right to Rectification</h4>
                <p>Request correction of inaccurate or incomplete data.</p>
            </div>
            <div class="rights-card">
                <h4>Right to Erasure</h4>
                <p>Request deletion of your data where it is no longer necessary or lawfully held.</p>
            </div>
            <div class="rights-card">
                <h4>Right to Restriction</h4>
                <p>Request that we limit processing of your data in certain circumstances.</p>
            </div>
            <div class="rights-card">
                <h4>Right to Data Portability</h4>
                <p>Request your data in a structured, machine-readable format for transfer.</p>
            </div>
            <div class="rights-card">
                <h4>Right to Object</h4>
                <p>Object to processing based on legitimate interests or for direct marketing.</p>
            </div>
            <div class="rights-card">
                <h4>Right to Withdraw Consent</h4>
                <p>Withdraw consent at any time where processing is based on consent.</p>
            </div>
            <div class="rights-card">
                <h4>Right to Complain</h4>
                <p>Lodge a complaint with the Nigeria Data Protection Commission (NDPC).</p>
            </div>
        </div>
        <p style="margin-top:20px;">To exercise any of these rights, email us at <a href="mailto:support@xdigitalfoodbank.com" style="color:var(--teal);">support@xdigitalfoodbank.com</a> with the subject line "Data Rights Request". We will respond within 30 days.</p>
    </div>

    <div class="section" id="p10">
        <span class="section-num">Section 10</span>
        <h2>Cookies &amp; Tracking Technologies</h2>
        <p>The Platform uses cookies and similar technologies to:</p>
        <ul>
            <li>Maintain your login session;</li>
            <li>Remember your preferences;</li>
            <li>Measure Platform usage and performance.</li>
        </ul>
        <p>We use the following types of cookies:</p>
        <ul>
            <li><strong>Essential cookies</strong> — required for the Platform to function (e.g., session authentication). These cannot be disabled.</li>
            <li><strong>Analytics cookies</strong> — help us understand how users interact with the Platform. You may opt out via your browser settings.</li>
        </ul>
        <p>You can control cookies through your browser settings. Disabling essential cookies may affect Platform functionality.</p>
    </div>

    <div class="section" id="p11">
        <span class="section-num">Section 11</span>
        <h2>Children's Privacy</h2>
        <p>The Platform is not directed to children under the age of 13. We do not knowingly collect personal data from children under 13 without verifiable parental consent.</p>
        <p>Users between 13–17 may use the Platform only with the consent and supervision of a parent or legal guardian. If we become aware that we have inadvertently collected data from a child under 13 without consent, we will promptly delete it.</p>
        <p>If you believe a child's data has been collected without appropriate consent, contact us at <a href="mailto:support@xdigitalfoodbank.com">support@xdigitalfoodbank.com</a>.</p>
    </div>

    <div class="section" id="p12">
        <span class="section-num">Section 12</span>
        <h2>Security Measures</h2>
        <p>We implement appropriate technical and organisational measures to protect your personal data against unauthorised access, disclosure, alteration, or destruction, including:</p>
        <ul>
            <li>TLS/HTTPS encryption for all data in transit;</li>
            <li>Password hashing using industry-standard algorithms (bcrypt);</li>
            <li>Role-based access control limiting data access to authorised personnel;</li>
            <li>Regular security assessments and vulnerability monitoring;</li>
            <li>Secure payment processing handled entirely by Paystack (PCI-DSS compliant).</li>
        </ul>
        <p>In the event of a data breach that poses a risk to your rights and freedoms, we will notify affected users and the Nigeria Data Protection Commission (NDPC) within 72 hours of becoming aware of the breach, in accordance with the NDPA 2023.</p>
    </div>

    <div class="section" id="p13">
        <span class="section-num">Section 13</span>
        <h2>Changes to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. When we make material changes, we will:</p>
        <ul>
            <li>Update the effective date at the top of this page;</li>
            <li>Notify registered users via email or a prominent notice on the Platform;</li>
            <li>Where required by law, seek your consent before applying material changes.</li>
        </ul>
        <p>Your continued use of the Platform after changes are posted constitutes acceptance of the revised policy. We encourage you to review this page periodically.</p>
    </div>

    <div class="section" id="p14">
        <span class="section-num">Section 14</span>
        <h2>Contact &amp; Complaints</h2>
        <p>For any privacy-related questions, requests, or concerns, please contact our Data Protection Officer:</p>
        <ul>
            <li><strong>Company:</strong> Next Digital Solutions (FoodBank)</li>
            <li><strong>Address:</strong> Emeka Anyaoku Street, Garki, Area 11, FCT, Abuja, Nigeria</li>
            <li><strong>Email:</strong> <a href="mailto:support@xdigitalfoodbank.com">support@xdigitalfoodbank.com</a></li>
            <li><strong>Phone:</strong> +234 701 389 2929</li>
        </ul>
        <p>If you are not satisfied with our response, you have the right to lodge a complaint with the <strong>Nigeria Data Protection Commission (NDPC)</strong>:</p>
        <ul>
            <li>Website: <a href="https://ndpc.gov.ng" target="_blank" rel="noopener">ndpc.gov.ng</a></li>
            <li>Email: <a href="mailto:info@ndpc.gov.ng">info@ndpc.gov.ng</a></li>
        </ul>
    </div>

</div>

<div class="legal-footer">
    <p>&copy; <?php echo date('Y'); ?> Foodbank CRM &mdash; Next Digital Solutions (FoodBank). All rights reserved.</p>
    <p style="margin-top:6px;">
        <a href="<?php echo $index_url; ?>">Home</a> &nbsp;&bull;&nbsp;
        <a href="terms.php">Terms &amp; Conditions</a>
    </p>
</div>

</body>
</html>
