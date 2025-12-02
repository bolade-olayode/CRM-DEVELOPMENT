<?php
/**
 * Process Subscription Payment with Paystack
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

$langs->load("admin");

$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);

if (!$user_is_beneficiary) {
    accessforbidden('You do not have access.');
}

// CSRF Check
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        accessforbidden('Invalid security token');
    }
}

// Get beneficiary info
$sql_ben = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res_ben = $db->query($sql_ben);
$subscriber = $db->fetch_object($res_ben);
$subscriber_id = $subscriber->rowid;

// Get form data
$selected_tier = GETPOST('selected_tier', 'alpha');
$amount = GETPOST('amount', 'int');

if (empty($selected_tier) || empty($amount)) {
    setEventMessages('Invalid subscription selection', null, 'errors');
    header('Location: renew_subscription.php');
    exit;
}

// Get tier details
$sql_tier = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE tier_type = '".$db->escape($selected_tier)."'";
$res_tier = $db->query($sql_tier);
$tier = $db->fetch_object($res_tier);

// Paystack configuration
$paystack_public_key = 'pk_test_27e3e802c6afc73a7b4cadb65254648a9cebd6dc'; 
$paystack_secret_key = 'sk_test 24845eca974e163568aa6dd497590551e1ad2260'; 

llxHeader('', 'Payment');

echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }
</style>';

print '<div style="width: 100%; padding: 30px; box-sizing: border-box; max-width: 800px; margin: 0 auto;">';

print '<div style="background: white; padding: 40px; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">';

print '<h1 style="margin: 0 0 30px 0;">💳 Complete Payment</h1>';

print '<div style="background: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 30px; text-align: left;">';
print '<h3 style="margin: 0 0 20px 0;">Payment Summary</h3>';
print '<div style="display: flex; justify-content: space-between; margin-bottom: 15px;">';
print '<span>Subscription Plan:</span>';
print '<strong>'.dol_escape_htmltag($tier->tier_name).'</strong>';
print '</div>';
print '<div style="display: flex; justify-content: space-between; margin-bottom: 15px;">';
print '<span>Duration:</span>';
print '<strong>'.floor($tier->duration_days / 365).' year(s)</strong>';
print '</div>';
print '<hr style="margin: 20px 0;">';
print '<div style="display: flex; justify-content: space-between; font-size: 20px;">';
print '<span><strong>Total Amount:</strong></span>';
print '<strong style="color: #28a745;">₦'.number_format($amount, 2).'</strong>';
print '</div>';
print '</div>';

print '<button id="paystack-btn" class="butAction" style="width: 100%; padding: 15px; font-size: 18px; margin: 0 0 20px 0;">Pay ₦'.number_format($amount, 0).' with Paystack</button>';

print '<a href="renew_subscription.php" style="color: #666;">← Change plan</a>';

print '</div>';

print '</div>';

?>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
document.getElementById('paystack-btn').addEventListener('click', function(e) {
    e.preventDefault();
    
    var handler = PaystackPop.setup({
        key: '<?php echo $paystack_public_key; ?>',
        email: '<?php echo dol_escape_js($subscriber->email); ?>',
        amount: <?php echo $amount * 100; ?>, // Amount in kobo
        currency: 'NGN',
        ref: 'SUB-'+Math.floor((Math.random() * 1000000000) + 1),
        metadata: {
            subscriber_id: <?php echo $subscriber_id; ?>,
            subscriber_name: '<?php echo dol_escape_js($subscriber->firstname.' '.$subscriber->lastname); ?>',
            subscription_tier: '<?php echo dol_escape_js($selected_tier); ?>',
            payment_type: 'subscription',
            custom_fields: [
                {
                    display_name: "Subscriber ID",
                    variable_name: "subscriber_id",
                    value: "<?php echo dol_escape_js($subscriber->ref); ?>"
                },
                {
                    display_name: "Plan",
                    variable_name: "plan",
                    value: "<?php echo dol_escape_js($tier->tier_name); ?>"
                }
            ]
        },
        callback: function(response){
            // Payment successful - redirect to verification
            window.location.href = 'subscription_payment_callback.php?reference=' + response.reference + '&tier=<?php echo urlencode($selected_tier); ?>';
        },
        onClose: function(){
            alert('Payment cancelled');
        }
    });
    
    handler.openIframe();
});
</script>

<?php
llxFooter();
?>
