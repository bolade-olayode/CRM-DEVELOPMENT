<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

$langs->load("admin");

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('You need administrator rights to access this page.');
}

$action = GETPOST('action', 'alpha');
$notice = '';

// ============================================================
// HANDLE ACTIONS
// ============================================================

// Link user to vendor
if ($action == 'link_vendor' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed: invalid CSRF token.'];
    } else {
        $fk_user   = (int)GETPOST('fk_user', 'int');
        $fk_vendor = (int)GETPOST('fk_vendor', 'int');
        if ($fk_user && $fk_vendor) {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_user_vendor (fk_user, fk_vendor, created_by)
                    VALUES (".$fk_user.", ".$fk_vendor.", ".(int)$user->id.")";
            if ($db->query($sql)) {
                $notice = ['success', 'User successfully linked to vendor. They can now log in to the vendor dashboard.'];
            } elseif ($db->errno() == 1062) {
                $notice = ['warning', 'This user is already linked to that vendor.'];
            } else {
                $notice = ['error', 'Error: '.$db->lasterror()];
            }
        }
    }
}

// Link user to beneficiary
if ($action == 'link_beneficiary' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed: invalid CSRF token.'];
    } else {
        $fk_user        = (int)GETPOST('fk_user', 'int');
        $fk_beneficiary = (int)GETPOST('fk_beneficiary', 'int');
        if ($fk_user && $fk_beneficiary) {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_user_beneficiary (fk_user, fk_beneficiary, created_by)
                    VALUES (".$fk_user.", ".$fk_beneficiary.", ".(int)$user->id.")";
            if ($db->query($sql)) {
                $notice = ['success', 'User successfully linked to beneficiary. They can now log in to the subscriber dashboard.'];
            } elseif ($db->errno() == 1062) {
                $notice = ['warning', 'This user is already linked to that beneficiary.'];
            } else {
                $notice = ['error', 'Error: '.$db->lasterror()];
            }
        }
    }
}

// Unlink vendor
if ($action == 'unlink_vendor') {
    if (!isset($_GET['token']) || $_GET['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed.'];
    } else {
        $link_id = (int)GETPOST('id', 'int');
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_user_vendor WHERE rowid = ".$link_id;
        if ($db->query($sql)) {
            $notice = ['success', 'User unlinked from vendor. They will lose vendor dashboard access.'];
        } else {
            $notice = ['error', 'Error: '.$db->lasterror()];
        }
    }
}

// Unlink beneficiary
if ($action == 'unlink_beneficiary') {
    if (!isset($_GET['token']) || $_GET['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed.'];
    } else {
        $link_id = (int)GETPOST('id', 'int');
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_user_beneficiary WHERE rowid = ".$link_id;
        if ($db->query($sql)) {
            $notice = ['success', 'User unlinked from beneficiary. They will lose subscriber dashboard access.'];
        } else {
            $notice = ['error', 'Error: '.$db->lasterror()];
        }
    }
}

// ============================================================
// FETCH DATA
// ============================================================

$sql = "SELECT rowid, login, lastname, firstname FROM ".MAIN_DB_PREFIX."user WHERE entity IN (0,1) ORDER BY login";
$res_users = $db->query($sql);
$users_list = [];
while ($obj = $db->fetch_object($res_users)) $users_list[] = $obj;

$sql = "SELECT rowid, ref, name FROM ".MAIN_DB_PREFIX."foodbank_vendors ORDER BY name";
$res_vendors = $db->query($sql);
$vendors_list = [];
while ($obj = $db->fetch_object($res_vendors)) $vendors_list[] = $obj;

$sql = "SELECT rowid, ref, firstname, lastname FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries ORDER BY lastname";
$res_bens = $db->query($sql);
$bens_list = [];
while ($obj = $db->fetch_object($res_bens)) $bens_list[] = $obj;

