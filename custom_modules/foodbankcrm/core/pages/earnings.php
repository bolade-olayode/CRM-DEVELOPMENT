<?php
/**
 * ADMIN EARNINGS — FoodbankCRM
 * Breakdown of subscription revenue vs. package/order revenue.
 */
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;
$langs->load("admin");

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Access Denied: Administrative privileges required.');
}

$_SESSION["mainmenu"] = "foodbankcrm";

// ── Summary totals ─────────────────────────────────────────────────────────────
$sub_revenue = (float)($db->fetch_object($db->query(
    "SELECT COALESCE(SUM(amount),0) as t FROM ".MAIN_DB_PREFIX."foodbank_payments
     WHERE payment_status='Success' AND payment_type='Subscription'"
))->t ?? 0);

$order_revenue = (float)($db->fetch_object($db->query(
    "SELECT COALESCE(SUM(total_amount),0) as t FROM ".MAIN_DB_PREFIX."foodbank_distributions
     WHERE payment_status='Paid'"
))->t ?? 0);

$total_revenue = $sub_revenue + $order_revenue;

// This month
$sub_month = (float)($db->fetch_object($db->query(
    "SELECT COALESCE(SUM(amount),0) as t FROM ".MAIN_DB_PREFIX."foodbank_payments
     WHERE payment_status='Success' AND payment_type='Subscription'
       AND YEAR(payment_date)=YEAR(CURDATE()) AND MONTH(payment_date)=MONTH(CURDATE())"
))->t ?? 0);
$order_month = (float)($db->fetch_object($db->query(
    "SELECT COALESCE(SUM(total_amount),0) as t FROM ".MAIN_DB_PREFIX."foodbank_distributions
     WHERE payment_status='Paid'
       AND YEAR(datec)=YEAR(CURDATE()) AND MONTH(datec)=MONTH(CURDATE())"
))->t ?? 0);
$total_month = $sub_month + $order_month;

// Sub count / order count
$sub_count   = (int)($db->fetch_object($db->query(
    "SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_payments
     WHERE payment_status='Success' AND payment_type='Subscription'"
))->t ?? 0);
$order_count = (int)($db->fetch_object($db->query(
    "SELECT COUNT(*) as t FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE payment_status='Paid'"
))->t ?? 0);

// ── Monthly chart data — last 12 months ───────────────────────────────────────
$months_labels = [];
$chart_sub     = [];
$chart_orders  = [];

for ($i = 11; $i >= 0; $i--) {
    $y = date('Y', strtotime("-$i months"));
    $m = date('m', strtotime("-$i months"));
    $months_labels[] = date('M Y', strtotime("-$i months"));

    $s = (float)($db->fetch_object($db->query(
        "SELECT COALESCE(SUM(amount),0) as t FROM ".MAIN_DB_PREFIX."foodbank_payments
         WHERE payment_status='Success' AND payment_type='Subscription'
           AND YEAR(payment_date)=$y AND MONTH(payment_date)=$m"
    ))->t ?? 0);

    $o = (float)($db->fetch_object($db->query(
        "SELECT COALESCE(SUM(total_amount),0) as t FROM ".MAIN_DB_PREFIX."foodbank_distributions
         WHERE payment_status='Paid' AND YEAR(datec)=$y AND MONTH(datec)=$m"
    ))->t ?? 0);

    $chart_sub[]    = $s;
    $chart_orders[] = $o;
}

// ── Revenue by subscription tier ──────────────────────────────────────────────
$sql_tiers = "SELECT b.subscription_type AS tier,
                     COUNT(p.rowid)        AS subs_sold,
                     COALESCE(SUM(p.amount),0) AS tier_revenue
              FROM ".MAIN_DB_PREFIX."foodbank_payments p
              LEFT JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON b.rowid = p.fk_subscriber
              WHERE p.payment_status='Success' AND p.payment_type='Subscription'
              GROUP BY b.subscription_type
              ORDER BY tier_revenue DESC";
$res_tiers = $db->query($sql_tiers);

// ── Revenue by package ─────────────────────────────────────────────────────────
$sql_pkgs = "SELECT dl.note AS package_name,
                    SUM(d.total_amount) AS revenue,
                    COUNT(DISTINCT d.rowid) AS orders
             FROM ".MAIN_DB_PREFIX."foodbank_distributions d
             INNER JOIN ".MAIN_DB_PREFIX."foodbank_distribution_lines dl ON dl.fk_distribution = d.rowid
             WHERE d.payment_status='Paid'
             GROUP BY dl.note
             ORDER BY revenue DESC
             LIMIT 8";
