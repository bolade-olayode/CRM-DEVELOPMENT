<?php
/**
 * P
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;

$reference = GETPOST('reference', 'alpha');

if (empty($reference)) {
    die("No reference supplied");
}

// Verify payment with Paystack
$paystack_secret_key = 'sk_test_24845eca974e163568aa6dd497590551e1ad2260'; 

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
    die("Curl Error: " . $err);
}

$result = json_decode($response);

if ($result->data->status == 'success') {
    // 1. Get the Order ID from the metadata we sent
    // (Ensure your process_order_payment.php sends order_id in metadata)
    $order_id = isset($result->data->metadata->order_id) ? (int)$result->data->metadata->order_id : 0;

    if ($order_id > 0) {
        // 2. UPDATE the existing order
        // Note: Do NOT overwrite total_amount from Paystack response — keep the original order total
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_distributions
                SET payment_status = 'Paid',
                    status = 'Prepared',
                    payment_reference = '".$db->escape($reference)."',
                    payment_gateway = 'Paystack',
                    payment_date = NOW()
                WHERE rowid = ".$order_id;
        
        $db->query($sql);

        // 3. Update Payment Record
        $sql_pay = "UPDATE ".MAIN_DB_PREFIX."foodbank_payments 
                    SET payment_status = 'Success',
                        payment_date = NOW()
                    WHERE fk_order = ".$order_id;
        $db->query($sql_pay);
        
        // Redirect to success
        header('Location: order_confirmation.php?order_id='.$order_id.'&payment=success');
        exit;
    }
}

// Fallback
header('Location: dashboard_beneficiary.php');
exit;
?>