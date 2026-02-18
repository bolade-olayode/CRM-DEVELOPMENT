<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

// Security check - Admin only
if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$order_id = GETPOST('id', 'int');
$new_status = GETPOST('status', 'alpha');

if (!$order_id || !$new_status) {
    header('Location: admin_orders.php');
    exit;
}

// Validate status
$valid_statuses = array('Pending', 'Prepared', 'Bundled', 'In Transit', 'Delivered');
if (!in_array($new_status, $valid_statuses)) {
    header('Location: admin_orders.php');
    exit;
}

// Update order status
$sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_distributions 
        SET status = '".$db->escape($new_status)."' 
        WHERE rowid = ".(int)$order_id;

if ($db->query($sql)) {
    // If delivered, mark payment as complete if it was Pay_On_Delivery
    if ($new_status == 'Delivered') {
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_distributions 
                SET payment_status = 'Paid', payment_date = NOW() 
                WHERE rowid = ".(int)$order_id." 
                AND payment_status = 'Pay on Delivery'";
        $db->query($sql);
        
        // Also update payment record
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_payments 
                SET payment_status = 'Success', payment_date = NOW() 
                WHERE fk_order = ".(int)$order_id." 
                AND payment_status = 'Pay on Delivery'";
        $db->query($sql);
    }
    
    header('Location: admin_orders.php?msg=status_updated');
} else {
    header('Location: admin_orders.php?error=update_failed');
}

exit;
?>