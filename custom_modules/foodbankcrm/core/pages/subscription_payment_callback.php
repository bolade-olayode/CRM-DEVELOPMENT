
<?php
/**
 * Subscription Payment Callback - Verify and Activate
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

$langs->load("admin");

// Get reference and tier
$reference = GETPOST('reference', 'alpha');
$tier_type = GETPOST('tier', 'alpha');

if (empty($reference) || empty($tier_type)) {
    header('Location: renew_subscription.php');
    exit;
}

// Verify payment with Paystack
$paystack_secret_key = 'sk_test 24845eca974e163568aa6dd497590551e1ad2260';

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

if ($err) {
    llxHeader('', 'Payment Error');
    echo '<style>
    #id-left { display: none !important; }
    #id-right { margin-left: 0 !important; width: 100% !important; }
    </style>';
    print '<div class="error">Payment verification failed. Please contact support.</div>';
    llxFooter();
    exit;
}

$result = json_decode($response);

if ($result->data->status == 'success') {
    // Payment successful - Activate subscription
    
    // Get beneficiary
    $sql_ben = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
    $res_ben = $db->query($sql_ben);
    $beneficiary = $db->fetch_object($res_ben);
    $subscriber_id = $beneficiary->rowid;
    
    // Get tier details
    $sql_tier = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE tier_type = '".$db->escape($tier_type)."'";
    $res_tier = $db->query($sql_tier);
    $tier = $db->fetch_object($res_tier);
    
    // Calculate new end date
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+' . $tier->duration_days . ' days'));
    
    // Calculate amount
    $amount = $result->data->amount / 100; // Convert from kobo
    
    $db->begin();
    
    try {
        // Update subscription
        $sql_update = "UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET
                       subscription_type = '".$db->escape($tier_type)."',
                       subscription_status = 'Active',
                       subscription_start_date = '".$db->escape($start_date)."',
                       subscription_end_date = '".$db->escape($end_date)."',
                       subscription_fee = ".(float)$tier->price.",
                       payment_method = 'Paystack',
                       last_payment_date = '".$db->escape(date('Y-m-d'))."'
                       WHERE rowid = ".(int)$subscriber_id;
        
        if (!$db->query($sql_update)) {
            throw new Exception('Failed to update subscription: '.$db->lasterror());
        }
        
        // Record payment
        $sql_payment = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_payments 
                        (fk_subscriber, payment_type, amount, payment_method, payment_status, 
                         payment_reference, payment_date, datec)
                        VALUES (
                            ".(int)$subscriber_id.",
                            'Subscription',
                            ".(float)$amount.",
                            'Paystack',
                            'Paid',
                            '".$db->escape($reference)."',
                            NOW(),
                            NOW()
                        )";
        
        if (!$db->query($sql_payment)) {
            throw new Exception('Failed to record payment: '.$db->lasterror());
        }
        
        $db->commit();
        
        // Redirect to success page
        header('Location: subscription_success.php?tier='.urlencode($tier_type).'&end_date='.urlencode($end_date));
        exit;
        
    } catch (Exception $e) {
        $db->rollback();
        llxHeader('', 'Error');
        echo '<style>
        #id-left { display: none !important; }
        #id-right { margin-left: 0 !important; width: 100% !important; }
        </style>';
        print '<div class="error">Failed to activate subscription: '.dol_escape_htmltag($e->getMessage()).'</div>';
        print '<a href="renew_subscription.php" class="butAction">Try Again</a>';
        llxFooter();
        exit;
    }
    
} else {
    llxHeader('', 'Payment Failed');
    echo '<style>
    #id-left { display: none !important; }
    #id-right { margin-left: 0 !important; width: 100% !important; }
    </style>';
    print '<div class="error">Payment verification failed. Please try again.</div>';
    print '<a href="renew_subscription.php" class="butAction">Back to Subscription</a>';
    llxFooter();
    exit;
}
?>
