<?php
/**
 * Subscription Status Check - Include at top of protected pages
 */

// This file should be included in: product_catalog.php, view_cart.php, checkout.php

if (!isset($subscriber) || !isset($subscriber_id)) {
    return; // Skip check if subscriber not loaded
}

// Check if subscription end date has passed
if (!empty($subscriber->subscription_end_date)) {
    $end_date = strtotime($subscriber->subscription_end_date);
    $today = strtotime(date('Y-m-d'));
    
    if ($end_date < $today && $subscriber->subscription_status != 'Expired') {
        // Mark as expired
        $sql_expire = "UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries 
                       SET subscription_status = 'Expired' 
                       WHERE rowid = ".(int)$subscriber_id;
        $db->query($sql_expire);
        
        $subscriber->subscription_status = 'Expired';
    }
}

// Block access if expired or guest trying to order
$current_page = basename($_SERVER['PHP_SELF']);
$restricted_pages = array('view_cart.php', 'checkout.php', 'add_to_cart.php');

if (in_array($current_page, $restricted_pages)) {
    if ($subscriber->subscription_status == 'Expired') {
        setEventMessages('Your subscription has expired. Please renew to continue.', null, 'warnings');
        header('Location: renew_subscription.php');
        exit;
    }
    
    if ($subscriber->subscription_type == 'Guest') {
        setEventMessages('Guest users cannot place orders. Please subscribe to continue.', null, 'warnings');
        header('Location: renew_subscription.php');
        exit;
    }
}

// Show warning banner if expiring soon (within 7 days)
if (!empty($subscriber->subscription_end_date)) {
    $end_date = strtotime($subscriber->subscription_end_date);
    $days_remaining = floor(($end_date - time()) / 86400);
    
    if ($days_remaining > 0 && $days_remaining <= 7) {
        setEventMessages('⚠️ Your subscription expires in '.$days_remaining.' day(s). Renew now to avoid interruption.', null, 'warnings');
    }
}
?>
