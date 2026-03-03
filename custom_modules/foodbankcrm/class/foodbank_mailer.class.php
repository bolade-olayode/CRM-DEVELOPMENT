<?php
/**
 * FoodbankCRM Mailer
 *
 * Central class for all outgoing emails.
 * Uses Dolibarr's CMailFile which picks up the SMTP settings you
 * configure in: Admin → Setup → Email → Outgoing Mail.
 *
 * Usage example:
 *   require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/foodbank_mailer.class.php';
 *   FoodbankMailer::sendOrderConfirmation($subscriber, $ref, $total, $cart_items, $is_cod);
 *
 * All methods are non-blocking — if an email fails it is logged via
 * dol_syslog() and the caller's flow continues uninterrupted.
 */

class FoodbankMailer
{
    const COLOR_PRIMARY = '#0d9488';
    const COLOR_DARK    = '#0f766e';

    // =========================================================================
    // PRIVATE: Infrastructure
    // =========================================================================

    /**
     * Returns the FROM address in "Name <email>" format,
     * pulled from Dolibarr's global SMTP configuration.
     */
    private static function from()
    {
        $email = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
        $name  = getDolGlobalString('MAIN_MAIL_EMAIL_FROM_NAME');
        if (empty($name)) $name = 'FoodbankCRM';
        if (empty($email)) return '';
        return '"' . addslashes($name) . '" <' . $email . '>';
    }

    /**
     * Returns the admin notification email.
     * Configured in FoodbankCRM Settings → Admin Notification Email.
     * Falls back to the SMTP FROM address if not set.
     */
    private static function adminEmail()
    {
        $addr = getDolGlobalString('FOODBANK_ADMIN_NOTIFICATION_EMAIL');
        if (empty($addr)) {
            $addr = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
        }
        return $addr;
    }

    /**
     * Core send method. All public methods call this.
     * Returns true on success, false on any failure.
     * Never throws an exception.
     */
    private static function send($to_email, $to_name, $subject, $html_body)
    {
        if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            dol_syslog('FoodbankMailer: Invalid recipient "' . $to_email . '" — skipping', LOG_WARNING);
            return false;
        }

        $from = self::from();
        if (empty($from)) {
            dol_syslog('FoodbankMailer: MAIN_MAIL_EMAIL_FROM not configured — email not sent', LOG_WARNING);
            return false;
        }

        require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

        $to   = empty($to_name) ? $to_email : '"' . addslashes($to_name) . '" <' . $to_email . '>';
        $mail = new CMailFile(
            $subject,
            $to,
            $from,
            $html_body,
            array(), array(), array(), // no attachments
            '', '',                    // no CC, BCC
            0,                         // no delivery receipt
            1                          // is HTML (1 = yes)
        );

        if (!empty($mail->error)) {
            dol_syslog('FoodbankMailer: Init error — ' . $mail->error, LOG_ERR);
            return false;
        }

        $result = $mail->sendfile();
        if (!$result) {
            $err = isset($mail->error) ? $mail->error : 'unknown error';
            dol_syslog('FoodbankMailer: Send failed to ' . $to_email . ' — ' . $err, LOG_ERR);
            return false;
        }

