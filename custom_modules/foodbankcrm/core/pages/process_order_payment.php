<?php
/**
 * Process Order Payment
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;

if (!FoodbankPermissions::isBeneficiary($user, $db)) {
    accessforbidden('You do not have access to this page.');
}

$order_id = GETPOST('order_id', 'int');
if (!$order_id) { header('Location: dashboard_beneficiary.php'); exit; }

$sql_ben = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res_ben = $db->query($sql_ben);
if (!$res_ben || $db->num_rows($res_ben) == 0) {
    accessforbidden('Beneficiary profile not found.');
}
$subscriber_id = (int)$db->fetch_object($res_ben)->rowid;

$sql = "SELECT d.*, b.firstname, b.lastname, b.email, b.phone
        FROM ".MAIN_DB_PREFIX."foodbank_distributions d
        INNER JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON d.fk_beneficiary = b.rowid
        WHERE d.rowid = ".(int)$order_id." AND d.fk_beneficiary = ".$subscriber_id;
$res   = $db->query($sql);
$order = $db->fetch_object($res);
if (!$order) { header('Location: dashboard_beneficiary.php'); exit; }

$sql_items = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines WHERE fk_distribution = ".(int)$order_id;
$res_items = $db->query($sql_items);

$paystack_public_key = getDolGlobalString('FOODBANK_PAYSTACK_PUBLIC_KEY');

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Complete Payment');
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
    .fb-nav-link{color:rgba(255,255,255,.82);text-decoration:none;font-size:13px;font-weight:500;padding:7px 14px;border-radius:20px}
    .fb-nav-links{display:flex;gap:2px}
    @media(max-width:768px){
        .fb-nav-links{display:none!important;position:fixed;top:64px;left:0;right:0;flex-direction:column;background:linear-gradient(180deg,#0f766e,#0d9488);padding:6px 0 12px;z-index:9998;box-shadow:0 8px 24px rgba(0,0,0,.25);gap:0}
        .fb-nav-links.fb-open{display:flex!important}
    }
    .fb-nav-link:hover{background:rgba(255,255,255,.18);color:#fff}
    .fb-logout{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.35);padding:6px 16px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600}

    .page-wrap{max-width:640px;margin:0 auto;padding:40px 20px 60px}
    .pay-card{background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,.08);border:1px solid #e8edf2;overflow:hidden}
    .pay-card-top{padding:32px 36px;text-align:center;border-bottom:1px solid #f1f5f9}
    .pay-card-top h1{margin:0 0 6px;font-size:24px;font-weight:800;color:#1e293b}
    .pay-card-top p{margin:0;color:#64748b;font-size:13px}
    .pay-icon{width:64px;height:64px;background:linear-gradient(135deg,#d1fae5,#a7f3d0);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 16px}
    .pay-secure{display:inline-flex;align-items:center;gap:5px;margin-top:8px;font-size:11px;color:#059669;font-weight:600;background:#d1fae5;padding:3px 10px;border-radius:20px}
    .order-summary{padding:24px 36px;border-bottom:1px solid #f1f5f9}
    .order-summary h3{margin:0 0 14px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8}
    .item-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f8fafc}
    .item-row:last-of-type{border-bottom:none}
    .item-name{font-size:13px;color:#475569;font-weight:500}
    .item-qty{font-size:12px;color:#94a3b8}
    .total-row{display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:14px;border-top:2px solid #f1f5f9}
    .total-label{font-size:15px;font-weight:700;color:#1e293b}
    .total-amount{font-size:22px;font-weight:800;color:#0d9488}
    .pay-actions{padding:24px 36px}
    .btn-pay{display:block;width:100%;padding:16px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:16px;font-weight:800;border:none;border-radius:10px;cursor:pointer;transition:all .2s;box-shadow:0 4px 14px rgba(16,185,129,.35);letter-spacing:.3px}
    .btn-pay:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(16,185,129,.45)}
    .loading-state{display:none;text-align:center;padding:10px 0}
    @keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
    .spinner{width:40px;height:40px;border:4px solid #d1fae5;border-top-color:#059669;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 12px}
    .btn-cancel-link{display:block;text-align:center;margin-top:14px;font-size:13px;color:#94a3b8;text-decoration:none}
    .btn-cancel-link:hover{color:#64748b}
    .not-configured{text-align:center;padding:48px 36px}
    .not-configured h2{margin:16px 0 10px;font-size:20px;color:#dc2626}
    .not-configured p{color:#64748b;margin-bottom:24px;font-size:14px}
    .btn-back-link{display:inline-flex;padding:10px 24px;background:#0d9488;color:#fff;border-radius:30px;text-decoration:none;font-weight:700;font-size:14px}
</style>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var btn = document.getElementById('fb-hamburger');
    var links = document.querySelector('.fb-nav-links');
    if (!btn || !links) return;
    btn.addEventListener('click', function(){
        links.classList.toggle('fb-open');
        btn.classList.toggle('open');
    });
    document.addEventListener('click', function(e){
        if (!btn.contains(e.target) && !links.contains(e.target)) {
            links.classList.remove('fb-open');
            btn.classList.remove('open');
        }
    });
});
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
print '<div class="pay-card">';

if (empty($paystack_public_key)) {
    print '<div class="not-configured">';
    print '<div style="font-size:52px">⚙️</div>';
    print '<h2>Payment Not Configured</h2>';
    print '<p>Paystack has not been set up yet. Please contact the administrator to enable online payments.</p>';
    print '<a href="view_order.php?id='.$order_id.'" class="btn-back-link">← Back to Order</a>';
    print '</div>';
    print '</div></div>';
    llxFooter(); exit;
}

// Header
print '<div class="pay-card-top">';
print '<div class="pay-icon">💳</div>';
print '<h1>Complete Payment</h1>';
print '<p>Order '.dol_escape_htmltag($order->ref).' — '.dol_escape_htmltag($order->firstname.' '.$order->lastname).'</p>';
print '<div class="pay-secure">🔒 Secured by Paystack</div>';
print '</div>';

// Order Summary
print '<div class="order-summary">';
print '<h3>Order Items</h3>';
if ($res_items && $db->num_rows($res_items) > 0) {
    while ($item = $db->fetch_object($res_items)) {
        print '<div class="item-row">';
        print '<span class="item-name">'.dol_escape_htmltag($item->product_name).'</span>';
        print '<span class="item-qty">'.number_format($item->quantity, 0).' '.dol_escape_htmltag($item->unit).'</span>';
        print '</div>';
    }
}
print '<div class="total-row">';
print '<span class="total-label">Total Amount</span>';
print '<span class="total-amount">₦'.number_format($order->total_amount, 2).'</span>';
print '</div>';
print '</div>';

// Pay Button
print '<div class="pay-actions">';
print '<button id="paystack-btn" class="btn-pay">Pay ₦'.number_format($order->total_amount, 0).' Securely</button>';
print '<div class="loading-state" id="loading">';
print '<div class="spinner"></div>';
print '<p style="color:#64748b;font-size:13px;margin:0">Processing your payment...</p>';
print '</div>';
print '<a href="my_orders.php" class="btn-cancel-link">← Cancel & Return to Orders</a>';
print '</div>';

print '</div>'; // pay-card
print '</div>'; // page-wrap
?>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
document.getElementById('paystack-btn').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('paystack-btn').style.display = 'none';
    document.getElementById('loading').style.display = 'block';

    var handler = PaystackPop.setup({
        key: '<?php echo $paystack_public_key; ?>',
        email: '<?php echo dol_escape_js($order->email); ?>',
        amount: <?php echo $order->total_amount * 100; ?>,
        currency: 'NGN',
        ref: 'ORD-<?php echo $order->ref; ?>-'+Math.floor((Math.random() * 1000000000) + 1),
        metadata: { order_id: <?php echo $order_id; ?> },
        callback: function(response){
            fetch('update_payment_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    order_id: <?php echo $order_id; ?>,
                    reference: response.reference,
                    status: 'success'
                })
            })
            .then(res => res.json())
            .then(data => {
                window.location.href = 'order_confirmation.php?order_id=<?php echo $order_id; ?>&payment=success';
            })
            .catch(error => {
                console.error(error);
                window.location.href = 'order_confirmation.php?order_id=<?php echo $order_id; ?>&payment=success';
            });
        },
        onClose: function(){
            document.getElementById('loading').style.display = 'none';
            document.getElementById('paystack-btn').style.display = 'block';
        }
    });
    handler.openIframe();
});
</script>

<?php llxFooter(); ?>
