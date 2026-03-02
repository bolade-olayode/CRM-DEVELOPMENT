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
llxHeader('', 'Create Package');

// --- CSS ---
print '<style>
    div#id-top, #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; }
    
    .fb-container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
    .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 40px; border: 1px solid #eee; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: 600; font-size: 13px; color: #444; margin-bottom: 5px; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
    
    /* Item Table */
    .item-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .item-table th { background: #f8f9fa; text-align: left; padding: 10px; font-size: 12px; color: #666; border-bottom: 2px solid #eee; }
    .item-table td { padding: 10px; border-bottom: 1px solid #eee; }
    .item-input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
</style>';

$notice = '';
$hide_form = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed.</div>';
    } else {
        $p = new Package($db);
        $p->ref = $_POST['ref'];
        $p->name = $_POST['name'];
        $p->description = $_POST['description'];
        $p->status = $_POST['status'];
        
        $pid = $p->create($user);
        
        if ($pid > 0) {
            // Save Items
            $count = 0;
            if (!empty($_POST['product_name'])) {
                foreach ($_POST['product_name'] as $k => $name) {
                    if (empty(trim($name))) continue;
                    $item = new PackageItem($db);
                    $item->fk_package = $pid;
                    $item->product_name = $name;
                    $item->quantity = $_POST['quantity'][$k];
                    $item->unit = $_POST['unit'][$k];
                    $item->unit_price = $_POST['unit_price'][$k];
                    $item->create($user);
                    $count++;
                }
            }
            
            $notice = '<div class="ok" style="padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; color: #155724; margin-bottom: 20px; text-align: center;">
                        <div style="font-size: 40px; margin-bottom: 10px;">📦</div>
                        <strong>Package Created Successfully!</strong><br>
                        Added '.$count.' items.<br><br>
                        <a href="packages.php" class="button" style="background:#28a745; color:white; border:none; padding:10px 20px;">View Packages</a>
                       </div>';
            $hide_form = true;
        } else {
            $notice = '<div class="error">Error: '.$p->error.'</div>';
        }
    }
}

print '<div class="fb-container">';

if (!$hide_form) {
    print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
    print '<div><h1 style="margin: 0;">📦 New Package</h1><p style="color:#888; margin: 5px 0 0 0;">Create a food box template</p></div>';
    print '<div><a href="packages.php" class="button" style="background:#eee; color:#333; margin-right: 10px;">Cancel</a>';
    print '<a href="dashboard_admin.php" class="button" style="background:#333; color:#fff;">Dashboard</a></div>';
    print '</div>';
}

print $notice;

if (!$hide_form) {
    print '<div class="fb-card">';
    print '<form method="POST" action="'.basename(__FILE__).'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';

    print '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">';
    print '<div class="form-group"><label>Package Name</label><input type="text" name="name" required placeholder="e.g. Family Relief Box"></div>';
    print '<div class="form-group"><label>Reference (Optional)</label><input type="text" name="ref" placeholder="Auto-generated"></div>';
    print '</div>';
    
    print '<div class="form-group"><label>Description</label><textarea name="description" rows="2" placeholder="Describe contents..."></textarea></div>';
    print '<input type="hidden" name="status" value="Active">';

    print '<h3 style="margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px;">📦 Package Items</h3>';
    
    print '<table class="item-table" id="itemTable">';
    print '<thead><tr><th>Product Name</th><th width="15%">Qty</th><th width="15%">Unit</th><th width="15%">Est. Price (₦)</th><th width="5%"></th></tr></thead>';
    print '<tbody id="itemBody">';
    // Initial Row
    print '<tr>
            <td><input type="text" name="product_name[]" class="item-input" required placeholder="Item name"></td>
            <td><input type="number" name="quantity[]" class="item-input" value="1" min="0" step="0.01"></td>
            <td><input type="text" name="unit[]" class="item-input" list="unit-list" placeholder="kg, units..." value="kg"></td>
            <td><input type="number" name="unit_price[]" class="item-input" value="0" min="0" step="0.01"></td>
            <td><button type="button" class="button small" style="background:#dc3545; color:white;" onclick="removeRow(this)">X</button></td>
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
    
    print '<div style="margin-top: 10px;">';
    print '<button type="button" class="button small" style="background:#667eea; color:white;" onclick="addRow()">+ Add Another Item</button>';
    print '</div>';

    print '<div style="margin-top: 30px; text-align: center;">';
    print '<button type="submit" class="butAction" style="padding: 12px 40px; font-size: 16px;">Create Package</button>';
    print '</div>';

    print '</form>';
    print '</div>';
}

print '</div>'; // End Container

// JS for Dynamic Rows
print '<script>
function addRow() {
    var table = document.getElementById("itemBody");
    var row = table.rows[0].cloneNode(true);
    var inputs = row.getElementsByTagName("input");
    for(var i=0; i<inputs.length; i++) inputs[i].value = "";
    var selects = row.getElementsByTagName("select");
    for(var i=0; i<selects.length; i++) selects[i].selectedIndex = 0;
    inputs[1].value = "1"; // Default qty
    inputs[2].value = "0"; // Default price (index 2 because select is not an input element)
    table.appendChild(row);
}
function removeRow(btn) {
    var row = btn.parentNode.parentNode;
    if (document.getElementById("itemBody").rows.length > 1) {
        row.parentNode.removeChild(row);
    } else {
        alert("You must have at least one item.");
    }
}
</script>';

llxFooter();
?>