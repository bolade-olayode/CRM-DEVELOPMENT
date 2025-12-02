<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/package.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/packageitem.class.php';

$langs->load("admin");
llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Package ID is missing.</div>';
    print '<div><a href="packages.php">← Back to Packages</a></div>';
    llxFooter(); exit;
}

$package_id = (int) $_GET['id'];
$package = new Package($db);
$package->fetch($package_id);

$notice = '';

// Handle item deletion
if (isset($_GET['delete_item'])) {
    $item_id = (int) $_GET['delete_item'];
    $item = new PackageItem($db);
    $item->fetch($item_id);

    if ($item->delete($user) > 0) {
        $notice = '<div class="ok">Item deleted successfully!</div>';
    } else {
        $notice = '<div class="error">Error deleting item.</div>';
    }
}

// Handle item update
if (isset($_POST['update_item'])) {
    $item_id = (int) $_POST['item_id'];
    $item = new PackageItem($db);
    $item->fetch($item_id);
    
    $item->product_name = $_POST['item_product_name'];
    $item->quantity = $_POST['item_quantity'];
    $item->unit = $_POST['item_unit'];
    $item->unit_price = $_POST['item_price'];
    $item->fk_vendor_preferred = !empty($_POST['item_vendor']) ? $_POST['item_vendor'] : null;
    
    if ($item->update($user) > 0) {
        $notice = '<div class="ok">Item updated successfully!</div>';
    } else {
        $notice = '<div class="error">Error updating item.</div>';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['update_item'])) {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        // Update package
        $package->name = $_POST['name'];
        $package->description = $_POST['description'];
        $package->status = $_POST['status'];

        if ($package->update($user) > 0) {
            // Add new items
            $items_added = 0;
            $items_failed = 0;

            if (!empty($_POST['product_name']) && is_array($_POST['product_name'])) {
                foreach ($_POST['product_name'] as $index => $product_name) {
                    if (empty(trim($product_name))) continue;

                    $item = new PackageItem($db);
                    $item->fk_package = $package_id;
                    $item->product_name = trim($product_name);
                    $item->quantity = !empty($_POST['product_quantity'][$index]) ? $_POST['product_quantity'][$index] : 0;
                    $item->unit = !empty($_POST['product_unit'][$index]) ? $_POST['product_unit'][$index] : 'kg';
                    $item->unit_price = !empty($_POST['product_price'][$index]) ? $_POST['product_price'][$index] : 0;
                    $item->fk_vendor_preferred = !empty($_POST['product_vendor'][$index]) ? $_POST['product_vendor'][$index] : null;

                    if ($item->create($user) > 0) {
                        $items_added++;
                    } else {
                        $items_failed++;
                    }
                }
            }

            $notice = '<div class="ok">Package updated successfully!';
            if ($items_added > 0) {
                $notice .= '<br>✅ '.$items_added.' new item(s) added.';
            }
            if ($items_failed > 0) {
                $notice .= '<br>⚠ '.$items_failed.' item(s) failed to add.';
            }
            $notice .= '</div>';

            // Refresh package data
            $package->fetch($package_id);
        } else {
            $notice = '<div class="error">Update failed: '.$package->error.'</div>';
        }
    }
}

// Get existing items
$existing_items = PackageItem::getAllByPackage($db, $package_id);

// Calculate total package price
$total_price = 0;
foreach ($existing_items as $item) {
    $total_price += ($item->quantity * $item->unit_price);
}

// Get all vendors for dropdown
$vendors_list = array();
$sql = "SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE 1 ORDER BY name ASC";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $vendors_list[$obj->rowid] = $obj->name;
    }
}

print $notice;
print '<div><a href="packages.php">← Back to Packages</a></div><br>';
?>

<h2>Edit Package: <?php echo dol_escape_htmltag($package->name); ?></h2>