        dol_syslog('FoodbankMailer: "' . $subject . '" sent to ' . $to_email, LOG_INFO);
        return true;
    }

    // =========================================================================
    // PRIVATE: HTML template helpers
    // All emails share the same header/footer shell.
    // =========================================================================

    /**
     * Wraps content in the FoodbankCRM branded email shell.
     * $preheader = short text shown in inbox preview (hidden in email body).
     */
    private static function wrap($title, $preheader, $content)
    {
        $p = self::COLOR_PRIMARY;
        $d = self::COLOR_DARK;

        return '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <title>' . htmlspecialchars($title) . '</title>
</head>
<body style="margin:0;padding:0;background:#f0f4f8;font-family:\'Segoe UI\',Arial,Helvetica,sans-serif">
<!-- Inbox preview text (hidden) -->
<div style="display:none;max-height:0;overflow:hidden;color:#f0f4f8">' . htmlspecialchars($preheader) . '&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8;padding:32px 16px">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">
      <!-- HEADER -->
      <tr><td style="background:linear-gradient(135deg,' . $d . ',' . $p . ');border-radius:16px 16px 0 0;padding:32px 40px;text-align:center">
        <div style="font-size:40px;margin-bottom:8px">&#127814;</div>
        <div style="color:#ffffff;font-size:22px;font-weight:800;letter-spacing:-0.5px">FoodbankCRM</div>
        <div style="color:rgba(255,255,255,0.75);font-size:13px;margin-top:6px">' . htmlspecialchars($title) . '</div>
      </td></tr>
      <!-- BODY -->
      <tr><td style="background:#ffffff;padding:40px;border-left:1px solid #e8edf2;border-right:1px solid #e8edf2">
        ' . $content . '
      </td></tr>
      <!-- FOOTER -->
      <tr><td style="background:#f8fafc;border-radius:0 0 16px 16px;border:1px solid #e8edf2;border-top:none;padding:24px 40px;text-align:center">
        <p style="margin:0 0 6px;font-size:12px;color:#94a3b8">This is an automated message from FoodbankCRM. Please do not reply to this email.</p>
        <p style="margin:0;font-size:11px;color:#cbd5e1">&copy; ' . date('Y') . ' FoodbankCRM &mdash; All rights reserved.</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>';
    }

    /** Bold heading */
    private static function h($text)
    {
        return '<h2 style="margin:0 0 20px;font-size:22px;font-weight:800;color:#1e293b;line-height:1.3">' . $text . '</h2>';
    }

    /** Paragraph */
    private static function p($text)
    {
        return '<p style="margin:0 0 16px;font-size:15px;color:#475569;line-height:1.6">' . $text . '</p>';
    }

    /** CTA button */
    private static function btn($url, $label)
    {
        $p = self::COLOR_PRIMARY;
        return '<div style="text-align:center;margin:28px 0">
  <a href="' . htmlspecialchars($url) . '" style="display:inline-block;background:' . $p . ';color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:8px;font-size:15px;font-weight:700">' . $label . '</a>
</div>';
    }

    /** Key-value info table */
    private static function infoTable(array $rows)
    {
        $html = '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e8edf2;border-radius:10px;overflow:hidden;margin:20px 0;font-size:14px">';
        foreach ($rows as $label => $value) {
            $html .= '<tr>
  <td style="padding:11px 16px;color:#64748b;width:42%;border-bottom:1px solid #f1f5f9;vertical-align:top">' . $label . '</td>
  <td style="padding:11px 16px;color:#1e293b;font-weight:600;border-bottom:1px solid #f1f5f9;vertical-align:top">' . $value . '</td>
</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    /** Coloured alert box */
    private static function alert($message, $type = 'info')
    {
        $s = [
            'info'    => 'background:#dbeafe;border:1px solid #bfdbfe;color:#1e40af',
            'success' => 'background:#d1fae5;border:1px solid #a7f3d0;color:#065f46',
            'warning' => 'background:#fffbeb;border:1px solid #fcd34d;color:#92400e',
        ][$type] ?? 'background:#dbeafe;border:1px solid #bfdbfe;color:#1e40af';

        return '<div style="' . $s . ';border-radius:8px;padding:14px 18px;margin:16px 0;font-size:14px">' . $message . '</div>';
    }

    // =========================================================================
    // PUBLIC: Email methods
    // =========================================================================

    /**
     * 1. OTP Verification Email
     * Sent on registration and on code resend.
     * Shows a big, prominent 6-digit code with a 10-minute expiry note.
     */
    public static function sendOtpEmail($to_email, $firstname, $otp)
    {
        $name = htmlspecialchars($firstname);
        $code = htmlspecialchars($otp);
        $p    = self::COLOR_PRIMARY;
        $d    = self::COLOR_DARK;

        $content = self::h('Verify Your Email Address &#128231;')
            . self::p('Hi ' . $name . ', welcome to FoodbankCRM! Enter the verification code below to activate your account.')
            . '<div style="text-align:center;margin:32px 0">
  <div style="display:inline-block;background:#f0fdfa;border:2px dashed ' . $p . ';border-radius:14px;padding:24px 48px">
    <div style="font-size:40px;font-weight:800;letter-spacing:10px;color:' . $d . '">' . $code . '</div>
    <div style="font-size:12px;color:#64748b;margin-top:10px">&#8987; This code expires in <strong>10 minutes</strong></div>
  </div>
</div>'
            . self::p('If you did not register on FoodbankCRM, you can safely ignore this email.');

        return self::send(
            $to_email,
            $firstname,
            'Your FoodbankCRM Verification Code: ' . $otp,
            self::wrap('Email Verification', 'Your verification code is: ' . $otp, $content)
        );
    }

    /**
     * 2. Welcome Email
     * Sent after the subscriber successfully verifies their OTP.
     */
    public static function sendWelcomeEmail($firstname, $lastname, $to_email, $username)
    {
        $name = htmlspecialchars($firstname . ' ' . $lastname);
        $user = htmlspecialchars($username);

        $content = self::h('Welcome to FoodbankCRM, ' . htmlspecialchars($firstname) . '! &#127814;')
            . self::p('Your email has been verified and your account is now fully active. Here\'s a summary:')
            . self::infoTable([
                'Full Name' => $name,
                'Username'  => $user,
                'Email'     => htmlspecialchars($to_email),
                'Status'    => '&#10003; Active',
            ])
            . self::p('To start placing food orders, log in and select a subscription plan. Your plan gives you access to all available food packages for the subscription period.')
            . self::p('If you have any questions, do not hesitate to reach out.');

        return self::send(
            $to_email,
            $firstname . ' ' . $lastname,
            'Welcome to FoodbankCRM — Your Account is Active!',
            self::wrap('Account Activated', 'Your FoodbankCRM account is now active. Welcome!', $content)
        );
    }

    /**
     * 3. Admin — New Registration Notification
     * Sent to the admin when a new subscriber or vendor registers.
     * $type = 'subscriber' | 'vendor'
     */
    public static function sendAdminNewRegistration($type, $full_name, $reg_email)
    {
        $admin_email = self::adminEmail();
        if (empty($admin_email)) return false;

        $label     = ($type === 'vendor') ? 'Vendor' : 'Subscriber';
        $icon      = ($type === 'vendor') ? '&#127978;' : '&#128100;';
        $admin_url = DOL_URL_ROOT . '/custom/foodbankcrm/core/pages/'
                   . ($type === 'vendor' ? 'vendors.php' : 'beneficiaries.php');

        $content = self::h($icon . ' New ' . $label . ' Registration')
            . self::p('A new ' . strtolower($label) . ' has just registered on FoodbankCRM.')
            . self::infoTable([
                'Name'       => htmlspecialchars($full_name),
                'Email'      => htmlspecialchars($reg_email),
                'Type'       => $label,
                'Registered' => date('F j, Y \a\t g:i A'),
            ])
            . self::btn($admin_url, 'View ' . $label . 's &#8594;')
            . self::p('Log in to the admin panel to review the registration.');

        return self::send(
            $admin_email,
            'FoodbankCRM Admin',
            '[FoodbankCRM] New ' . $label . ' — ' . $full_name,
            self::wrap('New ' . $label . ' Registration', 'A new ' . strtolower($label) . ' just registered.', $content)
        );
    }

    /**
     * 4. Order Confirmation
     * Sent after a subscriber places an order at checkout.
     * $cart_items: array of stdClass with ->package_name, ->quantity, ->line_total
     */
    public static function sendOrderConfirmation($subscriber, $ref, $total, array $cart_items, $is_cod = false)
    {
        $fname    = htmlspecialchars($subscriber->firstname);
        $ref_html = htmlspecialchars($ref);
        $amount   = '&#8358;' . number_format($total, 2);
        $pay_txt  = $is_cod ? '&#128181; Cash on Delivery' : '&#128179; Pay via Paystack';

        // Build items rows
        $items_html = '<table width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;border:1px solid #e8edf2;border-radius:8px;overflow:hidden;font-size:13px">
  <tr style="background:#f8fafc">
    <th style="padding:10px 14px;text-align:left;color:#64748b;font-weight:600">Package</th>
    <th style="padding:10px 14px;text-align:center;color:#64748b;font-weight:600">Qty</th>
    <th style="padding:10px 14px;text-align:right;color:#64748b;font-weight:600">Subtotal</th>
  </tr>';
        foreach ($cart_items as $item) {
            $items_html .= '<tr>
  <td style="padding:10px 14px;color:#334155;border-top:1px solid #f1f5f9">' . htmlspecialchars($item->package_name) . '</td>
  <td style="padding:10px 14px;color:#64748b;text-align:center;border-top:1px solid #f1f5f9">' . (int)$item->quantity . '</td>
  <td style="padding:10px 14px;color:#1e293b;font-weight:600;text-align:right;border-top:1px solid #f1f5f9">&#8358;' . number_format($item->line_total, 2) . '</td>
</tr>';
        }
        $items_html .= '<tr style="background:#f0fdfa">
  <td colspan="2" style="padding:12px 14px;font-weight:700;color:#1e293b">Total</td>
  <td style="padding:12px 14px;font-weight:800;color:' . self::COLOR_PRIMARY . ';text-align:right;font-size:15px">' . $amount . '</td>
</tr></table>';

        $pay_note = $is_cod
            ? self::alert('&#128181; Please have <strong>' . $amount . '</strong> ready for payment when your order is delivered.', 'warning')
            : self::alert('&#128179; Your payment is pending. Complete it from your orders page to get your order processed.', 'info');

        $content = self::h('Order Confirmed, ' . $fname . '! &#128230;')
            . self::p('Your order has been successfully placed. Here are the details:')
            . self::infoTable([
                'Order Reference' => '<strong>' . $ref_html . '</strong>',
                'Payment Method'  => $pay_txt,
                'Status'          => 'Pending Processing',
                'Date'            => date('F j, Y'),
            ])
            . '<div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;margin-top:20px;margin-bottom:4px">Your Items</div>'
            . $items_html
            . $pay_note;

        return self::send(
            $subscriber->email,
            $subscriber->firstname . ' ' . $subscriber->lastname,
            'Order Confirmed — ' . $ref . ' | FoodbankCRM',
            self::wrap('Order Confirmation', 'Your order ' . $ref . ' has been placed successfully.', $content)
        );
    }

    /**
     * 5. Payment Receipt
     * Sent after Paystack payment is confirmed (via webhook or direct verify).
     */
    public static function sendPaymentReceipt($subscriber, $order_ref, $order_total, $paystack_reference)
    {
        $fname  = htmlspecialchars($subscriber->firstname);
        $ref    = htmlspecialchars($order_ref);
        $amount = '&#8358;' . number_format($order_total, 2);
        $payref = htmlspecialchars($paystack_reference);

        $content = self::h('Payment Confirmed &#10003;')
            . self::p('Hi ' . $fname . ', your payment has been received and confirmed. Your order is now being prepared.')
            . self::infoTable([
                'Order Reference'   => $ref,
                'Amount Paid'       => $amount,
                'Payment Reference' => '<code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:12px">' . $payref . '</code>',
                'Payment Method'    => 'Paystack',
                'Date'              => date('F j, Y'),
                'Status'            => '&#10003; Paid',
            ])
            . self::alert('&#128230; Your order is now queued for preparation. You will receive another update when it is dispatched.', 'success');

        return self::send(
            $subscriber->email,
            $subscriber->firstname . ' ' . $subscriber->lastname,
            'Payment Confirmed — ' . $order_ref . ' | FoodbankCRM',
            self::wrap('Payment Receipt', 'Payment of ₦' . number_format($order_total, 2) . ' confirmed.', $content)
        );
    }

    /**
     * 6. Subscription Activated
     * Sent after a subscription plan is successfully activated or renewed.
     */
    public static function sendSubscriptionActivated($subscriber, $tier_name, $end_date)
    {
        $fname     = htmlspecialchars($subscriber->firstname);
        $plan      = htmlspecialchars($tier_name);
        $valid_to  = date('F j, Y', strtotime($end_date));
        $days_left = max(0, (int)floor((strtotime($end_date) - time()) / 86400));

        $content = self::h('Subscription Activated! &#127881;')
            . self::p('Congratulations, ' . $fname . '! Your <strong>' . $plan . '</strong> plan is now active.')
            . self::infoTable([
                'Plan'           => $plan,
                'Status'         => '&#10003; Active',
                'Valid Until'    => $valid_to,
                'Days Remaining' => $days_left . ' days',
                'Payment'        => 'Paid via Paystack',
            ])
            . self::p('You can now browse and order food packages from your dashboard throughout your subscription period.')
            . self::alert('&#128161; Tip: You can check your subscription status and days remaining any time from your profile page.', 'success');

        return self::send(
            $subscriber->email,
            $subscriber->firstname . ' ' . $subscriber->lastname,
            'Subscription Activated — ' . $tier_name . ' | FoodbankCRM',
            self::wrap('Subscription Activated', $tier_name . ' plan is active until ' . $valid_to . '.', $content)
        );
    }

    /**
     * 7. Order Status Update
     * Sent when an admin changes an order's fulfilment status.
     * $new_status = 'Prepared' | 'In Transit' | 'Delivered'
     */
    public static function sendOrderStatusUpdate($subscriber, $order_ref, $new_status)
    {
        $fname  = htmlspecialchars($subscriber->firstname);
        $ref    = htmlspecialchars($order_ref);
        $status = htmlspecialchars($new_status);

        $icons = [
            'Prepared'   => '&#128230;',
            'In Transit' => '&#128666;',
            'Delivered'  => '&#10003;',
        ];
        $icon = $icons[$new_status] ?? '&#128203;';

        $notes = [
            'Prepared'   => 'Your order has been packed and is ready for dispatch. We will notify you again when it is on its way.',
            'In Transit' => 'Your order is on its way! Please ensure someone is available to receive it at your delivery address.',
            'Delivered'  => 'Your order has been delivered. We hope you enjoy it — thank you for using FoodbankCRM!',
        ];
        $note_text = $notes[$new_status] ?? 'Your order status has been updated. Log in for full details.';

        $content = self::h($icon . ' Order Status: ' . $status)
            . self::p('Hi ' . $fname . ', your order status has been updated.')
            . self::infoTable([
                'Order Reference' => $ref,
                'New Status'      => $icon . ' <strong>' . $status . '</strong>',
                'Updated'         => date('F j, Y \a\t g:i A'),
            ])
            . self::p($note_text);

        return self::send(
            $subscriber->email,
            $subscriber->firstname . ' ' . $subscriber->lastname,
            'Order Update: ' . $status . ' — ' . $order_ref . ' | FoodbankCRM',
            self::wrap('Order Status Update', 'Your order ' . $order_ref . ' is now ' . $new_status . '.', $content)
        );
    }
}
