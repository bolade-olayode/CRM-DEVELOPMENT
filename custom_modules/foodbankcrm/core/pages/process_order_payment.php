<?php
/**
 * Process Payment - RESTORED DESIGN + FIXED LOGIC
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;

// Permission check
if (!FoodbankPermissions::isBeneficiary($user, $db)) {
    accessforbidden('You do not have access to this page.');
}

$order_id = GETPOST('order_id', 'int');
if (!$order_id) { header('Location: dashboard_beneficiary.php'); exit; }

// Get subscriber ID for ownership check
$sql_ben = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res_ben = $db->query($sql_ben);
if (!$res_ben || $db->num_rows($res_ben) == 0) {
    accessforbidden('Beneficiary profile not found.');
}
$subscriber_id = (int)$db->fetch_object($res_ben)->rowid;

// Get order — only if it belongs to this subscriber
$sql = "SELECT d.*, b.firstname, b.lastname, b.email, b.phone
        FROM ".MAIN_DB_PREFIX."foodbank_distributions d
        INNER JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON d.fk_beneficiary = b.rowid
        WHERE d.rowid = ".(int)$order_id." AND d.fk_beneficiary = ".$subscriber_id;
$res = $db->query($sql);
$order = $db->fetch_object($res);

if (!$order) { header('Location: dashboard_beneficiary.php'); exit; }

// Get Items (Restoring the list you liked)
$sql_items = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines WHERE fk_distribution = ".(int)$order_id;
$res_items = $db->query($sql_items);

// Paystack public key from config
$paystack_public_key = getDolGlobalString('FOODBANK_PAYSTACK_PUBLIC_KEY');
if (empty($paystack_public_key)) {
    print '<div class="error">Paystack is not configured. Please contact the administrator.</div>';
    llxFooter(); exit;
}

llxHeader('', 'Complete Payment');

// UI: Hide Top Bar + Animation
print '<style>
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header { display: none !important; }
    html, body { background-color: #f8f9fa !important; margin: 0; width: 100%; overflow-x: hidden; }
    #id-right, .id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
    .fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    .loading { animation: pulse 1.5s ease-in-out infinite; }
</style>';

print '<div style="max-width: 800px; margin: 40px auto; padding: 0 20px; font-family: \'Segoe UI\', sans-serif;">';

// Main Card
print '<div style="background: white; padding: 40px; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.1);">';

// Header
print '<div style="text-align: center; margin-bottom: 30px;">';
print '<div style="font-size: 64px; margin-bottom: 15px;">💳</div>';
print '<h1 style="margin: 0 0 10px 0; font-size: 28px;">Complete Payment</h1>';
print '<p style="color: #666; margin: 0;">Secure payment powered by Paystack</p>';
print '</div>';

// --- RESTORED: ITEM LIST ---
print '<div style="background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 30px;">';
print '<h3 style="margin: 0 0 15px 0; font-size: 18px; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 10px;">Order Summary ('.dol_escape_htmltag($order->ref).')</h3>';

if ($res_items && $db->num_rows($res_items) > 0) {
    while ($item = $db->fetch_object($res_items)) {
        print '<div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">';
        print '<span>'.dol_escape_htmltag($item->product_name).'</span>';
        print '<span style="color: #666;">'.number_format($item->quantity, 0).' '.dol_escape_htmltag($item->unit).'</span>';
        print '</div>';
    }
}

print '<div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #ddd; display: flex; justify-content: space-between; align-items: center;">';
print '<span style="font-weight: bold; font-size: 18px;">Total Amount:</span>';
print '<span style="font-size: 24px; font-weight: bold; color: #28a745;">₦'.number_format($order->total_amount, 2).'</span>';
print '</div>';
print '</div>';
// --- END ITEM LIST ---

// Payment Button
print '<button id="paystack-btn" class="butAction" style="width: 100%; padding: 18px; font-size: 18px; font-weight: bold; margin-bottom: 20px; cursor: pointer; border: none; border-radius: 8px; background: #28a745; color: white;">';
print 'Pay ₦'.number_format($order->total_amount, 0).' Now';
print '</button>';

// Loading Indicator
print '<div id="loading" style="display: none; text-align: center; padding: 20px;">';
print '<div class="loading" style="font-size: 48px; margin-bottom: 15px;">⏳</div>';
print '<p style="color: #666;">Processing payment...</p>';
print '</div>';

// Cancel Link
print '<div style="text-align: center; margin-top: 15px;">';
print '<a href="dashboard_beneficiary.php" style="color: #999; font-size: 14px; text-decoration: none;">Cancel Payment & Go to Dashboard</a>';
print '</div>';

print '</div></div>';
?>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
document.getElementById('paystack-btn').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('loading').style.display = 'block';
    document.getElementById('paystack-btn').style.display = 'none';
    
    var handler = PaystackPop.setup({
        key: '<?php echo $paystack_public_key; ?>',
        email: '<?php echo dol_escape_js($order->email); ?>',
        amount: <?php echo $order->total_amount * 100; ?>,
        currency: 'NGN',
        ref: 'ORD-<?php echo $order->ref; ?>-'+Math.floor((Math.random() * 1000000000) + 1),
        metadata: { order_id: <?php echo $order_id; ?> },
        callback: function(response){
            // THIS IS THE CRITICAL FIX: Handles the DB update securely
            fetch('update_payment_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    order_id: <?php echo $order_id; ?>,
                    reference: response.reference,
                    status: 'success'
                })
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    window.location.href = 'order_confirmation.php?order_id=<?php echo $order_id; ?>&payment=success';
                } else {
                    // Show error but allow retry
                    alert('Payment successful, but database update failed: ' + (data.error || 'Unknown error'));
                    window.location.href = 'order_confirmation.php?order_id=<?php echo $order_id; ?>&payment=success'; // Try going anyway
                }
            })
            .catch(error => {
                console.error(error);
                alert('Connection error. Please contact support.');
                // Even if fetch fails, if payment worked, try to show success
                window.location.href = 'order_confirmation.php?order_id=<?php echo $order_id; ?>&payment=success'; 
            });
        },
        onClose: function(){
            document.getElementById('loading').style.display = 'none';
            document.getElementById('paystack-btn').style.display = 'block';
        }
    });
    handler.openIframe();
});
</script>

<?php llxFooter(); ?>