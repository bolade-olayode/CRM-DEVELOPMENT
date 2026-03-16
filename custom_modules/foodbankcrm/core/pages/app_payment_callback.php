<?php
/**
 * FoodbankCRM — App Payment Callback
 *
 * Paystack redirects here after payment (from the mobile app).
 * This page immediately redirects to the app's deep-link scheme so that
 * expo-web-browser / openAuthSessionAsync can intercept it cleanly.
 *
 * Usage: set callback_url in payment.php to this page's full HTTPS URL.
 *   https://yourdomain.com/htdocs/custom/foodbankcrm/core/pages/app_payment_callback.php
 *
 * Paystack appends: ?reference=xxx&trxref=xxx
 */

$reference = isset($_GET['reference']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['reference']) : '';
$trxref    = isset($_GET['trxref'])    ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['trxref'])    : '';

$ref = $reference ?: $trxref;

if (empty($ref)) {
    // No reference — send back to app anyway so the browser closes
    header('Location: app://payment-callback?error=no_reference');
    exit;
}

header('Location: app://payment-callback?reference=' . rawurlencode($ref) . '&trxref=' . rawurlencode($ref));
exit;
