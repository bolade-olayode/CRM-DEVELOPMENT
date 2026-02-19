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


// --- 2. SECURITY & ROLE CHECK ---
$is_admin = FoodbankPermissions::isAdmin($user);
$is_vendor = FoodbankPermissions::isVendor($user, $db);

if (!$is_admin && !$is_vendor) {
    accessforbidden('Access Denied');
}

// Identify Vendor
$vendor_id = 0;
$vendor_name = 'Administrator';

if ($is_vendor && !$is_admin) {
    $sql = "SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
    $res = $db->query($sql);
    if ($res && $obj = $db->fetch_object($res)) {
        $vendor_id = $obj->rowid;
        $vendor_name = $obj->name;
    }
}

// --- 3. HANDLE ACTIONS ---
$notice = '';

// Create Ticket (Vendor)
if ($is_vendor && $_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'create') {
    $subject = GETPOST('subject', 'alpha');
    $category = GETPOST('category', 'alpha');
    $priority = GETPOST('priority', 'alpha');
    $message = GETPOST('message', 'restricthtml');
    $ref = 'TKT-'.date('ymd').'-'.rand(100,999);
    
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
        $notice = '<div class="alert-box alert-success">✓ Support ticket <strong>'.$ref.'</strong> created successfully.</div>';
    } else {
        $notice = '<div class="alert-box alert-error">Error creating ticket: '.$db->lasterror().'</div>';
    }
}

// Reply/Close Ticket (Admin)
if ($is_admin && $_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'update') {
    $ticket_id = GETPOST('ticket_id', 'int');
    $reply = GETPOST('admin_reply', 'restricthtml');
    $status = GETPOST('status', 'alpha');
    
    $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_support SET 
            admin_reply = '".$db->escape($reply)."',
            status = '".$db->escape($status)."',
            date_updated = NOW()
            WHERE rowid = ".(int)$ticket_id;
            
    if ($db->query($sql)) {
        $notice = '<div class="alert-box alert-success">✓ Ticket updated.</div>';
    }
}

llxHeader('', 'Vendor Support');

