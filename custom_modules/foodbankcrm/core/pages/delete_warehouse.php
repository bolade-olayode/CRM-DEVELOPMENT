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

    print '<div class="warning">';
    print '<p><strong>Are you sure you want to delete this warehouse?</strong></p>';
    print '<p><strong>Ref:</strong> '.dol_escape_htmltag($obj->ref).'</p>';
    print '<p><strong>Name:</strong> '.dol_escape_htmltag($obj->label).'</p>';
    print '<p><strong>Address:</strong> '.dol_escape_htmltag($obj->address ?: '—').'</p>';
    print '</div>';

    print '<form method="POST" action="'.basename(__FILE__).'?id='.$id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input class="button butActionDelete" type="submit" value="Yes, delete">';
    print ' <a class="button" href="warehouses.php">Cancel</a>';
    print '</form>';

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