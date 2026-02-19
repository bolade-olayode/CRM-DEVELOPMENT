<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// Security check
if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

llxHeader('', 'Delete Subscription Tier');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Tier ID is missing.</div>';
    print '<div><a href="subscription_tiers.php">← Back to Subscription Tiers</a></div>';
    llxFooter(); exit;
}

$tier_id = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // CONFIRMATION PAGE
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE rowid = ".$tier_id;
    $res = $db->query($sql);
    $tier = $db->fetch_object($res);
    
    if (!$tier) {
        print '<div class="error">Tier not found.</div>';
        print '<div><a href="subscription_tiers.php">← Back</a></div>';
        llxFooter(); exit;
    }
    
    // Check how many subscribers use this tier
    $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries 
            WHERE subscription_type = '".$db->escape($tier->tier_type)."'";
    $res = $db->query($sql);
    $subscriber_count = $db->fetch_object($res)->count ?? 0;
    
    print '<div class="warning" style="padding: 20px; border: 2px solid #f57c00; background: #fff3e0; margin-bottom: 20px;">';
    print '<h3 style="margin-top: 0;">⚠️ Confirm Subscription Tier Deletion</h3>';
    print '<p><strong>Tier Name:</strong> '.dol_escape_htmltag($tier->tier_name).'</p>';
    print '<p><strong>Type:</strong> '.dol_escape_htmltag($tier->tier_type).'</p>';
    print '<p><strong>Price:</strong> ₦'.number_format($tier->price, 2).'</p>';
    print '<p><strong>Duration:</strong> '.$tier->duration_months.' months</p>';
    print '<p><strong>Current Subscribers:</strong> '.$subscriber_count.'</p>';
    
    if ($subscriber_count > 0) {
        print '<div class="error" style="margin-top: 15px; padding: 15px; background: #ffebee; border: 2px solid #d32f2f;">';
        print '<h4 style="margin-top: 0; color: #d32f2f;">❌ Cannot Delete This Tier</h4>';
        print '<p>This subscription tier has <strong>'.$subscriber_count.' active subscriber(s)</strong>.</p>';
        print '<p>To delete this tier, you must first:</p>';
        print '<ul>';
        print '<li>Cancel or migrate all subscribers to a different tier, OR</li>';
        print '<li>Mark the tier as "Inactive" instead of deleting it</li>';
        print '</ul>';
        print '<p><strong>This protection ensures subscriber data integrity.</strong></p>';
        print '</div>';
        print '<br><a class="button" href="subscription_tiers.php">← Back to Subscription Tiers</a>';
        print '</div>';
    } else {
        print '<p style="color: #d32f2f; font-weight: bold; margin-top: 20px;">This action cannot be undone.</p>';
        print '</div>';
        
        print '<form method="POST" action="'.basename(__FILE__).'?id='.$tier_id.'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input class="button butActionDelete" type="submit" name="confirm" value="Yes, Delete This Tier">';
        print ' <a class="button" href="subscription_tiers.php">Cancel</a>';
        print '</form>';
    }
} else {
    // PROCESS DELETION
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        print '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        // Double-check no subscribers
        $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries 
                WHERE subscription_type IN (SELECT tier_type FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE rowid = ".$tier_id.")";
        $res = $db->query($sql);
        $count = $db->fetch_object($res)->count ?? 0;
        
        if ($count > 0) {
            print '<div class="error">Cannot delete: This tier has active subscribers.</div>';
        } else {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE rowid = ".$tier_id;
            if ($db->query($sql)) {
                print '<div class="ok">Subscription tier deleted successfully!</div>';
            } else {
                print '<div class="error">Error deleting tier: '.$db->lasterror().'</div>';
            }
        }
        print '<div><a href="subscription_tiers.php">← Back to Subscription Tiers</a></div>';
    }
}

llxFooter();
?>