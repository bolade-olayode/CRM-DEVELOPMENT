<?php
/*
 * 404 — Page Not Found — Foodbank CRM
 */

if (!defined('NOREQUIRESOC'))    define('NOREQUIRESOC',    '1');
if (!defined('NOREQUIRETRAN'))   define('NOREQUIRETRAN',   '1');
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK',     '1');
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',  '1');
if (!defined('NOLOGIN'))         define('NOLOGIN',         '1');
if (!defined('NOREQUIREMENU'))   define('NOREQUIREMENU',   '1');
if (!defined('NOREQUIREHTML'))   define('NOREQUIREHTML',   '1');

require_once dirname(__DIR__, 4) . '/main.inc.php';

http_response_code(404);

$base      = DOL_URL_ROOT . '/custom/foodbankcrm';
$index_url = $base . '/index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found — Foodbank CRM</title>
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
            --text:       #1e293b;
            --muted:      #64748b;
            --border:     #e2e8f0;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f8fafc;
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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

        /* ── Main ── */
        .main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 56px 48px;
            text-align: center;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 4px 24px rgba(0,0,0,.05);
        }

        /* ── Illustration ── */
        .illustration {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 32px;
        }
        .ill-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--teal-light);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .ill-icon { font-size: 52px; }
        .ill-badge {
            position: absolute;
            bottom: -4px;
            right: -4px;
            background: #f59e0b;
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 8px;
            border-radius: 20px;
            border: 2px solid #f8fafc;
        }

        /* ── Code ── */
        .error-code {
            font-size: 80px;
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, var(--teal-deep), #0891b2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 16px;
        }

        h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .sub {
            color: var(--muted);
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        /* ── Quick links ── */
        .links {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 28px;
        }
        .link-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
            text-decoration: none;
            color: var(--text);
            font-size: 14px;
            font-weight: 500;
            transition: border-color .15s, background .15s;
        }
        .link-row:hover {
            border-color: var(--teal);
            background: var(--teal-light);
            color: var(--teal-deep);
        }
        .link-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #fff;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .link-arrow { margin-left: auto; color: var(--muted); font-size: 16px; }

        /* ── Home button ── */
        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: var(--teal);
            color: #fff;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: background .15s;
        }
        .btn-home:hover { background: var(--teal-deep); }

        /* ── Footer ── */
        .page-footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: var(--muted);
            border-top: 1px solid var(--border);
        }
        .page-footer a { color: var(--teal); text-decoration: none; }

        @media (max-width: 480px) {
            .card { padding: 36px 20px; }
            .error-code { font-size: 64px; }
            h1 { font-size: 19px; }
        }
    </style>
</head>
<body>

<!-- Nav -->
<nav class="nav">
    <img src="<?php echo $base; ?>/img/favicon.png" alt="Foodbank CRM">
    <a class="nav-brand" href="<?php echo $index_url; ?>">Foodbank CRM</a>
</nav>

<!-- Main -->
<main class="main">
    <div class="card">

        <div class="illustration">
            <div class="ill-circle">
                <span class="ill-icon">🔍</span>
            </div>
            <span class="ill-badge">404</span>
        </div>

        <div class="error-code">404</div>
        <h1>Page Not Found</h1>
        <p class="sub">
            Oops! The page you're looking for doesn't exist or may have been moved.<br>
            Here are some helpful links to get you back on track.
        </p>

        <div class="links">
            <a class="link-row" href="<?php echo $index_url; ?>">
                <span class="link-icon">🏠</span>
                <span>Home — Foodbank CRM</span>
                <span class="link-arrow">›</span>
            </a>
            <a class="link-row" href="<?php echo $base; ?>/core/pages/dashboard_beneficiary.php">
                <span class="link-icon">📦</span>
                <span>Member Dashboard</span>
                <span class="link-arrow">›</span>
            </a>
            <a class="link-row" href="<?php echo $base; ?>/core/pages/dashboard_vendor.php">
                <span class="link-icon">🏪</span>
                <span>Vendor Dashboard</span>
                <span class="link-arrow">›</span>
            </a>
            <a class="link-row" href="mailto:support@xdigitalfoodbank.com">
                <span class="link-icon">✉️</span>
                <span>Contact Support</span>
                <span class="link-arrow">›</span>
            </a>
        </div>

        <a class="btn-home" href="<?php echo $index_url; ?>">
            ← Back to Home
        </a>

    </div>
</main>

<!-- Footer -->
<footer class="page-footer">
    Foodbank CRM &nbsp;·&nbsp; By <strong>Next Digital Solutions</strong> &nbsp;·&nbsp;
    <a href="<?php echo $base; ?>/core/pages/privacy_policy.php">Privacy Policy</a> &nbsp;·&nbsp;
    <a href="<?php echo $base; ?>/core/pages/terms.php">Terms &amp; Conditions</a>
</footer>

</body>
</html>
