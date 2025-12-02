<?php
/**
 * Admin - Manage Subscription Tiers
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

$langs->load("admin");

// Check admin permission
if (!$user->admin) {
    accessforbidden('Admin access required');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        setEventMessages('Security check failed', null, 'errors');
    } else {
        $action = GETPOST('action', 'alpha');
        
        if ($action == 'update') {
            $tier_id = GETPOST('tier_id', 'int');
            $price = GETPOST('price', 'int');
            $duration = GETPOST('duration', 'int');
            $description = GETPOST('description', 'restricthtml');
            
            $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_subscription_tiers 
                    SET price = ".(float)$price.",
                        duration_days = ".(int)$duration.",
                        description = '".$db->escape($description)."'
                    WHERE rowid = ".(int)$tier_id;
            
            if ($db->query($sql)) {
                setEventMessages('Tier updated successfully', null, 'mesgs');
            } else {
                setEventMessages('Error: '.$db->lasterror(), null, 'errors');
            }
        }
    }
}

llxHeader('', 'Subscription Tiers');

print '<div class="fiche">';
print '<h1>💳 Manage Subscription Tiers</h1>';

// Get all tiers
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers ORDER BY price ASC";
$res = $db->query($sql);

if ($res) {
    print '<table class="border centpercent">';
    print '<tr class="liste_titre">';
    print '<th>Plan Name</th>';
    print '<th>Type</th>';
    print '<th>Price (₦)</th>';
    print '<th>Duration (days)</th>';
    print '<th>Max Orders/Month</th>';
    print '<th>Description</th>';
    print '<th class="center">Actions</th>';
    print '</tr>';
    
    while ($tier = $db->fetch_object($res)) {
        print '<tr class="oddeven">';
        print '<td><strong>'.dol_escape_htmltag($tier->tier_name).'</strong></td>';
        print '<td>'.dol_escape_htmltag($tier->tier_type).'</td>';
        print '<td><strong>₦'.number_format($tier->price, 0).'</strong></td>';
        print '<td>'.floor($tier->duration_days).' ('.floor($tier->duration_days/365).' year(s))</td>';
        print '<td>'.($tier->max_orders_per_month ?: 'Unlimited').'</td>';
        print '<td>'.dol_escape_htmltag($tier->description).'</td>';
        print '<td class="center">';
        print '<a href="edit_subscription_tier.php?id='.$tier->rowid.'" class="butAction">Edit</a>';
        print '</td>';
        print '</tr>';
    }
    
    print '</table>';
}

print '</div>';

llxFooter();
?>
