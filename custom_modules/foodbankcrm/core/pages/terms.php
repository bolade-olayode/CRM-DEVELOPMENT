<?php
/*
 * Terms and Conditions — Foodbank CRM
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
    <title>Terms &amp; Conditions — Foodbank CRM</title>
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
        /* NAV */
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

        /* HERO BAND */
        .legal-hero {
            background: linear-gradient(135deg, var(--teal-deep) 0%, var(--teal) 100%);
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

        /* BODY */
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

        /* FOOTER */
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
    <h1>Terms &amp; Conditions</h1>
    <p>Please read these terms carefully before using the Foodbank CRM platform.</p>
    <span class="effective-badge">Effective Date: 1 January 2025</span>
</div>

<div class="legal-wrap">

    <div class="toc">
        <h3>Table of Contents</h3>
        <ol>
            <li><a href="#s1">Acceptance of Terms</a></li>
            <li><a href="#s2">Eligibility</a></li>
            <li><a href="#s3">User Accounts</a></li>
            <li><a href="#s4">Permitted Use</a></li>
            <li><a href="#s5">User Content</a></li>
            <li><a href="#s6">Third-Party Services</a></li>
            <li><a href="#s7">Donations &amp; Transactions</a></li>
            <li><a href="#s8">Intellectual Property</a></li>
            <li><a href="#s9">Privacy &amp; Data Protection</a></li>
            <li><a href="#s10">Security</a></li>
            <li><a href="#s11">Disclaimers</a></li>
            <li><a href="#s12">Limitation of Liability</a></li>
            <li><a href="#s13">Indemnification</a></li>
            <li><a href="#s14">Termination</a></li>
            <li><a href="#s15">Governing Law</a></li>
            <li><a href="#s16">Changes to Terms</a></li>
            <li><a href="#s17">Contact Us</a></li>
        </ol>
    </div>

    <div class="section" id="s1">
        <span class="section-num">Section 01</span>
        <h2>Acceptance of Terms</h2>
        <p>Welcome to Foodbank CRM, a platform operated by <strong>Next Digital Solutions (FoodBank)</strong>. By accessing or using the Foodbank CRM website, mobile application, or any related services (collectively, the "Platform"), you agree to be bound by these Terms and Conditions ("Terms").</p>
        <p>If you do not agree to these Terms, you must not access or use the Platform. Your continued use of the Platform constitutes acceptance of any updates or revisions to these Terms.</p>
    </div>

    <div class="section" id="s2">
        <span class="section-num">Section 02</span>
        <h2>Eligibility</h2>
        <p>To use the Platform, you must:</p>
        <ul>
            <li>Be at least 18 years of age, or have parental/guardian consent if between 13–17 years old;</li>
            <li>Have the legal capacity to enter into binding agreements;</li>
            <li>Not be prohibited from using the Platform under Nigerian law or the laws of your jurisdiction;</li>
            <li>Provide accurate and truthful information during registration.</li>
        </ul>
        <p>We reserve the right to verify eligibility and deny access to any user who does not meet these requirements.</p>
    </div>

    <div class="section" id="s3">
        <span class="section-num">Section 03</span>
        <h2>User Accounts</h2>
        <p>You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account. You agree to:</p>
        <ul>
            <li>Provide accurate, current, and complete information during registration;</li>
            <li>Update your information promptly if it changes;</li>
            <li>Notify us immediately at <a href="mailto:support@xdigitalfoodbank.com">support@xdigitalfoodbank.com</a> if you suspect unauthorised access to your account;</li>
            <li>Not share your password or account access with any other person;</li>
            <li>Not create multiple accounts for the same individual or entity without our prior written consent.</li>
        </ul>
        <p>We reserve the right to suspend or terminate accounts that violate these Terms.</p>
    </div>

    <div class="section" id="s4">
        <span class="section-num">Section 04</span>
        <h2>Permitted Use</h2>
        <p>You agree to use the Platform solely for its intended purpose of facilitating food distribution, supply management, and community welfare. You must not:</p>
        <ul>
            <li>Use the Platform for any unlawful, fraudulent, or harmful purpose;</li>
            <li>Post or transmit misleading, defamatory, obscene, or offensive content;</li>
            <li>Attempt to gain unauthorised access to any part of the Platform or its infrastructure;</li>
            <li>Use automated tools, bots, or scripts to scrape, crawl, or interact with the Platform;</li>
            <li>Interfere with or disrupt the integrity or performance of the Platform;</li>
            <li>Impersonate any person or entity, or misrepresent your affiliation with any person or entity;</li>
            <li>Use the Platform to distribute unsolicited communications (spam).</li>
        </ul>
    </div>

    <div class="section" id="s5">
        <span class="section-num">Section 05</span>
        <h2>User Content</h2>
        <p>By submitting content to the Platform (including listings, messages, documents, and images), you grant Next Digital Solutions a non-exclusive, royalty-free, worldwide licence to use, display, and process that content solely for the purpose of operating and improving the Platform.</p>
        <p>You represent and warrant that:</p>
        <ul>
            <li>You own or have the necessary rights to the content you submit;</li>
            <li>Your content does not infringe any third-party intellectual property rights;</li>
            <li>Your content complies with applicable Nigerian law and these Terms.</li>
        </ul>
        <p>We reserve the right to remove any content that violates these Terms without prior notice.</p>
    </div>

    <div class="section" id="s6">
        <span class="section-num">Section 06</span>
        <h2>Third-Party Services</h2>
        <p>The Platform integrates with third-party services including but not limited to Paystack (payment processing) and cloud infrastructure providers. Your use of these services is governed by their respective terms and privacy policies.</p>
        <p>We do not control and are not responsible for the content, privacy practices, or availability of third-party services. We encourage you to review the terms of any third-party service you use through our Platform.</p>
        <p>Any transactions processed through third-party payment gateways are subject to those providers' terms. We are not liable for any losses arising from third-party service failures or errors.</p>
    </div>

    <div class="section" id="s7">
        <span class="section-num">Section 07</span>
        <h2>Donations &amp; Transactions</h2>
        <p>All financial transactions on the Platform, including subscription payments, vendor payments, and donations, are processed through secure third-party payment providers. By initiating a transaction, you confirm that:</p>
        <ul>
            <li>You are authorised to use the payment method provided;</li>
            <li>The payment details you provide are accurate and complete;</li>
            <li>You understand that subscription fees and confirmed transactions may be non-refundable unless otherwise stated.</li>
        </ul>
        <p>We reserve the right to reverse or cancel transactions suspected of fraud, error, or policy violation. All amounts are stated in Nigerian Naira (&#8358;) unless otherwise indicated.</p>
        <p>In the event of a payment dispute, contact us at <a href="mailto:support@xdigitalfoodbank.com">support@xdigitalfoodbank.com</a> within 14 days of the transaction date.</p>
    </div>

    <div class="section" id="s8">
        <span class="section-num">Section 08</span>
        <h2>Intellectual Property</h2>
        <p>All content, design elements, software, trademarks, logos, and materials on the Platform are the exclusive property of Next Digital Solutions (FoodBank) or its licensors and are protected under applicable Nigerian and international intellectual property laws.</p>
        <p>You are granted a limited, non-exclusive, non-transferable licence to access and use the Platform for its intended purpose. You must not:</p>
        <ul>
            <li>Copy, reproduce, modify, or distribute any part of the Platform without our prior written consent;</li>
            <li>Reverse-engineer or attempt to extract the source code of any software component;</li>
            <li>Use our trademarks or branding without our express permission.</li>
        </ul>
    </div>

    <div class="section" id="s9">
        <span class="section-num">Section 09</span>
        <h2>Privacy &amp; Data Protection</h2>
        <p>We are committed to protecting your personal data in accordance with the <strong>Nigeria Data Protection Act 2023 (NDPA)</strong> and the Nigeria Data Protection Regulation (NDPR).</p>
        <p>We collect and process personal data including your name, contact details, payment information, and usage data for the purposes of operating the Platform, processing transactions, and improving our services. We do not sell your personal data to third parties.</p>
        <p>For full details on how we collect, use, store, and protect your data — including your rights as a data subject — please read our <a href="privacy_policy.php" style="color: var(--teal);">Privacy Policy</a>.</p>
        <p>By using the Platform, you consent to the collection and processing of your data as described in our Privacy Policy.</p>
    </div>

    <div class="section" id="s10">
        <span class="section-num">Section 10</span>
        <h2>Security</h2>
        <p>We implement industry-standard security measures to protect the Platform and your data, including encrypted communications (TLS/HTTPS), secure password hashing, and role-based access controls.</p>
        <p>However, no system is completely secure. You acknowledge that:</p>
        <ul>
            <li>You use the Platform at your own risk in respect of any security incidents beyond our reasonable control;</li>
            <li>You are responsible for maintaining the security of your own devices and account credentials;</li>
            <li>You will report any suspected security vulnerabilities promptly to <a href="mailto:support@xdigitalfoodbank.com">support@xdigitalfoodbank.com</a>.</li>
        </ul>
    </div>

    <div class="section" id="s11">
        <span class="section-num">Section 11</span>
        <h2>Disclaimers</h2>
        <p>The Platform is provided on an <strong>"as is" and "as available"</strong> basis without warranties of any kind, express or implied, including but not limited to warranties of merchantability, fitness for a particular purpose, or non-infringement.</p>
        <p>We do not warrant that:</p>
        <ul>
            <li>The Platform will be uninterrupted, error-free, or free from viruses or other harmful components;</li>
            <li>Results obtained from using the Platform will be accurate or reliable;</li>
            <li>Any defects in the Platform will be corrected.</li>
        </ul>
        <p>We disclaim all liability for actions taken or not taken based on information provided through the Platform.</p>
    </div>

    <div class="section" id="s12">
        <span class="section-num">Section 12</span>
        <h2>Limitation of Liability</h2>
        <p>To the fullest extent permitted by applicable law, Next Digital Solutions (FoodBank) and its directors, officers, employees, and agents shall not be liable for any:</p>
        <ul>
            <li>Indirect, incidental, special, consequential, or punitive damages;</li>
            <li>Loss of profits, data, goodwill, or business opportunities;</li>
            <li>Damages arising from unauthorised access to or alteration of your data;</li>
            <li>Any other damages arising from your use of or inability to use the Platform.</li>
        </ul>
        <p>Where liability cannot be excluded by law, our total aggregate liability to you for any claim arising from these Terms or your use of the Platform shall not exceed <strong>&#8358;50,000 (Fifty Thousand Naira)</strong> or the amount you paid to us in the preceding three (3) months, whichever is greater.</p>
    </div>

    <div class="section" id="s13">
        <span class="section-num">Section 13</span>
        <h2>Indemnification</h2>
        <p>You agree to indemnify, defend, and hold harmless Next Digital Solutions (FoodBank), its affiliates, directors, employees, and agents from and against any claims, liabilities, damages, losses, and expenses (including reasonable legal fees) arising from:</p>
        <ul>
            <li>Your use of or access to the Platform;</li>
            <li>Your violation of these Terms;</li>
            <li>Your violation of any applicable law or third-party rights;</li>
            <li>Any content you submit to the Platform.</li>
        </ul>
    </div>

    <div class="section" id="s14">
        <span class="section-num">Section 14</span>
        <h2>Termination</h2>
        <p>We reserve the right to suspend or permanently terminate your access to the Platform at any time, without prior notice, if we reasonably believe you have violated these Terms or engaged in fraudulent, harmful, or illegal activity.</p>
        <p>You may terminate your account at any time by contacting us at <a href="mailto:support@xdigitalfoodbank.com">support@xdigitalfoodbank.com</a>. Upon termination:</p>
        <ul>
            <li>Your right to access and use the Platform ceases immediately;</li>
            <li>We may retain your data as required by law or for legitimate business purposes;</li>
            <li>Any outstanding obligations or liabilities incurred prior to termination shall survive.</li>
        </ul>
        <p>Sections relating to intellectual property, disclaimers, limitation of liability, indemnification, and governing law shall survive any termination of these Terms.</p>
    </div>

    <div class="section" id="s15">
        <span class="section-num">Section 15</span>
        <h2>Governing Law &amp; Dispute Resolution</h2>
        <p>These Terms are governed by and construed in accordance with the laws of the <strong>Federal Republic of Nigeria</strong>. Any dispute arising out of or in connection with these Terms shall first be subject to good-faith negotiation between the parties.</p>
        <p>If a dispute cannot be resolved through negotiation within 30 days, it shall be referred to the exclusive jurisdiction of the courts of <strong>Lagos State, Nigeria</strong>.</p>
        <p>Nothing in this clause prevents either party from seeking urgent injunctive relief from any court of competent jurisdiction.</p>
    </div>

    <div class="section" id="s16">
        <span class="section-num">Section 16</span>
        <h2>Changes to Terms</h2>
        <p>We may update these Terms from time to time to reflect changes in our services, technology, or applicable law. We will notify registered users of material changes via email or a prominent notice on the Platform.</p>
        <p>The date of the most recent revision will be shown at the top of this page. Your continued use of the Platform after any changes constitutes your acceptance of the revised Terms.</p>
        <p>If you do not agree with any revised Terms, you must stop using the Platform and may request deletion of your account.</p>
    </div>

    <div class="section" id="s17">
        <span class="section-num">Section 17</span>
        <h2>Contact Us</h2>
        <p>If you have any questions, concerns, or requests regarding these Terms, please contact us:</p>
        <ul>
            <li><strong>Company:</strong> Next Digital Solutions (FoodBank)</li>
            <li><strong>Address:</strong> Emeka Anyaoku Street, Garki, Area 11, FCT, Abuja, Nigeria</li>
            <li><strong>Email:</strong> <a href="mailto:support@xdigitalfoodbank.com">support@xdigitalfoodbank.com</a></li>
            <li><strong>Phone:</strong> +234 701 389 2929</li>
            <li><strong>Website:</strong> <a href="https://xdigitalfoodbank.com" target="_blank" rel="noopener">xdigitalfoodbank.com</a></li>
        </ul>
        <p>We aim to respond to all inquiries within 2 business days.</p>
    </div>

</div>

<div class="legal-footer">
    <p>&copy; <?php echo date('Y'); ?> Foodbank CRM &mdash; Next Digital Solutions (FoodBank). All rights reserved.</p>
    <p style="margin-top:6px;">
        <a href="<?php echo $index_url; ?>">Home</a> &nbsp;&bull;&nbsp;
        <a href="privacy_policy.php">Privacy Policy</a>
    </p>
</div>

</body>
</html>
