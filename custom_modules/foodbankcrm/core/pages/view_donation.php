<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

$donation_id = GETPOST('id', 'int');

if (!$donation_id) {
    header('Location: donations.php');
    exit;
}

$user_is_admin  = FoodbankPermissions::isAdmin($user);
$user_is_vendor = FoodbankPermissions::isVendor($user, $db);

if (!$user_is_admin && !$user_is_vendor) {
    accessforbidden('Access denied.');
}

$sql = "SELECT d.*,
        v.name as vendor_name, v.contact_email as vendor_email, v.contact_phone as vendor_phone,
        w.label as warehouse_name, w.address as warehouse_location
        FROM ".MAIN_DB_PREFIX."foodbank_donations d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON d.fk_vendor = v.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_warehouses w ON d.fk_warehouse = w.rowid
        WHERE d.rowid = ".(int)$donation_id;

$res = $db->query($sql);
if (!$res) { header('Location: donations.php'); exit; }

$donation = $db->fetch_object($res);
if (!$donation) { header('Location: donations.php'); exit; }

// Vendors can only view their own donations
if ($user_is_vendor && !$user_is_admin) {
    $sql_check  = "SELECT fk_vendor FROM ".MAIN_DB_PREFIX."foodbank_user_vendor WHERE fk_user = ".(int)$user->id." LIMIT 1";
    $res_check  = $db->query($sql_check);
    $vendor_row = $res_check ? $db->fetch_object($res_check) : null;
    if (!$vendor_row || (int)$vendor_row->fk_vendor != (int)$donation->fk_vendor) {
        accessforbidden('You do not have permission to view this entry.');
    }
}

$status_map = [
    'Pending'  => ['class' => 'badge-pending', 'icon' => '⏳'],
    'Received' => ['class' => 'badge-active',  'icon' => '✅'],
    'Allocated'=> ['class' => 'badge-blue',    'icon' => '📦'],
    'Rejected' => ['class' => 'badge-expired', 'icon' => '✕'],
];
$sm        = $status_map[$donation->status] ?? ['class' => 'badge-default', 'icon' => '?'];
$back_link = $user_is_admin ? 'donations.php' : 'my_donations.php';
$available = $donation->quantity - $donation->quantity_allocated;

$_SESSION["mainmenu"] = "foodbankcrm";
$_fb_admin_head = '<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">'
              . '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/css/admin_mobile.css">';
llxHeader($_fb_admin_head, 'Inventory Log Details');
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
#id-top { display: none !important; }
.side-nav, .side-nav-vert { top: 0 !important; height: 100vh !important; }
#id-right { padding-top: 30px !important; background: var(--surface) !important; min-height: 100vh; }
.fiche { max-width: 100% !important; margin: 0 !important; }

.fb-wrap { max-width: 1100px; margin: 0 auto; padding: 24px 28px; font-family: var(--font); }

