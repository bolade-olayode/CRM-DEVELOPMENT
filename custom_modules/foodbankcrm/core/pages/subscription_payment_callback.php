<?php
/**
 * Subscription Payment Callback - FIXED KEY (Underscore added)
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

$langs->load("admin");

// 1. Get Params
$reference = GETPOST('reference', 'alpha');
$tier_type = GETPOST('tier', 'alpha');

if (empty($reference) || empty($tier_type)) {
    header('Location: renew_subscription.php');
    exit;
}

// 2. Verify with Paystack
$paystack_secret_key = getDolGlobalString('FOODBANK_PAYSTACK_SECRET_KEY');
if (empty($paystack_secret_key)) {
    printErrorPage('Paystack is not configured. Contact admin.');
}

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . $reference,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $paystack_secret_key,
        "Cache-Control: no-cache",
    ],
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

// --- ERROR UI ---
function printErrorPage($msg) {
    $_SESSION["mainmenu"] = "foodbankcrm";
    llxHeader('', 'Payment Error');
    print '<style>
        #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header { display: none !important; }
        html, body { background-color: #f8f9fa !important; margin: 0; width: 100%; overflow-x: hidden; }
        #id-right, .id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
        .error-card { 
            background: white; padding: 40px; border-radius: 12px; text-align: center; 
            max-width: 600px; margin: 50px auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
        }
    </style>';
    print '<div class="error-card">';
    print '<div style="font-size: 60px; margin-bottom: 20px;">⚠️</div>';
    print '<h2 style="color: #dc3545; margin: 0 0 15px 0;">Payment Verification Failed</h2>';
    print '<p style="color: #666; margin-bottom: 25px;">'.$msg.'</p>';
    print '<a href="renew_subscription.php" class="butAction">Try Again</a>';
    print '</div>';
    llxFooter();
    exit;
}

if ($err) {
    printErrorPage('Connection Error: ' . $err);
}

$result = json_decode($response);

// 3. CHECK RESULT
if ($result && isset($result->data) && $result->data->status == 'success') {
    
    // Get beneficiary
    $sql_ben = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
    $res_ben = $db->query($sql_ben);
    $beneficiary = $db->fetch_object($res_ben);
    $subscriber_id = $beneficiary->rowid;
    
    // Get tier
    $sql_tier = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE tier_type = '".$db->escape($tier_type)."'";
    $res_tier = $db->query($sql_tier);
    $tier = $db->fetch_object($res_tier);
    
    if (!$tier) {
        printErrorPage('Configuration Error: Subscription Tier not found.');
    }
    
    // Dates & Amount
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+' . $tier->duration_days . ' days'));
    $amount = $result->data->amount / 100;
    
    $db->begin();
    
    try {
        // Update Beneficiary
        $sql_update = "UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET
                       subscription_type = '".$db->escape($tier_type)."',
                       subscription_status = 'Active',
                       subscription_start_date = '".$db->escape($start_date)."',
                       subscription_end_date = '".$db->escape($end_date)."',
                       subscription_fee = ".(float)$tier->price.",
                       max_orders_per_month = ".(int)$tier->max_orders_per_month.",
                       payment_method = 'Paystack',
                       last_payment_date = NOW()
                       WHERE rowid = ".(int)$subscriber_id;

        if (!$db->query($sql_update)) throw new Exception('Profile Update Failed: '.$db->lasterror());

        // Prevent duplicate payment records
        $sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_payments WHERE payment_reference = '".$db->escape($reference)."'";
        $res_dup = $db->query($sql_check);
        if ($res_dup && $db->num_rows($res_dup) == 0) {
            $sql_payment = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_payments
                            (fk_subscriber, payment_type, amount, payment_method, payment_status,
                             payment_reference, payment_date, datec)
                            VALUES (
                                ".(int)$subscriber_id.",
                                'Subscription',
                                ".(float)$amount.",
                                'Paystack',
                                'Success',
                                '".$db->escape($reference)."',
                                NOW(),
                                NOW()
                            )";
            if (!$db->query($sql_payment)) throw new Exception('Payment Record Failed: '.$db->lasterror());
        }
        
        $db->commit();
        
        // Success Redirect
        header('Location: subscription_success.php?tier='.urlencode($tier->tier_name).'&end_date='.urlencode($end_date));
        exit;
        
    } catch (Exception $e) {
        $db->rollback();
        printErrorPage('System Error: ' . $e->getMessage());
    }
    
} else {
    // Show specific error from Paystack
    $msg = isset($result->message) ? $result->message : 'Unknown error from payment gateway';
    printErrorPage('Paystack Error: ' . $msg);
}
?>