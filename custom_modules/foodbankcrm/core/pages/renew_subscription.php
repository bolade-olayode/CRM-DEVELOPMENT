<?php
/**
 * Renew Subscription Page
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

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
$subscriber = $db->fetch_object($res_ben);
$subscriber_id = $subscriber->rowid;

// Get available subscription tiers
$sql_tiers = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE status = 'Active' AND tier_type != 'Guest' ORDER BY price ASC";
$res_tiers = $db->query($sql_tiers);

llxHeader('', 'Renew Subscription');

// Hide left menu and make FULL WIDTH
echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }

.tier-card {
    background: white;
    border: 3px solid #e0e0e0;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    transition: all 0.3s;
    cursor: pointer;
}

.tier-card:hover {
    border-color: #667eea;
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.tier-card.selected {
    border-color: #28a745;
    background: #f0fff4;
}
</style>';

print '<div style="width: 100%; padding: 30px; box-sizing: border-box; max-width: 1200px; margin: 0 auto;">';

// Current subscription info
print '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 40px;">';
print '<h1 style="margin: 0 0 20px 0; font-size: 32px;">🔄 Subscription Management</h1>';

if ($subscriber->subscription_status == 'Expired') {
    print '<div style="background: rgba(255,255,255,0.2); padding: 20px; border-radius: 8px; margin-bottom: 20px;">';
    print '<h2 style="margin: 0 0 10px 0;">⚠️ Your Subscription Has Expired</h2>';
    print '<p style="margin: 0; font-size: 16px;">Renew now to continue placing orders and accessing benefits.</p>';
    print '</div>';
}

print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">';

print '<div>';
print '<div style="opacity: 0.9; font-size: 14px; margin-bottom: 5px;">Current Plan</div>';
print '<div style="font-size: 22px; font-weight: bold;">'.dol_escape_htmltag($subscriber->subscription_type).'</div>';
print '</div>';

print '<div>';
print '<div style="opacity: 0.9; font-size: 14px; margin-bottom: 5px;">Status</div>';
print '<div style="font-size: 20px; font-weight: bold;">';
$status_emoji = ($subscriber->subscription_status == 'Active') ? '✅' : '❌';
print $status_emoji.' '.dol_escape_htmltag($subscriber->subscription_status);
print '</div>';
print '</div>';

if ($subscriber->subscription_end_date) {
    $days_remaining = floor((strtotime($subscriber->subscription_end_date) - time()) / 86400);
    
    print '<div>';
    print '<div style="opacity: 0.9; font-size: 14px; margin-bottom: 5px;">Expires On</div>';
    print '<div style="font-size: 18px; font-weight: bold;">'.dol_print_date($db->jdate($subscriber->subscription_end_date), 'day').'</div>';
    if ($days_remaining > 0) {
        print '<div style="font-size: 14px; opacity: 0.9; margin-top: 5px;">('.$days_remaining.' days remaining)</div>';
    }
    print '</div>';
}

print '</div>';
print '</div>';

// Subscription tiers
print '<h2 style="margin: 0 0 30px 0; text-align: center;">Choose Your Plan</h2>';

print '<form method="POST" action="process_subscription_payment.php" id="subscription-form">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 40px;">';

if ($res_tiers) {
    while ($tier = $db->fetch_object($res_tiers)) {
        $is_current = ($tier->tier_type == $subscriber->subscription_type);
        
        print '<div class="tier-card" onclick="selectTier(\''.$tier->tier_type.'\', '.$tier->price.')" id="tier-'.$tier->tier_type.'">';
        
        if ($is_current) {
            print '<div style="background: #28a745; color: white; display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 15px;">CURRENT PLAN</div>';
        }
        
        print '<h3 style="margin: 0 0 10px 0; font-size: 24px; color: #333;">'.dol_escape_htmltag($tier->tier_name).'</h3>';
        
        print '<div style="font-size: 48px; font-weight: bold; color: #28a745; margin: 20px 0;">₦'.number_format($tier->price, 0).'</div>';
        
        print '<div style="color: #666; font-size: 14px; margin-bottom: 20px;">';
        if ($tier->duration_days >= 365) {
            print 'Valid for '.floor($tier->duration_days / 365).' year(s)';
        } else {
            print 'Valid for '.$tier->duration_days.' days';
        }
        print '</div>';
        
        if ($tier->description) {
            print '<p style="color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 20px;">'.nl2br(dol_escape_htmltag($tier->description)).'</p>';
        }
        
        if ($tier->max_orders_per_month) {
            print '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 15px;">';
            print '<strong>Up to '.$tier->max_orders_per_month.' orders/month</strong>';
            print '</div>';
        } else {
            print '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 15px;">';
            print '<strong>Unlimited orders</strong>';
            print '</div>';
        }
        
        print '</div>';
    }
}

print '</div>';

print '<input type="hidden" name="selected_tier" id="selected_tier" required>';
print '<input type="hidden" name="amount" id="amount" required>';

print '<div style="text-align: center;">';
print '<button type="submit" class="butAction" style="padding: 15px 40px; font-size: 18px; margin: 0;">Pay with Paystack →</button>';
print '</div>';

print '</form>';

// Back link for active subscriptions
if ($subscriber->subscription_status == 'Active') {
    print '<div style="text-align: center; margin-top: 30px;">';
    print '<a href="dashboard_beneficiary.php" style="color: #666; font-size: 16px;">← Back to Dashboard</a>';
    print '</div>';
}

print '</div>';

?>

<script>
let selectedTier = null;

function selectTier(tierType, price) {
    // Remove previous selection
    document.querySelectorAll('.tier-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selection to clicked card
    document.getElementById('tier-' + tierType).classList.add('selected');
    
    // Set hidden form values
    document.getElementById('selected_tier').value = tierType;
    document.getElementById('amount').value = price;
    
    selectedTier = tierType;
}

document.getElementById('subscription-form').addEventListener('submit', function(e) {
    if (!selectedTier) {
        e.preventDefault();
        alert('Please select a subscription plan');
    }
});
</script>

<?php
llxFooter();
?>
