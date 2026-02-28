<?php
/**
 * ADMIN: Subscriber Profile View
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$id = GETPOST('id', 'int');
if (!$id) accessforbidden();

$object = new Beneficiary($db);
if ($object->fetch($id) < 1) accessforbidden();

// Resolve linked Dolibarr user (fk_user first, email fallback)
$user_login        = '';
$user_status_label = 'No Login Account';
$user_status_class = 'badge-default';
$user_link         = '';

if (!empty($object->fk_user)) {
    $u_res = $db->query("SELECT rowid, login, statut FROM ".MAIN_DB_PREFIX."user WHERE rowid = ".(int)$object->fk_user);
    if ($u_res && ($u_obj = $db->fetch_object($u_res))) {
        $user_login        = $u_obj->login;
        $user_status_label = $u_obj->statut == 1 ? 'Login Enabled' : 'Login Disabled';
        $user_status_class = $u_obj->statut == 1 ? 'badge-active' : 'badge-expired';
        $user_link         = DOL_URL_ROOT.'/user/card.php?id='.$u_obj->rowid;
    }
} elseif ($object->email) {
    $u_res = $db->query("SELECT rowid, login, statut FROM ".MAIN_DB_PREFIX."user WHERE email = '".$db->escape($object->email)."' LIMIT 1");
    if ($u_res && ($u_obj = $db->fetch_object($u_res))) {
        $user_login        = $u_obj->login;
        $user_status_label = $u_obj->statut == 1 ? 'Login Enabled' : 'Login Disabled';
        $user_status_class = $u_obj->statut == 1 ? 'badge-active' : 'badge-expired';
        $user_link         = DOL_URL_ROOT.'/user/card.php?id='.$u_obj->rowid;
    }
}

$order_count = (int)$db->fetch_object($db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE fk_beneficiary = ".(int)$id))->c;

$name         = trim($object->firstname.' '.$object->lastname);
$initial      = strtoupper(mb_substr($object->firstname ?: $object->lastname ?: '?', 0, 1));
$status       = !empty($object->subscription_status) ? $object->subscription_status : 'Pending';
$status_class = $status == 'Active' ? 'badge-active' : ($status == 'Expired' ? 'badge-expired' : 'badge-pending');

llxHeader('', 'Subscriber Profile');
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
.profile-avatar { width: 64px; height: 64px; border-radius: 16px; background: var(--accent); color: #fff; font-size: 28px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; margin-right: 18px; flex-shrink: 0; }
.profile-name h1 { margin: 0 0 6px; font-size: 22px; font-weight: 800; color: #1e293b; }
.profile-name .meta { font-size: 13px; color: #94a3b8; }

.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none !important; transition: background .2s; }
.btn-primary:hover { background: var(--accent-dark); text-decoration: none !important; }
.btn-ghost  { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none !important; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; text-decoration: none !important; }

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
.data-val { font-size: 14px; color: #1e293b; font-weight: 600; text-align: right; }

.badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
.badge-active  { background: #dcfce7; color: #15803d; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-expired { background: #fee2e2; color: #991b1b; }
.badge-default { background: #f1f5f9; color: #475569; }

.status-center { text-align: center; padding: 24px 22px; border-bottom: 1px solid #f1f5f9; }
.plan-name { font-size: 20px; font-weight: 800; color: #1e293b; margin: 14px 0 4px; }
.plan-sub  { font-size: 12px; color: #94a3b8; }

.fb-card.notes-card { background: #fffbeb; }
.fb-card.notes-card .fb-card-head { border-bottom-color: #fde68a; }
.fb-card.notes-card .fb-card-head h3 { color: #92400e; }
.notes-text { padding: 14px 22px; font-size: 13px; color: #374151; line-height: 1.6; }

.account-link { display: block; text-align: center; padding: 12px; font-size: 13px; color: var(--accent); text-decoration: none !important; font-weight: 600; border-top: 1px solid #f1f5f9; transition: background .15s; }
.account-link:hover { background: #f8fafc; text-decoration: none !important; }
</style>

<div class="fb-wrap">

    <!-- Header -->
    <div class="fb-page-header">
        <div class="profile-meta">
            <div class="profile-avatar"><?php echo $initial; ?></div>
            <div class="profile-name">
                <h1><?php echo dol_escape_htmltag($name); ?> &nbsp;<span class="badge <?php echo $status_class; ?>"><?php echo $status; ?></span></h1>
                <span class="meta">Ref: <strong><?php echo dol_escape_htmltag($object->ref); ?></strong> &nbsp;&middot;&nbsp; Joined: <?php echo dol_print_date($db->jdate($object->date_creation), 'day'); ?></span>
            </div>
        </div>
        <div style="flex-shrink:0; margin-top:4px;">
            <a href="beneficiaries.php" class="btn-ghost">← Back to List</a>
            <a href="edit_beneficiary.php?id=<?php echo $object->id; ?>" class="btn-primary">✏️ Edit Profile</a>
        </div>
    </div>

    <!-- Meta Strip -->
    <div class="meta-strip">
        <div class="meta-item">Email: <strong><?php echo $object->email ? dol_escape_htmltag($object->email) : '—'; ?></strong></div>
        <span class="meta-sep">·</span>
        <div class="meta-item">Phone: <strong><?php echo $object->phone ? dol_escape_htmltag($object->phone) : '—'; ?></strong></div>
        <span class="meta-sep">·</span>
        <div class="meta-item">Family: <strong><?php echo (int)$object->family_size > 0 ? (int)$object->family_size.' members' : '1 member'; ?></strong></div>
        <span class="meta-sep">·</span>
        <div class="meta-item">Orders: <strong><?php echo $order_count; ?></strong></div>
    </div>

    <!-- Profile Grid -->
    <div class="profile-grid">

        <!-- LEFT -->
        <div>
            <div class="fb-card">
                <div class="fb-card-head"><h3>Personal Information</h3></div>
                <div class="data-row"><span class="data-lbl">Email Address</span><span class="data-val"><?php echo $object->email ? dol_escape_htmltag($object->email) : '<span style="color:#cbd5e1">Not provided</span>'; ?></span></div>
                <div class="data-row"><span class="data-lbl">Phone Number</span><span class="data-val"><?php echo $object->phone ? dol_escape_htmltag($object->phone) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
                <div class="data-row"><span class="data-lbl">Gender</span><span class="data-val"><?php echo $object->gender ? dol_escape_htmltag($object->gender) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
                <div class="data-row"><span class="data-lbl">Date of Birth</span><span class="data-val"><?php echo $object->dob ? dol_print_date($object->dob, 'day') : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
                <div class="data-row"><span class="data-lbl">NIN / ID Number</span><span class="data-val"><?php echo $object->identification_number ? dol_escape_htmltag($object->identification_number) : '<span style="color:#cbd5e1">Not provided</span>'; ?></span></div>
            </div>

            <div class="fb-card">
                <div class="fb-card-head"><h3>Address & Location</h3></div>
                <div class="data-row"><span class="data-lbl">Street Address</span><span class="data-val"><?php echo $object->address ? dol_escape_htmltag($object->address) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
                <div class="data-row"><span class="data-lbl">City / LGA</span><span class="data-val"><?php echo $object->city ? dol_escape_htmltag($object->city) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
                <div class="data-row"><span class="data-lbl">State</span><span class="data-val"><?php echo $object->state ? dol_escape_htmltag($object->state) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
            </div>

            <div class="fb-card">
                <div class="fb-card-head"><h3>Household & Employment</h3></div>
                <div class="data-row"><span class="data-lbl">Family Size</span><span class="data-val"><?php echo (int)$object->family_size > 0 ? (int)$object->family_size.' Members' : '1 Member'; ?></span></div>
                <div class="data-row"><span class="data-lbl">Employment Status</span><span class="data-val"><?php echo $object->employment_status ? dol_escape_htmltag($object->employment_status) : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
            </div>

            <?php if (!empty($object->note)) : ?>
            <div class="fb-card notes-card">
                <div class="fb-card-head"><h3>Admin Notes</h3></div>
                <div class="notes-text"><?php echo nl2br(dol_escape_htmltag($object->note)); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT -->
        <div>
            <div class="fb-card">
                <div class="fb-card-head"><h3>Subscription</h3></div>
                <div class="status-center">
                    <span class="badge <?php echo $status_class; ?>" style="font-size:13px; padding:8px 20px;"><?php echo $status; ?></span>
                    <div class="plan-name"><?php echo $object->subscription_type ? dol_escape_htmltag($object->subscription_type) : 'No Plan'; ?></div>
                    <div class="plan-sub">Current Subscription Plan</div>
                </div>
                <div class="data-row"><span class="data-lbl">Start Date</span><span class="data-val"><?php echo $object->subscription_start_date ? dol_print_date($object->subscription_start_date, 'day') : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
                <div class="data-row"><span class="data-lbl">Renewal Due</span><span class="data-val"><?php echo $object->subscription_end_date ? dol_print_date($object->subscription_end_date, 'day') : '<span style="color:#cbd5e1">—</span>'; ?></span></div>
                <div class="data-row"><span class="data-lbl">Total Orders</span><span class="data-val"><?php echo $order_count; ?></span></div>
            </div>

            <div class="fb-card">
                <div class="fb-card-head"><h3>System Account</h3></div>
                <div class="data-row"><span class="data-lbl">Login Status</span><span class="badge <?php echo $user_status_class; ?>"><?php echo $user_status_label; ?></span></div>
                <?php if ($user_login) : ?>
                <div class="data-row"><span class="data-lbl">Username</span><span class="data-val" style="font-family:monospace;"><?php echo dol_escape_htmltag($user_login); ?></span></div>
                <?php endif; ?>
                <?php if ($user_link) : ?>
                <a href="<?php echo $user_link; ?>" class="account-link" target="_blank">Manage Login Credentials →</a>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<?php llxFooter(); ?>
