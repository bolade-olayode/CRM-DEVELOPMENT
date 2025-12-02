<?php
/**
 * Paystack Payment Callback
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

$langs->load("admin");

// Get reference
$reference = GETPOST('reference', 'alpha');

if (empty($reference)) {
    header('Location: view_cart.php');
    exit;
}

// Verify payment with Paystack
$paystack_secret_key = 'sk_test_YOUR_SECRET_KEY_HERE'; // REPLACE

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
    print '<div class="error">Payment verification failed. Please contact support.</div>';
    llxFooter();
    exit;
}

$result = json_decode($response);

if ($result->data->status == 'success') {
    // Payment successful - Create distribution
    
    // Get beneficiary
    $sql_ben = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
    $res_ben = $db->query($sql_ben);
    $beneficiary = $db->fetch_object($res_ben);
    
    // Generate reference
    $ref = 'DIS'.date('Y').'-'.str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Calculate total
    $total = $result->data->amount / 100; // Convert from kobo
    
    // Create distribution
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_distributions 
            (ref, fk_beneficiary, date_distribution, status, payment_status, 
             payment_method, payment_reference, payment_date, total_amount, payment_gateway)
            VALUES (
                '".$db->escape($ref)."',
                ".(int)$beneficiary->rowid.",
                NOW(),
                'Prepared',
                'Paid',
                'Paystack',
                '".$db->escape($reference)."',
                NOW(),
                ".(float)$total.",
                'Paystack'
            )";
    
    if ($db->query($sql)) {
        $distribution_id = $db->last_insert_id(MAIN_DB_PREFIX."foodbank_distributions");
        
        // TODO: Add distribution items from cart
        // TODO: Update donation quantities
        // TODO: Create delivery booking with SendBox
        
        // Clear cart
        $_SESSION['cart'] = array();
        
        // Redirect to success page
        header('Location: order_success.php?id='.$distribution_id);
        exit;
    } else {
        llxHeader('', 'Order Error');
        print '<div class="error">Failed to create order. Please contact support. Reference: '.$reference.'</div>';
        llxFooter();
        exit;
    }
    
} else {
    llxHeader('', 'Payment Failed');
    print '<div class="error">Payment verification failed. Please try again.</div>';
    print '<a href="view_cart.php" class="butAction">Back to Cart</a>';
    llxFooter();
    exit;
}
require_once dirname(__DIR__, 4) . '/custom/foodbankcrm/core/pages/sendbox_delivery.php';
$sendbox_api_key = 'YOUR_SENDBOX_API_KEY_HERE';
$sendbox = new SendBoxDelivery($sendbox_api_key);

// Prepare items list
$items_list = array();
foreach ($_SESSION['cart'] as $item) {
    $items_list[] = array(
        'name' => $item['product_name'],
        'quantity' => $item['quantity'],
        'weight' => 1,
        'amount' => $item['price'] ?? 0
    );
}

// Create delivery
$delivery_data = array(
    'destination_name' => $beneficiary->firstname . ' ' . $beneficiary->lastname,
    'destination_phone' => $beneficiary->phone,
    'destination_address' => $beneficiary->address,
    'destination_state' => 'Lagos', // Parse from address
    'items' => $items_list,
    'weight' => count($_SESSION['cart']) * 2, // Estimate
    'value' => $total,
    'reference' => $ref,
    'pickup_date' => date('Y-m-d', strtotime('+1 day'))
);

$delivery_result = $sendbox->createDelivery($delivery_data);

if (!isset($delivery_result['error'])) {
    // Save tracking number
    $tracking_number = $delivery_result['tracking_number'] ?? null;
    
    $sql_update = "UPDATE ".MAIN_DB_PREFIX."foodbank_distributions 
                   SET note = 'Tracking Number: ".$db->escape($tracking_number)."'
                   WHERE rowid = ".(int)$distribution_id;
    $db->query($sql_update);
}
?>