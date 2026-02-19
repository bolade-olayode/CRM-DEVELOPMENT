<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/warehouse.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader();

print '<p><a class="butAction" href="create_warehouse.php">+ ADD WAREHOUSE</a></p>';

$sql = "SELECT rowid, ref, label, address, capacity
        FROM ".MAIN_DB_PREFIX."foodbank_warehouses
        ORDER BY ref";
$res = $db->query($sql);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><th>Ref</th><th>Label</th><th>Address</th><th>Capacity</th><th>Actions</th></tr>';
if ($res) {
  while ($o = $db->fetch_object($res)) {
    print '<tr class="oddeven">';
    print '<td>'.dol_escape_htmltag($o->ref).'</td>';
    print '<td>'.dol_escape_htmltag($o->label).'</td>';
    print '<td>'.dol_escape_htmltag($o->address).'</td>';
    print '<td>'.(int)$o->capacity.'</td>';
    print '<td><a href="edit_warehouse.php?id='.$o->rowid.'">Edit</a> | <a href="delete_warehouse.php?id='.$o->rowid.'">Delete</a></td>';
    print '</tr>';
  }
}
print '</table>';

llxFooter();