<form method="POST" action="<?php echo $_SERVER['PHP_SELF'].'?id='.$package_id; ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">

  <table class="border centpercent">
    <tr>
      <td width="25%">Ref</td>
      <td><strong><?php echo dol_escape_htmltag($package->ref); ?></strong> (cannot be changed)</td>
    </tr>
    <tr>
      <td><span class="fieldrequired">Package Name</span></td>
      <td><input class="flat" type="text" name="name" value="<?php echo dol_escape_htmltag($package->name); ?>" required></td>
    </tr>
    <tr>
      <td>Description</td>
      <td><textarea class="flat" name="description" rows="3"><?php echo dol_escape_htmltag($package->description); ?></textarea></td>
    </tr>
    <tr>
      <td>Status</td>
      <td>
        <select class="flat" name="status">
          <option value="Active" <?php echo $package->status == 'Active' ? 'selected' : ''; ?>>Active</option>
          <option value="Inactive" <?php echo $package->status == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select>
      </td>
    </tr>
  </table>

  <br>
  <h3>📦 Items in This Package</h3>
  
  <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
    <strong>💰 Current Package Price:</strong> <span style="font-size: 20px; color: #1976d2;">₦<?php echo number_format($total_price, 2); ?></span>
  </div>

  <?php if (count($existing_items) > 0): ?>
  <h4>Current Items:</h4>
  <table class="noborder centpercent">
    <tr class="liste_titre">
      <th>Product Name</th>
      <th>Quantity</th>
      <th>Unit</th>
      <th>Unit Price</th>
      <th>Subtotal</th>
      <th>Vendor</th>
      <th class="center">Action</th>
    </tr>
    <?php foreach ($existing_items as $item): ?>
    <tr class="oddeven">
      <td><strong><?php echo dol_escape_htmltag($item->product_name); ?></strong></td>
      <td><?php echo dol_escape_htmltag($item->quantity); ?></td>
      <td><?php echo dol_escape_htmltag($item->unit); ?></td>
      <td>₦<?php echo number_format($item->unit_price, 2); ?></td>
      <td><strong>₦<?php echo number_format($item->quantity * $item->unit_price, 2); ?></strong></td>
      <td><?php echo $item->vendor_name ? dol_escape_htmltag($item->vendor_name) : '—'; ?></td>
      <td class="center">
        <a href="javascript:void(0);" onclick="editItem(<?php echo $item->id; ?>, '<?php echo addslashes($item->product_name); ?>', <?php echo $item->quantity; ?>, '<?php echo $item->unit; ?>', <?php echo $item->unit_price; ?>, <?php echo $item->fk_vendor_preferred ?? 0; ?>)" style="color: #1976d2; margin-right: 10px;">Edit</a>
        <a href="<?php echo $_SERVER['PHP_SELF'].'?id='.$package_id.'&delete_item='.$item->id.'&token='.newToken(); ?>"
           onclick="return confirm('Delete this item?');"
           style="color: #dc3545;">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <br>
  <?php else: ?>
  <p style="color: #999;">This package has no items yet.</p>
  <?php endif; ?>

  <h4>Add New Items:</h4>
  <div id="items-container">
    <table class="noborder centpercent">
      <tr class="liste_titre">
        <th width="22%">Product Name</th>
        <th width="12%">Quantity</th>
        <th width="10%">Unit</th>
        <th width="12%"><span class="fieldrequired">Unit Price (₦)</span></th>
        <th width="20%">Preferred Vendor</th>
        <th width="8%">Action</th>
      </tr>
      <tr class="item-row">
        <td><input class="flat" type="text" name="product_name[]" placeholder="e.g., Rice, Oil, Beans" style="width:95%;"></td>
        <td><input class="flat" type="number" name="product_quantity[]" step="0.01" placeholder="10" style="width:95%;"></td>
        <td>
          <select class="flat" name="product_unit[]" style="width:95%;">
            <option value="kg">kg</option>
            <option value="liters">liters</option>
            <option value="boxes">boxes</option>
            <option value="bags">bags</option>
            <option value="units">units</option>
          </select>
        </td>
        <td><input class="flat" type="number" name="product_price[]" step="0.01" placeholder="500.00" style="width:95%;"></td>
        <td>
          <select class="flat" name="product_vendor[]" style="width:95%;">
            <option value="">-- No Preference --</option>
            <?php foreach ($vendors_list as $vendor_id => $vendor_name): ?>
            <option value="<?php echo $vendor_id; ?>"><?php echo dol_escape_htmltag($vendor_name); ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><button type="button" class="button small" onclick="removeItemRow(this)">Remove</button></td>
      </tr>
    </table>
  </div>

  <br>
  <div style="margin-bottom: 20px;">
    <button type="button" class="button" onclick="addItemRow()">+ Add Another Item</button>
  </div>

  <br>
  <div class="center">
    <input class="button" type="submit" value="Update Package">
    <a class="button" href="packages.php">Cancel</a>
  </div>
