<?php
/**
 * Vendor Support System - Helpdesk & Ticketing
 * Auto-creates database table on first run.
 */
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;

$langs->load("admin");

$sql_check = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."foodbank_support (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    fk_vendor INT NOT NULL,
    ref VARCHAR(30),
    subject VARCHAR(255) NOT NULL,
    category VARCHAR(50),
    priority VARCHAR(20) DEFAULT 'Normal',
    message TEXT,
    admin_reply TEXT,
    status VARCHAR(20) DEFAULT 'Open',
    date_created DATETIME,
    date_updated DATETIME
)";
$db->query($sql_check);

// --- SECURITY & ROLE CHECK ---
$is_admin  = FoodbankPermissions::isAdmin($user);
$is_vendor = FoodbankPermissions::isVendor($user, $db);

if (!$is_admin && !$is_vendor) {
    accessforbidden('Access Denied');
}

// Identify Vendor
$vendor_id   = 0;
$vendor_name = 'Administrator';

if ($is_vendor && !$is_admin) {
    $sql = "SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
    $res = $db->query($sql);
    if ($res && $obj = $db->fetch_object($res)) {
        $vendor_id   = $obj->rowid;
        $vendor_name = $obj->name;
    }
}

// --- HANDLE ACTIONS ---
$notice = '';

// Create Ticket (Vendor)
if ($is_vendor && $_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'create') {
    $subject  = GETPOST('subject', 'alpha');
    $category = GETPOST('category', 'alpha');
    $priority = GETPOST('priority', 'alpha');
    $message  = GETPOST('message', 'restricthtml');
    $ref      = 'TKT-'.date('ymd').'-'.rand(100,999);

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_support
            (fk_vendor, ref, subject, category, priority, message, status, date_created)
            VALUES (
                ".(int)$vendor_id.",
                '".$db->escape($ref)."',
                '".$db->escape($subject)."',
                '".$db->escape($category)."',
                '".$db->escape($priority)."',
                '".$db->escape($message)."',
                'Open',
                NOW()
            )";

    if ($db->query($sql)) {
        $notice = 'success:Ticket <strong>'.$ref.'</strong> submitted successfully.';
    } else {
        $notice = 'error:Error creating ticket: '.$db->lasterror();
    }
}

// Reply/Close Ticket (Admin)
if ($is_admin && $_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'update') {
    $ticket_id = GETPOST('ticket_id', 'int');
    $reply     = GETPOST('admin_reply', 'restricthtml');
    $status    = GETPOST('status', 'alpha');

    $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_support SET
            admin_reply  = '".$db->escape($reply)."',
            status       = '".$db->escape($status)."',
            date_updated = NOW()
            WHERE rowid  = ".(int)$ticket_id;

    if ($db->query($sql)) {
        $notice = 'success:Ticket updated successfully.';
    } else {
        $notice = 'error:Update failed: '.$db->lasterror();
    }
}

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', $is_admin ? 'Support Desk' : 'Vendor Support');
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

/* Admin keeps sidebar; vendor gets full-width */
<?php if (!$is_admin) : ?>
#id-top { display: none !important; }
.side-nav, .side-nav-vert, #id-left { display: none !important; }
#id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
<?php else : ?>
#id-top { display: none !important; }
.side-nav, .side-nav-vert { top: 0 !important; height: 100vh !important; }
#id-right { padding-top: 30px !important; background: var(--surface) !important; min-height: 100vh; }
<?php endif; ?>

.fiche { max-width: 100% !important; margin: 0 !important; }

.fb-wrap { max-width: 1200px; margin: 0 auto; padding: 24px 28px; font-family: var(--font); }

