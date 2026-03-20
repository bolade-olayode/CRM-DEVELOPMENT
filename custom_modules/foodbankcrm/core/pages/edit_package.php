<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/package.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/packageitem.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
$_SESSION["mainmenu"] = "foodbankcrm";
$_fb_admin_head = '<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">'
              . '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/css/admin_mobile.css">';
llxHeader($_fb_admin_head, 'Edit Package');

if (!isset($_GET['id'])) { header("Location: packages.php"); exit; }
$id = (int)$_GET['id'];

// CSS (Reuse same clean style)
print '<style>div#id-top, #id-top { display: none !important; } .side-nav { top: 0 !important; height: 100vh !important; } #id-right { padding-top: 30px !important; } .fb-container { max-width: 1000px; margin: 0 auto; padding: 0 20px; } .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 40px; border: 1px solid #eee; } .form-group { margin-bottom: 15px; } .form-group label { display: block; font-weight: 600; font-size: 13px; color: #444; margin-bottom: 5px; } .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; } .item-table { width: 100%; border-collapse: collapse; margin-top: 10px; } .item-table th { background: #f8f9fa; text-align: left; padding: 10px; font-size: 12px; color: #666; border-bottom: 2px solid #eee; } .item-table td { padding: 10px; border-bottom: 1px solid #eee; } .item-input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }</style>';

$p = new Package($db);
$p->fetch($id);

$notice = '';
$hide_form = false;

// Handle Item Deletion (CSRF protected)
if (isset($_GET['del_item'])) {
    if (!isset($_GET['token']) || $_GET['token'] != $_SESSION['newtoken']) {
        setEventMessages("Security check failed. Please try again.", null, 'errors');
    } else {
        $item = new PackageItem($db);
        $item->id = (int)$_GET['del_item'];
        $item->delete($user);
    }
    header("Location: edit_package.php?id=".$id); exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $p->name = $_POST['name'];
    $p->description = $_POST['description'];
    $p->status = $_POST['status'];
    
    if ($p->update($user) > 0) {
        // Add New Items
        if (!empty($_POST['product_name'])) {
            foreach ($_POST['product_name'] as $k => $name) {
                if (empty(trim($name))) continue;
                $item = new PackageItem($db);
                $item->fk_package = $id;
                $item->product_name = $name;
                $item->quantity = $_POST['quantity'][$k];
                $item->unit = $_POST['unit'][$k];
                $item->unit_price = $_POST['unit_price'][$k];
                $item->create($user);
            }
        }
        
        $notice = '<div class="ok" style="padding:20px; background:#d4edda; border:1px solid #c3e6cb; border-radius:8px; color:#155724; text-align:center; margin-bottom:20px;">
                    <div style="font-size:40px; margin-bottom:10px;">✅</div>
                    <strong>Package Updated Successfully!</strong><br><br>
                    <a href="packages.php" class="button" style="background:#28a745; color:white; padding:10px 20px;">Return to List</a>
                    <a href="edit_package.php?id='.$id.'" class="button" style="background:#eee; color:#333; margin-left:10px;">Edit Again</a>
                   </div>';
        $hide_form = true;
    } else {
        $notice = '<div class="error">Update failed.</div>';
    }
}

// Fetch Existing Items
$items = PackageItem::getAllByPackage($db, $id);

print '<div class="fb-container">';

if (!$hide_form) {
    print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
    print '<div><h1 style="margin: 0;">✏️ Edit Package</h1><p style="color:#888; margin: 5px 0 0 0;">Ref: <strong>'.$p->ref.'</strong></p></div>';
    print '<div><a href="packages.php" class="button" style="background:#eee; color:#333;">Cancel</a></div>';
    print '</div>';
}

print $notice;

if (!$hide_form) {
    print '<div class="fb-card">';
    print '<form method="POST" action="'.basename(__FILE__).'?id='.$id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';

    print '<div class="form-group"><label>Package Name</label><input type="text" name="name" value="'.dol_escape_htmltag($p->name).'" required></div>';
    print '<div class="form-group"><label>Description</label><textarea name="description" rows="2">'.dol_escape_htmltag($p->description).'</textarea></div>';
    print '<div class="form-group"><label>Status</label><select name="status"><option value="Active"'.($p->status=='Active'?' selected':'').'>Active</option><option value="Inactive"'.($p->status=='Inactive'?' selected':'').'>Inactive</option></select></div>';

    print '<h3 style="margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px;">📦 Existing Items</h3>';
    
    if (count($items) > 0) {
        print '<table class="item-table">';
        print '<thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Price</th><th>Action</th></tr></thead><tbody>';
        foreach($items as $i) {
            print '<tr>';
            print '<td>'.dol_escape_htmltag($i->product_name).'</td>';
            print '<td>'.number_format($i->quantity).'</td>';
            print '<td>'.$i->unit.'</td>';
            print '<td>₦'.number_format($i->unit_price,2).'</td>';
            print '<td><a href="edit_package.php?id='.$id.'&del_item='.$i->rowid.'&token='.newToken().'" style="color:red; font-size:12px;" onclick="return confirm(\'Remove this item?\')">Remove</a></td>';
            print '</tr>';
        }
        print '</tbody></table>';
    } else {
        print '<p style="color:#999; font-style:italic;">No items yet.</p>';
    }

    print '<h3 style="margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px;">+ Add New Items</h3>';
    
    print '<table class="item-table" id="itemTable">';
    print '<thead><tr><th>Product Name</th><th width="15%">Qty</th><th width="15%">Unit</th><th width="15%">Price (₦)</th><th width="5%"></th></tr></thead>';
    print '<tbody id="itemBody">';
    print '<tr>
            <td><input type="text" name="product_name[]" class="item-input" placeholder="New Item Name"></td>
            <td><input type="number" name="quantity[]" class="item-input" value="1" min="0" step="0.01"></td>
            <td><input type="text" name="unit[]" class="item-input" list="unit-list" placeholder="kg, units..." value="kg"></td>
            <td><input type="number" name="unit_price[]" class="item-input" value="0" min="0" step="0.01"></td>
            <td></td>
           </tr>';
    print '</tbody></table>';
    print '<datalist id="unit-list">
            <option value="kg">
            <option value="g">
            <option value="units">
            <option value="packs">
            <option value="bags">
            <option value="boxes">
            <option value="cartons">
            <option value="liters">
            <option value="crates">
            <option value="tonnes">
           </datalist>';
    print '<button type="button" class="button small" style="margin-top:10px; background:#667eea; color:white;" onclick="addRow()">+ Add Another</button>';

    print '<div style="margin-top: 30px; text-align: center;">';
    print '<button type="submit" class="butAction" style="padding: 12px 40px; font-size: 16px;">Save Changes</button>';
    print '</div>';

    print '</form>';
    print '</div>';
}

print '</div>';

print '<script>
function addRow() {
    var table = document.getElementById("itemBody");
    var row = table.rows[0].cloneNode(true);
    var inputs = row.getElementsByTagName("input");
    for(var i=0; i<inputs.length; i++) inputs[i].value = "";
    var selects = row.getElementsByTagName("select");
    for(var i=0; i<selects.length; i++) selects[i].selectedIndex = 0;
    inputs[1].value = "1"; inputs[2].value = "0";
    table.appendChild(row);
}
</script>';

llxFooter();
?>