<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Warehouse ID is missing in the URL.</div>';
    print '<div><a href="warehouses.php">← Back to Warehouses</a></div>';
    llxFooter(); exit;
}

$id = (int) $_GET['id'];

// Fetch warehouse
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_warehouses WHERE rowid = ".$id;
$resql = $db->query($sql);
$wh = $db->fetch_object($resql);

if (!$wh) {
    print '<div class="error">Warehouse not found.</div>';
    print '<div><a href="warehouses.php">← Back to Warehouses</a></div>';
    llxFooter(); exit;
}

$notice = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $ref = $db->escape(GETPOST('ref','alpha'));
        $label = $db->escape(GETPOST('label','alpha'));
        $address = $db->escape(GETPOST('address','restricthtml'));
        $capacity = (int) GETPOST('capacity','int');

        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_warehouses 
                SET ref='".$ref."',
                    label='".$label."',
                    address='".$address."',
                    capacity=".$capacity."
                WHERE rowid=".$id;
        
        $res = $db->query($sql);
        $notice = $res ? '<div class="ok">Warehouse updated successfully!</div>'
                       : '<div class="error">Update failed: '.$db->lasterror().'</div>';
    }
}

print $notice;
print '<div><a href="warehouses.php">← Back to Warehouses</a></div><br>';
?>

<h2>Edit Warehouse</h2>
<form method="POST" action="<?php echo basename(__FILE__).'?id='.$id; ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">
  Ref: <input class="flat" type="text" name="ref" value="<?php echo dol_escape_htmltag($wh->ref); ?>" required><br>
  Label: <input class="flat" type="text" name="label" value="<?php echo dol_escape_htmltag($wh->label); ?>" required><br>
  Address: <textarea class="flat" name="address"><?php echo dol_escape_htmltag($wh->address); ?></textarea><br>
  Capacity: <input class="flat" type="number" name="capacity" value="<?php echo $wh->capacity; ?>" min="0"><br>
  <input class="button" type="submit" value="Update Warehouse">
</form>

<?php llxFooter(); ?>