$sql = "SELECT uv.rowid, u.login, v.name AS vendor_name, uv.date_created
        FROM ".MAIN_DB_PREFIX."foodbank_user_vendor uv
        LEFT JOIN ".MAIN_DB_PREFIX."user u ON uv.fk_user = u.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON uv.fk_vendor = v.rowid
        ORDER BY uv.date_created DESC";
$res_vl = $db->query($sql);
$vendor_links = [];
while ($obj = $db->fetch_object($res_vl)) $vendor_links[] = $obj;

$sql = "SELECT ub.rowid, u.login,
        CONCAT(b.firstname, ' ', b.lastname) AS ben_name, ub.date_created
        FROM ".MAIN_DB_PREFIX."foodbank_user_beneficiary ub
        LEFT JOIN ".MAIN_DB_PREFIX."user u ON ub.fk_user = u.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON ub.fk_beneficiary = b.rowid
        ORDER BY ub.date_created DESC";
$res_bl = $db->query($sql);
$ben_links = [];
while ($obj = $db->fetch_object($res_bl)) $ben_links[] = $obj;

$_SESSION["mainmenu"] = "foodbankcrm";
$_fb_admin_head = '<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">'
              . '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/css/admin_mobile.css">';
llxHeader($_fb_admin_head, 'User Role Management');
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

