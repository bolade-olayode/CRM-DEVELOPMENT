<?php
/**
 * Payment Status Updater - SECURED
 * Now verifies payment with Paystack before updating DB
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOHEADER'))       define('NOHEADER', 1);

ob_start();

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

ob_clean();
header('Content-Type: application/json');

global $db, $user;

// --- 1. AUTH CHECK: User must be logged in ---
if (empty($user->id)) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// --- 2. Get & validate input ---
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['order_id']) || !isset($input['reference'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$order_id = (int)$input['order_id'];
$reference = $input['reference'];

// --- 3. OWNERSHIP CHECK: Order must belong to logged-in user ---
$sql_check = "SELECT d.rowid, d.total_amount, d.payment_status
              FROM ".MAIN_DB_PREFIX."foodbank_distributions d
              INNER JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON d.fk_beneficiary = b.rowid
              WHERE d.rowid = ".$order_id." AND b.fk_user = ".(int)$user->id;
$res_check = $db->query($sql_check);

if (!$res_check || $db->num_rows($res_check) == 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Order not found or access denied']);
    exit;
}

$order = $db->fetch_object($res_check);

// Prevent double-processing
if ($order->payment_status === 'Paid') {
    echo json_encode(['status' => 'success', 'message' => 'Already processed']);
    exit;
}

// --- 4. VERIFY PAYMENT WITH PAYSTACK ---
$paystack_secret_key = getDolGlobalString('FOODBANK_PAYSTACK_SECRET_KEY');
if (empty($paystack_secret_key)) {
    echo json_encode(['error' => 'Paystack is not configured. Contact admin.']);
    exit;
}

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
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
    dol_syslog("Paystack verification curl error: " . $err, LOG_ERR);
    echo json_encode(['error' => 'Payment verification failed. Please contact support.']);
    exit;
}

$result = json_decode($response);

if (!$result || !isset($result->data) || $result->data->status !== 'success') {
    dol_syslog("Paystack verification failed for ref: " . $reference, LOG_ERR);
    echo json_encode(['error' => 'Payment could not be verified']);
    exit;
}

// --- 5. AMOUNT CHECK: Paystack amount must match order total ---
$paid_amount = $result->data->amount / 100; // Paystack returns kobo
$expected_amount = (float)$order->total_amount;

if (abs($paid_amount - $expected_amount) > 1) {
    dol_syslog("Payment amount mismatch for order $order_id: paid=$paid_amount expected=$expected_amount", LOG_ERR);
    echo json_encode(['error' => 'Payment amount mismatch']);
    exit;
}

// --- 6. ALL CHECKS PASSED: Update Database ---
$db->begin();

$sql_dist = "UPDATE ".MAIN_DB_PREFIX."foodbank_distributions
             SET payment_status = 'Paid',
                 status = 'Prepared',
                 payment_reference = '".$db->escape($reference)."',
                 payment_gateway = 'Paystack',
                 payment_date = NOW()
             WHERE rowid = ".$order_id;

$res_dist = $db->query($sql_dist);

$sql_pay = "UPDATE ".MAIN_DB_PREFIX."foodbank_payments
            SET payment_status = 'Success',
                payment_date = NOW()
            WHERE fk_order = ".$order_id;

$res_pay = $db->query($sql_pay);

if ($res_dist) {
    $db->commit();
    echo json_encode(['status' => 'success']);
} else {
    $db->rollback();
    dol_syslog("DB update failed for order $order_id: " . $db->lasterror(), LOG_ERR);
    echo json_encode(['error' => 'Database update failed. Please contact support.']);
}
exit;
?>
