<?php
/**
 * ADMIN: Vendor Profile View
 */
define('NOCSRFCHECK', 1);
define('NOTOKENRENEWAL', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");

$id       = GETPOST('id', 'int');
$action   = GETPOST('action', 'alpha');
$msg_code = GETPOST('msg', 'alpha');

if (!$id) accessforbidden();

// --- Handle Approve ---
if ($action == 'approve') {
    $res_v  = $db->query("SELECT contact_email, contact_person, name, fk_user FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE rowid = ".(int)$id);
    $vendor = $res_v ? $db->fetch_object($res_v) : null;

    $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_vendors SET status = 'Active' WHERE rowid = ".(int)$id);

    // Activate linked Dolibarr user
    if ($vendor && !empty($vendor->fk_user)) {
        $db->query("UPDATE ".MAIN_DB_PREFIX."user SET statut = 1 WHERE rowid = ".(int)$vendor->fk_user);
    }

    // Send approval email
    if ($vendor && $vendor->contact_email) {
        $subject = "Account Approved - Foodbank Partner";
        $message = "Dear ".$vendor->contact_person.",\n\n";
        $message .= "Your vendor account for ".$vendor->name." has been APPROVED.\n\n";
        $message .= "You can now log in to your dashboard to manage inventory.\n";
        $message .= "Login: ".DOL_MAIN_URL_ROOT."/custom/foodbankcrm/index.php\n\n";
        $message .= "Best Regards,\nFoodbank Admin Team";
        $from = !empty($conf->global->MAIN_MAIL_EMAIL_FROM) ? $conf->global->MAIN_MAIL_EMAIL_FROM : 'no-reply@foodbank.com';
        $mail = new CMailFile($subject, $vendor->contact_email, $from, $message);
        $mail->sendfile();
    }
    header("Location: view_vendor.php?id=".$id."&msg=approved"); exit;
}

// --- Handle Reject ---
if ($action == 'reject') {
    $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_vendors SET status = 'Inactive' WHERE rowid = ".(int)$id);
    header("Location: view_vendor.php?id=".$id."&msg=rejected"); exit;
}

// Fetch vendor
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE rowid = ".(int)$id;
$res = $db->query($sql);
if (!$res || $db->num_rows($res) == 0) accessforbidden("Vendor not found.");
$obj = $db->fetch_object($res);

// Fetch linked login
$user_login        = '';
$user_status_label = 'No Login Account';
$user_status_class = 'badge-default';
$user_link         = '';
if (!empty($obj->fk_user)) {
    $res_u = $db->query("SELECT rowid, login, statut FROM ".MAIN_DB_PREFIX."user WHERE rowid = ".(int)$obj->fk_user);
    if ($res_u && $u = $db->fetch_object($res_u)) {
        $user_login        = $u->login;
        $user_status_label = $u->statut == 1 ? 'Login Enabled' : 'Login Disabled';
        $user_status_class = $u->statut == 1 ? 'badge-active' : 'badge-expired';
        $user_link         = DOL_URL_ROOT.'/user/card.php?id='.$u->rowid;
    }
}

$initial      = strtoupper(mb_substr($obj->name ?: '?', 0, 1));
$status       = $obj->status ?: 'Pending';
$status_class = $status == 'Active' ? 'badge-active' : ($status == 'Inactive' ? 'badge-expired' : 'badge-pending');

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

.fb-wrap { max-width: 1100px; margin: 0 auto; padding: 24px 28px; font-family: var(--font); }

.fb-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.profile-meta { display: flex; align-items: center; }
.profile-avatar { width: 64px; height: 64px; border-radius: 16px; background: var(--accent); color: #fff; font-size: 28px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; margin-right: 18px; flex-shrink: 0; }
.profile-name h1 { margin: 0 0 6px; font-size: 22px; font-weight: 800; color: #1e293b; }
.profile-name .meta { font-size: 13px; color: #94a3b8; }

.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none !important; border: none; cursor: pointer; transition: background .2s; }
.btn-primary:hover { background: var(--accent-dark); text-decoration: none !important; }
.btn-ghost { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none !important; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; text-decoration: none !important; }
.btn-approve { display: inline-flex; align-items: center; gap: 6px; background: #16a34a; color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; font-family: var(--font); transition: background .2s; margin-right: 10px; }
.btn-approve:hover { background: #15803d; }
.btn-reject  { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #dc2626 !important; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px; border: 1.5px solid #fca5a5; cursor: pointer; font-family: var(--font); transition: background .15s; }
.btn-reject:hover { background: #fef2f2; }

.pending-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius); padding: 20px 24px; margin-bottom: 22px; }
.pending-box h3 { margin: 0 0 8px; color: #92400e; font-size: 15px; }
.pending-box p  { margin: 0 0 16px; color: #92400e; font-size: 13px; }

.alert-notice { padding: 13px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
.alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.alert-danger  { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

.meta-strip { display: flex; gap: 20px; flex-wrap: wrap; background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); padding: 14px 22px; margin-bottom: 22px; align-items: center; }
.meta-item { font-size: 13px; color: #64748b; }
.meta-item strong { color: #1e293b; font-weight: 700; }
.meta-sep { color: #e2e8f0; font-size: 18px; }

.profile-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }

.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; margin-bottom: 20px; }
.fb-card-head { padding: 14px 22px; border-bottom: 1px solid #f1f5f9; }
.fb-card-head h3 { margin: 0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #94a3b8; }

.data-row { display: flex; justify-content: space-between; align-items: center; padding: 13px 22px; border-bottom: 1px solid #f8fafc; }
.data-row:last-child { border-bottom: none; }
.data-lbl { font-size: 13px; color: #64748b; font-weight: 500; flex-shrink: 0; }
.data-val { font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; max-width: 60%; word-break: break-word; }

.badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
.badge-active  { background: #dcfce7; color: #15803d; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-expired { background: #fee2e2; color: #991b1b; }
.badge-default { background: #f1f5f9; color: #475569; }

.account-link { display: block; text-align: center; padding: 12px; font-size: 13px; color: var(--accent); text-decoration: none !important; font-weight: 600; border-top: 1px solid #f1f5f9; transition: background .15s; }
.account-link:hover { background: #f8fafc; }
</style>

<div class="fb-wrap">

    <!-- Header -->
    <div class="fb-page-header">
        <div class="profile-meta">
            <div class="profile-avatar"><?php echo $initial; ?></div>
            <div class="profile-name">
                <h1><?php echo dol_escape_htmltag($obj->name); ?> &nbsp;<span class="badge <?php echo $status_class; ?>"><?php echo $status; ?></span></h1>
                <span class="meta">Ref: <strong><?php echo dol_escape_htmltag($obj->ref); ?></strong> &nbsp;&middot;&nbsp; <?php echo dol_escape_htmltag($obj->category ?: 'No Category'); ?></span>
            </div>
        </div>
        <div style="flex-shrink:0; margin-top:4px;">
            <a href="vendors.php" class="btn-ghost">← Back to List</a>
            <a href="edit_vendor.php?id=<?php echo $obj->rowid; ?>" class="btn-primary">✏️ Edit Profile</a>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($msg_code == 'approved') : ?>
    <div class="alert-notice alert-success">✅ Vendor approved successfully. Notification email sent and login activated.</div>
    <?php elseif ($msg_code == 'rejected') : ?>
    <div class="alert-notice alert-danger">❌ Vendor rejected. Their access has been disabled.</div>
    <?php endif; ?>

    <!-- Pending Action Box -->
    <?php if ($obj->status == 'Pending') : ?>
    <div class="pending-box">
        <h3>⚠️ Action Required</h3>
        <p>This vendor is pending review. Review the details below and approve or reject their application.</p>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <form method="POST" action="view_vendor.php?id=<?php echo $obj->rowid; ?>" onsubmit="return confirm('Approve this vendor? An email notification will be sent.');" style="margin:0;">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn-approve">✅ Approve Access</button>
            </form>
            <form method="POST" action="view_vendor.php?id=<?php echo $obj->rowid; ?>" onsubmit="return confirm('Reject this vendor?');" style="margin:0;">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn-reject">❌ Reject</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Meta Strip -->
    <div class="meta-strip">
        <div class="meta-item">Email: <strong><?php echo $obj->email ? dol_escape_htmltag($obj->email) : '—'; ?></strong></div>
        <span class="meta-sep">·</span>
        <div class="meta-item">Phone: <strong><?php echo $obj->phone ? dol_escape_htmltag($obj->phone) : '—'; ?></strong></div>
        <span class="meta-sep">·</span>
        <div class="meta-item">Category: <strong><?php echo $obj->category ? dol_escape_htmltag($obj->category) : '—'; ?></strong></div>
    </div>

    <!-- Profile Grid -->
    <div class="profile-grid">

        <!-- LEFT -->
        <div>
            <div class="fb-card">
                <div class="fb-card-head"><h3>Contact Details</h3></div>
                <div class="data-row"><span class="data-lbl">Contact Person</span><span class="data-val"><?php echo $obj->contact_person ? dol_escape_htmltag($obj->contact_person) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
                <div class="data-row"><span class="data-lbl">Email</span><span class="data-val"><?php echo $obj->contact_email ? '<a href="mailto:'.dol_escape_htmltag($obj->contact_email).'" style="color:var(--accent);">'.dol_escape_htmltag($obj->contact_email).'</a>' : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
                <div class="data-row"><span class="data-lbl">Phone</span><span class="data-val"><?php echo $obj->contact_phone ? dol_escape_htmltag($obj->contact_phone) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
                <div class="data-row"><span class="data-lbl">Address</span><span class="data-val"><?php echo $obj->address ? nl2br(dol_escape_htmltag($obj->address)) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
            </div>

            <div class="fb-card">
                <div class="fb-card-head"><h3>Business Information</h3></div>
                <div class="data-row"><span class="data-lbl">RC Number</span><span class="data-val"><?php echo $obj->registration_number ? dol_escape_htmltag($obj->registration_number) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
                <div class="data-row"><span class="data-lbl">Tax ID (TIN)</span><span class="data-val"><?php echo $obj->tax_id ? dol_escape_htmltag($obj->tax_id) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
                <div class="data-row"><span class="data-lbl">Website</span><span class="data-val"><?php echo $obj->website ? '<a href="'.dol_escape_htmltag($obj->website).'" target="_blank" style="color:var(--accent);">'.dol_escape_htmltag($obj->website).'</a>' : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
            </div>

            <div class="fb-card">
                <div class="fb-card-head"><h3>Banking Information</h3></div>
                <div class="data-row"><span class="data-lbl">Bank Name</span><span class="data-val"><?php echo $obj->bank_name ? dol_escape_htmltag($obj->bank_name) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
                <div class="data-row"><span class="data-lbl">Account Number</span><span class="data-val"><?php echo $obj->bank_account_number ? dol_escape_htmltag($obj->bank_account_number) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
            </div>

            <?php if (!empty($obj->description)) : ?>
            <div class="fb-card" style="background:#fffbeb;">
                <div class="fb-card-head" style="border-bottom-color:#fde68a;"><h3 style="color:#92400e;">Admin Notes</h3></div>
                <div style="padding:14px 22px; font-size:13px; color:#374151; line-height:1.6;"><?php echo nl2br(dol_escape_htmltag($obj->description)); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT -->
        <div>
            <div class="fb-card">
                <div class="fb-card-head"><h3>System Account</h3></div>
                <div class="data-row">
                    <span class="data-lbl">Login Status</span>
                    <span class="badge <?php echo $user_status_class; ?>"><?php echo $user_status_label; ?></span>
                </div>
                <?php if ($user_login) : ?>
                <div class="data-row">
                    <span class="data-lbl">Username</span>
                    <span class="data-val" style="font-family:monospace;"><?php echo dol_escape_htmltag($user_login); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($user_link) : ?>
                <a href="<?php echo $user_link; ?>" class="account-link" target="_blank">Manage Login Credentials →</a>
                <?php endif; ?>
            </div>

            <?php if ($obj->status == 'Active') : ?>
            <div class="fb-card">
                <div class="fb-card-head"><h3>Quick Actions</h3></div>
                <div style="padding:16px 22px; display:flex; flex-direction:column; gap:10px;">
                    <form method="POST" action="view_vendor.php?id=<?php echo $obj->rowid; ?>" onsubmit="return confirm('Disable this vendor?');" style="margin:0;">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn-reject" style="width:100%; justify-content:center;">Disable Vendor</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php llxFooter(); ?>
