<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// Security check - Admin only
if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

llxHeader('', 'Subscription Tiers');

$action = GETPOST('action', 'alpha');
$notice = '';

// Handle delete (CSRF protected)
if ($action == 'delete' && GETPOST('id', 'int')) {
    if (!isset($_GET['token']) || $_GET['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed. Please try again.</div>';
    } else {
        $id = GETPOST('id', 'int');

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE rowid = ".(int)$id;
        if ($db->query($sql)) {
            $notice = '<div class="ok">Subscription tier deleted successfully!</div>';
        } else {
            $notice = '<div class="error">Error deleting tier: '.$db->lasterror().'</div>';
        }
    }
}

// Handle activate/deactivate (CSRF protected)
if ($action == 'toggle' && GETPOST('id', 'int')) {
    if (!isset($_GET['token']) || $_GET['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed. Please try again.</div>';
    } else {
        $id = GETPOST('id', 'int');

        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_subscription_tiers
                SET is_active = IF(is_active = 1, 0, 1)
                WHERE rowid = ".(int)$id;

        if ($db->query($sql)) {
            $notice = '<div class="ok">Subscription tier status updated!</div>';
        } else {
            $notice = '<div class="error">Error: '.$db->lasterror().'</div>';
        }
    }
}

print $notice;

print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
print '<h1>💳 Subscription Tiers</h1>';
print '<a class="butAction" href="create_subscription_tier.php">+ Create Tier</a>';
print '</div>';

// Fetch all tiers
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers ORDER BY price ASC";
$res = $db->query($sql);

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>Tier Name</th>';
print '<th>Type</th>';
print '<th>Duration</th>';
print '<th>Price</th>';
print '<th>Benefits</th>';
print '<th>Status</th>';
print '<th class="center">Actions</th>';
print '</tr>';

if ($res && $db->num_rows($res) > 0) {
    while ($obj = $db->fetch_object($res)) {
        $status_color = $obj->is_active ? '#4caf50' : '#999';
        $status_bg = $obj->is_active ? '#e8f5e9' : '#f5f5f5';
        $status_text = $obj->is_active ? 'Active' : 'Inactive';
        
        print '<tr class="oddeven">';
        print '<td><strong>'.dol_escape_htmltag($obj->tier_name).'</strong></td>';
        print '<td>'.dol_escape_htmltag($obj->tier_type).'</td>';
        print '<td>'.$obj->duration_months.' months</td>';
        print '<td><strong>₦'.number_format($obj->price, 2).'</strong></td>';
        print '<td>'.dol_trunc(dol_escape_htmltag($obj->benefits), 50).'</td>';
        print '<td>';
        print '<span style="display:inline-block; padding:4px 10px; border-radius:4px; background:'.$status_bg.'; color:'.$status_color.'; font-weight:bold; font-size:11px;">';
        print $status_text;
        print '</span>';
        print '</td>';
        print '<td class="center">';
        print '<a href="edit_subscription_tier.php?id='.$obj->rowid.'">Edit</a> | ';
        print '<a href="subscription_tiers.php?action=toggle&id='.$obj->rowid.'&token='.newToken().'">'.($obj->is_active ? 'Deactivate' : 'Activate').'</a> | ';
        print '<a href="delete_subscription_tier.php?id='.$obj->rowid.'" style="color: #dc3545;">Delete</a>';
        print '</td>';
        print '</tr>';
    }
} else {
    print '<tr><td colspan="7" class="center" style="padding: 30px; color: #999;">';
    print 'No subscription tiers found. <a href="create_subscription_tier.php">Create your first tier!</a>';
    print '</td></tr>';
}

print '</table>';

print '<div style="margin-top: 30px; background: #e3f2fd; padding: 20px; border-radius: 5px; border-left: 4px solid #2196f3;">';
print '<h3 style="margin-top: 0;">💡 About Subscription Tiers</h3>';
print '<p>Subscription tiers define membership plans that users can purchase to access your platform.</p>';
print '<ul>';
print '<li><strong>Annual:</strong> Full-year membership (recommended for regular users)</li>';
print '<li><strong>Donor:</strong> Premium tier that supports the foodbank mission</li>';
print '<li><strong>Guest:</strong> Short-term or trial membership</li>';
print '</ul>';
print '</div>';

llxFooter();
?>