// --- 4. CSS STYLES ---
print '<style>
    /* HIDE CHROME */
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header { display: none !important; }
    
    /* LAYOUT */
    html, body { background-color: #f8f9fa !important; margin: 0; width: 100%; overflow-x: hidden; }
    #id-right, .id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
    
    .support-container { width: 95%; max-width: 1200px; margin: 0 auto; padding: 40px 20px; font-family: "Segoe UI", sans-serif; }
    
    .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #eee; margin-bottom: 25px; }
    
    /* FORM ELEMENTS */
    .form-group { margin-bottom: 15px; }
    .form-label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 14px; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
    .btn-submit { background: #667eea; color: white; border: none; padding: 10px 25px; border-radius: 30px; font-weight: bold; cursor: pointer; }
    .btn-submit:hover { background: #5a6fd6; }
    
    /* BADGES */
    .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; color: white; display: inline-block; }
    .badge-open { background: #28a745; }
    .badge-closed { background: #6c757d; }
    .badge-progress { background: #ffc107; color: #333; }
    
    .priority-high { color: #dc3545; font-weight: bold; }
    .priority-normal { color: #28a745; }
    
    .btn-logout {
        background: white; color: #dc3545; border: 1px solid #dc3545; 
        padding: 8px 16px; border-radius: 30px; text-decoration: none; 
        font-weight: bold; font-size: 13px; display: inline-flex; align-items: center; gap: 5px;
    }
    
    /* ALERTS */
    .alert-box { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    
    /* ADMIN REPLY BOX */
    .admin-reply-box { background: #f1f8ff; border-left: 4px solid #007bff; padding: 15px; margin-top: 10px; border-radius: 4px; font-size: 14px; color: #333; }
</style>';

print '<div class="support-container">';

// --- HEADER ---
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<div>';
if ($is_admin) {
    print '<h1 style="margin: 0; color: #2c3e50;">🛡️ Admin Support Desk</h1>';
    print '<p style="color: #666; margin: 5px 0 0 0;">Manage incoming vendor tickets</p>';
} else {
    print '<h1 style="margin: 0; color: #2c3e50;">💬 Vendor Support</h1>';
    print '<p style="color: #666; margin: 5px 0 0 0;">We are here to help, <strong>'.dol_escape_htmltag($vendor_name).'</strong></p>';
}
print '</div>';

print '<div style="display: flex; gap: 10px; align-items: center;">';
if (!$is_admin) print '<a href="dashboard_vendor.php" class="btn-outline" style="color:#666; text-decoration:none; font-weight:600; margin-right:10px;">← Dashboard</a>';
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="btn-logout"><span>🚪</span> Logout</a>';
print '</div>';
print '</div>';

print $notice;

print '<div style="display: grid; grid-template-columns: '.($is_admin ? '1fr' : '1fr 2fr').'; gap: 30px;">';

// --- LEFT COLUMN: NEW TICKET FORM (Vendor Only) ---
if (!$is_admin) {
    print '<div>';
    print '<div class="card">';
    print '<h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">📝 Open New Ticket</h3>';
    
    print '<form method="POST" action="'.basename(__FILE__).'">';
    print '<input type="hidden" name="action" value="create">';
    
    print '<div class="form-group"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control" required placeholder="Brief summary..."></div>';
    
    print '<div class="form-group"><label class="form-label">Category</label>';
    print '<select name="category" class="form-control">';
    print '<option value="Technical">Technical Issue</option>';
    print '<option value="Logistics">Logistics / Supply</option>';
    print '<option value="Account">Account / Profile</option>';
    print '<option value="Other">Other</option>';
    print '</select></div>';
    
    print '<div class="form-group"><label class="form-label">Priority</label>';
    print '<select name="priority" class="form-control">';
    print '<option value="Normal">Normal</option>';
    print '<option value="High" style="color:red;">High Priority</option>';
    print '</select></div>';
    
    print '<div class="form-group"><label class="form-label">Message</label><textarea name="message" class="form-control" rows="5" required placeholder="Describe your issue..."></textarea></div>';
    
    print '<button type="submit" class="btn-submit">Submit Ticket</button>';
    print '</form>';
    print '</div>';
    
    // Help Info
    print '<div style="background: #e3f2fd; padding: 20px; border-radius: 8px; color: #0d47a1;">';
    print '<strong>💡 Need urgent help?</strong><br>You can also call our hotline at <strong>0800-FOODBANK</strong> between 9 AM - 5 PM.';
    print '</div>';
    
    print '</div>';
}

// --- RIGHT COLUMN: TICKET LIST (Everyone) ---
print '<div>';
print '<div class="card">';
print '<h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">'.($is_admin ? '📥 All Incoming Tickets' : '🕒 My Ticket History').'</h3>';

// Query Logic
$sql = "SELECT t.*, v.name as vendor_name 
        FROM ".MAIN_DB_PREFIX."foodbank_support t
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON t.fk_vendor = v.rowid
        WHERE 1=1";

if (!$is_admin) {
    $sql .= " AND t.fk_vendor = ".(int)$vendor_id;
}
$sql .= " ORDER BY t.status ASC, t.date_created DESC"; // Open tickets first

$res = $db->query($sql);

if ($db->num_rows($res) > 0) {
    while ($ticket = $db->fetch_object($res)) {
        $status_class = ($ticket->status == 'Open') ? 'badge-open' : (($ticket->status == 'Closed') ? 'badge-closed' : 'badge-progress');
        $priority_class = ($ticket->priority == 'High') ? 'priority-high' : 'priority-normal';
        
        print '<div style="border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 15px;">';
        
        // Ticket Header
        print '<div style="display:flex; justify-content:space-between; margin-bottom:10px;">';
        print '<div>';
        print '<span style="font-weight:bold; color:#333; font-size:16px;">'.dol_escape_htmltag($ticket->subject).'</span>';
        print '<div style="font-size:12px; color:#888;">Ref: '.$ticket->ref.' • '.dol_print_date($db->jdate($ticket->date_created), 'dayhour').'</div>';
        if ($is_admin) print '<div style="font-size:12px; color:#667eea; font-weight:bold;">Vendor: '.dol_escape_htmltag($ticket->vendor_name).'</div>';
        print '</div>';
        print '<div style="text-align:right;">';
        print '<span class="badge '.$status_class.'">'.$ticket->status.'</span><br>';
        print '<span style="font-size:11px; margin-top:5px; display:block;" class="'.$priority_class.'">'.$ticket->priority.'</span>';
        print '</div>';
        print '</div>';
        
        // Message Body
        print '<div style="background:#fafafa; padding:10px; border-radius:4px; font-size:14px; color:#555;">'.nl2br(dol_escape_htmltag($ticket->message)).'</div>';
        
        // Admin Reply Display
        if (!empty($ticket->admin_reply)) {
            print '<div class="admin-reply-box">';
            print '<strong>👨‍💼 Admin Reply:</strong><br>';
            print nl2br(dol_escape_htmltag($ticket->admin_reply));
            print '</div>';
        }
        
        // --- ADMIN ACTION AREA ---
        if ($is_admin) {
            print '<div style="margin-top:15px; padding-top:10px; border-top:1px dashed #ddd;">';
            print '<form method="POST" action="'.basename(__FILE__).'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="ticket_id" value="'.$ticket->rowid.'">';
            
            print '<div style="display:flex; gap:10px;">';
            print '<input type="text" name="admin_reply" class="form-control" placeholder="Type reply..." value="'.dol_escape_htmltag($ticket->admin_reply).'">';
            print '<select name="status" class="form-control" style="width:120px;">';
            print '<option value="Open" '.($ticket->status=='Open'?'selected':'').'>Open</option>';
            print '<option value="In Progress" '.($ticket->status=='In Progress'?'selected':'').'>In Progress</option>';
            print '<option value="Closed" '.($ticket->status=='Closed'?'selected':'').'>Closed</option>';
            print '</select>';
            print '<button type="submit" class="btn-submit" style="padding:8px 15px; font-size:12px;">Update</button>';
            print '</div>';
            print '</form>';
            print '</div>';
        }
        
        print '</div>'; // End Ticket Item
    }
} else {
    print '<div style="text-align:center; padding:40px; color:#999;">No tickets found.</div>';
}

print '</div>'; // End Card
print '</div>'; // End Right Column

print '</div>'; // End Grid
print '</div>'; // End Container

llxFooter();
?>