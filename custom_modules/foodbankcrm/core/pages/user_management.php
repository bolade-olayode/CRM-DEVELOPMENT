<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

$langs->load("admin");

// Security check
if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('You need administrator rights to access this page.');
}

llxHeader('', 'User Role Management');

$action = GETPOST('action', 'alpha');
$notice = '';

// ============================================
// HANDLE ACTIONS
// ============================================

// Link user to vendor
if ($action == 'link_vendor' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $fk_user = GETPOST('fk_user', 'int');
        $fk_vendor = GETPOST('fk_vendor', 'int');
        
        if ($fk_user && $fk_vendor) {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_user_vendor (fk_user, fk_vendor, created_by) 
                    VALUES (".(int)$fk_user.", ".(int)$fk_vendor.", ".(int)$user->id.")";
            
            if ($db->query($sql)) {
                $notice = '<div class="ok">User successfully linked to vendor!</div>';
            } else {
                if ($db->errno() == 1062) { // Duplicate entry
                    $notice = '<div class="warning">This user is already linked to this vendor.</div>';
                } else {
                    $notice = '<div class="error">Error: '.$db->lasterror().'</div>';
                }
            }
        }
    }
}

// Link user to beneficiary
if ($action == 'link_beneficiary' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $fk_user = GETPOST('fk_user', 'int');
        $fk_beneficiary = GETPOST('fk_beneficiary', 'int');
        
        if ($fk_user && $fk_beneficiary) {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_user_beneficiary (fk_user, fk_beneficiary, created_by) 
                    VALUES (".(int)$fk_user.", ".(int)$fk_beneficiary.", ".(int)$user->id.")";
            
            if ($db->query($sql)) {
                $notice = '<div class="ok">User successfully linked to beneficiary!</div>';
            } else {
                if ($db->errno() == 1062) {
                    $notice = '<div class="warning">This user is already linked to this beneficiary.</div>';
                } else {
                    $notice = '<div class="error">Error: '.$db->lasterror().'</div>';
                }
            }
        }
    }
}

