<?php
/**
 * View Order Details (Subscriber)
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

if (isset($_SESSION['foodbank_checked'])) {
    $_SESSION['foodbank_checked'] = false;
}

$langs->load("admin");

$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);

if (!$user_is_beneficiary) {
    accessforbidden('You do not have access to view orders.');
}

$order_id = GETPOST('id', 'int');
if (empty($order_id)) {
    header('Location: my_orders.php');
    exit;
}

// Get beneficiary profile
$sql_ben = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res_ben = $db->query($sql_ben);
if (!$res_ben || $db->num_rows($res_ben) == 0) {
    accessforbidden('Beneficiary profile not found.');
}
$beneficiary = $db->fetch_object($res_ben);
$subscriber_id = (int)$beneficiary->rowid;

$_fb_admin_head = '<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">'
              . '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/css/admin_mobile.css">';

// Get order
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_distributions
        WHERE rowid = ".(int)$order_id."
        AND fk_beneficiary = ".(int)$subscriber_id;
$res = $db->query($sql);
if (!$res || $db->num_rows($res) == 0) {
    $_SESSION["mainmenu"] = "foodbankcrm";
    llxHeader($_fb_admin_head, 'Order Not Found');
    echo '<style>#id-left { display:none!important; } #id-right { margin-left:0!important; width:100%!important; } </style>';
    echo '<div style="padding:40px; text-align:center; font-family:Segoe UI,sans-serif;">';
    echo '<p style="color:#94a3b8; font-size:15px;">Order not found or you do not have permission to view it.</p>';
    echo '<a href="my_orders.php" style="color:#4f46e5; font-weight:600;">← Back to My Orders</a>';
    echo '</div>';
    llxFooter();
    exit;
}
$order = $db->fetch_object($res);

// Status helpers
$statuses = ['Pending', 'Bundled', 'Picked Up', 'In Transit', 'Delivered'];
$status_colors = [
    'Pending'   => '#f59e0b',
    'Bundled'   => '#6366f1',
    'Picked Up' => '#3b82f6',
    'In Transit'=> '#0ea5e9',
    'Delivered' => '#10b981',
];
$status_icons = [
    'Pending'   => '⏳',
    'Bundled'   => '📦',
    'Picked Up' => '🚚',
    'In Transit'=> '🛻',
    'Delivered' => '✅',
];
$current_idx = array_search($order->status, $statuses);
if ($current_idx === false) $current_idx = 0;
$color = $status_colors[$order->status] ?? '#94a3b8';
$icon  = $status_icons[$order->status]  ?? '?';

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader($_fb_admin_head, 'Order Details');
?>
<style>
:root {
    --accent:       #4f46e5;
    --accent-light: #e0e7ff;
    --accent-dark:  #3730a3;
    --surface:      #f8fafc;
    --radius:       12px;
    --shadow:       0 4px 12px rgba(0,0,0,0.06);
    --font:         "Segoe UI", Roboto, Arial, sans-serif;
}
/* Hide left nav for subscriber portal */
#id-left, #id-top { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; background: var(--surface) !important; }
.fiche { max-width: 100% !important; margin: 0 !important; }

.fb-wrap { max-width: 1000px; margin: 0 auto; padding: 28px 24px; font-family: var(--font); }

