<?php
/**
 * Order Confirmation
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;
$langs->load("admin");

if (!FoodbankPermissions::isBeneficiary($user, $db)) {
    accessforbidden('You must be a subscriber.');
}

$sql_ben = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res_ben = $db->query($sql_ben);
if (!$res_ben || $db->num_rows($res_ben) == 0) accessforbidden();
$subscriber    = $db->fetch_object($res_ben);
$subscriber_id = (int)$subscriber->rowid;

$order_id = GETPOST('order_id', 'int');
if (!$order_id) { header('Location: product_catalog.php'); exit; }

$sql = "SELECT d.*, b.firstname, b.lastname, b.email, b.phone
        FROM ".MAIN_DB_PREFIX."foodbank_distributions d
        INNER JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON d.fk_beneficiary = b.rowid
        WHERE d.rowid = ".(int)$order_id."
        AND d.fk_beneficiary = ".$subscriber_id;
$res   = $db->query($sql);
$order = $db->fetch_object($res);
if (!$order) { header('Location: my_orders.php'); exit; }

$sql_items = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines WHERE fk_distribution = ".(int)$order_id;
$res_items = $db->query($sql_items);

$is_cod             = ($order->payment_method == 'pay_on_delivery');
$is_paid            = in_array($order->payment_status, ['Paid', 'Success']);
$is_pending_paystack = (!$is_cod && !$is_paid);

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Order Confirmation');
?>
<link rel="stylesheet" href="<?php echo DOL_URL_ROOT; ?>/custom/foodbankcrm/css/responsive.css">
<style>
    #id-top,.side-nav,.side-nav-vert,#id-left,.login_block,.tmenudiv,.nav-bar,header{display:none!important;width:0!important;height:0!important;pointer-events:none!important}
    html,body{background:#f0f4f8!important;margin:0!important;padding:0!important;width:100%!important;overflow-x:hidden!important;font-family:'Segoe UI',system-ui,sans-serif}
    #id-container,#id-right,.id-right{margin:0!important;padding:0!important;width:100vw!important;max-width:100vw!important;display:block!important}
    .fiche{width:100%!important;max-width:100%!important;margin:0!important}
    *{box-sizing:border-box}
    body{padding-top:64px!important}

    .fb-nav{position:fixed;top:0;left:0;right:0;height:64px;background:linear-gradient(90deg,#0f766e,#0d9488);display:flex;align-items:center;justify-content:space-between;padding:0 28px;z-index:9999;box-shadow:0 2px 16px rgba(0,0,0,.18)}
    .fb-nav-brand{font-size:17px;font-weight:800;color:#fff;text-decoration:none}
    .fb-nav-links{display:flex;gap:2px}
    .fb-nav-link{color:rgba(255,255,255,.82);text-decoration:none;font-size:13px;font-weight:500;padding:7px 14px;border-radius:20px}
    .fb-nav-link:hover{background:rgba(255,255,255,.18);color:#fff}
    .fb-logout{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.35);padding:6px 16px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600}

    .page-wrap{max-width:900px;margin:0 auto;padding:36px 24px 60px}

    /* STATUS BANNERS */
    .banner{border-radius:16px;padding:36px 40px;text-align:center;margin-bottom:28px;position:relative;overflow:hidden}
    .banner-success{background:linear-gradient(135deg,#064e3b,#059669)}
    .banner-cod{background:linear-gradient(135deg,#0f766e,#0d9488)}
    .banner-warn{background:linear-gradient(135deg,#78350f,#d97706)}
    .banner h1{margin:0 0 8px;font-size:28px;font-weight:800;color:#fff}
    .banner p{margin:0;font-size:15px;color:rgba(255,255,255,.8)}
    .banner-icon{font-size:64px;margin-bottom:16px;display:block}
    .banner-ref{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);color:#fff;padding:6px 16px;border-radius:20px;font-size:13px;font-weight:700;margin-top:14px}

    /* GRID */
    .conf-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
    @media(max-width:640px){.conf-grid{grid-template-columns:1fr}}

    /* CARDS */
    .card{background:#fff;border-radius:14px;box-shadow:0 4px 16px rgba(0,0,0,.06);border:1px solid #e8edf2;overflow:hidden}
    .card-header{padding:16px 22px;border-bottom:1px solid #f1f5f9}
    .card-header h3{margin:0;font-size:14px;font-weight:700;color:#1e293b}
    .card-body{padding:18px 22px}
    .item-row{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid #f8fafc;font-size:13px}
    .item-row:last-child{border-bottom:none}
    .item-name{font-weight:600;color:#334155}
    .item-check{color:#10b981;font-size:16px;font-weight:800}
    .detail-row{display:flex;justify-content:space-between;font-size:13px;margin-bottom:10px}
    .detail-label{color:#94a3b8;font-weight:500}
    .detail-value{font-weight:700;color:#1e293b}
    .total-row{display:flex;justify-content:space-between;margin-top:12px;padding-top:12px;border-top:2px solid #f1f5f9;font-size:17px;font-weight:800;color:#0d9488}
    .status-badge{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase}
    .b-prepared{background:#fef9c3;color:#854d0e}
    .b-pending{background:#f1f5f9;color:#475569}
    .b-delivered{background:#d1fae5;color:#065f46}

    /* PENDING PAY ALERT */
    .pay-alert{background:#fffbeb;border:1px solid #fcd34d;border-radius:12px;padding:20px 24px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
    .btn-pay-now{display:inline-flex;align-items:center;gap:6px;padding:10px 22px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:13px;font-weight:700;border-radius:30px;text-decoration:none;box-shadow:0 3px 10px rgba(16,185,129,.3)}

    /* ACTIONS */
    .conf-actions{display:flex;gap:12px;flex-wrap:wrap;justify-content:center;margin-top:8px}
    .btn-action{display:inline-flex;align-items:center;gap:6px;padding:11px 22px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;transition:all .2s}
    .btn-primary{background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;box-shadow:0 3px 10px rgba(13,148,136,.25)}
    .btn-secondary{background:#f1f5f9;color:#475569}
    .btn-primary:hover,.btn-secondary:hover{transform:translateY(-1px)}
</style>
<script>
(function(){
    var btn = document.getElementById('fb-hamburger');
    var links = document.querySelector('.fb-nav-links');
    if (btn && links) {
        btn.addEventListener('click', function(){
            links.classList.toggle('fb-open');
            btn.classList.toggle('open');
        });
        // Close on outside click
        document.addEventListener('click', function(e){
            if (!btn.contains(e.target) && !links.contains(e.target)) {
                links.classList.remove('fb-open');
                btn.classList.remove('open');
            }
        });
    }
})();
</script>

<?php
print '<nav class="fb-nav">';
print '<a href="dashboard_beneficiary.php" class="fb-nav-brand">🥦 FoodbankCRM</a>';
print '<div class="fb-nav-links">';
print '<a href="product_catalog.php" class="fb-nav-link">Packages</a>';
print '<a href="view_cart.php" class="fb-nav-link">My Cart</a>';
print '<a href="my_orders.php" class="fb-nav-link">Orders</a>';
print '<a href="my_profile.php" class="fb-nav-link">Profile</a>';
print '</div>';
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="fb-logout">🚪 Logout</a>';
print '<button class="fb-nav-hamburger" id="fb-hamburger" aria-label="Toggle menu"><span></span><span></span><span></span></button>';
print '</nav>';

print '<div class="page-wrap">';

// STATUS BANNER
if ($is_cod) {
    print '<div class="banner banner-cod">';
    print '<span class="banner-icon">📦</span>';
    print '<h1>Order Confirmed!</h1>';
    print '<p>Your order has been placed. Please have <strong>₦'.number_format($order->total_amount, 2).'</strong> ready for cash payment on delivery.</p>';
    print '<div class="banner-ref">📋 Ref: '.dol_escape_htmltag($order->ref).'</div>';
    print '</div>';
} elseif ($is_paid) {
    print '<div class="banner banner-success">';
    print '<span class="banner-icon">✅</span>';
    print '<h1>Payment Successful!</h1>';
    print '<p>Thank you, <strong>'.dol_escape_htmltag($order->firstname).'</strong>! Your order has been confirmed and is being prepared.</p>';
    print '<div class="banner-ref">📋 Ref: '.dol_escape_htmltag($order->ref).'</div>';
    print '</div>';
} else {
    print '<div class="banner banner-warn">';
    print '<span class="banner-icon">⚠️</span>';
    print '<h1>Payment Pending</h1>';
    print '<p>Your order is saved but payment hasn\'t been completed yet.</p>';
    print '<div class="banner-ref">📋 Ref: '.dol_escape_htmltag($order->ref).'</div>';
    print '</div>';
}

// PENDING PAYMENT ALERT
if ($is_pending_paystack) {
    print '<div class="pay-alert">';
    print '<div>';
    print '<div style="font-weight:700;color:#92400e;margin-bottom:4px">💳 Complete Your Payment</div>';
    print '<div style="font-size:13px;color:#78350f">Your order is reserved. Complete the payment to have it processed.</div>';
    print '</div>';
    print '<a href="process_order_payment.php?order_id='.$order_id.'" class="btn-pay-now">Pay ₦'.number_format($order->total_amount, 0).' Now</a>';
    print '</div>';
}

// GRID: items + details
print '<div class="conf-grid">';

// Items card
$status_badge = ['Prepared' => 'b-prepared', 'Pending' => 'b-pending', 'Delivered' => 'b-delivered'];
$badge_cls = $status_badge[$order->status] ?? 'b-pending';
print '<div class="card">';
print '<div class="card-header"><h3>📦 Order Items</h3></div>';
print '<div class="card-body">';
if ($res_items && $db->num_rows($res_items) > 0) {
    while ($item = $db->fetch_object($res_items)) {
        print '<div class="item-row">';
        print '<div><div class="item-name">'.dol_escape_htmltag($item->product_name).'</div>';
        print '<div style="font-size:11px;color:#94a3b8;margin-top:2px">'.number_format($item->quantity, 0).' '.dol_escape_htmltag($item->unit).'</div></div>';
        print '<span class="item-check">✓</span>';
        print '</div>';
    }
}
print '</div></div>';

// Details card
$pay_label = $is_cod ? '💵 Cash on Delivery' : ($is_paid ? '✅ Paid via Paystack' : '⏳ Pending');
print '<div class="card">';
print '<div class="card-header"><h3>📋 Order Details</h3></div>';
print '<div class="card-body">';
print '<div class="detail-row"><span class="detail-label">Reference</span><span class="detail-value">'.dol_escape_htmltag($order->ref).'</span></div>';
print '<div class="detail-row"><span class="detail-label">Date</span><span class="detail-value">'.dol_print_date($db->jdate($order->datec), 'day').'</span></div>';
print '<div class="detail-row"><span class="detail-label">Status</span><span class="status-badge '.$badge_cls.'">'.dol_escape_htmltag($order->status).'</span></div>';
print '<div class="detail-row"><span class="detail-label">Payment</span><span class="detail-value">'.$pay_label.'</span></div>';
if ($order->phone) {
    print '<div class="detail-row"><span class="detail-label">Contact</span><span class="detail-value">'.dol_escape_htmltag($order->phone).'</span></div>';
}
if ($order->note) {
    print '<div style="margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9">';
    print '<div class="detail-label" style="margin-bottom:4px">Note</div>';
    print '<div style="font-size:13px;color:#475569">'.dol_escape_htmltag($order->note).'</div>';
    print '</div>';
}
print '<div class="total-row"><span>Total</span><span>₦'.number_format($order->total_amount, 2).'</span></div>';
print '</div></div>';

print '</div>'; // conf-grid

// Action buttons
print '<div class="conf-actions">';
print '<a href="my_orders.php" class="btn-action btn-primary">📋 My Orders</a>';
print '<a href="product_catalog.php" class="btn-action btn-secondary">📦 Browse Packages</a>';
print '<a href="dashboard_beneficiary.php" class="btn-action btn-secondary">🏠 Dashboard</a>';
print '</div>';

print '</div>'; // page-wrap

llxFooter();
?>