</form>

<!-- Edit Item Modal (Simple Form) -->
<div id="editModal" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border: 2px solid #333; z-index: 9999; box-shadow: 0 4px 20px rgba(0,0,0,0.3); min-width: 500px;">
    <h3>Edit Item</h3>
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF'].'?id='.$package_id; ?>">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">
        <input type="hidden" name="update_item" value="1">
        <input type="hidden" name="item_id" id="edit_item_id">
        
        <table class="border centpercent">
            <tr>
                <td>Product Name:</td>
                <td><input class="flat" type="text" name="item_product_name" id="edit_product_name" required style="width: 100%;"></td>
            </tr>
            <tr>
                <td>Quantity:</td>
                <td><input class="flat" type="number" name="item_quantity" id="edit_quantity" step="0.01" required style="width: 100%;"></td>
            </tr>
            <tr>
                <td>Unit:</td>
                <td>
                    <select class="flat" name="item_unit" id="edit_unit" style="width: 100%;">
                        <option value="kg">kg</option>
                        <option value="liters">liters</option>
                        <option value="boxes">boxes</option>
                        <option value="bags">bags</option>
                        <option value="units">units</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Unit Price (₦):</td>
                <td><input class="flat" type="number" name="item_price" id="edit_price" step="0.01" required style="width: 100%;"></td>
            </tr>
            <tr>
                <td>Vendor:</td>
                <td>
                    <select class="flat" name="item_vendor" id="edit_vendor" style="width: 100%;">
                        <option value="">-- No Preference --</option>
                        <?php foreach ($vendors_list as $vendor_id => $vendor_name): ?>
                        <option value="<?php echo $vendor_id; ?>"><?php echo dol_escape_htmltag($vendor_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        
        <br>
        <div class="center">
            <input class="button" type="submit" value="Save Changes">
            <button type="button" class="button" onclick="closeEditModal()">Cancel</button>
        </div>
    </form>
</div>
<div id="modalOverlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9998;" onclick="closeEditModal()"></div>

<script>
function editItem(id, name, qty, unit, price, vendor) {
    document.getElementById('edit_item_id').value = id;
    document.getElementById('edit_product_name').value = name;
    document.getElementById('edit_quantity').value = qty;
    document.getElementById('edit_unit').value = unit;
    document.getElementById('edit_price').value = price;
    document.getElementById('edit_vendor').value = vendor || '';
    
    document.getElementById('editModal').style.display = 'block';
    document.getElementById('modalOverlay').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}

function addItemRow() {
    var container = document.getElementById('items-container').querySelector('table');
    var newRow = container.querySelector('.item-row').cloneNode(true);

    var inputs = newRow.querySelectorAll('input, select');
    inputs.forEach(function(input) {
        if (input.type === 'text' || input.type === 'number') {
            input.value = '';
        } else if (input.tagName === 'SELECT') {
            input.selectedIndex = 0;
        }
    });

    container.appendChild(newRow);
}

function removeItemRow(button) {
    var container = document.getElementById('items-container').querySelector('table');
    var rows = container.querySelectorAll('.item-row');

    if (rows.length > 1) {
        button.closest('.item-row').remove();
    } else {
        alert('You must keep at least one item row. Just leave it empty if you don\'t want to add items.');
    }
}
</script>

<?php llxFooter(); ?>