/* Page header */
.fb-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
.fb-page-header h1 { margin: 0; font-size: 24px; font-weight: 800; color: #1e293b; }
.fb-page-header p  { margin: 4px 0 0; color: #64748b; font-size: 14px; }

/* Buttons */
.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; border: none; cursor: pointer; font-family: var(--font); transition: background .2s; }
.btn-primary:hover { background: var(--accent-dark); }
.btn-ghost   { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; }
.btn-danger  { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #dc2626 !important; padding: 9px 16px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none; border: 1px solid #fecaca; transition: background .15s; }
.btn-danger:hover { background: #fef2f2; }

/* Notice banner */
.fb-notice { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
.fb-notice.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.fb-notice.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

/* Stats strip (admin only) */
.stats-strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
.stat-tile { background: #fff; border-radius: var(--radius); padding: 20px 24px; box-shadow: var(--shadow); border-top: 4px solid var(--accent); }
.stat-tile.green  { border-color: #10b981; }
.stat-tile.amber  { border-color: #f59e0b; }
.stat-tile .val { font-size: 30px; font-weight: 800; color: #1e293b; line-height: 1; }
.stat-tile .lbl { font-size: 12px; color: #64748b; margin-top: 6px; text-transform: uppercase; letter-spacing: .5px; }

/* Layout grid */
.sp-grid { display: grid; gap: 24px; }
.sp-grid.two-col { grid-template-columns: 1fr 2fr; }
.sp-grid.one-col  { grid-template-columns: 1fr; }

/* Cards */
.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; margin-bottom: 20px; }
.fb-card-head { padding: 18px 24px; border-bottom: 1px solid #f1f5f9; }
.fb-card-head h3 { margin: 0; font-size: 15px; font-weight: 700; color: #1e293b; }
.fb-card-body { padding: 20px 24px; }

/* Ticket item */
.ticket-item { border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; margin-bottom: 16px; background: #fff; transition: box-shadow .15s; }
.ticket-item:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
.ticket-item:last-child { margin-bottom: 0; }

.ticket-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.ticket-title { font-weight: 700; font-size: 15px; color: #1e293b; margin-bottom: 4px; }
.ticket-meta  { font-size: 12px; color: #94a3b8; }
.ticket-vendor { font-size: 12px; color: var(--accent); font-weight: 600; margin-top: 2px; }

.ticket-body { background: #f8fafc; border-radius: 6px; padding: 12px 14px; font-size: 13px; color: #374151; line-height: 1.6; margin-bottom: 12px; }

.admin-reply { background: #eff6ff; border-left: 3px solid var(--accent); border-radius: 6px; padding: 12px 14px; font-size: 13px; color: #1e40af; margin-bottom: 12px; }
.admin-reply strong { display: block; margin-bottom: 4px; font-size: 12px; text-transform: uppercase; letter-spacing: .4px; }

/* Admin reply form */
.reply-form { background: #f8fafc; border-radius: 8px; padding: 14px 16px; margin-top: 12px; border-top: 1px dashed #e2e8f0; }
.reply-row   { display: flex; gap: 10px; align-items: flex-start; margin-top: 10px; }
.reply-input { flex: 1; border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 12px; font-size: 13px; font-family: var(--font); outline: none; background: #fff; }
.reply-input:focus { border-color: var(--accent); }
.reply-select { border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 12px; font-size: 13px; font-family: var(--font); background: #fff; width: 130px; }
.reply-btn { background: var(--accent); color: #fff; border: none; border-radius: 8px; padding: 9px 16px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: var(--font); white-space: nowrap; }
.reply-btn:hover { background: var(--accent-dark); }

/* Badges */
.badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.badge-open     { background: #dcfce7; color: #15803d; }
.badge-closed   { background: #f1f5f9; color: #475569; }
.badge-progress { background: #fef3c7; color: #92400e; }
.badge-high     { background: #fee2e2; color: #991b1b; }
.badge-normal   { background: #e0e7ff; color: #3730a3; }

/* New ticket form */
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
.form-control { width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; font-size: 13px; font-family: var(--font); outline: none; box-sizing: border-box; background: #f8fafc; color: #374151; }
.form-control:focus { border-color: var(--accent); background: #fff; box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
textarea.form-control { resize: vertical; }

/* Help tip */
.help-tip { background: #eff6ff; border-radius: 10px; padding: 16px 18px; font-size: 13px; color: #1e40af; margin-top: 16px; }
.help-tip strong { display: block; margin-bottom: 4px; }

.empty-state { text-align: center; padding: 50px 40px; color: #94a3b8; }
.empty-state h3 { font-size: 16px; color: #64748b; margin: 0 0 6px; }
@media(max-width:768px){
    .fb-wrap { padding: 20px 14px !important; }
    .stats-strip { grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
    .sp-grid.two-col { grid-template-columns: 1fr !important; }
    .stat-tile { padding: 14px 16px; }
    .stat-tile .val { font-size: 24px; }
}
@media(max-width:480px){ .stats-strip { grid-template-columns: 1fr !important; } }
</style>

<?php
// --- Stats for admin ---
if ($is_admin) {
    $t_open   = (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_support WHERE status='Open'"))->c;
    $t_prog   = (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_support WHERE status='In Progress'"))->c;
    $t_closed = (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_support WHERE status='Closed'"))->c;
}

// Parse notice
$notice_type = $notice_msg = '';
if ($notice) {
    [$notice_type, $notice_msg] = explode(':', $notice, 2);
}
?>

<div class="fb-wrap">

    <!-- Page Header -->
    <div class="fb-page-header">
        <div>
            <?php if ($is_admin) : ?>
            <h1>🛡️ Support Desk</h1>
            <p>Review and respond to vendor support tickets</p>
            <?php else : ?>
            <h1>💬 Vendor Support</h1>
            <p>Get help from our team — welcome, <strong><?php echo dol_escape_htmltag($vendor_name); ?></strong></p>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($is_admin) : ?>
            <a href="dashboard_admin.php" class="btn-ghost">← Dashboard</a>
            <?php else : ?>
            <a href="dashboard_vendor.php" class="btn-ghost">← Dashboard</a>
            <a href="<?php echo DOL_URL_ROOT; ?>/user/logout.php" class="btn-danger">🚪 Logout</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($notice_msg) : ?>
    <div class="fb-notice <?php echo $notice_type; ?>"><?php echo $notice_msg; ?></div>
    <?php endif; ?>

    <?php if ($is_admin) : ?>
    <!-- Admin: stats strip -->
    <div class="stats-strip">
        <div class="stat-tile">
            <div class="val"><?php echo $t_open; ?></div>
            <div class="lbl">Open Tickets</div>
        </div>
        <div class="stat-tile amber">
            <div class="val"><?php echo $t_prog; ?></div>
            <div class="lbl">In Progress</div>
        </div>
        <div class="stat-tile green">
            <div class="val"><?php echo $t_closed; ?></div>
            <div class="lbl">Resolved</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Content Grid -->
    <div class="sp-grid <?php echo $is_admin ? 'one-col' : 'two-col'; ?>">

        <?php if (!$is_admin) : ?>
        <!-- Vendor: New Ticket Form -->
        <div>
            <div class="fb-card">
                <div class="fb-card-head"><h3>📝 Open New Ticket</h3></div>
                <div class="fb-card-body">
                    <form method="POST" action="<?php echo basename(__FILE__); ?>">
                        <input type="hidden" name="action" value="create">
                        <div class="form-group">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" required placeholder="Brief summary of your issue...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-control">
                                <option value="Technical">Technical Issue</option>
                                <option value="Logistics">Logistics / Supply</option>
                                <option value="Account">Account / Profile</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-control">
                                <option value="Normal">Normal</option>
                                <option value="High">High Priority</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="5" required placeholder="Describe your issue in detail..."></textarea>
                        </div>
                        <button type="submit" class="btn-primary" style="width:100%;">Submit Ticket</button>
                    </form>
                </div>
            </div>

            <div class="help-tip">
                <strong>💡 Need urgent help?</strong>
                Call our hotline at <strong>0800-FOODBANK</strong><br>Monday – Friday, 9 AM – 5 PM
            </div>
        </div>
        <?php endif; ?>

        <!-- Ticket List -->
        <div>
            <div class="fb-card">
                <div class="fb-card-head">
                    <h3><?php echo $is_admin ? '📥 All Incoming Tickets' : '🕒 My Ticket History'; ?></h3>
                </div>
                <div class="fb-card-body">
                <?php
                $sql = "SELECT t.*, v.name AS vendor_name
                        FROM ".MAIN_DB_PREFIX."foodbank_support t
                        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON t.fk_vendor = v.rowid
                        WHERE 1=1";
                if (!$is_admin) $sql .= " AND t.fk_vendor = ".(int)$vendor_id;
                $sql .= " ORDER BY t.status ASC, t.date_created DESC";
                $res = $db->query($sql);

                if ($res && $db->num_rows($res) > 0) :
                    while ($ticket = $db->fetch_object($res)) :
                        $s = $ticket->status;
                        $badge_class = $s == 'Open' ? 'badge-open' : ($s == 'Closed' ? 'badge-closed' : 'badge-progress');
                        $pri_class   = $ticket->priority == 'High' ? 'badge-high' : 'badge-normal';
                ?>
                    <div class="ticket-item">
                        <div class="ticket-header">
                            <div>
                                <div class="ticket-title"><?php echo dol_escape_htmltag($ticket->subject); ?></div>
                                <div class="ticket-meta">
                                    Ref: <?php echo dol_escape_htmltag($ticket->ref); ?> &nbsp;·&nbsp;
                                    <?php echo dol_print_date($db->jdate($ticket->date_created), 'dayhour'); ?>
                                    <?php if ($ticket->category) echo '&nbsp;·&nbsp;'.dol_escape_htmltag($ticket->category); ?>
                                </div>
                                <?php if ($is_admin && $ticket->vendor_name) : ?>
                                <div class="ticket-vendor">From: <?php echo dol_escape_htmltag($ticket->vendor_name); ?></div>
                                <?php endif; ?>
                            </div>
                            <div style="text-align:right; flex-shrink:0; margin-left:12px;">
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $s; ?></span><br>
                                <span class="badge <?php echo $pri_class; ?>" style="margin-top:4px;"><?php echo $ticket->priority; ?></span>
                            </div>
                        </div>

                        <div class="ticket-body"><?php echo nl2br(dol_escape_htmltag($ticket->message)); ?></div>

                        <?php if (!empty($ticket->admin_reply)) : ?>
                        <div class="admin-reply">
                            <strong>Admin Reply</strong>
                            <?php echo nl2br(dol_escape_htmltag($ticket->admin_reply)); ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($is_admin) : ?>
                        <div class="reply-form">
                            <div style="font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.4px; margin-bottom:8px;">Respond</div>
                            <form method="POST" action="<?php echo basename(__FILE__); ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="ticket_id" value="<?php echo $ticket->rowid; ?>">
                                <div class="reply-row">
                                    <input type="text" name="admin_reply" class="reply-input" placeholder="Type your reply..." value="<?php echo dol_escape_htmltag($ticket->admin_reply); ?>">
                                    <select name="status" class="reply-select">
                                        <option value="Open"        <?php echo $s=='Open'?        'selected':''; ?>>Open</option>
                                        <option value="In Progress" <?php echo $s=='In Progress'? 'selected':''; ?>>In Progress</option>
                                        <option value="Closed"      <?php echo $s=='Closed'?      'selected':''; ?>>Closed</option>
                                    </select>
                                    <button type="submit" class="reply-btn">Update</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; else : ?>
                    <div class="empty-state">
                        <h3>No tickets found</h3>
                        <p style="font-size:13px;"><?php echo $is_admin ? 'No vendor tickets have been submitted yet.' : 'You have no open tickets. Use the form to submit one.'; ?></p>
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- end sp-grid -->
</div><!-- end fb-wrap -->

<?php
llxFooter();
?>
