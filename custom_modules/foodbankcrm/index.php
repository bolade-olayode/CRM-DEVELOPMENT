<?php
/**
 * FOODBANK CRM — PUBLIC LANDING PAGE
 * Auto-redirects logged-in users to their dashboard.
 */

if (!defined("NOLOGIN"))    define("NOLOGIN",    1);
if (!defined("NOCSRFCHECK")) define("NOCSRFCHECK", 1);
if (!defined("NOIPCHECK"))  define("NOIPCHECK",  1);

require_once dirname(__DIR__, 2) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/foodbankcrm/class/permissions.class.php';

// Auto-redirect logged-in users
if (!empty($user->id)) {
    if ($user->admin || FoodbankPermissions::isAdmin($user)) {
        header('Location: core/pages/dashboard_admin.php'); exit;
    } elseif (FoodbankPermissions::isVendor($user, $db)) {
        header('Location: core/pages/dashboard_vendor.php'); exit;
    } elseif (FoodbankPermissions::isBeneficiary($user, $db)) {
        header('Location: core/pages/dashboard_beneficiary.php'); exit;
    }
    header('Location: '.DOL_URL_ROOT.'/index.php'); exit;
}

$login_url      = DOL_URL_ROOT . '/index.php?backtopage=' . urlencode(DOL_URL_ROOT . '/custom/foodbankcrm/index.php');
$base           = DOL_URL_ROOT . '/custom/foodbankcrm';
$logo_colored   = $base . '/img/logo.png';
$logo_white     = $base . '/img/logo-white.png';
$favicon        = $base . '/img/favicon.png';
$register_url   = 'core/pages/register.php';
$vendor_url     = 'core/pages/register_vendor.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodbank CRM | Smart Food Distribution Platform</title>
    <meta name="description" content="Connecting communities through efficient food distribution. Subscribe, browse packages, and get food delivered — all in one platform built for food banks.">
    <link rel="icon" type="image/png" href="<?php echo $favicon; ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600;1,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }

        :root {
            --teal:        #0d9488;
            --teal-dark:   #0f766e;
            --teal-deep:   #134e4a;
            --teal-faint:  #f0fdfa;
            --teal-mid:    #ccfbf1;
            --amber:       #d97706;
            --amber-faint: #fffbeb;
            --amber-mid:   #fde68a;
            --text:        #111827;
            --text-2:      #374151;
            --muted:       #6b7280;
            --muted-light: #9ca3af;
            --border:      #e5e7eb;
            --surface:     #f9fafb;
            --bg:          #ffffff;
            --dark:        #0f172a;
            --dark-2:      #1e293b;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--text);
            background: var(--bg);
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4 {
            font-family: 'EB Garamond', Georgia, serif;
        }

        /* ── NAV ───────────────────────────────────────────────────── */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 300;
            height: 68px;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 6%;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(229,231,235,0.8);
            transition: box-shadow .25s;
        }
        nav.scrolled { box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
        .nav-logo img { height: 44px; display: block; }
        .nav-links { display: flex; align-items: center; gap: 36px; }
        .nav-links a {
            font-family: 'Inter', sans-serif;
            font-size: 14px; font-weight: 500; color: var(--muted);
            text-decoration: none; transition: color .15s; letter-spacing: .01em;
        }
        .nav-links a:hover { color: var(--teal); }
        .nav-cta { display: flex; gap: 10px; align-items: center; }
        .btn-nav-ghost {
            padding: 8px 20px; border-radius: 8px;
            border: 1.5px solid var(--border); background: transparent;
            font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; color: var(--text);
            text-decoration: none; transition: all .15s;
        }
        .btn-nav-ghost:hover { border-color: var(--teal); color: var(--teal); background: var(--teal-faint); }
        .btn-nav-solid {
            padding: 9px 20px; border-radius: 8px;
            background: var(--teal); color: #fff;
            font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600;
            text-decoration: none; transition: background .15s;
        }
        .btn-nav-solid:hover { background: var(--teal-dark); }
        .hamburger {
            display: none; flex-direction: column; gap: 5px;
            cursor: pointer; padding: 6px; background: none; border: none;
        }
        .hamburger span { display: block; width: 22px; height: 2px; background: var(--text); border-radius: 2px; transition: all .2s; }
        .mobile-menu {
            display: none; position: fixed; top: 68px; left: 0; right: 0; z-index: 299;
            background: #fff; border-bottom: 1px solid var(--border);
            padding: 24px 6%; flex-direction: column; gap: 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        .mobile-menu.open { display: flex; }
        .mobile-menu a { font-size: 16px; font-weight: 500; color: var(--text); text-decoration: none; }
        .mob-cta { display: flex; flex-direction: column; gap: 10px; margin-top: 8px; }
        .mob-cta a { text-align: center; }

        /* ── HERO ──────────────────────────────────────────────────── */
        #home {
            min-height: 100vh;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            text-align: center;
            padding: 120px 6% 80px;
            background:
                radial-gradient(ellipse 80% 60% at 50% -10%, rgba(13,148,136,0.12) 0%, transparent 70%),
                radial-gradient(ellipse 60% 40% at 90% 80%, rgba(217,119,6,0.07) 0%, transparent 60%),
                #ffffff;
            position: relative; overflow: hidden;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--teal-faint); border: 1px solid var(--teal-mid);
            color: var(--teal-dark); border-radius: 30px;
            padding: 6px 16px; font-size: 13px; font-weight: 600;
            font-family: 'Inter', sans-serif;
            margin-bottom: 32px; letter-spacing: .01em;
        }
        .hero-badge-dot {
            width: 7px; height: 7px; border-radius: 50%; background: var(--teal);
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.8)} }
        .hero-title {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: clamp(44px, 7vw, 88px);
            font-weight: 700;
            line-height: 1.05;
            letter-spacing: -2px;
            color: var(--text);
            margin-bottom: 28px;
            max-width: 860px;
        }
        .hero-title em { font-style: italic; color: var(--teal); }
        .hero-sub {
            font-size: clamp(16px, 1.5vw, 19px);
            color: var(--muted);
            line-height: 1.75;
            max-width: 560px;
            margin: 0 auto 44px;
            font-weight: 400;
        }
        .hero-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 15px 36px; border-radius: 10px;
            background: var(--teal); color: #fff;
            font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 700;
            text-decoration: none; transition: background .2s, transform .2s, box-shadow .2s;
            box-shadow: 0 4px 16px rgba(13,148,136,0.3);
        }
        .btn-primary:hover { background: var(--teal-dark); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(13,148,136,0.35); }
        .btn-outline {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 15px 36px; border-radius: 10px;
            border: 2px solid var(--border); color: var(--text);
            font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 600;
            text-decoration: none; background: #fff; transition: all .2s;
        }
        .btn-outline:hover { border-color: var(--teal); color: var(--teal); transform: translateY(-2px); }
        .hero-scroll-hint {
            position: absolute; bottom: 36px; left: 50%; transform: translateX(-50%);
            display: flex; flex-direction: column; align-items: center; gap: 6px;
            color: var(--muted-light); font-size: 12px; font-family: 'Inter', sans-serif; font-weight: 500;
            letter-spacing: .05em; text-transform: uppercase;
        }
        .scroll-line {
            width: 1px; height: 40px;
            background: linear-gradient(to bottom, var(--border), transparent);
        }

        /* ── STATS ─────────────────────────────────────────────────── */
        .stats-band { background: var(--teal-deep); padding: 0 6%; }
        .stats-inner {
            max-width: 1100px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(4, 1fr);
            border-left: 1px solid rgba(255,255,255,0.1);
        }
        .stat-cell {
            padding: 40px 32px;
            border-right: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        .stat-num {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: 46px; font-weight: 700; color: #fff;
            line-height: 1; letter-spacing: -2px;
        }
        .stat-num em { font-style: normal; color: var(--teal-mid); font-size: 34px; }
        .stat-lbl { font-size: 13px; color: rgba(255,255,255,0.55); margin-top: 8px; letter-spacing: .03em; }

        /* ── SECTIONS ──────────────────────────────────────────────── */
        section { padding: 112px 6%; }
        .section-inner { max-width: 1100px; margin: 0 auto; }
        .section-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 12px; font-weight: 700; letter-spacing: .12em;
            text-transform: uppercase; color: var(--teal);
            margin-bottom: 20px;
        }
        .section-eyebrow::before {
            content: '';
            display: block; width: 24px; height: 2px;
            background: var(--teal); border-radius: 2px;
        }
        .section-eyebrow.amber { color: var(--amber); }
        .section-eyebrow.amber::before { background: var(--amber); }
        h2.section-title {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: clamp(32px, 4vw, 52px);
            font-weight: 700; line-height: 1.1;
            letter-spacing: -1.5px; color: var(--text);
            margin-bottom: 20px;
        }
        h2.section-title em { font-style: italic; color: var(--teal); }
        p.section-body {
            font-size: 17px; color: var(--muted); line-height: 1.75;
            max-width: 560px; font-weight: 400;
        }
        .center { text-align: center; }
        .center p.section-body { margin: 0 auto; }

        /* ── FEATURES ──────────────────────────────────────────────── */
        #features { background: #fff; }
        .features-header { margin-bottom: 64px; }
        .feature-grid {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 1px; background: var(--border);
            border: 1px solid var(--border); border-radius: 20px; overflow: hidden;
        }
        .feat-card { background: #fff; padding: 40px 36px; transition: background .2s; }
        .feat-card:hover { background: var(--teal-faint); }
        .feat-num {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: 13px; font-weight: 600; color: var(--teal);
            letter-spacing: .1em; margin-bottom: 24px; opacity: .6;
        }
        .feat-icon { font-size: 28px; margin-bottom: 18px; display: block; }
        .feat-card h3 {
            font-size: 20px; font-weight: 600; color: var(--text);
            margin-bottom: 12px; letter-spacing: -.3px; line-height: 1.2;
        }
        .feat-card p { font-size: 14px; color: var(--muted); line-height: 1.7; }

        /* ── HOW IT WORKS ──────────────────────────────────────────── */
        #how { background: var(--surface); }
        .how-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 48px; margin-top: 72px; }
        .how-step { position: relative; }
        .how-step::after {
            content: '→';
            position: absolute; right: -28px; top: 26px;
            font-size: 20px; color: var(--border);
        }
        .how-step:last-child::after { display: none; }
        .step-circle {
            width: 56px; height: 56px; border-radius: 50%;
            border: 2px solid var(--teal);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 24px;
        }
        .step-n {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: 24px; font-weight: 700; color: var(--teal); line-height: 1;
        }
        .how-step h3 {
            font-size: 21px; font-weight: 600; color: var(--text);
            margin-bottom: 12px; letter-spacing: -.3px;
        }
        .how-step p { font-size: 15px; color: var(--muted); line-height: 1.7; }

        /* ── SPLIT SECTIONS ────────────────────────────────────────── */
        .split-wrap { display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center; }
        .split-wrap.flip { direction: rtl; }
        .split-wrap.flip > * { direction: ltr; }
        .split-text { display: flex; flex-direction: column; gap: 0; }
        .benefit-list {
            list-style: none; display: flex; flex-direction: column; gap: 16px;
            margin: 32px 0 40px;
        }
        .benefit-list li {
            display: flex; align-items: flex-start; gap: 14px;
            font-size: 15px; color: var(--text-2); line-height: 1.6;
        }
        .chk {
            width: 22px; height: 22px; min-width: 22px;
            border-radius: 50%; background: var(--teal-mid);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; color: var(--teal-dark); font-weight: 700;
            margin-top: 2px; flex-shrink: 0;
        }
        .chk.amber-chk { background: #fde68a; color: #92400e; }

        /* Member visual card */
        .bene-card {
            background: linear-gradient(160deg, var(--teal-deep) 0%, var(--teal-dark) 100%);
            border-radius: 24px; padding: 36px;
            box-shadow: 0 24px 64px rgba(13,148,136,0.2);
        }
        .bene-card-title {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: 22px; font-weight: 600; color: #fff;
            margin-bottom: 28px; letter-spacing: -.3px;
        }
        .tier-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 18px; margin-bottom: 10px;
            background: rgba(255,255,255,0.1); border-radius: 12px;
            transition: background .2s;
        }
        .tier-item:last-child { margin-bottom: 0; }
        .tier-item:hover { background: rgba(255,255,255,0.16); }
        .tier-name { font-size: 15px; font-weight: 600; color: #fff; }
        .tier-tag {
            font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.7);
            background: rgba(255,255,255,0.15); border-radius: 20px;
            padding: 4px 12px;
        }

        /* Vendor visual card */
        #vendors { background: var(--amber-faint); }
        .vendor-card {
            background: linear-gradient(160deg, #92400e 0%, var(--amber) 100%);
            border-radius: 24px; padding: 36px;
            box-shadow: 0 24px 64px rgba(217,119,6,0.2);
        }
        .vendor-card-title {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: 22px; font-weight: 600; color: #fff; margin-bottom: 24px;
        }
        .vendor-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 2px; border-radius: 12px; overflow: hidden; }
        .v-stat { padding: 24px; text-align: center; }
        .v-stat:nth-child(odd) { background: rgba(255,255,255,0.12); }
        .v-stat:nth-child(even) { background: rgba(0,0,0,0.08); }
        .v-stat-num {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: 38px; font-weight: 700; color: #fff; line-height: 1;
        }
        .v-stat-lbl { font-size: 12px; color: rgba(255,255,255,0.65); margin-top: 4px; }
        .btn-amber {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 15px 36px; border-radius: 10px;
            background: var(--amber); color: #fff;
            font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 700;
            text-decoration: none; transition: background .2s, transform .2s;
            box-shadow: 0 4px 16px rgba(217,119,6,0.3);
        }
        .btn-amber:hover { background: #b45309; transform: translateY(-2px); }

        /* ── CTA BANNER ────────────────────────────────────────────── */
        .cta-band { background: var(--teal-deep); padding: 96px 6%; text-align: center; }
        .cta-band h2 {
            font-family: 'EB Garamond', Georgia, serif;
            font-size: clamp(36px, 5vw, 60px);
            font-weight: 700; color: #fff;
            letter-spacing: -1.5px; line-height: 1.1; margin-bottom: 20px;
        }
        .cta-band h2 em { font-style: italic; color: var(--teal-mid); }
        .cta-band p { font-size: 18px; color: rgba(255,255,255,0.6); max-width: 500px; margin: 0 auto 40px; line-height: 1.7; }
        .cta-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
        .btn-cta-white {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 15px 36px; border-radius: 10px;
            background: #fff; color: var(--teal-deep);
            font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 700;
            text-decoration: none; transition: all .2s;
        }
        .btn-cta-white:hover { background: var(--teal-mid); transform: translateY(-2px); }
        .btn-cta-ghost {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 15px 36px; border-radius: 10px;
            border: 2px solid rgba(255,255,255,0.3); color: #fff;
            font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 600;
            text-decoration: none; background: transparent; transition: all .2s;
        }
        .btn-cta-ghost:hover { border-color: rgba(255,255,255,0.7); background: rgba(255,255,255,0.08); }
        .btn-cta-label { font-size: 10px; opacity: .6; letter-spacing: .05em; text-transform: uppercase; display: block; margin-bottom: 2px; }
        .btn-cta-text { display: block; }

        /* ── APP SECTION ───────────────────────────────────────────── */
        #app { background: var(--dark); }
        .app-inner { display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center; }
        .app-tag { color: rgba(255,255,255,0.4); }
        .app-tag::before { background: rgba(255,255,255,0.3); }
        #app h2.section-title { color: #fff; }
        #app h2.section-title em { color: var(--teal-mid); }
        #app p.section-body { color: rgba(255,255,255,0.5); }
        .app-feats { display: flex; flex-direction: column; gap: 14px; margin: 32px 0 40px; }
        .app-feat {
            display: flex; align-items: center; gap: 14px;
            font-size: 15px; color: rgba(255,255,255,0.65);
        }
        .af-icon {
            width: 40px; height: 40px; min-width: 40px; border-radius: 10px;
            background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: center; font-size: 18px;
        }
        .dl-btns { display: flex; gap: 12px; flex-wrap: wrap; }
        .dl-btn {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 12px 22px; border-radius: 10px;
            background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);
            color: #fff; text-decoration: none; transition: background .2s;
        }
        .dl-btn:hover { background: rgba(255,255,255,0.14); }
        .dl-btn-icon { font-size: 22px; }
        .dl-btn-text { display: flex; flex-direction: column; }
        .dl-btn-sub { font-size: 10px; opacity: .55; font-family: 'Inter', sans-serif; }
        .dl-btn-title { font-size: 14px; font-weight: 700; font-family: 'Inter', sans-serif; }

        /* Phone mockups */
        .phone-group { display: flex; justify-content: center; align-items: flex-end; gap: 20px; }
        .phone {
            border-radius: 36px;
            box-shadow: 0 32px 80px rgba(0,0,0,0.5), 0 0 0 8px var(--dark-2);
            overflow: hidden; background: var(--dark-2); position: relative;
        }
        .phone-sm { width: 150px; height: 290px; border-radius: 28px; box-shadow: 0 20px 48px rgba(0,0,0,0.5), 0 0 0 6px var(--dark-2); }
        .phone-lg { width: 188px; height: 360px; margin-bottom: 32px; }
        .phone-notch {
            position: absolute; top: 12px; left: 50%; transform: translateX(-50%);
            width: 72px; height: 18px; background: var(--dark);
            border-radius: 10px; z-index: 5;
        }
        .phone-screen { height: 100%; display: flex; flex-direction: column; }
        .ps-login {
            flex: 1; background: var(--teal-faint);
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; padding: 20px; padding-top: 44px;
        }
        .ps-login img { width: 90px; margin-bottom: 12px; }
        .ps-login-lbl { font-size: 10px; font-weight: 700; color: var(--text); }
        .ps-dash { flex: 1; background: #f8fafc; display: flex; flex-direction: column; }
        .ps-topbar { background: var(--teal); padding: 10px 14px 10px; padding-top: 32px; }
        .ps-topbar img { height: 18px; filter: brightness(0) invert(1); }
        .ps-body { padding: 12px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .ps-card {
            background: #fff; border-radius: 8px; padding: 10px 12px;
            display: flex; gap: 8px; align-items: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .ps-card-dot { width: 28px; height: 28px; border-radius: 7px; flex-shrink: 0; }
        .ps-card-info { display: flex; flex-direction: column; gap: 2px; }
        .ps-card-name { font-size: 8px; font-weight: 700; color: var(--text); }
        .ps-card-price { font-size: 8px; color: var(--teal); font-weight: 700; }
        .ps-stat { background: #fff; border-radius: 8px; padding: 10px 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .ps-stat-val { font-size: 16px; font-weight: 800; color: var(--teal); line-height: 1; }
        .ps-stat-lbl { font-size: 7px; color: var(--muted); margin-top: 3px; text-transform: uppercase; letter-spacing: .5px; }
        .ps-bar { width: 100%; height: 4px; background: var(--border); border-radius: 2px; margin-top: 6px; overflow: hidden; }
        .ps-bar-fill { height: 100%; width: 65%; background: var(--teal); border-radius: 2px; }

        /* ── FOOTER ────────────────────────────────────────────────── */
        footer { background: var(--dark); padding: 72px 6% 36px; }
        .footer-grid {
            max-width: 1100px; margin: 0 auto;
            display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
            gap: 56px; margin-bottom: 56px;
        }
        .footer-brand img { height: 44px; margin-bottom: 20px; filter: brightness(0) invert(1); }
        .footer-brand p { font-size: 14px; color: #6b7280; line-height: 1.7; max-width: 260px; }
        .footer-col h5 {
            font-family: 'Inter', sans-serif;
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .1em; color: #4b5563; margin-bottom: 18px;
        }
        .footer-col a {
            display: block; font-size: 14px; color: #6b7280;
            text-decoration: none; margin-bottom: 12px; transition: color .15s;
        }
        .footer-col a:hover { color: #d1d5db; }
        .footer-bottom {
            max-width: 1100px; margin: 0 auto;
            border-top: 1px solid #1e293b; padding-top: 28px;
            display: flex; justify-content: space-between; align-items: center;
            font-size: 13px; color: #374151;
        }

        /* ── RESPONSIVE ────────────────────────────────────────────── */
        @media (max-width: 960px) {
            .stats-inner { grid-template-columns: repeat(2, 1fr); }
            .stat-cell:nth-child(2) { border-right: none; }
            .feature-grid { grid-template-columns: 1fr 1fr; }
            .how-grid { grid-template-columns: 1fr; max-width: 480px; margin: 56px auto 0; }
            .how-step::after { display: none; }
            .split-wrap, .split-wrap.flip { grid-template-columns: 1fr; direction: ltr; }
            .app-inner { grid-template-columns: 1fr; }
            .phone-group { display: none; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .nav-links { display: none; }
            .hamburger { display: flex; }
        }
        @media (max-width: 600px) {
            section { padding: 80px 5%; }
            #home { padding: 100px 5% 72px; }
            .stats-inner { grid-template-columns: repeat(2, 1fr); }
            .stat-cell { padding: 28px 20px; }
            .stat-num { font-size: 36px; }
            .feature-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; gap: 36px; }
            .footer-bottom { flex-direction: column; gap: 8px; text-align: center; }
            .hero-title { letter-spacing: -1.5px; }
            .cta-band { padding: 72px 5%; }
        }
    </style>
</head>
<body>

<!-- ── NAV ──────────────────────────────────────────────────────────── -->
<nav id="topnav">
    <a href="#home" class="nav-logo">
        <img src="<?php echo $logo_colored; ?>" alt="Foodbank CRM">
    </a>
    <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#how">How It Works</a>
        <a href="#members">Members</a>
        <a href="#vendors">Vendors</a>
        <a href="#app">Mobile App</a>
    </div>
    <div class="nav-cta">
        <a href="<?php echo $login_url; ?>" class="btn-nav-ghost">Sign In</a>
        <a href="<?php echo $register_url; ?>" class="btn-nav-solid">Join Free</a>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Open menu">
        <span></span><span></span><span></span>
    </button>
</nav>

<div class="mobile-menu" id="mobile-menu">
    <a href="#features" class="mob-link">Features</a>
    <a href="#how" class="mob-link">How It Works</a>
    <a href="#members" class="mob-link">Members</a>
    <a href="#vendors" class="mob-link">Vendors</a>
    <a href="#app" class="mob-link">Mobile App</a>
    <div class="mob-cta">
        <a href="<?php echo $login_url; ?>" class="btn-nav-ghost">Sign In</a>
        <a href="<?php echo $register_url; ?>" class="btn-nav-solid">Join Free</a>
    </div>
</div>

<!-- ── HERO ──────────────────────────────────────────────────────────── -->
<section id="home">
    <div class="hero-badge">
        <span class="hero-badge-dot"></span>
        Now live — Foodbank CRM v1.0
    </div>
    <h1 class="hero-title">
        Feeding Communities,<br><em>Smarter.</em>
    </h1>
    <p class="hero-sub">
        A complete food bank management platform — from subscription to delivery. Connect members, vendors, and administrators in one seamless system.
    </p>
    <div class="hero-actions">
        <a href="<?php echo $register_url; ?>" class="btn-primary">Subscribe as Member →</a>
        <a href="<?php echo $vendor_url; ?>" class="btn-outline">Apply as Vendor Partner</a>
    </div>
    <div class="hero-scroll-hint">
        <div class="scroll-line"></div>
        Scroll
    </div>
</section>

<!-- ── STATS ─────────────────────────────────────────────────────────── -->
<div class="stats-band">
    <div class="stats-inner">
        <div class="stat-cell">
            <div class="stat-num">1,000<em>+</em></div>
            <div class="stat-lbl">Members Served</div>
        </div>
        <div class="stat-cell">
            <div class="stat-num">50<em>+</em></div>
            <div class="stat-lbl">Vendor Partners</div>
        </div>
        <div class="stat-cell">
            <div class="stat-num">5,000<em>+</em></div>
            <div class="stat-lbl">Orders Fulfilled</div>
        </div>
        <div class="stat-cell">
            <div class="stat-num">100<em>%</em></div>
            <div class="stat-lbl">Secure Payments</div>
        </div>
    </div>
</div>

<!-- ── FEATURES ──────────────────────────────────────────────────────── -->
<section id="features">
    <div class="section-inner">
        <div class="features-header center">
            <div class="section-eyebrow" style="justify-content:center;">Platform Features</div>
            <h2 class="section-title">Everything you need,<br><em>in one place</em></h2>
            <p class="section-body" style="margin:0 auto 0;">A complete end-to-end system for food bank management — from registration to final-mile delivery.</p>
        </div>
        <div class="feature-grid">
            <div class="feat-card">
                <div class="feat-num">01</div>
                <span class="feat-icon">📦</span>
                <h3>Package Catalogue</h3>
                <p>Browse curated food packages from verified vendors, complete with descriptions, photos, and tier-based availability controls.</p>
            </div>
            <div class="feat-card">
                <div class="feat-num">02</div>
                <span class="feat-icon">🛒</span>
                <h3>Cart &amp; Checkout</h3>
                <p>Add packages to cart, confirm delivery details, and pay securely via Paystack — a seamless experience in seconds.</p>
            </div>
            <div class="feat-card">
                <div class="feat-num">03</div>
                <span class="feat-icon">📍</span>
                <h3>Live Order Tracking</h3>
                <p>Follow every order through Pending → Prepared → Bundled → In Transit → Delivered in real time.</p>
            </div>
            <div class="feat-card">
                <div class="feat-num">04</div>
                <span class="feat-icon">💳</span>
                <h3>Subscription Plans</h3>
                <p>Guest, Basic, Standard, and Premium tiers — flexible plans that scale with each member's household needs.</p>
            </div>
            <div class="feat-card">
                <div class="feat-num">05</div>
                <span class="feat-icon">🤝</span>
                <h3>Vendor Network</h3>
                <p>Food vendors list products, receive assigned orders, manage fulfilment status, and review earnings from one dashboard.</p>
            </div>
            <div class="feat-card">
                <div class="feat-num">06</div>
                <span class="feat-icon">📊</span>
                <h3>Admin Control Room</h3>
                <p>Full oversight of registrations, payment flow, order pipeline, inventory logs, and subscription management.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── HOW IT WORKS ───────────────────────────────────────────────────── -->
<section id="how">
    <div class="section-inner">
        <div class="center">
            <div class="section-eyebrow" style="justify-content:center;">How It Works</div>
            <h2 class="section-title">Up and running<br><em>in three steps</em></h2>
            <p class="section-body" style="margin:0 auto;">From sign-up to receiving your first food package — simple, fast, and transparent.</p>
        </div>
        <div class="how-grid">
            <div class="how-step">
                <div class="step-circle"><span class="step-n">I</span></div>
                <h3>Register &amp; Subscribe</h3>
                <p>Create your account and choose a subscription plan that fits your household. Plans are activated instantly via secure Paystack payment.</p>
            </div>
            <div class="how-step">
                <div class="step-circle"><span class="step-n">II</span></div>
                <h3>Browse &amp; Order</h3>
                <p>Explore available food packages from partner vendors, add items to your cart, confirm your delivery address, and complete payment.</p>
            </div>
            <div class="how-step">
                <div class="step-circle"><span class="step-n">III</span></div>
                <h3>Track &amp; Receive</h3>
                <p>Follow your order status from preparation through bundling and transit, all the way to your door — live updates, every step.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── FOR MEMBERS ────────────────────────────────────────────────────── -->
<section id="members" style="background:#fff;">
    <div class="section-inner">
        <div class="split-wrap">
            <div class="split-text">
                <div class="section-eyebrow">For Members</div>
                <h2 class="section-title">Food security,<br><em>at your fingertips</em></h2>
                <ul class="benefit-list">
                    <li><div class="chk">✓</div><span>Browse food packages and place orders from the app or any web browser</span></li>
                    <li><div class="chk">✓</div><span>Flexible subscription tiers — choose what fits your household and budget</span></li>
                    <li><div class="chk">✓</div><span>See your monthly usage and remaining order allowance at a glance</span></li>
                    <li><div class="chk">✓</div><span>Real-time status updates from order placement to doorstep delivery</span></li>
                    <li><div class="chk">✓</div><span>Manage your profile, delivery address, and subscription in one place</span></li>
                </ul>
                <a href="<?php echo $register_url; ?>" class="btn-primary" style="align-self:flex-start;">Register as Member →</a>
            </div>
            <div>
                <div class="bene-card">
                    <div class="bene-card-title">Membership Tiers</div>
                    <div class="tier-item">
                        <span class="tier-name">Guest</span>
                        <span class="tier-tag">Browse Only</span>
                    </div>
                    <div class="tier-item">
                        <span class="tier-name">Basic</span>
                        <span class="tier-tag">4 orders / month</span>
                    </div>
                    <div class="tier-item">
                        <span class="tier-name">Standard</span>
                        <span class="tier-tag">8 orders / month</span>
                    </div>
                    <div class="tier-item">
                        <span class="tier-name">Premium</span>
                        <span class="tier-tag">Unlimited orders</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── FOR VENDORS ────────────────────────────────────────────────────── -->
<section id="vendors">
    <div class="section-inner">
        <div class="split-wrap flip">
            <div class="split-text">
                <div class="section-eyebrow amber">For Vendors</div>
                <h2 class="section-title">Grow your reach<br><em>within the network</em></h2>
                <ul class="benefit-list">
                    <li><div class="chk amber-chk">✓</div><span>List your food products with images, descriptions, and pricing</span></li>
                    <li><div class="chk amber-chk">✓</div><span>Receive and manage orders assigned to your store by the platform</span></li>
                    <li><div class="chk amber-chk">✓</div><span>Update fulfilment status and track your live order pipeline</span></li>
                    <li><div class="chk amber-chk">✓</div><span>View earnings history and supply reports with detailed breakdowns</span></li>
                    <li><div class="chk amber-chk">✓</div><span>Contact platform support directly from within the vendor dashboard</span></li>
                </ul>
                <a href="<?php echo $vendor_url; ?>" class="btn-amber" style="align-self:flex-start;">Join as Vendor Partner →</a>
            </div>
            <div>
                <div class="vendor-card">
                    <div class="vendor-card-title">Vendor Dashboard</div>
                    <div class="vendor-stats">
                        <div class="v-stat">
                            <div class="v-stat-num">24</div>
                            <div class="v-stat-lbl">Active Orders</div>
                        </div>
                        <div class="v-stat">
                            <div class="v-stat-num">₦48k</div>
                            <div class="v-stat-lbl">This Month</div>
                        </div>
                        <div class="v-stat">
                            <div class="v-stat-num">12</div>
                            <div class="v-stat-lbl">Products Listed</div>
                        </div>
                        <div class="v-stat">
                            <div class="v-stat-num">98%</div>
                            <div class="v-stat-lbl">Fulfilment Rate</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── CTA BAND ───────────────────────────────────────────────────────── -->
<div class="cta-band">
    <h2>Ready to get started?<br><em>Join today — it's free.</em></h2>
    <p>Create your account in under two minutes and access a food bank system built for real communities.</p>
    <div class="cta-btns">
        <a href="<?php echo $register_url; ?>" class="btn-cta-white">
            <span class="btn-cta-label">For members</span>
            <span class="btn-cta-text">Subscribe &amp; Get Food →</span>
        </a>
        <a href="<?php echo $vendor_url; ?>" class="btn-cta-ghost">
            <span class="btn-cta-label">For food suppliers</span>
            <span class="btn-cta-text">Apply as Vendor Partner</span>
        </a>
    </div>
</div>

<!-- ── APP SECTION ───────────────────────────────────────────────────── -->
<section id="app">
    <div class="section-inner">
        <div class="app-inner">
            <div>
                <div class="section-eyebrow app-tag">Mobile App</div>
                <h2 class="section-title">Your food bank,<br><em>in your pocket</em></h2>
                <p class="section-body">The Foodbank CRM app gives members and vendors full platform access on the go — from ordering to live delivery tracking.</p>
                <div class="app-feats">
                    <div class="app-feat"><div class="af-icon">📱</div>Available on Android</div>
                    <div class="app-feat"><div class="af-icon">🔔</div>Push notifications for every order update</div>
                    <div class="app-feat"><div class="af-icon">🔐</div>Secure JWT-based authentication</div>
                    <div class="app-feat"><div class="af-icon">⚡</div>Fast, responsive, offline-capable</div>
                </div>
                <div class="dl-btns">
                    <a href="#" class="dl-btn">
                        <span class="dl-btn-icon">▶</span>
                        <span class="dl-btn-text">
                            <span class="dl-btn-sub">Get it on</span>
                            <span class="dl-btn-title">Google Play</span>
                        </span>
                    </a>
                    <a href="#" class="dl-btn">
                        <span class="dl-btn-icon">⬇</span>
                        <span class="dl-btn-text">
                            <span class="dl-btn-sub">Direct Download</span>
                            <span class="dl-btn-title">APK File</span>
                        </span>
                    </a>
                </div>
            </div>

            <div class="phone-group">
                <div class="phone phone-sm">
                    <div class="phone-screen">
                        <div class="ps-login">
                            <img src="<?php echo $logo_colored; ?>" alt="Foodbank CRM">
                            <div class="ps-login-lbl">Welcome back</div>
                        </div>
                    </div>
                </div>
                <div class="phone phone-lg">
                    <div class="phone-notch"></div>
                    <div class="phone-screen">
                        <div class="ps-dash">
                            <div class="ps-topbar">
                                <img src="<?php echo $logo_colored; ?>" alt="Foodbank CRM">
                            </div>
                            <div class="ps-body">
                                <div class="ps-stat">
                                    <div class="ps-stat-val">4 / 6</div>
                                    <div class="ps-stat-lbl">Orders this month</div>
                                    <div class="ps-bar"><div class="ps-bar-fill"></div></div>
                                </div>
                                <div class="ps-card">
                                    <div class="ps-card-dot" style="background:#ccfbf1;"></div>
                                    <div class="ps-card-info">
                                        <div class="ps-card-name">Fresh Produce Box</div>
                                        <div class="ps-card-price">₦ 2,500</div>
                                    </div>
                                </div>
                                <div class="ps-card">
                                    <div class="ps-card-dot" style="background:#fde68a;"></div>
                                    <div class="ps-card-info">
                                        <div class="ps-card-name">Dry Goods Bundle</div>
                                        <div class="ps-card-price">₦ 1,800</div>
                                    </div>
                                </div>
                                <div class="ps-card">
                                    <div class="ps-card-dot" style="background:#fed7aa;"></div>
                                    <div class="ps-card-info">
                                        <div class="ps-card-name">Protein Package</div>
                                        <div class="ps-card-price">₦ 3,200</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ── FOOTER ─────────────────────────────────────────────────────────── -->
<footer>
    <div class="footer-grid">
        <div class="footer-brand">
            <img src="<?php echo $logo_colored; ?>" alt="Foodbank CRM">
            <p>Connecting communities through efficient food distribution and supply management.</p>
        </div>
        <div class="footer-col">
            <h5>Platform</h5>
            <a href="#features">Features</a>
            <a href="#how">How It Works</a>
            <a href="#app">Mobile App</a>
        </div>
        <div class="footer-col">
            <h5>Get Started</h5>
            <a href="<?php echo $register_url; ?>">Subscribe as Member</a>
            <a href="<?php echo $vendor_url; ?>">Apply as Vendor Partner</a>
            <a href="<?php echo $login_url; ?>">Sign In</a>
        </div>
        <div class="footer-col">
            <h5>Contact</h5>
            <a href="mailto:info@xdigitalsolutions.com">info@xdigitalsolutions.com</a>
            <a href="https://xdigitalfoodbank.com">xdigitalfoodbank.com</a>
        </div>
        <div class="footer-col">
            <h5>Legal</h5>
            <a href="core/pages/privacy_policy.php">Privacy Policy</a>
            <a href="core/pages/terms.php">Terms &amp; Conditions</a>
        </div>
    </div>
    <div class="footer-bottom">
        <span>&copy; <?php echo date('Y'); ?> Foodbank CRM. All rights reserved.</span>
        <span>
            <a href="core/pages/privacy_policy.php" style="color:#6b7280;text-decoration:none;margin-right:16px;">Privacy Policy</a>
            <a href="core/pages/terms.php" style="color:#6b7280;text-decoration:none;">Terms &amp; Conditions</a>
        </span>
    </div>
</footer>

<script>
// Nav shadow on scroll
window.addEventListener('scroll', function () {
    document.getElementById('topnav').classList.toggle('scrolled', window.scrollY > 10);
});

// Hamburger
document.getElementById('hamburger').addEventListener('click', function () {
    document.getElementById('mobile-menu').classList.toggle('open');
});

// Close mobile menu on link click
document.querySelectorAll('.mob-link').forEach(function (a) {
    a.addEventListener('click', function () {
        document.getElementById('mobile-menu').classList.remove('open');
    });
});
</script>

</body>
</html>