$res_pkgs = $db->query($sql_pkgs);

// ── Recent transactions ────────────────────────────────────────────────────────
$sql_recent_sub = "SELECT p.rowid, p.amount, p.payment_date, p.payment_reference,
                          b.firstname, b.lastname, b.subscription_type AS tier
                   FROM ".MAIN_DB_PREFIX."foodbank_payments p
                   LEFT JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON b.rowid = p.fk_subscriber
                   WHERE p.payment_status='Success' AND p.payment_type='Subscription'
                   ORDER BY p.payment_date DESC LIMIT 10";
$res_recent_sub = $db->query($sql_recent_sub);

$sql_recent_ord = "SELECT d.rowid, d.ref, d.total_amount, d.datec,
                          b.firstname, b.lastname, d.payment_reference
                   FROM ".MAIN_DB_PREFIX."foodbank_distributions d
                   LEFT JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON b.rowid = d.fk_beneficiary
                   WHERE d.payment_status='Paid'
                   ORDER BY d.datec DESC LIMIT 10";
$res_recent_ord = $db->query($sql_recent_ord);

llxHeader('', 'Earnings — FoodbankCRM Admin');
?>
<style>
:root {
    --accent: #4f46e5; --accent-light: #e0e7ff;
    --green:  #10b981; --green-light:  #d1fae5;
    --blue:   #3b82f6; --blue-light:   #dbeafe;
    --amber:  #f59e0b; --amber-light:  #fef3c7;
    --purple: #8b5cf6; --purple-light: #ede9fe;
    --surface: #f1f5f9; --radius: 14px;
    --shadow: 0 2px 8px rgba(0,0,0,.07);
    --shadow-md: 0 6px 20px rgba(0,0,0,.10);
    --font: "Segoe UI", Roboto, Arial, sans-serif;
}
#id-top { display: none !important; }
.side-nav, .side-nav-vert { top: 0 !important; height: 100vh !important; }
#id-right { padding-top: 0 !important; background: var(--surface) !important; min-height: 100vh; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }

.er-wrap { max-width: 1440px; margin: 0 auto; padding-bottom: 48px; font-family: var(--font); }

