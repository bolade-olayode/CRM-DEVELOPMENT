<?php
// delete_warehouses.php - FINAL VERSION

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Warehouse ID is missing.</div>';
    print '<div><a href="warehouses.php">Back to Warehouses</a></div>';
    llxFooter(); exit;
}

$id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_warehouses WHERE rowid = ".$id;
    $resql = $db->query($sql);
    if (!$resql || !$obj = $db->fetch_object($resql)) {
        print '<div class="error">Warehouse not found.</div>';
        print '<div><a href="warehouses.php">Back to Warehouses</a></div>';
        llxFooter(); exit;
    }

    // Pre-check: does this warehouse have any distributions?
    $r_dist = $db->query("SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE fk_warehouse = ".$id);
    $dist_count = $r_dist ? (int)$db->fetch_object($r_dist)->c : 0;

    if ($dist_count > 0) {
        // Block deletion and show prominent warning
        print '<div style="background:#fff3cd; border:3px solid #f59e0b; border-radius:12px; padding:30px; margin-bottom:20px;">';
        print '<div style="font-size:48px; text-align:center; margin-bottom:15px;">⚠️</div>';
        print '<h2 style="color:#92400e; text-align:center; margin:0 0 15px;">This Warehouse Contains Goods</h2>';
        print '<p style="font-size:15px; color:#78350f; text-align:center; margin:0 0 20px;">';
        print '<strong>'.dol_escape_htmltag($obj->label).'</strong> (Ref: '.dol_escape_htmltag($obj->ref).')';
        print ' is linked to <strong>'.$dist_count.' distribution(s)</strong>.</p>';
        print '<div style="background:#fef3c7; border-radius:8px; padding:20px; margin-bottom:20px;">';
        print '<p style="font-weight:700; color:#92400e; margin:0 0 10px;">Before you can delete this warehouse, you must:</p>';
        print '<ol style="color:#78350f; margin:0; padding-left:20px; line-height:2;">';
        print '<li>Go to each linked distribution and reassign it to a different warehouse</li>';
        print '<li>Or move all goods to another warehouse first</li>';
        print '<li>Once no distributions are linked, you can safely delete it</li>';
        print '</ol>';
        print '</div>';
        print '<p style="text-align:center; color:#92400e; font-size:13px; margin:0;">Deleting a warehouse that contains active distributions would break your records and cannot be done safely.</p>';
        print '</div>';
        print '<div style="text-align:center;">';
        print '<a href="warehouses.php" class="button">← Back to Warehouses</a>';
        print '</div>';
    } else {
        // No linked records — show standard confirm
        print '<div style="background:#fee2e2; border:3px solid #ef4444; border-radius:12px; padding:30px; margin-bottom:20px;">';
        print '<div style="font-size:48px; text-align:center; margin-bottom:15px;">🗑️</div>';
        print '<h2 style="color:#991b1b; text-align:center; margin:0 0 15px;">Confirm Warehouse Deletion</h2>';
        print '<p style="text-align:center; color:#7f1d1d; font-size:15px; margin:0 0 20px;">You are about to permanently delete the following warehouse. This action cannot be undone.</p>';
        print '<table style="width:100%; max-width:400px; margin:0 auto 20px; border-collapse:collapse;">';
        print '<tr><td style="padding:8px 12px; font-weight:600; color:#991b1b;">Ref:</td><td style="padding:8px 12px;">'.dol_escape_htmltag($obj->ref).'</td></tr>';
        print '<tr style="background:#fef2f2;"><td style="padding:8px 12px; font-weight:600; color:#991b1b;">Name:</td><td style="padding:8px 12px;">'.dol_escape_htmltag($obj->label).'</td></tr>';
        print '<tr><td style="padding:8px 12px; font-weight:600; color:#991b1b;">Address:</td><td style="padding:8px 12px;">'.dol_escape_htmltag($obj->address ?: '—').'</td></tr>';
        print '</table>';
        print '</div>';

        print '<div style="text-align:center;">';
        print '<form method="POST" action="'.basename(__FILE__).'?id='.$id.'" style="display:inline;">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input class="button butActionDelete" type="submit" value="Yes, permanently delete this warehouse">';
        print '</form>';
        print ' <a class="button" href="warehouses.php" style="margin-left:10px;">Cancel</a>';
        print '</div>';
    }

} else {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        print '<div class="error">Invalid CSRF token.</div>';
    } else {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_warehouses WHERE rowid = ".$id;
        $resql = $db->query($sql);

        if ($resql && $db->affected_rows($db->db) > 0) {
            print '<div class="ok">Warehouse deleted successfully!</div>';
        } else {
            $error = $db->lasterror();
            
            print '<div class="error" style="padding:25px; background:#ffebee; border:3px solid #c62828; border-radius:12px; font-size:16px; line-height:1.6;">';
            print '<strong>CANNOT DELETE THIS WAREHOUSE</strong><br><br>';
            
            if (strpos($error, 'foreign key constraint') !== false || 
                strpos($error, 'Cannot delete or update a parent row') !== false) {
                
                print '<strong>This warehouse is in use and cannot be deleted.</strong><br><br>';
                print 'It contains:<br>';
                print '• Food distributions to beneficiaries<br>';
                print '• Active stock and inventory records<br><br>';
                print 'Deleting it would break your entire system.<br><br>';
                print 'What to do instead:<br>';
                print '1. Create a new warehouse<br>';
                print '2. Move all stock and reassign distributions<br>';
                print '3. Mark this one as "Inactive" (recommended)<br><br>';
                print 'This protection is permanent and cannot be disabled.';
                
            } else {
                print 'Technical error: '.dol_escape_htmltag($error);
            }
            print '</div>';
        }
        print '<div style="margin-top:25px; text-align:center;">';
        print '<a href="warehouses.php" class="button">Back to Warehouses</a>';
        print '</div>';
    }
}

llxFooter();
?>