.fb-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.fb-page-header h1 { margin: 0 0 6px; font-size: 22px; font-weight: 800; color: #1e293b; }
.fb-page-header p  { margin: 0; font-size: 13px; color: #94a3b8; }

.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none !important; border: none; cursor: pointer; transition: background .2s; }
.btn-primary:hover { background: var(--accent-dark); text-decoration: none !important; }
.btn-ghost  { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none !important; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; text-decoration: none !important; }

.meta-strip { display: flex; gap: 20px; flex-wrap: wrap; background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); padding: 14px 22px; margin-bottom: 22px; align-items: center; }
.meta-item { font-size: 13px; color: #64748b; }
.meta-item strong { color: #1e293b; font-weight: 700; }
.meta-sep { color: #e2e8f0; }

.detail-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }

.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; margin-bottom: 20px; }
.fb-card-head { padding: 14px 22px; border-bottom: 1px solid #f1f5f9; }
.fb-card-head h3 { margin: 0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #94a3b8; }

.product-hero { padding: 22px 22px; border-bottom: 1px solid #f1f5f9; }
.product-name { font-size: 22px; font-weight: 800; color: #1e293b; margin: 0 0 6px; }
.product-qty  { font-size: 28px; font-weight: 800; color: var(--accent); line-height: 1.1; }
.product-unit { font-size: 15px; color: #94a3b8; font-weight: 500; }
.product-value { font-size: 13px; color: #64748b; margin-top: 6px; }

.data-row { display: flex; justify-content: space-between; align-items: center; padding: 13px 22px; border-bottom: 1px solid #f8fafc; }
.data-row:last-child { border-bottom: none; }
.data-lbl { font-size: 13px; color: #64748b; font-weight: 500; flex-shrink: 0; }
.data-val { font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; }

.badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
.badge-active  { background: #dcfce7; color: #15803d; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-expired { background: #fee2e2; color: #991b1b; }
.badge-blue    { background: #dbeafe; color: #1e40af; }
.badge-default { background: #f1f5f9; color: #475569; }

.ref-chip { background: var(--accent-light); color: var(--accent-dark); padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }

.status-display { text-align: center; padding: 24px 22px; border-bottom: 1px solid #f1f5f9; }
.status-icon  { font-size: 36px; display: block; margin-bottom: 10px; }
.status-label { font-size: 16px; font-weight: 800; color: #1e293b; }
.status-date  { font-size: 12px; color: #94a3b8; margin-top: 4px; }

.btn-approve { display: flex; align-items: center; justify-content: center; gap: 6px; background: #16a34a; color: #fff !important; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none !important; margin-bottom: 8px; }
.btn-approve:hover { background: #15803d; text-decoration: none !important; }
.btn-reject  { display: flex; align-items: center; justify-content: center; gap: 6px; background: #fff; color: #dc2626 !important; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none !important; border: 1px solid #fca5a5; }
.btn-reject:hover  { background: #fef2f2; text-decoration: none !important; }
</style>

<div class="fb-wrap">

    <div class="fb-page-header">
        <div>
            <h1>📦 <?php echo dol_escape_htmltag($donation->product_name ?: $donation->label); ?></h1>
            <p>Ref: <span class="ref-chip"><?php echo dol_escape_htmltag($donation->ref); ?></span> &nbsp;·&nbsp; Logged on <?php echo dol_print_date($db->jdate($donation->datec), 'dayhour'); ?></p>
        </div>
        <div style="flex-shrink:0; margin-top:4px;">
            <a href="<?php echo $back_link; ?>" class="btn-ghost">← Back to List</a>
            <?php if ($user_is_admin) : ?>
            <a href="edit_donation.php?id=<?php echo $donation_id; ?>" class="btn-primary">✏️ Edit</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="meta-strip">
        <div class="meta-item">Category: <strong><?php echo $donation->category ? dol_escape_htmltag($donation->category) : '—'; ?></strong></div>
        <span class="meta-sep">·</span>
        <div class="meta-item">Total: <strong><?php echo number_format($donation->quantity); ?> <?php echo dol_escape_htmltag($donation->unit); ?></strong></div>
        <span class="meta-sep">·</span>
        <div class="meta-item">Available: <strong><?php echo number_format($available); ?> <?php echo dol_escape_htmltag($donation->unit); ?></strong></div>
        <span class="meta-sep">·</span>
        <div class="meta-item">Status: <span class="badge <?php echo $sm['class']; ?>"><?php echo $sm['icon'].' '.$donation->status; ?></span></div>
    </div>

    <div class="detail-grid">

        <!-- LEFT -->
        <div>
            <div class="fb-card">
                <div class="fb-card-head"><h3>Product Information</h3></div>
                <div class="product-hero">
                    <div class="product-name"><?php echo dol_escape_htmltag($donation->product_name ?: $donation->label); ?></div>
                    <div class="product-qty"><?php echo number_format($donation->quantity); ?> <span class="product-unit"><?php echo dol_escape_htmltag($donation->unit); ?></span></div>
                    <?php if ($donation->unit_price > 0) : ?>
                    <div class="product-value">Unit price: <strong>₦<?php echo number_format($donation->unit_price, 2); ?></strong> &nbsp;·&nbsp; Total value: <strong>₦<?php echo number_format($donation->quantity * $donation->unit_price, 2); ?></strong></div>
                    <?php endif; ?>
                </div>
                <div class="data-row"><span class="data-lbl">Category</span><span class="data-val"><?php echo $donation->category ? dol_escape_htmltag($donation->category) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
                <div class="data-row"><span class="data-lbl">Allocated</span><span class="data-val"><?php echo number_format($donation->quantity_allocated); ?> <?php echo dol_escape_htmltag($donation->unit); ?></span></div>
                <div class="data-row"><span class="data-lbl">Still Available</span><span class="data-val" style="color:#16a34a; font-weight:800;"><?php echo number_format($available); ?> <?php echo dol_escape_htmltag($donation->unit); ?></span></div>
                <?php if ($donation->description) : ?>
                <div style="padding:14px 22px; border-top:1px solid #f1f5f9; font-size:13px; color:#374151; line-height:1.6;">
                    <?php echo nl2br(dol_escape_htmltag($donation->description)); ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="fb-card">
                <div class="fb-card-head"><h3>Vendor / Supplier</h3></div>
                <div class="data-row"><span class="data-lbl">Vendor</span><span class="data-val"><?php echo $donation->vendor_name ? dol_escape_htmltag($donation->vendor_name) : '<span style="color:#cbd5e1">Unknown</span>'; ?></span></div>
                <?php if ($donation->vendor_phone) : ?>
                <div class="data-row"><span class="data-lbl">Phone</span><span class="data-val"><?php echo dol_escape_htmltag($donation->vendor_phone); ?></span></div>
                <?php endif; ?>
                <?php if ($donation->vendor_email) : ?>
                <div class="data-row"><span class="data-lbl">Email</span><span class="data-val"><a href="mailto:<?php echo dol_escape_htmltag($donation->vendor_email); ?>" style="color:var(--accent);"><?php echo dol_escape_htmltag($donation->vendor_email); ?></a></span></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT -->
        <div>
            <div class="fb-card">
                <div class="fb-card-head"><h3>Status</h3></div>
                <div class="status-display">
                    <span class="status-icon"><?php echo $sm['icon']; ?></span>
                    <div class="status-label"><?php echo dol_escape_htmltag($donation->status); ?></div>
                    <div class="status-date">Logged <?php echo dol_print_date($db->jdate($donation->datec), 'dayhour'); ?></div>
                </div>
                <div class="data-row"><span class="data-lbl">Reference</span><span class="ref-chip"><?php echo dol_escape_htmltag($donation->ref); ?></span></div>
                <?php if ($donation->delivery_method) : ?>
                <div class="data-row"><span class="data-lbl">Delivery</span><span class="data-val"><?php echo dol_escape_htmltag($donation->delivery_method); ?></span></div>
                <?php endif; ?>
            </div>

            <div class="fb-card">
                <div class="fb-card-head"><h3>Storage Location</h3></div>
                <?php if ($donation->warehouse_name) : ?>
                <div class="data-row"><span class="data-lbl">Warehouse</span><span class="data-val"><?php echo dol_escape_htmltag($donation->warehouse_name); ?></span></div>
                <?php if ($donation->warehouse_location) : ?>
                <div class="data-row"><span class="data-lbl">Address</span><span class="data-val"><?php echo dol_escape_htmltag($donation->warehouse_location); ?></span></div>
                <?php endif; ?>
                <?php else : ?>
                <div style="padding:24px; text-align:center; color:#94a3b8; font-size:13px;">Not assigned to a warehouse</div>
                <?php endif; ?>
            </div>

            <?php if ($user_is_admin && $donation->status == 'Pending') : ?>
            <div class="fb-card">
                <div class="fb-card-head"><h3>Admin Actions</h3></div>
                <div style="padding:16px 22px;">
                    <a href="approve_donation.php?id=<?php echo $donation_id; ?>" class="btn-approve"
                       onclick="return confirm('Mark this donation as received?')">✅ Approve & Receive</a>
                    <a href="reject_donation.php?id=<?php echo $donation_id; ?>" class="btn-reject"
                       onclick="return confirm('Reject this donation?')">✕ Reject</a>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php llxFooter(); ?>
