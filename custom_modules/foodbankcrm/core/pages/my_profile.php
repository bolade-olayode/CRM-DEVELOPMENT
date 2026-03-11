<?php
/**
 * My Profile - VIEW & EDIT
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

if (isset($_SESSION['foodbank_checked'])) $_SESSION['foodbank_checked'] = false;

$langs->load("admin");

if (!FoodbankPermissions::isBeneficiary($user, $db)) {
    accessforbidden('You do not have access to profile.');
}

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);
$subscriber = $db->fetch_object($res);

$mode   = GETPOST('mode', 'alpha');
$action = GETPOST('action', 'alpha');
$msg    = '';
$msg_type = '';

if ($action == 'update') {
    // CSRF check: verify the hidden token in the form matches the one in the session.
    // This ensures the request genuinely came from our form, not from a malicious website.
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['newtoken']) {
        $msg      = 'Security check failed. Please refresh the page and try again.';
        $msg_type = 'error';
        $mode     = 'edit';
        // Skip the update — fall through to page render
    } else {

    $firstname      = GETPOST('firstname', 'alpha');
    $lastname       = GETPOST('lastname', 'alpha');
    $email          = GETPOST('email', 'alpha');
    $phone          = GETPOST('phone', 'alpha');
    $address        = GETPOST('address', 'restricthtml');
    $household_size = GETPOST('household_size', 'int');

    $sql_update = "UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET
                   firstname = '".$db->escape($firstname)."',
                   lastname = '".$db->escape($lastname)."',
                   email = '".$db->escape($email)."',
                   phone = '".$db->escape($phone)."',
                   address = '".$db->escape($address)."',
                   household_size = ".(int)$household_size."
                   WHERE rowid = ".(int)$subscriber->rowid;

    if ($db->query($sql_update)) {
        $msg = 'Profile updated successfully!';
        $msg_type = 'success';
        $res = $db->query($sql);
        $subscriber = $db->fetch_object($res);
        $mode = 'view';
    } else {
        $msg = 'Error updating profile: '.$db->lasterror();
        $msg_type = 'error';
        $mode = 'edit';
    }
    } // end else (CSRF passed)
}

// Cart count for nav badge
$sql_cart_cnt = "SELECT COUNT(*) as cnt FROM ".MAIN_DB_PREFIX."foodbank_cart WHERE fk_subscriber = ".(int)$subscriber->rowid;
$cart_cnt_obj = $db->fetch_object($db->query($sql_cart_cnt));
$cart_count   = $cart_cnt_obj ? (int)$cart_cnt_obj->cnt : 0;

// Days left on subscription
$sub_days_left = 0;
if ($subscriber->subscription_end_date) {
    $sub_days_left = max(0, (int)floor((strtotime($subscriber->subscription_end_date) - time()) / 86400));
}

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'My Profile');
?>
<link rel="stylesheet" href="<?php echo DOL_URL_ROOT; ?>/custom/foodbankcrm/css/responsive.css">
<style>
    #id-top,.side-nav,.side-nav-vert,#id-left,.login_block,.tmenudiv,.nav-bar,header{display:none!important;width:0!important;height:0!important;pointer-events:none!important}
    html,body{background:#f0f4f8!important;margin:0!important;padding:0!important;width:100%!important;overflow-x:hidden!important;font-family:'Segoe UI',system-ui,sans-serif}
    #id-container,#id-right,.id-right{margin:0!important;padding:0!important;width:100vw!important;max-width:100vw!important;display:block!important}
    .fiche{width:100%!important;max-width:100%!important;margin:0!important}
    *{box-sizing:border-box}
    body{padding-top:64px!important}

    /* NAV */
    .fb-nav{position:fixed;top:0;left:0;right:0;height:64px;background:linear-gradient(90deg,#0f766e,#0d9488);display:flex;align-items:center;justify-content:space-between;padding:0 28px;z-index:9999;box-shadow:0 2px 16px rgba(0,0,0,.18)}
    .fb-nav-brand{font-size:17px;font-weight:800;color:#fff;text-decoration:none;display:flex;align-items:center;gap:9px}
    .fb-nav-links{display:flex;gap:2px}
    @media(max-width:768px){
        .fb-nav-links{display:none!important;position:fixed;top:64px;left:0;right:0;flex-direction:column;background:linear-gradient(180deg,#0f766e,#0d9488);padding:6px 0 12px;z-index:9998;box-shadow:0 8px 24px rgba(0,0,0,.25);gap:0}
        .fb-nav-links.fb-open{display:flex!important}
    }
    .fb-nav-link{color:rgba(255,255,255,.82);text-decoration:none;font-size:13px;font-weight:500;padding:7px 14px;border-radius:20px;transition:background .15s}
    .fb-nav-link:hover,.fb-nav-link.active{background:rgba(255,255,255,.18);color:#fff}
    .fb-nav-right{display:flex;align-items:center;gap:14px}
    .fb-logout{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.35);padding:6px 16px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600}
    .cart-badge{background:#ef4444;color:#fff;font-size:10px;font-weight:800;padding:2px 6px;border-radius:10px;margin-left:2px}

    /* PAGE */
    .page-wrap{max-width:1050px;margin:0 auto;padding:36px 24px 60px}

    /* HERO */
    .profile-hero{background:linear-gradient(135deg,#134e4a,#0d9488);border-radius:16px;padding:32px;margin-bottom:28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px}
    .avatar{width:72px;height:72px;background:rgba(255,255,255,.2);border:3px solid rgba(255,255,255,.4);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800;color:#fff;flex-shrink:0}
    .hero-text h1{margin:0 0 5px;font-size:22px;font-weight:800;color:#fff}
    .hero-text p{margin:0;color:rgba(255,255,255,.72);font-size:13px}
    .hero-actions{display:flex;gap:10px;flex-wrap:wrap}
    .btn-edit-hero{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.35);padding:8px 18px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600;transition:all .2s}
    .btn-edit-hero:hover{background:rgba(255,255,255,.25)}

    /* GRID */
    .profile-grid{display:grid;grid-template-columns:2fr 1fr;gap:24px;align-items:start}
    @media(max-width:768px){.profile-grid{grid-template-columns:1fr}}

    /* CARDS */
    .card{background:#fff;border-radius:14px;box-shadow:0 4px 16px rgba(0,0,0,.06);border:1px solid #e8edf2;overflow:hidden;margin-bottom:20px}
    .card-header{padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between}
    .card-header h3{margin:0;font-size:15px;font-weight:700;color:#1e293b}
    .card-body{padding:22px 24px}

    /* VIEW MODE */
    .field-row{margin-bottom:18px}
    .field-row:last-child{margin-bottom:0}
    .field-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:5px}
    .field-value{font-size:15px;font-weight:600;color:#1e293b}
    .field-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 24px}
    @media(max-width:500px){.field-grid{grid-template-columns:1fr}}

    /* EDIT MODE */
    .form-group{margin-bottom:16px}
    .form-label{display:block;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;margin-bottom:6px}
    .form-control{width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;color:#1e293b;transition:border-color .15s;font-family:inherit}
    .form-control:focus{outline:none;border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.1)}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}
    @media(max-width:500px){.form-grid{grid-template-columns:1fr}}
    .btn-save{background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;border:none;padding:11px 24px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;transition:all .2s}
    .btn-save:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(13,148,136,.3)}
    .btn-cancel{background:#f1f5f9;color:#475569;border:none;padding:11px 20px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}

    /* SIDEBAR CARDS */
    .account-card{background:linear-gradient(135deg,#0f766e,#0d9488);border-radius:14px;padding:22px;color:#fff;margin-bottom:20px}
    .account-card h3{margin:0 0 16px;font-size:14px;font-weight:700;opacity:.8;text-transform:uppercase;letter-spacing:.5px}
    .acct-row{margin-bottom:12px}
    .acct-lbl{font-size:11px;opacity:.7;margin-bottom:2px}
    .acct-val{font-size:15px;font-weight:700}
    .sub-status-card{background:#fff;border-radius:14px;box-shadow:0 4px 16px rgba(0,0,0,.06);border:1px solid #e8edf2;padding:22px;margin-bottom:20px}
    .sub-status-card h3{margin:0 0 14px;font-size:14px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
    .sub-plan{font-size:20px;font-weight:800;color:#1e293b;margin-bottom:8px}
    .sub-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px}
    .sub-active{background:#d1fae5;color:#065f46}
    .sub-expired{background:#fee2e2;color:#991b1b}
    .sub-days{font-size:12px;color:#64748b;margin-top:6px}
    .btn-renew{display:block;width:100%;padding:10px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:13px;font-weight:700;border:none;border-radius:8px;cursor:pointer;text-align:center;text-decoration:none;margin-top:14px;transition:all .2s}
    .btn-renew:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(16,185,129,.3)}
    .security-card{background:#fff;border-radius:14px;box-shadow:0 4px 16px rgba(0,0,0,.06);border:1px solid #e8edf2;padding:22px;text-align:center}
    .security-card p{font-size:13px;color:#64748b;margin:0 0 14px}
    .btn-pw{display:block;padding:10px;background:#f1f5f9;color:#475569;font-size:13px;font-weight:600;border-radius:8px;text-decoration:none;transition:all .15s}
    .btn-pw:hover{background:#e2e8f0}

    /* ALERTS */
    .alert{padding:14px 18px;border-radius:10px;margin-bottom:22px;font-size:14px;font-weight:500}
    .alert-success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
    .alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
</style>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var btn = document.getElementById('fb-hamburger');
    var links = document.querySelector('.fb-nav-links');
    if (!btn || !links) return;
    var closeBtn = document.createElement('button');
    closeBtn.className = 'fb-nav-close-btn';
    closeBtn.innerHTML = '&#10005;&nbsp;&nbsp;Close';
    closeBtn.addEventListener('click', function(){
        links.classList.remove('fb-open');
        btn.classList.remove('open');
    });
    links.insertBefore(closeBtn, links.firstChild);
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
// NAV
print '<nav class="fb-nav">';
print '<a href="dashboard_beneficiary.php" class="fb-nav-brand">🥦 FoodbankCRM</a>';
print '<div class="fb-nav-links">';
print '<a href="product_catalog.php" class="fb-nav-link">Packages</a>';
$badge = $cart_count > 0 ? '<span class="cart-badge">'.$cart_count.'</span>' : '';
print '<a href="view_cart.php" class="fb-nav-link">My Cart'.$badge.'</a>';
print '<a href="my_orders.php" class="fb-nav-link">Orders</a>';
print '<a href="my_profile.php" class="fb-nav-link active">Profile</a>';
print '</div>';
print '<div class="fb-nav-right">';
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="fb-logout">🚪 Logout</a>';
print '</div>';
print '<button class="fb-nav-hamburger" id="fb-hamburger" aria-label="Toggle menu"><span></span><span></span><span></span></button>';
print '</nav>';

print '<div class="page-wrap">';

// ALERTS
if ($msg) {
    $cls = ($msg_type == 'success') ? 'alert-success' : 'alert-error';
    print '<div class="alert '.$cls.'">'.($msg_type == 'success' ? '✅ ' : '❌ ').htmlspecialchars($msg).'</div>';
}

// HERO
$initials = strtoupper(substr($subscriber->firstname, 0, 1).substr($subscriber->lastname, 0, 1));
print '<div class="profile-hero">';
print '<div style="display:flex;align-items:center;gap:18px">';
print '<div class="avatar">'.$initials.'</div>';
print '<div class="hero-text">';
print '<h1>'.dol_escape_htmltag($subscriber->firstname.' '.$subscriber->lastname).'</h1>';
print '<p>'.dol_escape_htmltag($subscriber->email).' · Subscriber</p>';
print '</div></div>';
print '<div class="hero-actions">';
if ($mode == 'edit') {
    print '<a href="my_profile.php" class="btn-edit-hero">✕ Cancel</a>';
} else {
    print '<a href="my_profile.php?mode=edit" class="btn-edit-hero">✏️ Edit Profile</a>';
}
print '</div>';
print '</div>';

print '<div class="profile-grid">';

// LEFT: Personal details
print '<div>';
print '<div class="card">';
print '<div class="card-header"><h3>Personal Details</h3>';
if ($mode != 'edit') {
    print '<a href="my_profile.php?mode=edit" style="font-size:12px;color:#0d9488;text-decoration:none;font-weight:600;">Edit →</a>';
}
print '</div>';
print '<div class="card-body">';

if ($mode == 'edit') {
    print '<form method="POST" action="my_profile.php">';
    print '<input type="hidden" name="action" value="update">';
    // Hidden CSRF token — generated fresh each page load, checked on submission.
    // An attacker's fake form won't have this token, so their request is rejected.
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<div class="form-grid">';
    print '<div class="form-group"><label class="form-label">First Name</label><input type="text" name="firstname" class="form-control" value="'.dol_escape_htmltag($subscriber->firstname).'" required></div>';
    print '<div class="form-group"><label class="form-label">Last Name</label><input type="text" name="lastname" class="form-control" value="'.dol_escape_htmltag($subscriber->lastname).'" required></div>';
    print '</div>';
    print '<div class="form-group"><label class="form-label">Email Address</label><input type="email" name="email" class="form-control" value="'.dol_escape_htmltag($subscriber->email).'" required></div>';
    print '<div class="form-group"><label class="form-label">Phone Number</label><input type="text" name="phone" class="form-control" value="'.dol_escape_htmltag($subscriber->phone).'"></div>';
    print '<div class="form-group"><label class="form-label">Home Address</label><textarea name="address" rows="3" class="form-control">'.dol_escape_htmltag($subscriber->address).'</textarea></div>';
    print '<div class="form-group"><label class="form-label">Household Size</label><input type="number" name="household_size" class="form-control" value="'.(int)$subscriber->household_size.'" min="1"></div>';
    print '<div style="display:flex;gap:10px;margin-top:6px">';
    print '<button type="submit" class="btn-save">💾 Save Changes</button>';
    print '<a href="my_profile.php" class="btn-cancel">Cancel</a>';
    print '</div>';
    print '</form>';
} else {
    print '<div class="field-grid">';
    print '<div class="field-row"><div class="field-label">First Name</div><div class="field-value">'.dol_escape_htmltag($subscriber->firstname).'</div></div>';
    print '<div class="field-row"><div class="field-label">Last Name</div><div class="field-value">'.dol_escape_htmltag($subscriber->lastname).'</div></div>';
    print '</div>';
    print '<div class="field-row"><div class="field-label">Email</div><div class="field-value">'.dol_escape_htmltag($subscriber->email).'</div></div>';
    print '<div class="field-row"><div class="field-label">Phone</div><div class="field-value">'.($subscriber->phone ? dol_escape_htmltag($subscriber->phone) : '<span style="color:#cbd5e1">Not set</span>').'</div></div>';
    print '<div class="field-row"><div class="field-label">Home Address</div><div class="field-value">'.($subscriber->address ? nl2br(dol_escape_htmltag($subscriber->address)) : '<span style="color:#cbd5e1">Not set</span>').'</div></div>';
    print '<div class="field-row"><div class="field-label">Household Size</div><div class="field-value">'.(int)$subscriber->household_size.' member'.((int)$subscriber->household_size != 1 ? 's' : '').'</div></div>';
}

print '</div></div>'; // card-body, card

print '</div>'; // left col

// RIGHT COLUMN
print '<div>';

// Account Info
print '<div class="account-card">';
print '<h3>📋 Account Info</h3>';
print '<div class="acct-row"><div class="acct-lbl">Subscriber ID</div><div class="acct-val">'.dol_escape_htmltag($subscriber->ref).'</div></div>';
print '<div class="acct-row"><div class="acct-lbl">Joined</div><div class="acct-val">'.dol_print_date($db->jdate($subscriber->registration_date), 'day').'</div></div>';
print '<div class="acct-row"><div class="acct-lbl">Username</div><div class="acct-val">'.dol_escape_htmltag($user->login).'</div></div>';
print '</div>';

// Subscription
$sub_status   = $subscriber->subscription_status;
$badge_class  = ($sub_status == 'Active') ? 'sub-active' : 'sub-expired';
$badge_icon   = ($sub_status == 'Active') ? '✅' : '❌';
print '<div class="sub-status-card">';
print '<h3>💳 Subscription</h3>';
print '<div class="sub-plan">'.dol_escape_htmltag($subscriber->subscription_type ?: 'Standard').'</div>';
print '<div><span class="sub-badge '.$badge_class.'">'.$badge_icon.' '.dol_escape_htmltag($sub_status).'</span></div>';
if ($subscriber->subscription_end_date) {
    if ($sub_days_left > 0) {
        print '<div class="sub-days">'.$sub_days_left.' days remaining · expires '.dol_print_date($db->jdate($subscriber->subscription_end_date), 'day').'</div>';
    } else {
        print '<div class="sub-days" style="color:#dc2626">Expired on '.dol_print_date($db->jdate($subscriber->subscription_end_date), 'day').'</div>';
    }
}
print '<a href="renew_subscription.php" class="btn-renew">'.($sub_status == 'Active' ? '🔄 Manage Plan' : '⚡ Renew Now').'</a>';
print '</div>';

// Security
print '<div class="security-card">';
print '<div style="font-size:32px;margin-bottom:10px">🔐</div>';
print '<p>Keep your account secure with a strong password.</p>';
print '<a href="subscriber_password.php" class="btn-pw">Change Password</a>';
print '</div>';

print '</div>'; // right col
print '</div>'; // grid
print '</div>'; // page-wrap

llxFooter();
?>
