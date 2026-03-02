<?php
/**
 * Renew Subscription - Modern UI Refinement
 */

// 1. Buffer & Clean
ob_start();

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

// Wipe noise
ob_clean();

global $user, $db, $conf;

if (isset($_SESSION['foodbank_checked'])) {
    $_SESSION['foodbank_checked'] = false;
}

$langs->load("admin");

// Security check
$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);
if (!$user_is_beneficiary) {
    accessforbidden('You do not have access.');
}

// Get beneficiary info
$sql_ben = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res_ben = $db->query($sql_ben);
if (!$res_ben || $db->num_rows($res_ben) == 0) {
    accessforbidden('Beneficiary profile not found.');
}
$subscriber = $db->fetch_object($res_ben);
$subscriber_id = (int)$subscriber->rowid;

// Get available tiers — show ALL Active tiers, no type filtering
$sql_tiers = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE status = 'Active' ORDER BY price ASC";
$res_tiers = $db->query($sql_tiers);

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Subscription Plans');

// --- MODERN CSS ---
print '<style>
    /* 1. HIDE CHROME */
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header { display: none !important; }
    
    /* 2. LAYOUT */
    html, body { background-color: #f8f9fa !important; margin: 0; width: 100%; overflow-x: hidden; }
    #id-right, .id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
    .fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }

    /* 3. CONTAINER */
    .ben-container { width: 95%; max-width: 1200px; margin: 0 auto; padding: 40px 20px; font-family: "Segoe UI", sans-serif; }

    /* 4. STATUS HEADER */
    .status-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 16px;
        margin-bottom: 50px;
        box-shadow: 0 10px 20px rgba(102,126,234,0.2);
    }
    .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; margin-top: 20px; }
    .status-item .label { opacity: 0.8; font-size: 14px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px; }
    .status-item .value { font-size: 24px; font-weight: 700; }

    /* 5. TIER CARDS */
    .tier-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 40px; }
    
    .tier-card {
        background: white;
        border: 2px solid #e0e0e0;
        border-radius: 16px;
        padding: 40px 30px;
        text-align: center;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    .tier-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        border-color: #667eea;
    }
    .tier-card.selected {
        border-color: #28a745;
        background-color: #f0fff4;
        box-shadow: 0 0 0 4px rgba(40,167,69,0.2);
    }
    .tier-card.selected::after {
        content: "✓ SELECTED";
        position: absolute;
        top: 0; right: 0;
        background: #28a745;
        color: white;
        padding: 5px 15px;
        font-size: 12px;
        font-weight: bold;
        border-bottom-left-radius: 10px;
    }

    .tier-name { font-size: 24px; color: #333; margin: 0 0 15px 0; font-weight: 700; }
    .tier-price { font-size: 42px; color: #28a745; font-weight: 800; margin-bottom: 20px; }
    .tier-desc { color: #666; line-height: 1.6; margin-bottom: 25px; font-size: 15px; }
    .tier-badge { background: #f1f3f5; padding: 8px 15px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #495057; display: inline-block; }

    /* 6. ACTION BUTTON */
    .btn-pay {
        background: linear-gradient(135deg, #28a745 0%, #218838 100%);
        color: white;
        border: none;
        padding: 18px 50px;
        border-radius: 50px;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 4px 15px rgba(40,167,69,0.3);
    }
    .btn-pay:hover { transform: scale(1.05); box-shadow: 0 6px 20px rgba(40,167,69,0.4); }
    
    .btn-back { color: #666; text-decoration: none; font-weight: 600; transition: color 0.2s; }
    .btn-back:hover { color: #333; }
</style>';

print '<div class="ben-container">';

// --- HEADER SECTION ---
print '<div class="status-card">';
print '<h1 style="margin: 0; font-size: 28px;">🔄 Subscription Management</h1>';

// Expiry Warning
if ($subscriber->subscription_status == 'Expired') {
    print '<div style="background: rgba(255,0,0,0.2); padding: 15px; border-radius: 8px; margin-top: 20px; border: 1px solid rgba(255,255,255,0.3);">';
    print '<strong>⚠️ Subscription Expired:</strong> Renew now to regain access to benefits.';
    print '</div>';
}

print '<div class="status-grid">';
    // Current Plan
    print '<div class="status-item">';
    print '<div class="label">Current Plan</div>';
    print '<div class="value">'.dol_escape_htmltag($subscriber->subscription_type).'</div>';
    print '</div>';

    // Status
    print '<div class="status-item">';
    print '<div class="label">Status</div>';
    $icon = ($subscriber->subscription_status == 'Active') ? '✅' : '❌';
    print '<div class="value">'.$icon.' '.dol_escape_htmltag($subscriber->subscription_status).'</div>';
    print '</div>';

    // Expiry
    if ($subscriber->subscription_end_date) {
        $days = floor((strtotime($subscriber->subscription_end_date) - time()) / 86400);
        print '<div class="status-item">';
        print '<div class="label">Expires On</div>';
        print '<div class="value">'.dol_print_date($db->jdate($subscriber->subscription_end_date), 'day').'</div>';
        if ($days > 0) print '<div style="font-size:13px; opacity:0.8; margin-top:5px;">('.$days.' days left)</div>';
        print '</div>';
    }
print '</div>'; // End Grid
print '</div>'; // End Card

// --- TIERS SECTION ---
print '<h2 style="text-align: center; margin-bottom: 40px; color: #333;">Choose Your Plan</h2>';

print '<form method="POST" action="process_subscription_payment.php" id="subscription-form">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="selected_tier" id="selected_tier" required>';
print '<input type="hidden" name="amount" id="amount" required>';

print '<div class="tier-grid">';

if ($res_tiers) {
    while ($tier = $db->fetch_object($res_tiers)) {
        $is_current = ($tier->tier_type == $subscriber->subscription_type);
        
        print '<div class="tier-card" onclick="selectTier('.$tier->rowid.', '.$tier->price.')" id="tier-'.$tier->rowid.'">';
        
        print '<div class="tier-name">'.dol_escape_htmltag($tier->tier_name).'</div>';
        print '<div class="tier-price">₦'.number_format($tier->price, 0).'</div>';
        
        print '<div class="tier-desc">';
        if ($tier->duration_days >= 365) {
            print 'Valid for '.floor($tier->duration_days / 365).' Year(s)';
        } else {
            print 'Valid for '.$tier->duration_days.' Days';
        }
        print '</div>';
        
        if ($tier->description) {
            print '<p style="color:#666; margin-bottom:20px;">'.nl2br(dol_escape_htmltag($tier->description)).'</p>';
        }
        
        // Limits
        if ($tier->max_orders_per_month) {
            print '<div class="tier-badge">Limit: '.$tier->max_orders_per_month.' orders/mo</div>';
        } else {
            print '<div class="tier-badge">Unlimited Orders</div>';
        }
        
        print '</div>'; // End Tier Card
    }
}
print '</div>'; // End Grid

// --- ACTION BUTTONS ---
print '<div style="text-align: center; margin-bottom: 50px;">';
print '<button type="submit" class="btn-pay">Pay with Paystack →</button>';
print '</div>';

print '</form>';

// Back Link
print '<div style="text-align: center;">';
print '<a href="dashboard_beneficiary.php" class="btn-back">← Back to Dashboard</a>';
print '</div>';

print '</div>'; // End Container

?>

<script>
let selectedTier = null;

function selectTier(tierId, price) {
    // 1. Deselect all
    document.querySelectorAll('.tier-card').forEach(card => {
        card.classList.remove('selected');
    });

    // 2. Select clicked
    document.getElementById('tier-' + tierId).classList.add('selected');

    // 3. Update Inputs
    document.getElementById('selected_tier').value = tierId;
    document.getElementById('amount').value = price;

    selectedTier = tierId;
}

document.getElementById('subscription-form').addEventListener('submit', function(e) {
    if (!selectedTier) {
        e.preventDefault();
        alert('Please click on a plan to select it.');
    }
});
</script>

<?php
llxFooter();
ob_end_flush();
?>