<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/warehouse.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader();

$notice = '';
if ($_SERVER['REQUEST_METHOD']=='POST') {
  if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
    $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
  } else {
    $wh = new Warehouse($db);
    $wh->ref = GETPOST('ref','alpha'); // Will auto-generate if empty
    $wh->label = GETPOST('label','alpha');
    $wh->address = GETPOST('address','restricthtml');
    $wh->capacity = GETPOST('capacity','int');
    
    $result = $wh->create($user);
    
    if ($result > 0) {
      $notice = '<div class="ok">Warehouse created successfully! Ref: '.$wh->ref.' (ID: '.$result.')</div>';
    } else {
      $notice = '<div class="error">Error creating warehouse: '.$wh->error.'</div>';
    }
  }
}

print $notice;
print '<div><a href="warehouses.php">← Back to Warehouses</a></div><br>';
?>

<h2>Create Warehouse</h2>
<form method="POST" action="<?php echo basename(__FILE__); ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">
  <table class="border centpercent">
    <tr>
      <td width="25%">Ref</td>
      <td><input class="flat" type="text" name="ref" placeholder="Leave empty for auto-generation (WAR2025-0001)"></td>
    </tr>
    <tr>
      <td><span class="fieldrequired">Label</span></td>
      <td><input class="flat" type="text" name="label" required></td>
    </tr>
    <tr>
      <td>Address</td>
      <td><textarea class="flat" name="address" rows="3" cols="50"></textarea></td>
    </tr>
    <tr>
      <td>Capacity</td>
      <td><input class="flat" type="number" name="capacity" min="0" value="0"></td>
    </tr>
  </table>
  <br>
  <div class="center">
    <input class="button" type="submit" value="Create Warehouse">
    <a class="button" href="warehouses.php">Cancel</a>
  </div>
</form>
<?php llxFooter(); ?>