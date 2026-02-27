<?php
/**
 * ADMIN DASHBOARD - FoodbankCRM
 */
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;
$langs->load("admin");

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Access Denied: Administrative privileges required.');
}

// ── Data ──────────────────────────────────────────────────────────────────────
$revenue      = (float)($db->fetch_object($db->query("SELECT COALESCE(SUM(amount),0) as t FROM ".MAIN_DB_PREFIX."foodbank_payments WHERE payment_status='Success'"))->t ?? 0);
$subs_active  = (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE subscription_status='Active'"))->t ?? 0);
$subs_total   = (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries"))->t ?? 0);
$pending_inv  = (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_donations WHERE status='Pending'"))->t ?? 0);
$vendors_active = (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE status='Active'"))->t ?? 0);
$vendors_pending= (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE status='Pending'"))->t ?? 0);

$open_tickets = 0;
if ($db->num_rows($db->query("SHOW TABLES LIKE '".MAIN_DB_PREFIX."foodbank_support'")) > 0) {
    $open_tickets = (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_support WHERE status='Open'"))->t ?? 0);
}

$orders_total   = (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_distributions"))->t ?? 0);
$orders_pending = (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE status='Pending'"))->t ?? 0);

// Pending inventory rows
$sql_inv = "SELECT d.rowid, d.ref, d.product_name, d.quantity, d.unit,
                   v.name AS vendor_name, d.date_donation
            FROM ".MAIN_DB_PREFIX."foodbank_donations d
            LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON d.fk_vendor = v.rowid
            WHERE d.status = 'Pending'
            ORDER BY d.date_donation ASC LIMIT 6";
$res_inv = $db->query($sql_inv);

// Pending vendor approvals
$sql_vend = "SELECT rowid, name, category, date_creation FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE status='Pending' ORDER BY date_creation ASC LIMIT 4";
$res_vend = $db->query($sql_vend);

// Recent distributions
$sql_dist = "SELECT d.rowid, d.ref, d.status, d.datec,
                    b.firstname, b.lastname
             FROM ".MAIN_DB_PREFIX."foodbank_distributions d
             INNER JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON d.fk_beneficiary = b.rowid
             ORDER BY d.datec DESC LIMIT 5";
$res_dist = $db->query($sql_dist);

$admin_name = trim($user->firstname.' '.$user->lastname) ?: $user->login;
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

llxHeader('', 'Admin Dashboard');
?>
<style>
:root {
    --accent:        #4f46e5;
    --accent-light:  #e0e7ff;
    --accent-dark:   #3730a3;
    --surface:       #f1f5f9;
    --radius:        14px;
    --radius-sm:     8px;
    --shadow:        0 2px 8px rgba(0,0,0,0.07);
    --shadow-md:     0 6px 20px rgba(0,0,0,0.10);
    --font:          "Segoe UI", Roboto, Arial, sans-serif;
}

#id-top { display: none !important; }
.side-nav, .side-nav-vert { top: 0 !important; height: 100vh !important; }
#id-right { padding-top: 0 !important; background: var(--surface) !important; min-height: 100vh; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }

/* ── Wrapper ── */
.db-wrap { max-width: 1440px; margin: 0 auto; padding: 0 0 40px; font-family: var(--font); }

/* ── Hero Banner ── */
.db-hero {
    background: linear-gradient(135deg, #3730a3 0%, #4f46e5 45%, #7c3aed 100%);
    padding: 36px 40px 100px;
    position: relative;
    overflow: hidden;
}
.db-hero::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 320px; height: 320px;
    background: rgba(255,255,255,.06);
    border-radius: 50%;
}
.db-hero::after {
    content: '';
    position: absolute;
    bottom: -80px; left: 30%;
    width: 260px; height: 260px;
    background: rgba(255,255,255,.04);
    border-radius: 50%;
}
.db-hero-inner { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: flex-start; }
.db-hero h1 { margin: 0; font-size: 27px; font-weight: 800; color: #fff; letter-spacing: -.3px; }
.db-hero p  { margin: 6px 0 0; color: rgba(255,255,255,.75); font-size: 14px; }
.db-hero-date { text-align: right; color: rgba(255,255,255,.65); font-size: 13px; }
.db-hero-date strong { display: block; color: #fff; font-size: 16px; font-weight: 700; }

.db-logout {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,.15); color: #fff !important;
    border: 1px solid rgba(255,255,255,.3);
    padding: 9px 18px; border-radius: 30px;
    font-weight: 600; font-size: 13px;
    text-decoration: none; backdrop-filter: blur(4px);
    transition: background .2s;
}
.db-logout:hover { background: rgba(255,255,255,.25); }

/* ── Stats (pulled up over hero) ── */
.db-stats-outer { padding: 0 32px; margin-top: -68px; position: relative; z-index: 10; margin-bottom: 32px; }
.db-stats-grid  { display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; }
@media (max-width: 1200px) { .db-stats-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 700px)  { .db-stats-grid { grid-template-columns: repeat(2, 1fr); } }

.db-stat {
    background: #fff;
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    padding: 20px 20px 18px;
    text-decoration: none;
    display: block;
    transition: transform .2s, box-shadow .2s;
    border-bottom: 3px solid transparent;
}
.db-stat:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(0,0,0,.12); }
.db-stat-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; margin-bottom: 14px;
}
.db-stat-val  { font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1; }
.db-stat-lbl  { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: .6px; margin-top: 5px; font-weight: 600; }
.db-stat-sub  { font-size: 11px; margin-top: 6px; font-weight: 500; }

.s-green  { border-color: #10b981; } .s-green  .db-stat-icon { background: #d1fae5; }
.s-blue   { border-color: #3b82f6; } .s-blue   .db-stat-icon { background: #dbeafe; }
.s-indigo { border-color: #4f46e5; } .s-indigo .db-stat-icon { background: #e0e7ff; }
.s-amber  { border-color: #f59e0b; } .s-amber  .db-stat-icon { background: #fef3c7; }
.s-red    { border-color: #ef4444; } .s-red    .db-stat-icon { background: #fee2e2; }
.s-purple { border-color: #8b5cf6; } .s-purple .db-stat-icon { background: #ede9fe; }

/* ── Content area ── */
.db-content { padding: 0 32px; display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
@media (max-width: 1100px) { .db-content { grid-template-columns: 1fr; } }

/* ── Cards ── */
.db-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; margin-bottom: 24px; }
.db-card-head {
    padding: 16px 22px; border-bottom: 1px solid #f1f5f9;
    display: flex; justify-content: space-between; align-items: center;
}
.db-card-head h3 { margin: 0; font-size: 14px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px; }
.db-card-head a  { font-size: 12px; font-weight: 600; color: var(--accent); text-decoration: none; }
.db-card-head a:hover { text-decoration: underline; }
.db-card-foot { padding: 12px 22px; border-top: 1px solid #f1f5f9; text-align: center; }
.db-card-foot a { color: var(--accent); font-size: 13px; font-weight: 600; text-decoration: none; }

/* ── Table ── */
.db-table { width: 100%; border-collapse: collapse; }
.db-table th { padding: 11px 20px; background: #f8fafc; color: #64748b; font-size: 10px; text-transform: uppercase; letter-spacing: .7px; border-bottom: 1px solid #e2e8f0; font-weight: 700; text-align: left; }
.db-table td { padding: 12px 20px; border-bottom: 1px solid #f8fafc; font-size: 13px; color: #334155; }
.db-table tr:last-child td { border-bottom: none; }
.db-table tr:hover td { background: #f8fafc; }

/* Chips */
.chip { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.chip-indigo { background: #e0e7ff; color: #3730a3; }
.chip-amber  { background: #fef3c7; color: #92400e; }
.chip-green  { background: #dcfce7; color: #15803d; }
.chip-blue   { background: #dbeafe; color: #1e40af; }
.chip-slate  { background: #f1f5f9; color: #475569; }
.chip-red    { background: #fee2e2; color: #991b1b; }

.review-btn {
    background: var(--accent-light); color: var(--accent) !important;
    padding: 4px 12px; border-radius: 20px;
    font-size: 11px; font-weight: 700; text-decoration: none;
}

/* ── Quick tools grid ── */
.db-tools-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 16px; }
.db-tool {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    text-align: center; padding: 18px 12px; border-radius: 12px;
    text-decoration: none; transition: transform .18s, box-shadow .18s;
    border: 1.5px solid transparent;
}
.db-tool:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.1); }
.db-tool-ico { font-size: 26px; margin-bottom: 8px; }
.db-tool-ttl { font-size: 12px; font-weight: 700; color: #1e293b; }
.db-tool-dsc { font-size: 10px; color: #94a3b8; margin-top: 2px; line-height: 1.3; }

.t-indigo { background: #eef2ff; border-color: #c7d2fe; } .t-indigo:hover { background: #e0e7ff; }
.t-green  { background: #f0fdf4; border-color: #bbf7d0; } .t-green:hover  { background: #dcfce7; }
.t-amber  { background: #fffbeb; border-color: #fde68a; } .t-amber:hover  { background: #fef3c7; }
.t-blue   { background: #eff6ff; border-color: #bfdbfe; } .t-blue:hover   { background: #dbeafe; }
.t-purple { background: #faf5ff; border-color: #e9d5ff; } .t-purple:hover { background: #ede9fe; }
.t-red    { background: #fff1f2; border-color: #fecdd3; } .t-red:hover    { background: #fee2e2; }

/* Alert dot */
.alert-dot { display: inline-block; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; margin-left: 6px; animation: pulse 1.6s infinite; }
@keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:.4; } }

/* Empty state */
.db-empty { padding: 40px; text-align: center; color: #94a3b8; }
.db-empty div { font-size: 32px; margin-bottom: 8px; }
.db-empty p   { margin: 0; font-size: 13px; }
</style>

<div class="db-wrap">

    <!-- Hero -->
    <div class="db-hero">
        <div class="db-hero-inner">
            <div>
                <h1><?php echo $greeting.', '.dol_escape_htmltag($admin_name); ?> 👋</h1>
                <p>FoodbankCRM &mdash; Admin Control Room</p>
            </div>
            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:14px;">
                <a href="<?php echo DOL_URL_ROOT; ?>/user/logout.php" class="db-logout">🚪 Logout</a>
                <div class="db-hero-date">
                    <strong><?php echo date('l, F j'); ?></strong>
                    <?php echo date('Y'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Strip -->
    <div class="db-stats-outer">
        <div class="db-stats-grid">

            <div class="db-stat s-green">
                <div class="db-stat-icon">💰</div>
                <div class="db-stat-val">₦<?php echo number_format($revenue, 0); ?></div>
                <div class="db-stat-lbl">Total Revenue</div>
                <div class="db-stat-sub" style="color:#10b981;">Verified payments</div>
            </div>

            <div class="db-stat s-blue">
                <div class="db-stat-icon">👥</div>
                <div class="db-stat-val"><?php echo $subs_active; ?></div>
                <div class="db-stat-lbl">Active Subscribers</div>
                <div class="db-stat-sub" style="color:#64748b;"><?php echo $subs_total; ?> total registered</div>
            </div>

            <a href="donations.php?filter_status=Pending" class="db-stat s-amber">
                <div class="db-stat-icon">📦</div>
                <div class="db-stat-val"><?php echo $pending_inv; ?><?php if ($pending_inv > 0) echo '<span class="alert-dot"></span>'; ?></div>
                <div class="db-stat-lbl">Pending Stock</div>
                <div class="db-stat-sub" style="color:#f59e0b;">Needs approval →</div>
            </a>

            <a href="vendor_support.php" class="db-stat s-red">
                <div class="db-stat-icon">🎫</div>
                <div class="db-stat-val"><?php echo $open_tickets; ?><?php if ($open_tickets > 0) echo '<span class="alert-dot"></span>'; ?></div>
                <div class="db-stat-lbl">Open Tickets</div>
                <div class="db-stat-sub" style="color:#ef4444;">Needs reply →</div>
            </a>

            <a href="vendors.php" class="db-stat s-indigo">
                <div class="db-stat-icon">🏢</div>
                <div class="db-stat-val"><?php echo $vendors_active; ?></div>
                <div class="db-stat-lbl">Active Vendors</div>
                <div class="db-stat-sub" style="color:<?php echo $vendors_pending > 0 ? '#f59e0b' : '#64748b'; ?>;">
                    <?php echo $vendors_pending > 0 ? $vendors_pending.' awaiting approval' : 'All approved'; ?>
                </div>
            </a>

            <a href="admin_orders.php" class="db-stat s-purple">
                <div class="db-stat-icon">🚚</div>
                <div class="db-stat-val"><?php echo $orders_total; ?></div>
                <div class="db-stat-lbl">Distributions</div>
                <div class="db-stat-sub" style="color:#8b5cf6;"><?php echo $orders_pending; ?> pending dispatch</div>
            </a>

        </div>
    </div>

    <!-- Main Content -->
    <div class="db-content">

        <!-- Left Column -->
        <div>

            <!-- Pending Inventory -->
            <div class="db-card">
                <div class="db-card-head">
                    <h3>📋 Pending Inventory Approvals<?php if ($pending_inv > 0) echo '<span class="alert-dot"></span>'; ?></h3>
                    <a href="donations.php">View all →</a>
                </div>
                <?php if ($res_inv && $db->num_rows($res_inv) > 0) : ?>
                <table class="db-table">
                    <thead>
                        <tr><th>Date</th><th>Ref</th><th>Vendor</th><th>Product</th><th>Qty</th><th style="text-align:right">Action</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $db->fetch_object($res_inv)) : ?>
                        <tr>
                            <td style="color:#94a3b8; white-space:nowrap;"><?php echo dol_print_date($db->jdate($row->date_donation), 'day'); ?></td>
                            <td><span class="chip chip-indigo"><?php echo dol_escape_htmltag($row->ref); ?></span></td>
                            <td><?php echo dol_escape_htmltag($row->vendor_name ?: '—'); ?></td>
                            <td><strong><?php echo dol_escape_htmltag($row->product_name); ?></strong></td>
                            <td><?php echo number_format($row->quantity).' '.dol_escape_htmltag($row->unit); ?></td>
                            <td style="text-align:right"><a href="view_donation.php?id=<?php echo $row->rowid; ?>" class="review-btn">Review</a></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="db-card-foot"><a href="donations.php?filter_status=Pending">View all pending inventory →</a></div>
                <?php else : ?>
                <div class="db-empty"><div>✅</div><p>All caught up — no inventory pending approval.</p></div>
                <?php endif; ?>
            </div>

            <!-- Vendors Awaiting Approval -->
            <?php if ($res_vend && $db->num_rows($res_vend) > 0) : ?>
            <div class="db-card">
                <div class="db-card-head">
                    <h3>🏢 Vendors Awaiting Approval<span class="alert-dot"></span></h3>
                    <a href="vendors.php">View all →</a>
                </div>
                <table class="db-table">
                    <thead>
                        <tr><th>Vendor</th><th>Category</th><th>Registered</th><th style="text-align:right;">Action</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($v = $db->fetch_object($res_vend)) : ?>
                        <tr>
                            <td><strong><?php echo dol_escape_htmltag($v->name); ?></strong></td>
                            <td><?php echo $v->category ? '<span class="chip chip-blue">'.dol_escape_htmltag($v->category).'</span>' : '—'; ?></td>
                            <td style="color:#94a3b8;"><?php echo dol_print_date($db->jdate($v->date_creation), 'day'); ?></td>
                            <td style="text-align:right;"><a href="view_vendor.php?id=<?php echo $v->rowid; ?>" class="review-btn">Approve</a></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Recent Distributions -->
            <div class="db-card">
                <div class="db-card-head">
                    <h3>🚚 Recent Distributions</h3>
                    <a href="admin_orders.php">View all →</a>
                </div>
                <?php
                $dist_status_chip = [
                    'Pending'    => 'chip-amber',
                    'Bundled'    => 'chip-blue',
                    'Picked Up'  => 'chip-blue',
                    'In Transit' => 'chip-amber',
                    'Delivered'  => 'chip-green',
                ];
                if ($res_dist && $db->num_rows($res_dist) > 0) :
                ?>
                <table class="db-table">
                    <thead>
                        <tr><th>Ref</th><th>Subscriber</th><th>Date</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($d = $db->fetch_object($res_dist)) :
                        $chip = $dist_status_chip[$d->status] ?? 'chip-slate';
                    ?>
                        <tr>
                            <td><span class="chip chip-indigo"><?php echo dol_escape_htmltag($d->ref); ?></span></td>
                            <td><?php echo dol_escape_htmltag($d->firstname.' '.$d->lastname); ?></td>
                            <td style="color:#94a3b8;"><?php echo dol_print_date($db->jdate($d->datec), 'day'); ?></td>
                            <td><span class="chip <?php echo $chip; ?>"><?php echo dol_escape_htmltag($d->status); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="db-card-foot"><a href="admin_orders.php">View all distributions →</a></div>
                <?php else : ?>
                <div class="db-empty"><div>📭</div><p>No distributions yet.</p></div>
                <?php endif; ?>
            </div>

        </div><!-- end left column -->

        <!-- Right Column -->
        <div>

            <!-- Quick Tools -->
            <div class="db-card" style="margin-bottom:24px;">
                <div class="db-card-head"><h3>⚡ Quick Actions</h3></div>
                <div class="db-tools-grid">
                    <a href="beneficiaries.php"          class="db-tool t-blue">
                        <div class="db-tool-ico">👥</div>
                        <div class="db-tool-ttl">Subscribers</div>
                        <div class="db-tool-dsc">View & manage</div>
                    </a>
                    <a href="vendors.php"                class="db-tool t-indigo">
                        <div class="db-tool-ico">🏢</div>
                        <div class="db-tool-ttl">Vendors</div>
                        <div class="db-tool-dsc">Approve & manage</div>
                    </a>
                    <a href="create_donation.php"        class="db-tool t-amber">
                        <div class="db-tool-ico">📦</div>
                        <div class="db-tool-ttl">Log Stock</div>
                        <div class="db-tool-dsc">Record inventory</div>
                    </a>
                    <a href="packages.php"               class="db-tool t-purple">
                        <div class="db-tool-ico">🎁</div>
                        <div class="db-tool-ttl">Packages</div>
                        <div class="db-tool-dsc">Food box templates</div>
                    </a>
                    <a href="subscription_tiers.php"     class="db-tool t-green">
                        <div class="db-tool-ico">💳</div>
                        <div class="db-tool-ttl">Plans</div>
                        <div class="db-tool-dsc">Pricing & tiers</div>
                    </a>
                    <a href="vendor_support.php"         class="db-tool t-red">
                        <div class="db-tool-ico">🛡️</div>
                        <div class="db-tool-ttl">Helpdesk</div>
                        <div class="db-tool-dsc"><?php echo $open_tickets; ?> open ticket<?php echo $open_tickets != 1 ? 's' : ''; ?></div>
                    </a>
                </div>
            </div>

            <!-- System Summary -->
            <div class="db-card">
                <div class="db-card-head"><h3>📊 System Summary</h3></div>
                <div style="padding: 6px 0;">
                <?php
                $summary_rows = [
                    ['Warehouses',         (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_warehouses"))->t ?? 0),           'warehouses.php',           'chip-slate'],
                    ['Active Packages',    (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_packages WHERE status='Active'"))->t ?? 0), 'packages.php', 'chip-purple'],
                    ['Subscription Tiers', (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE is_active=1"))->t ?? 0), 'subscription_tiers.php', 'chip-green'],
                    ['Inventory Entries',  (int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_donations"))->t ?? 0),              'donations.php',            'chip-amber'],
                    ['Expired Subscribers',(int)($db->fetch_object($db->query("SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE subscription_status='Expired'"))->t ?? 0), 'beneficiaries.php', 'chip-red'],
                ];
                foreach ($summary_rows as $sr) :
                ?>
                    <a href="<?php echo $sr[2]; ?>" style="display:flex; justify-content:space-between; align-items:center; padding:12px 22px; border-bottom:1px solid #f8fafc; text-decoration:none; transition:background .12s;">
                        <span style="font-size:13px; color:#334155; font-weight:500;"><?php echo $sr[0]; ?></span>
                        <span class="chip <?php echo $sr[3]; ?>"><?php echo $sr[1]; ?></span>
                    </a>
                <?php endforeach; ?>
                </div>
            </div>

        </div><!-- end right column -->

    </div><!-- end db-content -->

</div><!-- end db-wrap -->

<?php
llxFooter();
?>