.fb-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.fb-page-header h1 { margin: 0 0 6px; font-size: 22px; font-weight: 800; color: #1e293b; }
.fb-page-header p  { margin: 0; font-size: 13px; color: #94a3b8; }

.btn-ghost { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none !important; border: 1px solid #e2e8f0; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; text-decoration: none !important; }

/* Tracking card */
.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; margin-bottom: 20px; }
.fb-card-head { padding: 14px 22px; border-bottom: 1px solid #f1f5f9; }
.fb-card-head h3 { margin: 0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #94a3b8; }

/* Progress tracker */
.tracker { padding: 28px 24px 24px; }
.track-steps { display: flex; justify-content: space-between; position: relative; }
.track-line-bg { position: absolute; top: 20px; left: 0; right: 0; height: 4px; background: #e2e8f0; z-index: 0; }
.track-line-fill { position: absolute; top: 20px; left: 0; height: 4px; background: var(--accent); z-index: 0; transition: width .5s; }
.track-step { text-align: center; z-index: 1; flex: 1; position: relative; }
.track-dot { width: 40px; height: 40px; margin: 0 auto 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 3px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.15); font-weight: 700; }
.track-dot.done { background: var(--accent); color: #fff; }
.track-dot.active { background: #fff; border: 3px solid var(--accent); color: var(--accent); box-shadow: 0 0 0 4px rgba(79,70,229,.15); }
.track-dot.pending { background: #e2e8f0; color: #94a3b8; }
.track-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .4px; line-height: 1.3; }
.track-label.active { color: var(--accent); font-weight: 800; }

/* Detail grid */
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

/* Data rows */
.data-row { display: flex; justify-content: space-between; align-items: center; padding: 13px 22px; border-bottom: 1px solid #f8fafc; }
.data-row:last-child { border-bottom: none; }
.data-lbl { font-size: 13px; color: #64748b; font-weight: 500; flex-shrink: 0; }
.data-val { font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; max-width: 65%; }

/* Status badge inline */
.s-badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; background: var(--accent-light); color: var(--accent-dark); }

/* Pay badge */
.pay-badge { padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; }
.pay-paid { background: #dcfce7; color: #15803d; }
.pay-pend { background: #fef3c7; color: #92400e; }
.pay-pod  { background: #dbeafe; color: #1e40af; }

/* Order items table */
.items-table { width: 100%; border-collapse: collapse; }
.items-table thead th { text-align: left; padding: 12px 18px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
.items-table tbody td { padding: 13px 18px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #374151; }
.items-table tbody tr:last-child td { border-bottom: none; }
.total-row td { padding: 16px 18px; background: #f8fafc; font-weight: 700; font-size: 15px; color: #1e293b; }
.total-amount { font-size: 20px; color: #16a34a; font-weight: 800; }

.ref-chip { background: var(--accent-light); color: var(--accent-dark); padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
</style>

<div class="fb-wrap">

    <!-- Header -->
    <div class="fb-page-header">
        <div>
            <h1>📦 Order <?php echo dol_escape_htmltag($order->ref); ?></h1>
            <p>Placed on <?php echo dol_print_date($db->jdate($order->datec), 'day'); ?> &nbsp;·&nbsp; <?php echo (int)$order->item_count ?? ''; ?></p>
        </div>
        <a href="my_orders.php" class="btn-ghost">← My Orders</a>
    </div>

    <!-- Progress Tracker -->
    <div class="fb-card">
        <div class="fb-card-head"><h3>Order Tracking</h3></div>
        <div class="tracker">
            <div class="track-steps">
                <div class="track-line-bg"></div>
                <?php
                $fill_pct = $current_idx > 0 ? round(($current_idx / (count($statuses) - 1)) * 100) : 0;
                ?>
                <div class="track-line-fill" style="width:<?php echo $fill_pct; ?>%"></div>
                <?php foreach ($statuses as $idx => $st) :
                    $is_current   = ($st === $order->status);
                    $is_completed = ($idx < $current_idx);
                    $dot_class    = $is_completed ? 'done' : ($is_current ? 'active' : 'pending');
                    $lbl_class    = $is_current ? 'active' : '';
                    $dot_content  = $is_completed ? '✓' : ($status_icons[$st] ?? $idx + 1);
                ?>
                <div class="track-step">
                    <div class="track-dot <?php echo $dot_class; ?>"><?php echo $dot_content; ?></div>
                    <div class="track-label <?php echo $lbl_class; ?>"><?php echo $st; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Detail Grid -->
    <div class="detail-grid">

        <!-- Order Info -->
        <div class="fb-card">
            <div class="fb-card-head"><h3>Order Information</h3></div>
            <div class="data-row"><span class="data-lbl">Reference</span><span class="ref-chip"><?php echo dol_escape_htmltag($order->ref); ?></span></div>
            <div class="data-row">
                <span class="data-lbl">Status</span>
                <span class="s-badge"><?php echo $icon; ?> <?php echo dol_escape_htmltag($order->status); ?></span>
            </div>
            <div class="data-row"><span class="data-lbl">Order Date</span><span class="data-val"><?php echo dol_print_date($db->jdate($order->datec), 'day'); ?></span></div>
            <?php if ($order->scheduled_date) : ?>
            <div class="data-row"><span class="data-lbl">Scheduled Date</span><span class="data-val"><?php echo dol_print_date($db->jdate($order->scheduled_date), 'day'); ?></span></div>
            <?php endif; ?>
            <?php if ($order->payment_method) : ?>
            <div class="data-row"><span class="data-lbl">Payment Method</span><span class="data-val"><?php echo dol_escape_htmltag($order->payment_method); ?></span></div>
            <?php endif; ?>
            <?php if ($order->payment_status) :
                $pc = $order->payment_status == 'Paid' ? 'pay-paid' : ($order->payment_status == 'Pay_On_Delivery' ? 'pay-pod' : 'pay-pend');
            ?>
            <div class="data-row"><span class="data-lbl">Payment Status</span><span class="pay-badge <?php echo $pc; ?>"><?php echo str_replace('_', ' ', $order->payment_status); ?></span></div>
            <?php endif; ?>
        </div>

        <!-- Delivery Info -->
        <div class="fb-card">
            <div class="fb-card-head"><h3>Delivery Information</h3></div>
            <div class="data-row"><span class="data-lbl">Recipient</span><span class="data-val"><?php echo dol_escape_htmltag($beneficiary->firstname.' '.$beneficiary->lastname); ?></span></div>
            <?php if ($order->delivery_address) : ?>
            <div class="data-row" style="align-items:flex-start;">
                <span class="data-lbl">Address</span>
                <span class="data-val"><?php echo nl2br(dol_escape_htmltag($order->delivery_address)); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($order->notes) : ?>
            <div style="padding:14px 22px; border-top:1px solid #f1f5f9;">
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; margin-bottom:6px;">Delivery Notes</div>
                <div style="font-size:13px; color:#374151; background:#f8fafc; padding:10px 14px; border-radius:8px; line-height:1.5;"><?php echo nl2br(dol_escape_htmltag($order->notes)); ?></div>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Order Items -->
    <div class="fb-card">
        <div class="fb-card-head"><h3>Order Items</h3></div>
        <?php
        $sql_items = "SELECT product_name, quantity_distributed, unit, unit_price
                      FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines
                      WHERE fk_distribution = ".(int)$order_id;
        $res_items = $db->query($sql_items);
        if ($res_items && $db->num_rows($res_items) > 0) :
            $subtotal = 0;
        ?>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th style="text-align:center;">Quantity</th>
                    <th style="text-align:right;">Unit Price</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($item = $db->fetch_object($res_items)) :
                $item_total = $item->quantity_distributed * $item->unit_price;
                $subtotal  += $item_total;
            ?>
            <tr>
                <td><strong><?php echo dol_escape_htmltag($item->product_name); ?></strong></td>
                <td style="text-align:center;"><?php echo number_format($item->quantity_distributed, 2); ?> <?php echo dol_escape_htmltag($item->unit); ?></td>
                <td style="text-align:right;">₦<?php echo number_format($item->unit_price, 2); ?></td>
                <td style="text-align:right;"><strong>₦<?php echo number_format($item_total, 2); ?></strong></td>
            </tr>
            <?php endwhile; ?>
            <tr class="total-row">
                <td colspan="3" style="text-align:right;">Order Total:</td>
                <td style="text-align:right;" class="total-amount">₦<?php echo number_format($order->total_amount ?? $subtotal, 2); ?></td>
            </tr>
            </tbody>
        </table>
        <?php else : ?>
        <div style="padding:40px; text-align:center; color:#94a3b8; font-size:14px;">No items found for this order.</div>
        <?php endif; ?>
    </div>

</div>

<?php llxFooter(); ?>