// Unlink user from vendor (CSRF protected)
if ($action == 'unlink_vendor') {
    if (!isset($_GET['token']) || $_GET['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed. Please try again.</div>';
    } else {
        $link_id = GETPOST('id', 'int');

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_user_vendor WHERE rowid = ".(int)$link_id;
        if ($db->query($sql)) {
            $notice = '<div class="ok">User unlinked from vendor.</div>';
        } else {
            $notice = '<div class="error">Error: '.$db->lasterror().'</div>';
        }
    }
}

// Unlink user from beneficiary (CSRF protected)
if ($action == 'unlink_beneficiary') {
    if (!isset($_GET['token']) || $_GET['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed. Please try again.</div>';
    } else {
        $link_id = GETPOST('id', 'int');

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_user_beneficiary WHERE rowid = ".(int)$link_id;
        if ($db->query($sql)) {
            $notice = '<div class="ok">User unlinked from beneficiary.</div>';
        } else {
            $notice = '<div class="error">Error: '.$db->lasterror().'</div>';
        }
    }
}

// ============================================
// FETCH DATA
// ============================================

// Get all active users
$sql = "SELECT rowid, login, lastname, firstname FROM ".MAIN_DB_PREFIX."user WHERE entity IN (0,1) ORDER BY login";
$res_users = $db->query($sql);
$users_list = array();
while ($obj = $db->fetch_object($res_users)) {
    $users_list[] = $obj;
}

// Get all vendors
$sql = "SELECT rowid, ref, name FROM ".MAIN_DB_PREFIX."foodbank_vendors ORDER BY name";
$res_vendors = $db->query($sql);
$vendors_list = array();
while ($obj = $db->fetch_object($res_vendors)) {
    $vendors_list[] = $obj;
}

// Get all beneficiaries
$sql = "SELECT rowid, ref, firstname, lastname FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries ORDER BY lastname";
$res_beneficiaries = $db->query($sql);
$beneficiaries_list = array();
while ($obj = $db->fetch_object($res_beneficiaries)) {
    $beneficiaries_list[] = $obj;
}

// Get existing vendor links
$sql = "SELECT uv.rowid, uv.fk_user, uv.fk_vendor, u.login, v.name as vendor_name, uv.date_created
        FROM ".MAIN_DB_PREFIX."foodbank_user_vendor uv
        LEFT JOIN ".MAIN_DB_PREFIX."user u ON uv.fk_user = u.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON uv.fk_vendor = v.rowid
        ORDER BY uv.date_created DESC";
$res_vendor_links = $db->query($sql);
$vendor_links = array();
while ($obj = $db->fetch_object($res_vendor_links)) {
    $vendor_links[] = $obj;
}

// Get existing beneficiary links
$sql = "SELECT ub.rowid, ub.fk_user, ub.fk_beneficiary, u.login, 
        CONCAT(b.firstname, ' ', b.lastname) as beneficiary_name, ub.date_created
        FROM ".MAIN_DB_PREFIX."foodbank_user_beneficiary ub
        LEFT JOIN ".MAIN_DB_PREFIX."user u ON ub.fk_user = u.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON ub.fk_beneficiary = b.rowid
        ORDER BY ub.date_created DESC";
$res_beneficiary_links = $db->query($sql);
$beneficiary_links = array();
while ($obj = $db->fetch_object($res_beneficiary_links)) {
    $beneficiary_links[] = $obj;
}

// ============================================
// RENDER UI
// ============================================

print '<style>
.fb-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
.fb-card-header { font-size: 16px; font-weight: 600; margin-bottom: 15px; color: #333; }
.form-row { display: flex; gap: 15px; align-items: end; margin-bottom: 15px; }
.form-group { flex: 1; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px; }
.small-table { width: 100%; border-collapse: collapse; }
.small-table th, .small-table td { padding: 10px 8px; border-bottom: 1px solid #eee; text-align: left; font-size: 13px; }
.small-table th { background: #f9f9f9; font-weight: 600; }
</style>';

print $notice;

print '<h1>👥 User Role Management</h1>';
print '<p style="color: #666; margin-bottom: 30px;">Link Dolibarr users to vendors or beneficiaries to give them dashboard access.</p>';

// Two column layout
print '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">';

// Link User to Vendor
print '<div class="fb-card">';
print '<div class="fb-card-header">🏢 Link User to Vendor</div>';

print '<form method="POST" action="'.basename(__FILE__).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="link_vendor">';

print '<div class="form-group">';
print '<label>Select User <span style="color: red;">*</span></label>';
print '<select name="fk_user" class="flat" required style="width: 100%;">';
print '<option value="">-- Choose User --</option>';
foreach ($users_list as $u) {
    $display_name = $u->login;
    if ($u->firstname || $u->lastname) {
        $display_name .= ' ('.$u->firstname.' '.$u->lastname.')';
    }
    print '<option value="'.$u->rowid.'">'.dol_escape_htmltag($display_name).'</option>';
}
print '</select>';
print '</div>';

print '<div class="form-group" style="margin-top: 15px;">';
print '<label>Select Vendor <span style="color: red;">*</span></label>';
print '<select name="fk_vendor" class="flat" required style="width: 100%;">';
print '<option value="">-- Choose Vendor --</option>';
foreach ($vendors_list as $v) {
    print '<option value="'.$v->rowid.'">'.dol_escape_htmltag($v->ref.' - '.$v->name).'</option>';
}
print '</select>';
print '</div>';

print '<div style="margin-top: 20px;">';
print '<button type="submit" class="button">Link User to Vendor</button>';
print '</div>';

print '</form>';
print '</div>';

// Link User to Beneficiary
print '<div class="fb-card">';
print '<div class="fb-card-header">👤 Link User to Beneficiary</div>';

print '<form method="POST" action="'.basename(__FILE__).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="link_beneficiary">';

print '<div class="form-group">';
print '<label>Select User <span style="color: red;">*</span></label>';
print '<select name="fk_user" class="flat" required style="width: 100%;">';
print '<option value="">-- Choose User --</option>';
foreach ($users_list as $u) {
    $display_name = $u->login;
    if ($u->firstname || $u->lastname) {
        $display_name .= ' ('.$u->firstname.' '.$u->lastname.')';
    }
    print '<option value="'.$u->rowid.'">'.dol_escape_htmltag($display_name).'</option>';
}
print '</select>';
print '</div>';

print '<div class="form-group" style="margin-top: 15px;">';
print '<label>Select Beneficiary <span style="color: red;">*</span></label>';
print '<select name="fk_beneficiary" class="flat" required style="width: 100%;">';
print '<option value="">-- Choose Beneficiary --</option>';
foreach ($beneficiaries_list as $b) {
    print '<option value="'.$b->rowid.'">'.dol_escape_htmltag($b->ref.' - '.$b->firstname.' '.$b->lastname).'</option>';
}
print '</select>';
print '</div>';

print '<div style="margin-top: 20px;">';
print '<button type="submit" class="button">Link User to Beneficiary</button>';
print '</div>';

print '</form>';
print '</div>';

print '</div>';

// Existing Links
print '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">';

// Vendor Links Table
print '<div class="fb-card">';
print '<div class="fb-card-header">Current Vendor Links ('.count($vendor_links).')</div>';

if (count($vendor_links) > 0) {
    print '<table class="small-table">';
    print '<thead><tr><th>User</th><th>Vendor</th><th>Linked On</th><th>Action</th></tr></thead>';
    print '<tbody>';
    
    foreach ($vendor_links as $link) {
        print '<tr>';
        print '<td>'.dol_escape_htmltag($link->login).'</td>';
        print '<td>'.dol_escape_htmltag($link->vendor_name).'</td>';
        print '<td>'.dol_print_date($db->jdate($link->date_created), 'day').'</td>';
        print '<td><a href="user_management.php?action=unlink_vendor&id='.$link->rowid.'&token='.newToken().'" onclick="return confirm(\'Unlink this user?\');" style="color: #dc3545;">Unlink</a></td>';
        print '</tr>';
    }
    
    print '</tbody></table>';
} else {
    print '<p style="color: #999;">No vendor links yet.</p>';
}

print '</div>';

// Beneficiary Links Table
print '<div class="fb-card">';
print '<div class="fb-card-header">Current Beneficiary Links ('.count($beneficiary_links).')</div>';

if (count($beneficiary_links) > 0) {
    print '<table class="small-table">';
    print '<thead><tr><th>User</th><th>Beneficiary</th><th>Linked On</th><th>Action</th></tr></thead>';
    print '<tbody>';
    
    foreach ($beneficiary_links as $link) {
        print '<tr>';
        print '<td>'.dol_escape_htmltag($link->login).'</td>';
        print '<td>'.dol_escape_htmltag($link->beneficiary_name).'</td>';
        print '<td>'.dol_print_date($db->jdate($link->date_created), 'day').'</td>';
        print '<td><a href="user_management.php?action=unlink_beneficiary&id='.$link->rowid.'&token='.newToken().'" onclick="return confirm(\'Unlink this user?\');" style="color: #dc3545;">Unlink</a></td>';
        print '</tr>';
    }
    
    print '</tbody></table>';
} else {
    print '<p style="color: #999;">No beneficiary links yet.</p>';
}

print '</div>';

print '</div>';

// Instructions
print '<div class="fb-card" style="background: #e3f2fd; border-left: 4px solid #2196f3; margin-top: 20px;">';
print '<h3 style="margin-top: 0;">📖 How It Works</h3>';
print '<ol style="margin: 0; padding-left: 20px;">';
print '<li><strong>Create a Dolibarr user</strong> (Setup → Users) with appropriate permissions</li>';
print '<li><strong>Link the user</strong> to a vendor or beneficiary using the forms above</li>';
print '<li><strong>Grant permissions</strong>: Setup → Users → Edit User → Permissions → Foodbank CRM</li>';
print '<li><strong>User logs in</strong> and gets redirected to their role-specific dashboard</li>';
print '</ol>';
print '</div>';

llxFooter();
?>