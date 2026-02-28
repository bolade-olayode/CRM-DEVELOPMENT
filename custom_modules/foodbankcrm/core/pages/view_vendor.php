<?php
/**
 * ADMIN: Vendor Profile View
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");

$id      = GETPOST('id', 'int');
$action  = GETPOST('action', 'alpha');
$notice  = '';

if (!$id) accessforbidden();

// --- HANDLE APPROVAL (POST + CSRF) ---
if ($action == 'approve' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed. Please try again.'];
    } else {
        $sql_v = "SELECT contact_email, contact_person, name FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE rowid = ".(int)$id;
        $res_v = $db->query($sql_v);
        $vobj  = $res_v ? $db->fetch_object($res_v) : null;

        $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_vendors SET status = 'Active' WHERE rowid = ".(int)$id);

        // Also enable the linked Dolibarr user
        $db->query("UPDATE ".MAIN_DB_PREFIX."user u
                    JOIN ".MAIN_DB_PREFIX."foodbank_user_vendor uv ON uv.fk_user = u.rowid
                    SET u.statut = 1 WHERE uv.fk_vendor = ".(int)$id);

        if ($vobj && $vobj->contact_email) {
            $from    = !empty($conf->global->MAIN_MAIL_EMAIL_FROM) ? $conf->global->MAIN_MAIL_EMAIL_FROM : 'no-reply@foodbank.com';
            $subject = "Account Approved - Foodbank Partner";
            $message = "Dear ".$vobj->contact_person.",\n\n"
                      ."Your vendor account for ".$vobj->name." has been APPROVED.\n\n"
                      ."You can now log in to manage your inventory and supplies.\n"
                      ."Login: ".DOL_MAIN_URL_ROOT."/custom/foodbankcrm/index.php\n\n"
                      ."Best Regards,\nFoodbank Admin Team";
            $mail = new CMailFile($subject, $vobj->contact_email, $from, $message);
            $mail->sendfile();
            $notice = ['success', 'Vendor approved and notification email sent to '.$vobj->contact_email.'.'];
        } else {
            $notice = ['success', 'Vendor approved successfully.'];
        }
    }
}

// --- HANDLE REJECTION (POST + CSRF) ---
if ($action == 'reject' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed. Please try again.'];
    } else {
        $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_vendors SET status = 'Inactive' WHERE rowid = ".(int)$id);
        $notice = ['error', 'Vendor application rejected and access disabled.'];
    }
}

// Fetch vendor
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE rowid = ".(int)$id;
$res = $db->query($sql);
if (!$res || $db->num_rows($res) == 0) {
    header("Location: vendors.php"); exit;
}
$obj = $db->fetch_object($res);

// Meta
$initial     = strtoupper(mb_substr($obj->name, 0, 1));
$status      = $obj->status ?: 'Pending';
$status_class = $status == 'Active' ? 'badge-active' : ($status == 'Inactive' ? 'badge-inactive' : 'badge-pending');

// Linked user
$linked_login = '';
$u_res = $db->query("SELECT u.login FROM ".MAIN_DB_PREFIX."user u
                     JOIN ".MAIN_DB_PREFIX."foodbank_user_vendor uv ON uv.fk_user = u.rowid
                     WHERE uv.fk_vendor = ".(int)$id." LIMIT 1");
if ($u_res && ($u_obj = $db->fetch_object($u_res))) {
    $linked_login = $u_obj->login;
}

// Order count
$order_count = (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE fk_vendor = ".(int)$id))->c;

llxHeader('', 'Vendor Profile');
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

.fb-wrap { max-width: 1200px; margin: 0 auto; padding: 24px 28px; font-family: var(--font); }

.fb-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.profile-meta { display: flex; align-items: center; }
.vendor-avatar { width: 64px; height: 64px; border-radius: 16px; background: var(--accent); color: #fff; font-size: 28px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; margin-right: 18px; flex-shrink: 0; }
.profile-name h1 { margin: 0 0 6px; font-size: 22px; font-weight: 800; color: #1e293b; }
.profile-name .meta { font-size: 13px; color: #94a3b8; }

.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none !important; border: none; cursor: pointer; transition: background .2s; }
.btn-primary:hover { background: var(--accent-dark); text-decoration: none !important; }
.btn-ghost  { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none !important; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; text-decoration: none !important; }

/* Notice */
.fb-notice { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
.fb-notice.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.fb-notice.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

/* Meta strip */
.meta-strip { display: flex; gap: 20px; flex-wrap: wrap; background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); padding: 14px 22px; margin-bottom: 22px; align-items: center; }
.meta-item { font-size: 13px; color: #64748b; }
.meta-item strong { color: #1e293b; font-weight: 700; }
.meta-sep { color: #e2e8f0; font-size: 18px; }

/* Approval box */
.approve-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius); padding: 22px 26px; margin-bottom: 22px; }
.approve-box h3 { margin: 0 0 8px; font-size: 15px; color: #92400e; font-weight: 700; }
.approve-box p  { margin: 0 0 18px; font-size: 13px; color: #78350f; }
.approve-actions { display: flex; gap: 12px; }
.btn-approve { display: inline-flex; align-items: center; gap: 6px; background: #16a34a; color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none !important; border: none; cursor: pointer; transition: background .2s; }
.btn-approve:hover { background: #15803d; text-decoration: none !important; }
.btn-reject  { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #dc2626 !important; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none !important; border: 1px solid #fca5a5; cursor: pointer; transition: background .2s; }
.btn-reject:hover  { background: #fef2f2; text-decoration: none !important; }

/* Grid */
.profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.full-width { margin-bottom: 20px; }

/* Cards */
.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; margin-bottom: 20px; }
.fb-card-head { padding: 14px 22px; border-bottom: 1px solid #f1f5f9; }
.fb-card-head h3 { margin: 0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #94a3b8; }
.data-row { display: flex; justify-content: space-between; align-items: center; padding: 13px 22px; border-bottom: 1px solid #f8fafc; }
.data-row:last-child { border-bottom: none; }
.data-lbl { font-size: 13px; color: #64748b; font-weight: 500; flex-shrink: 0; }
.data-val { font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; word-break: break-word; max-width: 65%; }

/* Badges */
.badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
.badge-active   { background: #dcfce7; color: #15803d; }
.badge-pending  { background: #fef3c7; color: #92400e; }
.badge-inactive { background: #fee2e2; color: #991b1b; }

/* Banking section */
.banking-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.banking-cell { padding: 14px 22px; border-right: 1px solid #f1f5f9; }
.banking-cell:last-child { border-right: none; }
.banking-lbl { font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
.banking-val { font-size: 15px; color: #1e293b; font-weight: 600; }
</style>

<div class="fb-wrap">

    <!-- Header -->
    <div class="fb-page-header">
        <div class="profile-meta">
            <div class="vendor-avatar"><?php echo $initial; ?></div>
            <div class="profile-name">
                <h1><?php echo dol_escape_htmltag($obj->name); ?> &nbsp;<span class="badge <?php echo $status_class; ?>"><?php echo $status; ?></span></h1>
                <span class="meta">Ref: <strong><?php echo dol_escape_htmltag($obj->ref); ?></strong> &nbsp;&middot;&nbsp; Category: <?php echo dol_escape_htmltag($obj->category) ?: '—'; ?></span>
            </div>
        </div>
        <div style="flex-shrink:0; margin-top:4px;">
            <a href="vendors.php" class="btn-ghost">← Back to List</a>
            <a href="edit_vendor.php?id=<?php echo $obj->rowid; ?>" class="btn-primary">✏️ Edit Profile</a>
        </div>
    </div>

    <?php if ($notice) : ?>
    <div class="fb-notice <?php echo $notice[0]; ?>"><?php echo $notice[1]; ?></div>
    <?php endif; ?>

    <!-- Meta Strip -->
    <div class="meta-strip">
        <div class="meta-item">Contact: <strong><?php echo $obj->contact_person ? dol_escape_htmltag($obj->contact_person) : '—'; ?></strong></div>
        <span class="meta-sep">·</span>
        <div class="meta-item">Email: <strong><?php echo $obj->contact_email ? dol_escape_htmltag($obj->contact_email) : '—'; ?></strong></div>
        <span class="meta-sep">·</span>
        <div class="meta-item">Phone: <strong><?php echo $obj->contact_phone ? dol_escape_htmltag($obj->contact_phone) : '—'; ?></strong></div>
        <?php if ($order_count > 0) : ?>
        <span class="meta-sep">·</span>
        <div class="meta-item">Orders Supplied: <strong><?php echo $order_count; ?></strong></div>
        <?php endif; ?>
    </div>

    <!-- Approval Box (Pending only) -->
    <?php if ($status == 'Pending') : ?>
    <div class="approve-box">
        <h3>⚠️ Action Required</h3>
        <p>This vendor application is pending review. Approve to grant dashboard access, or reject to disable their account.</p>
        <div class="approve-actions">
            <form method="POST" action="view_vendor.php?id=<?php echo $id; ?>" style="margin:0;"
                  onsubmit="return confirm('Approve this vendor? An email notification will be sent.');">
                <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn-approve">✅ Approve Access</button>
            </form>
            <form method="POST" action="view_vendor.php?id=<?php echo $id; ?>" style="margin:0;"
                  onsubmit="return confirm('Reject this vendor? Their access will be disabled.');">
                <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn-reject">✕ Reject / Disapprove</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Profile Grid -->
    <div class="profile-grid">

        <!-- Contact Details -->
        <div class="fb-card">
            <div class="fb-card-head"><h3>Contact Details</h3></div>
            <div class="data-row"><span class="data-lbl">Contact Person</span><span class="data-val"><?php echo $obj->contact_person ? dol_escape_htmltag($obj->contact_person) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
            <div class="data-row"><span class="data-lbl">Email Address</span><span class="data-val"><a href="mailto:<?php echo dol_escape_htmltag($obj->contact_email); ?>" style="color:var(--accent);"><?php echo $obj->contact_email ? dol_escape_htmltag($obj->contact_email) : '<span style="color:#cbd5e1">—</span>'; ?></a></span></div>
            <div class="data-row"><span class="data-lbl">Phone Number</span><span class="data-val"><?php echo $obj->contact_phone ? dol_escape_htmltag($obj->contact_phone) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
            <div class="data-row"><span class="data-lbl">Office Address</span><span class="data-val"><?php echo $obj->address ? dol_escape_htmltag($obj->address) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
            <?php if ($obj->website) : ?>
            <div class="data-row"><span class="data-lbl">Website</span><span class="data-val"><a href="<?php echo dol_escape_htmltag($obj->website); ?>" target="_blank" style="color:var(--accent);"><?php echo dol_escape_htmltag($obj->website); ?></a></span></div>
            <?php endif; ?>
            <?php if ($linked_login) : ?>
            <div class="data-row"><span class="data-lbl">Login Account</span><span class="data-val" style="font-family:monospace;"><?php echo dol_escape_htmltag($linked_login); ?></span></div>
            <?php endif; ?>
        </div>

        <!-- Business Info -->
        <div class="fb-card">
            <div class="fb-card-head"><h3>Business Information</h3></div>
            <div class="data-row"><span class="data-lbl">Supply Category</span><span class="data-val"><?php echo $obj->category ? dol_escape_htmltag($obj->category) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
            <div class="data-row"><span class="data-lbl">Registration No (RC)</span><span class="data-val"><?php echo $obj->registration_number ? dol_escape_htmltag($obj->registration_number) : '<span style="color:#cbd5e1">Not provided</span>'; ?></span></div>
            <div class="data-row"><span class="data-lbl">Tax ID (TIN)</span><span class="data-val"><?php echo $obj->tax_id ? dol_escape_htmltag($obj->tax_id) : '<span style="color:#cbd5e1">Not provided</span>'; ?></span></div>
            <div class="data-row"><span class="data-lbl">Payment Terms</span><span class="data-val"><?php echo $obj->payment_terms ? dol_escape_htmltag($obj->payment_terms) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
            <div class="data-row"><span class="data-lbl">Status</span><span class="badge <?php echo $status_class; ?>"><?php echo $status; ?></span></div>
        </div>

    </div>

    <!-- Banking Section -->
    <div class="fb-card full-width">
        <div class="fb-card-head"><h3>Banking Information</h3></div>
        <div class="banking-grid">
            <div class="banking-cell">
                <div class="banking-lbl">Bank Name</div>
                <div class="banking-val"><?php echo $obj->bank_name ? dol_escape_htmltag($obj->bank_name) : '<span style="color:#cbd5e1">Not provided</span>'; ?></div>
            </div>
            <div class="banking-cell">
                <div class="banking-lbl">Account Number</div>
                <div class="banking-val"><?php echo $obj->bank_account_number ? dol_escape_htmltag($obj->bank_account_number) : '<span style="color:#cbd5e1">Not provided</span>'; ?></div>
            </div>
        </div>
    </div>

</div>

<?php llxFooter(); ?>