/* Page header */
.fb-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.fb-page-header h1 { margin: 0; font-size: 24px; font-weight: 800; color: #1e293b; }
.fb-page-header p  { margin: 4px 0 0; color: #64748b; font-size: 14px; }

.btn-ghost { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none; border: 1px solid #e2e8f0; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; }

/* Explainer banner */
.explainer { background: linear-gradient(135deg, #4f46e5 0%, #6d28d9 100%); border-radius: var(--radius); padding: 24px 28px; margin-bottom: 28px; color: #fff; }
.explainer h2 { margin: 0 0 10px; font-size: 17px; font-weight: 700; }
.explainer p  { margin: 0 0 16px; font-size: 13px; opacity: .9; line-height: 1.6; }
.steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.step { background: rgba(255,255,255,.15); border-radius: 8px; padding: 14px 16px; }
.step-num { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .6px; opacity: .7; margin-bottom: 4px; }
.step-text { font-size: 13px; font-weight: 500; line-height: 1.4; }

/* Notice */
.fb-notice { padding: 13px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
.fb-notice.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.fb-notice.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.fb-notice.warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

/* Two-col grid */
.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }

/* Cards */
.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
.fb-card-head { padding: 18px 22px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px; }
.fb-card-head h3 { margin: 0; font-size: 15px; font-weight: 700; color: #1e293b; }
.fb-card-head .count-chip { background: var(--accent-light); color: var(--accent-dark); padding: 2px 9px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.fb-card-body { padding: 20px 22px; }

/* Form elements */
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
.form-note  { font-size: 11px; color: #94a3b8; margin-top: 4px; }
.form-select { width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; font-size: 13px; font-family: var(--font); outline: none; background: #f8fafc; color: #374151; box-sizing: border-box; }
.form-select:focus { border-color: var(--accent); background: #fff; box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
.btn-submit { width: 100%; background: var(--accent); color: #fff; border: none; border-radius: 8px; padding: 11px 20px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: var(--font); transition: background .2s; margin-top: 4px; }
.btn-submit:hover { background: var(--accent-dark); }

/* Links table */
.links-table { width: 100%; border-collapse: collapse; }
.links-table th { text-align: left; padding: 10px 14px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
.links-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #374151; }
.links-table tr:last-child td { border-bottom: none; }
.links-table tr:hover td { background: #fafbff; }

.user-login  { font-weight: 600; color: #1e293b; font-family: monospace; font-size: 13px; }
.target-name { color: #374151; }
.date-text   { font-size: 12px; color: #94a3b8; }
.unlink-btn  { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; text-decoration: none; color: #ef4444 !important; border: 1px solid #fecaca; background: #fff; transition: all .15s; }
.unlink-btn:hover { background: #fef2f2; }

.empty-note { padding: 28px 20px; text-align: center; color: #94a3b8; font-size: 13px; }
</style>

<div class="fb-wrap">

    <!-- Page Header -->
    <div class="fb-page-header">
        <div>
            <h1>👥 User Role Management</h1>
            <p>Connect Dolibarr login accounts to vendor or beneficiary profiles</p>
        </div>
        <div>
            <a href="dashboard_admin.php" class="btn-ghost">← Dashboard</a>
        </div>
    </div>

    <?php if ($notice) : ?>
    <div class="fb-notice <?php echo $notice[0]; ?>"><?php echo $notice[1]; ?></div>
    <?php endif; ?>

    <!-- Explainer Banner -->
    <div class="explainer">
        <h2>What does this page do?</h2>
        <p>
            When a vendor or subscriber registers, their <strong>profile</strong> (in the Foodbank CRM database) gets created automatically.
            But their <strong>login account</strong> (a Dolibarr user) must also be connected to that profile so the system knows
            which dashboard to show them when they log in. This page manages those connections.
        </p>
        <div class="steps">
            <div class="step">
                <div class="step-num">Step 1</div>
                <div class="step-text">Vendor or subscriber registers — profile is created</div>
            </div>
            <div class="step">
                <div class="step-num">Step 2</div>
                <div class="step-text">A Dolibarr login account is created (auto or manually)</div>
            </div>
            <div class="step">
                <div class="step-num">Step 3</div>
                <div class="step-text">Use this page to link the account to the correct profile</div>
            </div>
            <div class="step">
                <div class="step-num">Step 4</div>
                <div class="step-text">User logs in and is redirected to their dashboard</div>
            </div>
        </div>
    </div>

    <!-- Link Forms -->
    <div class="two-col">

        <!-- Link to Vendor -->
        <div class="fb-card">
            <div class="fb-card-head">
                <span>🏢</span>
                <h3>Link User → Vendor</h3>
            </div>
            <div class="fb-card-body">
                <p style="font-size:13px; color:#64748b; margin:0 0 18px;">
                    Gives a Dolibarr login account access to a vendor's dashboard (manage inventory, track orders, etc.).
                </p>
                <form method="POST" action="<?php echo basename(__FILE__); ?>">
                    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                    <input type="hidden" name="action" value="link_vendor">

                    <div class="form-group">
                        <label class="form-label">Dolibarr Login Account <span style="color:#ef4444;">*</span></label>
                        <select name="fk_user" class="form-select" required>
                            <option value="">— Select a user —</option>
                            <?php foreach ($users_list as $u) :
                                $display = $u->login;
                                if ($u->firstname || $u->lastname) $display .= ' ('.$u->firstname.' '.$u->lastname.')';
                            ?>
                            <option value="<?php echo $u->rowid; ?>"><?php echo dol_escape_htmltag($display); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-note">The user who will log in (from Setup → Users)</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Vendor Profile <span style="color:#ef4444;">*</span></label>
                        <select name="fk_vendor" class="form-select" required>
                            <option value="">— Select a vendor —</option>
                            <?php foreach ($vendors_list as $v) : ?>
                            <option value="<?php echo $v->rowid; ?>"><?php echo dol_escape_htmltag($v->ref.' – '.$v->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-note">The vendor profile this user will manage</div>
                    </div>

                    <button type="submit" class="btn-submit">🔗 Link to Vendor</button>
                </form>
            </div>
        </div>

        <!-- Link to Beneficiary -->
        <div class="fb-card">
            <div class="fb-card-head">
                <span>👤</span>
                <h3>Link User → Subscriber</h3>
            </div>
            <div class="fb-card-body">
                <p style="font-size:13px; color:#64748b; margin:0 0 18px;">
                    Gives a Dolibarr login account access to a beneficiary's dashboard (browse packages, place orders, etc.).
                </p>
                <form method="POST" action="<?php echo basename(__FILE__); ?>">
                    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                    <input type="hidden" name="action" value="link_beneficiary">

                    <div class="form-group">
                        <label class="form-label">Dolibarr Login Account <span style="color:#ef4444;">*</span></label>
                        <select name="fk_user" class="form-select" required>
                            <option value="">— Select a user —</option>
                            <?php foreach ($users_list as $u) :
                                $display = $u->login;
                                if ($u->firstname || $u->lastname) $display .= ' ('.$u->firstname.' '.$u->lastname.')';
                            ?>
                            <option value="<?php echo $u->rowid; ?>"><?php echo dol_escape_htmltag($display); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-note">The user who will log in (from Setup → Users)</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Subscriber Profile <span style="color:#ef4444;">*</span></label>
                        <select name="fk_beneficiary" class="form-select" required>
                            <option value="">— Select a subscriber —</option>
                            <?php foreach ($bens_list as $b) : ?>
                            <option value="<?php echo $b->rowid; ?>"><?php echo dol_escape_htmltag($b->ref.' – '.$b->firstname.' '.$b->lastname); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-note">The beneficiary profile this user will access</div>
                    </div>

                    <button type="submit" class="btn-submit">🔗 Link to Subscriber</button>
                </form>
            </div>
        </div>

    </div><!-- end two-col -->

    <!-- Current Links -->
    <div class="two-col">

        <!-- Vendor Links -->
        <div class="fb-card">
            <div class="fb-card-head">
                <h3>Active Vendor Links</h3>
                <span class="count-chip"><?php echo count($vendor_links); ?></span>
            </div>
            <?php if (!empty($vendor_links)) : ?>
            <table class="links-table">
                <thead>
                    <tr>
                        <th>Login</th>
                        <th>Vendor</th>
                        <th>Linked On</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($vendor_links as $link) : ?>
                    <tr>
                        <td><span class="user-login"><?php echo dol_escape_htmltag($link->login); ?></span></td>
                        <td><span class="target-name"><?php echo dol_escape_htmltag($link->vendor_name); ?></span></td>
                        <td><span class="date-text"><?php echo dol_print_date($db->jdate($link->date_created), 'day'); ?></span></td>
                        <td>
                            <a href="user_management.php?action=unlink_vendor&id=<?php echo $link->rowid; ?>&token=<?php echo newToken(); ?>"
                               class="unlink-btn"
                               onclick="return confirm('Remove access for <?php echo dol_escape_js($link->login); ?>?')">✕ Unlink</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else : ?>
            <div class="empty-note">No vendor links yet.</div>
            <?php endif; ?>
        </div>

        <!-- Beneficiary Links -->
        <div class="fb-card">
            <div class="fb-card-head">
                <h3>Active Subscriber Links</h3>
                <span class="count-chip"><?php echo count($ben_links); ?></span>
            </div>
            <?php if (!empty($ben_links)) : ?>
            <table class="links-table">
                <thead>
                    <tr>
                        <th>Login</th>
                        <th>Subscriber</th>
                        <th>Linked On</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ben_links as $link) : ?>
                    <tr>
                        <td><span class="user-login"><?php echo dol_escape_htmltag($link->login); ?></span></td>
                        <td><span class="target-name"><?php echo dol_escape_htmltag($link->ben_name); ?></span></td>
                        <td><span class="date-text"><?php echo dol_print_date($db->jdate($link->date_created), 'day'); ?></span></td>
                        <td>
                            <a href="user_management.php?action=unlink_beneficiary&id=<?php echo $link->rowid; ?>&token=<?php echo newToken(); ?>"
                               class="unlink-btn"
                               onclick="return confirm('Remove access for <?php echo dol_escape_js($link->login); ?>?')">✕ Unlink</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else : ?>
            <div class="empty-note">No subscriber links yet.</div>
            <?php endif; ?>
        </div>

    </div><!-- end two-col -->

</div><!-- end fb-wrap -->

<?php
llxFooter();
?>
