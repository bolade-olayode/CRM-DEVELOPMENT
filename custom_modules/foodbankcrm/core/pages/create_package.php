<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/package.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/packageitem.class.php';

$langs->load("admin");
llxHeader();

$notice = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        // Create package
        $package = new Package($db);
        $package->ref = $_POST['ref']; // Will auto-generate if empty
        $package->name = $_POST['name'];
        $package->description = $_POST['description'];
        $package->status = $_POST['status'];

        $package_id = $package->create($user);

        if ($package_id > 0) {
            // Add package items
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
                    $item->note = !empty($_POST['product_note'][$index]) ? $_POST['product_note'][$index] : '';

                    if ($item->create($user) > 0) {
                        $items_added++;
                    } else {
                        $items_failed++;
                    }
                }
            }

            $notice = '<div class="ok">Package created successfully! Ref: '.$package->ref.' (ID: '.$package_id.')';
            if ($items_added > 0) {
                $notice .= '<br>✅ '.$items_added.' item(s) added to package.';
            }
            if ($items_failed > 0) {
                $notice .= '<br>⚠ '.$items_failed.' item(s) failed to add.';
            }
            $notice .= '</div>';
        } else {
            $notice = '<div class="error">Error creating package: '.dol_escape_htmltag($package->error).'</div>';
        }
    }
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

<h2>Create Package Template</h2>
<p style="color: #666; font-size: 13px;">
    A package is a predefined list of items that beneficiaries typically receive.
    Create templates like "Family Package", "Single Person Package", etc.
</p>

<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">

  <table class="border centpercent">
    <tr>
      <td width="25%">Ref</td>
      <td><input class="flat" type="text" name="ref" placeholder="Leave empty for auto-generation (PKG2025-0001)"></td>
    </tr>
    <tr>
      <td><span class="fieldrequired">Package Name</span></td>
      <td><input class="flat" type="text" name="name" placeholder="e.g., Family Package, Emergency Relief" required></td>
    </tr>
    <tr>
      <td>Description</td>
      <td><textarea class="flat" name="description" rows="3" placeholder="Brief description of this package..."></textarea></td>
    </tr>
    <tr>
      <td>Status</td>
      <td>
        <select class="flat" name="status">
          <option value="Active" selected>Active</option>
          <option value="Inactive">Inactive</option>
        </select>
      </td>
    </tr>
  </table>

  <br>
  <h3>📦 Items in This Package</h3>
  <p style="color: #666; font-size: 12px;">
    Define what items should be included in this package. You can optionally specify a preferred vendor for each item.
  </p>

  <div id="items-container">
    <table class="noborder centpercent">
      <tr class="liste_titre">
        <th width="25%">Product Name</th>
        <th width="12%">Quantity</th>
        <th width="10%">Unit</th>
        <th width="12%"><span class="fieldrequired">Unit Price (₦)</span></th>
        <th width="20%">Preferred Vendor</th>
        <th width="8%">Action</th>
      </tr>
      <tr class="item-row">
        <td><input class="flat" type="text" name="product_name[]" placeholder="e.g., Rice, Oil, Beans" style="width:95%;" required></td>
        <td><input class="flat" type="number" name="product_quantity[]" step="0.01" placeholder="10" style="width:95%;" required></td>
        <td>
          <select class="flat" name="product_unit[]" style="width:95%;">
            <option value="kg">kg</option>
            <option value="liters">liters</option>
            <option value="boxes">boxes</option>
            <option value="bags">bags</option>
            <option value="units">units</option>
          </select>
        </td>
        <td><input class="flat" type="number" name="product_price[]" step="0.01" placeholder="500.00" style="width:95%;" required></td>
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

  <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
    <strong>💡 Total Package Price:</strong> <span id="total-price" style="font-size: 18px; color: #1976d2;">₦0.00</span>
  </div>

  <br>
  <div class="center">
    <input class="button" type="submit" value="Create Package">
    <a class="button" href="packages.php">Cancel</a>
  </div>
</form>

<script>
function calculateTotal() {
    let total = 0;
    const rows = document.querySelectorAll('.item-row');
    
    rows.forEach(function(row) {
        const qty = parseFloat(row.querySelector('input[name="product_quantity[]"]').value) || 0;
        const price = parseFloat(row.querySelector('input[name="product_price[]"]').value) || 0;
        total += qty * price;
    });
    
    document.getElementById('total-price').textContent = '₦' + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function addItemRow() {
    var container = document.getElementById('items-container').querySelector('table');
    var newRow = container.querySelector('.item-row').cloneNode(true);

    // Clear input values
    var inputs = newRow.querySelectorAll('input, select');
    inputs.forEach(function(input) {
        if (input.type === 'text' || input.type === 'number') {
            input.value = '';
        } else if (input.tagName === 'SELECT') {
            input.selectedIndex = 0;
        }
    });

    container.appendChild(newRow);
    
    // Attach event listeners to new inputs
    attachCalculationListeners(newRow);
}

function removeItemRow(button) {
    var container = document.getElementById('items-container').querySelector('table');
    var rows = container.querySelectorAll('.item-row');

    if (rows.length > 1) {
        button.closest('.item-row').remove();
        calculateTotal();
    } else {
        alert('You must keep at least one item in the package.');
    }
}

function attachCalculationListeners(row) {
    const qtyInput = row.querySelector('input[name="product_quantity[]"]');
    const priceInput = row.querySelector('input[name="product_price[]"]');
    
    if (qtyInput) qtyInput.addEventListener('input', calculateTotal);
    if (priceInput) priceInput.addEventListener('input', calculateTotal);
}

// Initialize calculation listeners on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.item-row').forEach(attachCalculationListeners);
});
</script>

<?php
llxFooter(); ?>