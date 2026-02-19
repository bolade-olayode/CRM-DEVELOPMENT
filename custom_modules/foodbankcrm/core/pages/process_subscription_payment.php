<?php
/**
 * Process Subscription Payment - JS FETCH METHOD
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

$langs->load("admin");

// Security Check
$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);
if (!$user_is_beneficiary) accessforbidden();

// Get beneficiary
$sql_ben = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res_ben = $db->query($sql_ben);
$subscriber = $db->fetch_object($res_ben);
$subscriber_id = $subscriber->rowid;

// Get form data
$selected_tier = GETPOST('selected_tier', 'alpha');
$amount = GETPOST('amount', 'int');

if (empty($selected_tier) || empty($amount)) {
    header('Location: renew_subscription.php');
    exit;
}

// Get tier details
$sql_tier = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE tier_type = '".$db->escape($selected_tier)."'";
$res_tier = $db->query($sql_tier);
$tier = $db->fetch_object($res_tier);

// Paystack public key from config
$paystack_public_key = getDolGlobalString('FOODBANK_PAYSTACK_PUBLIC_KEY');
if (empty($paystack_public_key)) {
    print '<div class="error">Paystack is not configured. Please contact the administrator.</div>';
    llxFooter(); exit;
}

llxHeader('', 'Payment');

// --- CSS ---
print '<style>
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header { display: none !important; }
    html, body { background-color: #f8f9fa !important; margin: 0; width: 100%; overflow-x: hidden; }
    #id-right, .id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
    .fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .ben-container { width: 95%; max-width: 600px; margin: 0 auto; padding: 40px 20px; font-family: "Segoe UI", sans-serif; }
    .pay-card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); text-align: center; }
    .summary-box { background: #f8f9fa; border: 1px solid #eee; padding: 25px; border-radius: 12px; margin: 30px 0; text-align: left; }
    .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 15px; color: #555; }
    .summary-row.total { margin-top: 20px; padding-top: 15px; border-top: 2px solid #ddd; font-weight: bold; font-size: 20px; color: #333; }
    .btn-pay { background: linear-gradient(135deg, #28a745 0%, #218838 100%); color: white; border: none; padding: 16px 30px; width: 100%; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } }
    .loading { display:none; animation: pulse 1.5s ease-in-out infinite; margin-bottom:15px; font-size:40px; }
</style>';

print '<div class="ben-container">';
print '<div class="pay-card">';
print '<div class="loading" id="loading-icon">⏳</div>';
print '<h1 style="margin: 0 0 10px 0; font-size: 28px;">Complete Subscription</h1>';
print '<p style="color: #666; margin: 0;">Secure payment powered by Paystack</p>';

print '<div class="summary-box">';
print '<h3 style="margin: 0 0 20px 0; font-size: 16px; text-transform: uppercase; color: #999;">Order Summary</h3>';
print '<div class="summary-row"><span>Plan</span><strong>'.dol_escape_htmltag($tier->tier_name).'</strong></div>';
print '<div class="summary-row"><span>Duration</span><strong>'.floor($tier->duration_days / 365).' Year(s)</strong></div>';
print '<div class="summary-row total"><span>Total Amount</span><span style="color:#28a745">₦'.number_format($amount, 2).'</span></div>';
print '</div>';

print '<button id="paystack-btn" class="btn-pay">Pay ₦'.number_format($amount, 0).' Now</button>';
print '<br><br><a href="renew_subscription.php" style="color:#666; text-decoration:none;">← Cancel</a>';

print '</div></div>';

?>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
document.getElementById('paystack-btn').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('paystack-btn').style.display = 'none';
    document.getElementById('loading-icon').style.display = 'block';
    
    var handler = PaystackPop.setup({
        key: '<?php echo $paystack_public_key; ?>',
        email: '<?php echo dol_escape_js($subscriber->email); ?>',
        amount: <?php echo $amount * 100; ?>,
        currency: 'NGN',
        ref: 'SUB-'+Math.floor((Math.random() * 1000000000) + 1),
        metadata: {
            subscriber_id: <?php echo $subscriber_id; ?>,
            subscription_tier: '<?php echo dol_escape_js($selected_tier); ?>'
        },
        callback: function(response){
            // --- CALL THE BRIDGE FILE ---
            fetch('activate_subscription.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    subscriber_id: <?php echo $subscriber_id; ?>,
                    tier: '<?php echo dol_escape_js($selected_tier); ?>',
                    reference: response.reference
                })
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    // Success! Go to confirmation page
                    window.location.href = 'subscription_success.php?tier=<?php echo urlencode($tier->tier_name); ?>&end_date=' + data.end_date;
                } else {
                    alert('Payment successful, but update failed: ' + (data.message || 'Unknown error'));
                    window.location.href = 'dashboard_beneficiary.php';
                }
            })
            .catch(error => {
                console.error(error);
                alert('Connection error. Please refresh.');
            });
        },
        onClose: function(){
            document.getElementById('paystack-btn').style.display = 'inline-block';
            document.getElementById('loading-icon').style.display = 'none';
            alert('Payment cancelled');
        }
    });
    handler.openIframe();
});
</script>

<?php llxFooter(); ?>