/* Hero */
.er-hero {
    background: linear-gradient(135deg, #064e3b 0%, #059669 55%, #34d399 100%);
    padding: 36px 40px 100px; position: relative; overflow: hidden;
}
.er-hero::before { content:''; position:absolute; top:-60px; right:-60px; width:320px; height:320px; background:rgba(255,255,255,.06); border-radius:50%; }
.er-hero::after  { content:''; position:absolute; bottom:-80px; left:30%; width:260px; height:260px; background:rgba(255,255,255,.04); border-radius:50%; }
.er-hero-inner { position:relative; z-index:1; display:flex; justify-content:space-between; align-items:flex-start; }
.er-hero h1 { margin:0; font-size:26px; font-weight:800; color:#fff; letter-spacing:-.3px; }
.er-hero p  { margin:6px 0 0; color:rgba(255,255,255,.75); font-size:14px; }
.er-back { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.15); color:#fff !important;
           border:1px solid rgba(255,255,255,.3); padding:9px 18px; border-radius:30px;
           font-weight:600; font-size:13px; text-decoration:none; }
.er-back:hover { background:rgba(255,255,255,.25); }

/* Stats */
.er-stats-outer { padding:0 32px; margin-top:-68px; position:relative; z-index:10; margin-bottom:32px; }
.er-stats-grid  { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
@media(max-width:900px) { .er-stats-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:500px) { .er-stats-grid { grid-template-columns:1fr; } }

.er-stat { background:#fff; border-radius:var(--radius); box-shadow:var(--shadow-md); padding:20px 20px 18px; border-left:4px solid transparent; }
.er-stat-icon { font-size:22px; margin-bottom:10px; }
.er-stat-val  { font-size:26px; font-weight:800; color:#0f172a; }
.er-stat-lbl  { font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:.6px; margin-top:4px; font-weight:600; }
.er-stat-sub  { font-size:12px; margin-top:6px; font-weight:500; }

.st-total  { border-color:#0f172a; } .st-total  .er-stat-icon { color:#0f172a; }
.st-sub    { border-color:var(--green); } .st-sub  .er-stat-sub { color:var(--green); }
.st-order  { border-color:var(--blue);  } .st-order .er-stat-sub { color:var(--blue); }
.st-month  { border-color:var(--amber); } .st-month .er-stat-sub { color:var(--amber); }

/* Main layout */
.er-content { padding:0 32px; display:grid; grid-template-columns:1fr 380px; gap:24px; align-items:start; }
@media(max-width:1100px) { .er-content { grid-template-columns:1fr; } }

/* Cards */
.er-card { background:#fff; border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; margin-bottom:24px; }
.er-card-head { padding:16px 22px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; }
.er-card-head h3 { margin:0; font-size:14px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px; }
.er-card-foot { padding:12px 22px; border-top:1px solid #f1f5f9; text-align:center; }

/* Table */
.er-table { width:100%; border-collapse:collapse; }
.er-table th { padding:11px 20px; background:#f8fafc; color:#64748b; font-size:10px; text-transform:uppercase; letter-spacing:.7px; border-bottom:1px solid #e2e8f0; font-weight:700; text-align:left; }
.er-table td { padding:12px 20px; border-bottom:1px solid #f8fafc; font-size:13px; color:#334155; }
.er-table tr:last-child td { border-bottom:none; }
.er-table tr:hover td { background:#f8fafc; }

/* Chips */
.chip { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.chip-green  { background:#dcfce7; color:#15803d; }
.chip-blue   { background:#dbeafe; color:#1e40af; }
.chip-amber  { background:#fef3c7; color:#92400e; }
.chip-purple { background:#ede9fe; color:#6d28d9; }
.chip-slate  { background:#f1f5f9; color:#475569; }

/* Progress bar */
.prog-bar { height:6px; background:#f1f5f9; border-radius:3px; overflow:hidden; margin-top:6px; }
.prog-fill { height:100%; border-radius:3px; }

/* Chart container */
.chart-wrap { padding:20px 24px 16px; }

/* Tabs */
.er-tabs { display:flex; gap:0; border-bottom:2px solid #e2e8f0; padding:0 22px; }
.er-tab { padding:10px 16px; font-size:13px; font-weight:600; color:#94a3b8; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all .15s; }
.er-tab.active { color:var(--accent); border-bottom-color:var(--accent); }
.er-tab-panel { display:none; } .er-tab-panel.active { display:block; }

/* Empty */
.er-empty { padding:40px; text-align:center; color:#94a3b8; }
.er-empty div { font-size:32px; margin-bottom:8px; }

@media(max-width:768px) {
    .er-hero { padding:24px 16px 80px !important; }
    .er-hero-inner { flex-direction:column; gap:12px; }
    .er-stats-outer { padding:0 14px; }
    .er-content { padding:0 14px; }
    .er-table th, .er-table td { padding:10px 12px; font-size:12px; }
}
</style>

<div class="er-wrap">

    <!-- Hero -->
    <div class="er-hero">
        <div class="er-hero-inner">
            <div>
                <h1>💰 Earnings Overview</h1>
                <p>Subscription revenue &amp; package order revenue — <?php echo date('Y'); ?></p>
            </div>
            <a href="dashboard_admin.php" class="er-back">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="er-stats-outer">
        <div class="er-stats-grid">

            <div class="er-stat st-total">
                <div class="er-stat-icon">💰</div>
                <div class="er-stat-val">₦<?php echo number_format($total_revenue, 0); ?></div>
                <div class="er-stat-lbl">Total Revenue</div>
                <div class="er-stat-sub" style="color:#64748b;"><?php echo $sub_count + $order_count; ?> total transactions</div>
            </div>

            <div class="er-stat st-sub">
                <div class="er-stat-icon">💳</div>
                <div class="er-stat-val">₦<?php echo number_format($sub_revenue, 0); ?></div>
                <div class="er-stat-lbl">Subscription Revenue</div>
                <div class="er-stat-sub"><?php echo $sub_count; ?> subscription payments</div>
            </div>

            <div class="er-stat st-order">
                <div class="er-stat-icon">🛒</div>
                <div class="er-stat-val">₦<?php echo number_format($order_revenue, 0); ?></div>
                <div class="er-stat-lbl">Order Revenue</div>
                <div class="er-stat-sub"><?php echo $order_count; ?> paid orders</div>
            </div>

            <div class="er-stat st-month">
                <div class="er-stat-icon">📅</div>
                <div class="er-stat-val">₦<?php echo number_format($total_month, 0); ?></div>
                <div class="er-stat-lbl">This Month</div>
                <div class="er-stat-sub">
                    ₦<?php echo number_format($sub_month, 0); ?> subs &nbsp;+&nbsp; ₦<?php echo number_format($order_month, 0); ?> orders
                </div>
            </div>

        </div>
    </div>

    <!-- Main content -->
    <div class="er-content">

        <!-- Left Column -->
        <div>

            <!-- Revenue Chart -->
            <div class="er-card">
                <div class="er-card-head">
                    <h3>📈 Monthly Revenue — Last 12 Months</h3>
                </div>
                <div class="chart-wrap">
                    <canvas id="earningsChart" height="110"></canvas>
                </div>
            </div>

            <!-- Transactions Tabs -->
            <div class="er-card">
                <div class="er-card-head" style="padding-bottom:0; border-bottom:none;">
                    <h3>🧾 Recent Transactions</h3>
                </div>
                <div class="er-tabs">
                    <div class="er-tab active" onclick="switchTab('sub')">Subscriptions</div>
                    <div class="er-tab" onclick="switchTab('ord')">Orders</div>
                </div>

                <!-- Subscriptions panel -->
                <div id="panel-sub" class="er-tab-panel active">
                    <?php if ($res_recent_sub && $db->num_rows($res_recent_sub) > 0) : ?>
                    <table class="er-table">
                        <thead>
                            <tr><th>Date</th><th>Subscriber</th><th>Plan</th><th>Reference</th><th style="text-align:right">Amount</th></tr>
                        </thead>
                        <tbody>
                        <?php while ($r = $db->fetch_object($res_recent_sub)) : ?>
                            <tr>
                                <td style="color:#94a3b8;white-space:nowrap;"><?php echo dol_print_date($db->jdate($r->payment_date), 'day'); ?></td>
                                <td><strong><?php echo dol_escape_htmltag($r->firstname.' '.$r->lastname); ?></strong></td>
                                <td><span class="chip chip-green"><?php echo dol_escape_htmltag($r->tier ?: '—'); ?></span></td>
                                <td style="color:#94a3b8;font-size:12px;"><?php echo dol_escape_htmltag(substr($r->payment_reference, 0, 20)); ?></td>
                                <td style="text-align:right;font-weight:700;color:#10b981;">₦<?php echo number_format($r->amount, 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else : ?>
                    <div class="er-empty"><div>💳</div><p>No subscription payments yet.</p></div>
                    <?php endif; ?>
                </div>

                <!-- Orders panel -->
                <div id="panel-ord" class="er-tab-panel">
                    <?php if ($res_recent_ord && $db->num_rows($res_recent_ord) > 0) : ?>
                    <table class="er-table">
                        <thead>
                            <tr><th>Date</th><th>Order Ref</th><th>Subscriber</th><th>Reference</th><th style="text-align:right">Amount</th></tr>
                        </thead>
                        <tbody>
                        <?php while ($r = $db->fetch_object($res_recent_ord)) : ?>
                            <tr>
                                <td style="color:#94a3b8;white-space:nowrap;"><?php echo dol_print_date($db->jdate($r->datec), 'day'); ?></td>
                                <td><span class="chip chip-blue"><?php echo dol_escape_htmltag($r->ref); ?></span></td>
                                <td><strong><?php echo dol_escape_htmltag($r->firstname.' '.$r->lastname); ?></strong></td>
                                <td style="color:#94a3b8;font-size:12px;"><?php echo dol_escape_htmltag(substr($r->payment_reference ?? '', 0, 20)); ?></td>
                                <td style="text-align:right;font-weight:700;color:#3b82f6;">₦<?php echo number_format($r->total_amount, 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else : ?>
                    <div class="er-empty"><div>🛒</div><p>No paid orders yet.</p></div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- end left column -->

        <!-- Right Column -->
        <div>

            <!-- Revenue by Plan -->
            <div class="er-card">
                <div class="er-card-head"><h3>💳 Revenue by Subscription Plan</h3></div>
                <?php
                $tier_total = $sub_revenue ?: 1; // avoid div/0
                if ($res_tiers && $db->num_rows($res_tiers) > 0) :
                ?>
                <div style="padding:6px 0;">
                <?php while ($t = $db->fetch_object($res_tiers)) :
                    $pct = $tier_total > 0 ? round(($t->tier_revenue / $tier_total) * 100) : 0;
                    $tier_label = $t->tier ?: 'Unknown';
                    $colors = ['Guest'=>'#f59e0b','Annual'=>'#10b981','Donor'=>'#8b5cf6'];
                    $bar_color = $colors[$tier_label] ?? '#4f46e5';
                ?>
                    <div style="padding:14px 22px; border-bottom:1px solid #f8fafc;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div style="font-size:14px; font-weight:700; color:#0f172a;"><?php echo dol_escape_htmltag($tier_label); ?></div>
                                <div style="font-size:12px; color:#64748b; margin-top:2px;"><?php echo $t->subs_sold; ?> subscription<?php echo $t->subs_sold != 1 ? 's' : ''; ?></div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:16px; font-weight:800; color:#0f172a;">₦<?php echo number_format($t->tier_revenue, 0); ?></div>
                                <div style="font-size:12px; color:#94a3b8;"><?php echo $pct; ?>% of sub revenue</div>
                            </div>
                        </div>
                        <div class="prog-bar"><div class="prog-fill" style="width:<?php echo $pct; ?>%; background:<?php echo $bar_color; ?>;"></div></div>
                    </div>
                <?php endwhile; ?>
                </div>
                <?php else : ?>
                <div class="er-empty"><div>💳</div><p>No subscription data yet.</p></div>
                <?php endif; ?>
            </div>

            <!-- Top Packages by Revenue -->
            <div class="er-card">
                <div class="er-card-head"><h3>📦 Top Packages by Revenue</h3></div>
                <?php
                $pkg_max_rev = 1;
                $pkg_rows = [];
                if ($res_pkgs && $db->num_rows($res_pkgs) > 0) {
                    while ($p = $db->fetch_object($res_pkgs)) { $pkg_rows[] = $p; }
                    $pkg_max_rev = $pkg_rows[0]->revenue ?: 1;
                }
                if (!empty($pkg_rows)) :
                ?>
                <div style="padding:6px 0;">
                <?php foreach ($pkg_rows as $p) :
                    $pct = round(($p->revenue / $pkg_max_rev) * 100);
                ?>
                    <div style="padding:12px 22px; border-bottom:1px solid #f8fafc;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="font-size:13px; font-weight:600; color:#1e293b; max-width:180px;">
                                <?php echo dol_escape_htmltag($p->package_name ?: '—'); ?>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:14px; font-weight:800; color:#3b82f6;">₦<?php echo number_format($p->revenue, 0); ?></div>
                                <div style="font-size:11px; color:#94a3b8;"><?php echo $p->orders; ?> order<?php echo $p->orders != 1 ? 's' : ''; ?></div>
                            </div>
                        </div>
                        <div class="prog-bar"><div class="prog-fill" style="width:<?php echo $pct; ?>%; background:#3b82f6;"></div></div>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php else : ?>
                <div class="er-empty"><div>📦</div><p>No paid orders yet.</p></div>
                <?php endif; ?>
            </div>

        </div><!-- end right column -->

    </div><!-- end er-content -->

</div><!-- end er-wrap -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    var labels  = <?php echo json_encode($months_labels); ?>;
    var subData = <?php echo json_encode($chart_sub); ?>;
    var ordData = <?php echo json_encode($chart_orders); ?>;

    new Chart(document.getElementById('earningsChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Subscriptions',
                    data: subData,
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderRadius: 5,
                    borderSkipped: false,
                },
                {
                    label: 'Orders',
                    data: ordData,
                    backgroundColor: 'rgba(59, 130, 246, 0.85)',
                    borderRadius: 5,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top', labels: { font: { size: 12 }, padding: 16 } },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ' ₦' + ctx.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: { stacked: true, grid: { display: false }, ticks: { font: { size: 11 } } },
                y: {
                    stacked: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        font: { size: 11 },
                        callback: function(v) { return '₦' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v); }
                    }
                }
            }
        }
    });
})();

function switchTab(name) {
    document.querySelectorAll('.er-tab').forEach(function(t,i) {
        t.classList.toggle('active', (i === 0 && name === 'sub') || (i === 1 && name === 'ord'));
    });
    document.getElementById('panel-sub').classList.toggle('active', name === 'sub');
    document.getElementById('panel-ord').classList.toggle('active', name === 'ord');
}
</script>

<?php llxFooter(); ?>
