<?php
/**
 * Renew Subscription — Redesigned to match FoodbankCRM design system
 */

ob_start();

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

ob_clean();

global $user, $db, $conf;

if (isset($_SESSION['foodbank_checked'])) {
    $_SESSION['foodbank_checked'] = false;
}

$langs->load("admin");

if (!FoodbankPermissions::isBeneficiary($user, $db)) {
    accessforbidden('You do not have access.');
}

$sql_ben = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res_ben = $db->query($sql_ben);
if (!$res_ben || $db->num_rows($res_ben) == 0) {
    accessforbidden('Beneficiary profile not found.');
}
$subscriber    = $db->fetch_object($res_ben);
$subscriber_id = (int)$subscriber->rowid;

$sql_tiers = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE status = 'Active' ORDER BY price ASC";
$res_tiers = $db->query($sql_tiers);

$days_left = null;
if ($subscriber->subscription_end_date) {
    $days_left = (int)floor((strtotime($subscriber->subscription_end_date) - time()) / 86400);
}
$is_expired = ($subscriber->subscription_status === 'Expired');
$sub_name   = trim($subscriber->firstname.' '.$subscriber->lastname) ?: $user->login;

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Subscription Plans');
?>
<style>
/* ── RESET ───────────────────────────────────────────────── */
#id-top,.side-nav,.side-nav-vert,#id-left,.login_block,.tmenudiv,.nav-bar,header{display:none!important;width:0!important;height:0!important;pointer-events:none!important}
html,body{background:#f0f4f8!important;margin:0!important;padding:0!important;width:100%!important;overflow-x:hidden!important;font-family:'Segoe UI',system-ui,sans-serif}
#id-container,#id-right,.id-right{margin:0!important;padding:0!important;width:100vw!important;max-width:100vw!important;flex:none!important;display:block!important}
.fiche{width:100%!important;max-width:100%!important;margin:0!important}
body{padding-top:64px!important}
*{box-sizing:border-box}

/* ── TOPNAV ──────────────────────────────────────────────── */
.fb-nav{position:fixed;top:0;left:0;right:0;height:64px;background:linear-gradient(90deg,#0f766e,#0d9488);display:flex;align-items:center;justify-content:space-between;padding:0 28px;z-index:9999;box-shadow:0 2px 16px rgba(0,0,0,.18)}
.fb-nav-brand{font-size:17px;font-weight:800;color:#fff;text-decoration:none;display:flex;align-items:center;gap:9px;letter-spacing:-.3px}
.fb-nav-links{display:flex;gap:2px}
.fb-nav-link{color:rgba(255,255,255,.82);text-decoration:none;font-size:13px;font-weight:500;padding:7px 14px;border-radius:20px;transition:background .15s,color .15s}
.fb-nav-link:hover,.fb-nav-link.active{background:rgba(255,255,255,.18);color:#fff}
.fb-nav-right{display:flex;align-items:center;gap:14px}
.fb-nav-name{color:rgba(255,255,255,.85);font-size:13px;font-weight:500}
.fb-logout{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.35);padding:6px 16px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600;transition:all .2s}
.fb-logout:hover{background:rgba(255,255,255,.25)}

/* ── HERO ────────────────────────────────────────────────── */
.fb-hero{background:linear-gradient(135deg,#134e4a 0%,#0d9488 55%,#14b8a6 100%);padding:48px 32px 90px;position:relative;overflow:hidden}
.fb-hero::before{content:'';position:absolute;top:-80px;right:-80px;width:340px;height:340px;background:rgba(255,255,255,.05);border-radius:50%}
.fb-hero::after{content:'';position:absolute;bottom:-40px;left:0;right:0;height:80px;background:#f0f4f8;border-radius:50% 50% 0 0/100% 100% 0 0}
.fb-hero-inner{max-width:1100px;margin:0 auto;position:relative;z-index:1}
.fb-hero-label{color:rgba(255,255,255,.7);font-size:12px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:10px}
.fb-hero-title{color:#fff;font-size:30px;font-weight:800;margin:0 0 6px;line-height:1.2}
.fb-hero-sub{color:rgba(255,255,255,.7);font-size:14px;margin:0}

/* ── EXPIRED BANNER ──────────────────────────────────────── */
.fb-expired-banner{background:rgba(239,68,68,.18);border:1px solid rgba(239,68,68,.35);border-radius:12px;padding:16px 22px;margin-top:24px;display:flex;align-items:center;gap:12px;color:#fff;font-size:14px;font-weight:500}
.fb-expired-banner strong{font-size:15px;font-weight:700}

/* ── STATUS STRIP ────────────────────────────────────────── */
.fb-status-strip{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;max-width:1100px;margin:-52px auto 40px;padding:0 28px;position:relative;z-index:2}
@media(max-width:700px){.fb-status-strip{grid-template-columns:1fr}}
.fb-status-card{background:#fff;border-radius:14px;padding:20px 22px;box-shadow:0 4px 20px rgba(0,0,0,.08);border:1px solid #e8edf2}
.fb-status-label{font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px}
.fb-status-val{font-size:20px;font-weight:800;color:#1e293b}
.fb-status-sub{font-size:12px;color:#64748b;margin-top:4px}

/* ── SECTION TITLE ───────────────────────────────────────── */
.fb-section-title{text-align:center;font-size:22px;font-weight:800;color:#1e293b;margin:0 0 8px}
.fb-section-sub{text-align:center;font-size:14px;color:#64748b;margin:0 0 36px}

/* ── TIER CARDS ──────────────────────────────────────────── */
.tier-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;max-width:1100px;margin:0 auto 16px;padding:0 28px}
.tier-card{background:#fff;border:2px solid #e2e8f0;border-radius:18px;padding:36px 28px;text-align:center;cursor:pointer;transition:all .25s cubic-bezier(.25,.8,.25,1);position:relative;overflow:hidden}
.tier-card:hover{transform:translateY(-6px);box-shadow:0 16px 40px rgba(13,148,136,.12);border-color:#0d9488}
.tier-card.selected{border-color:#0d9488;background:linear-gradient(135deg,#f0fdfa 0%,#e6fffc 100%);box-shadow:0 0 0 4px rgba(13,148,136,.18),0 12px 36px rgba(13,148,136,.15)}
.tier-card.selected::before{content:'✓ SELECTED';position:absolute;top:0;right:0;background:linear-gradient(135deg,#0f766e,#0d9488);color:#fff;padding:6px 18px;font-size:11px;font-weight:800;letter-spacing:.8px;border-bottom-left-radius:12px}
.tier-card.current-plan{border-color:#f59e0b}
.tier-card.current-plan::before{content:'CURRENT PLAN';position:absolute;top:0;right:0;background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;padding:6px 18px;font-size:11px;font-weight:800;letter-spacing:.8px;border-bottom-left-radius:12px}

.tier-icon{font-size:36px;margin-bottom:16px;display:block}
.tier-name{font-size:20px;font-weight:800;color:#1e293b;margin:0 0 4px}
.tier-duration{font-size:12px;font-weight:600;color:#0d9488;text-transform:uppercase;letter-spacing:.8px;margin-bottom:20px}
.tier-price{margin:20px 0;padding:20px 0;border-top:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9}
.tier-price-amount{font-size:44px;font-weight:900;color:#1e293b;line-height:1}
.tier-price-currency{font-size:22px;font-weight:700;color:#64748b;vertical-align:top;margin-top:8px;display:inline-block}
.tier-price-period{font-size:13px;color:#94a3b8;margin-top:6px}
.tier-desc{font-size:14px;color:#64748b;line-height:1.6;margin:16px 0}
.tier-badge{display:inline-flex;align-items:center;gap:6px;background:#f0fdfa;color:#0f766e;border:1px solid #99f6e4;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;margin-top:4px}
.tier-select-hint{font-size:12px;color:#94a3b8;margin-top:16px;font-weight:500}
.tier-card.selected .tier-select-hint{color:#0d9488;font-weight:700}

/* ── PAY SECTION ─────────────────────────────────────────── */
.pay-section{max-width:1100px;margin:0 auto 60px;padding:0 28px}
.pay-box{background:#fff;border-radius:18px;border:1px solid #e2e8f0;box-shadow:0 4px 20px rgba(0,0,0,.06);padding:28px 36px;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap}
.pay-box-info{display:flex;align-items:center;gap:16px}
.pay-box-icon{width:52px;height:52px;background:#f0fdfa;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;border:1px solid #99f6e4;flex-shrink:0}
.pay-box-label{font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.7px;margin-bottom:4px}
.pay-box-val{font-size:18px;font-weight:800;color:#1e293b}
.pay-box-hint{font-size:12px;color:#94a3b8;margin-top:3px}
.btn-pay{display:inline-flex;align-items:center;gap:10px;background:linear-gradient(135deg,#0f766e,#0d9488);color:#fff;border:none;padding:16px 40px;border-radius:50px;font-size:16px;font-weight:800;cursor:pointer;transition:all .2s;box-shadow:0 6px 20px rgba(13,148,136,.35);letter-spacing:.3px}
.btn-pay:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(13,148,136,.45)}
.btn-pay:disabled{opacity:.5;cursor:not-allowed;transform:none;box-shadow:none}
.btn-back{display:inline-flex;align-items:center;gap:6px;color:#64748b;text-decoration:none;font-size:14px;font-weight:600;transition:color .2s;padding:10px 0}
.btn-back:hover{color:#0d9488}
</style>

<!-- Navbar -->
<nav class="fb-nav">
    <a href="dashboard_beneficiary.php" class="fb-nav-brand">🌿 FoodbankCRM</a>
    <div class="fb-nav-links">
        <a href="dashboard_beneficiary.php" class="fb-nav-link">Dashboard</a>
        <a href="available_packages.php" class="fb-nav-link">Packages</a>
        <a href="my_orders.php" class="fb-nav-link">My Orders</a>
        <a href="renew_subscription.php" class="fb-nav-link active">Subscription</a>
    </div>
    <div class="fb-nav-right">
        <span class="fb-nav-name"><?php echo dol_escape_htmltag($sub_name); ?></span>
        <a href="<?php echo DOL_URL_ROOT; ?>/user/logout.php" class="fb-logout">🚪 Logout</a>
    </div>
</nav>

<!-- Hero -->
<div class="fb-hero">
    <div class="fb-hero-inner">
        <div class="fb-hero-label">Subscription Management</div>
        <h1 class="fb-hero-title">🔄 Renew Your Plan</h1>
        <p class="fb-hero-sub">Choose a plan below and pay securely via Paystack</p>

        <?php if ($is_expired): ?>
        <div class="fb-expired-banner">
            <span style="font-size:22px">⚠️</span>
            <div><strong>Your subscription has expired.</strong> Renew now to regain full access to food packages and benefits.</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Status Strip -->
<div class="fb-status-strip">
    <div class="fb-status-card">
        <div class="fb-status-label">Current Plan</div>
        <div class="fb-status-val"><?php echo dol_escape_htmltag($subscriber->subscription_type ?: 'None'); ?></div>
        <div class="fb-status-sub">Active subscription plan</div>
    </div>
    <div class="fb-status-card">
        <div class="fb-status-label">Status</div>
        <?php
        $status = $subscriber->subscription_status;
        if ($status === 'Active') {
            $status_color = '#0d9488'; $status_icon = '✅'; $status_sub = 'In good standing';
        } elseif ($status === 'Expired') {
            $status_color = '#ef4444'; $status_icon = '❌'; $status_sub = 'Renewal required';
        } elseif ($status === 'Pending') {
            $status_color = '#f59e0b'; $status_icon = '⏳'; $status_sub = 'Awaiting payment';
        } else {
            $status_color = '#94a3b8'; $status_icon = '—'; $status_sub = 'No active subscription';
        }
        ?>
        <div class="fb-status-val" style="color:<?php echo $status_color; ?>">
            <?php echo $status_icon.' '.dol_escape_htmltag($status); ?>
        </div>
        <div class="fb-status-sub"><?php echo $status_sub; ?></div>
    </div>
    <div class="fb-status-card">
        <div class="fb-status-label">Expires On</div>
        <?php if ($subscriber->subscription_end_date): ?>
            <div class="fb-status-val"><?php echo dol_print_date($db->jdate($subscriber->subscription_end_date), 'day'); ?></div>
            <div class="fb-status-sub" style="color:<?php echo ($days_left !== null && $days_left <= 30) ? '#f59e0b' : '#64748b'; ?>">
                <?php
                if ($days_left !== null) {
                    echo $days_left > 0 ? $days_left.' days remaining' : 'Expired '.(abs($days_left)).' days ago';
                }
                ?>
            </div>
        <?php else: ?>
            <div class="fb-status-val" style="color:#94a3b8">—</div>
            <div class="fb-status-sub">No active subscription</div>
        <?php endif; ?>
    </div>
</div>

<!-- Plan Picker -->
<div style="max-width:1100px;margin:0 auto 32px;padding:0 28px;">
    <p class="fb-section-title">Choose Your Plan</p>
    <p class="fb-section-sub">Click a plan to select it, then complete payment below</p>
</div>

<form method="POST" action="process_subscription_payment.php" id="subscription-form">
    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
    <input type="hidden" name="selected_tier" id="selected_tier">
    <input type="hidden" name="amount" id="amount">

    <div class="tier-grid">
    <?php
    $tier_icons = ['#f59e0b', '#0d9488', '#8b5cf6', '#ef4444', '#3b82f6'];
    $icon_list  = ['🥉','🥈','🥇','💎','👑'];
    $i = 0;
    if ($res_tiers) {
        while ($tier = $db->fetch_object($res_tiers)) {
            $is_current  = ($tier->tier_type == $subscriber->subscription_type);
            $icon        = $icon_list[$i % count($icon_list)];
            $dur_label   = ($tier->duration_days >= 365)
                ? floor($tier->duration_days / 365).' Year'.( floor($tier->duration_days/365) > 1 ? 's' : '')
                : $tier->duration_days.' Days';
            ?>
            <div class="tier-card <?php echo $is_current ? 'current-plan' : ''; ?>"
                 onclick="selectTier(<?php echo $tier->rowid; ?>, <?php echo $tier->price; ?>, '<?php echo dol_escape_js($tier->tier_name); ?>')"
                 id="tier-<?php echo $tier->rowid; ?>">

                <span class="tier-icon"><?php echo $icon; ?></span>
                <div class="tier-name"><?php echo dol_escape_htmltag($tier->tier_name); ?></div>
                <div class="tier-duration">📅 <?php echo $dur_label; ?></div>

                <div class="tier-price">
                    <div>
                        <span class="tier-price-currency">₦</span><span class="tier-price-amount"><?php echo number_format($tier->price, 0); ?></span>
                    </div>
                    <div class="tier-price-period">one-time payment · <?php echo $dur_label; ?></div>
                </div>

                <?php if ($tier->description): ?>
                    <div class="tier-desc"><?php echo nl2br(dol_escape_htmltag($tier->description)); ?></div>
                <?php endif; ?>

                <?php if ($tier->max_orders_per_month): ?>
                <div class="tier-badge">
                    📦 <?php echo (int)$tier->max_orders_per_month; ?> orders/month
                </div>
                <?php endif; ?>

                <div class="tier-select-hint">Click to select this plan</div>
            </div>
            <?php
            $i++;
        }
    }
    ?>
    </div>

    <!-- Pay Box -->
    <div class="pay-section">
        <div class="pay-box" id="pay-box">
            <div class="pay-box-info">
                <div class="pay-box-icon">💳</div>
                <div>
                    <div class="pay-box-label">Selected Plan</div>
                    <div class="pay-box-val" id="pay-box-plan">— Select a plan above —</div>
                    <div class="pay-box-hint" id="pay-box-amount">Payment processed securely via Paystack</div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                <a href="dashboard_beneficiary.php" class="btn-back">← Back</a>
                <button type="submit" class="btn-pay" id="btn-pay" disabled>
                    Pay with Paystack &nbsp;🔒
                </button>
            </div>
        </div>
    </div>

</form>

<script>
let selectedTier = null;

function selectTier(tierId, price, name) {
    document.querySelectorAll('.tier-card').forEach(function(card) {
        card.classList.remove('selected');
        var hint = card.querySelector('.tier-select-hint');
        if (hint) hint.textContent = 'Click to select this plan';
    });

    var card = document.getElementById('tier-' + tierId);
    card.classList.add('selected');
    var hint = card.querySelector('.tier-select-hint');
    if (hint) hint.textContent = '✓ This plan is selected';

    document.getElementById('selected_tier').value = tierId;
    document.getElementById('amount').value = price;

    var fmt = new Intl.NumberFormat('en-NG', {minimumFractionDigits: 0}).format(price);
    document.getElementById('pay-box-plan').textContent = name;
    document.getElementById('pay-box-amount').textContent = '₦' + fmt + ' — Paystack secure checkout';
    document.getElementById('btn-pay').disabled = false;

    selectedTier = tierId;

    document.getElementById('pay-box').scrollIntoView({behavior: 'smooth', block: 'nearest'});
}

document.getElementById('subscription-form').addEventListener('submit', function(e) {
    if (!selectedTier) {
        e.preventDefault();
        alert('Please select a plan first.');
    }
});
</script>

<?php
llxFooter();
ob_end_flush();
?>
