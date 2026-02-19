<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/package.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/packageitem.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

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

// Get package items
$items = PackageItem::getAllByPackage($db, $package_id);

// Get usage count
$sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE fk_package = ".$package_id;
$resql = $db->query($sql);
$usage_count = 0;
if ($resql) {
    $obj = $db->fetch_object($resql);
    $usage_count = $obj->count;
}

print '<div><a href="packages.php">← Back to Packages</a></div><br>';
?>

<div style="display: flex; justify-content: space-between; align-items: center;">
    <h2><?php echo dol_escape_htmltag($package->name); ?></h2>
    <div>
        <a class="butAction" href="edit_package.php?id=<?php echo $package_id; ?>">Edit Package</a>
        <a class="butActionDelete" href="delete_package.php?id=<?php echo $package_id; ?>">Delete Package</a>
    </div>
</div>

<table class="border centpercent">
    <tr>
        <td width="25%"><strong>Ref</strong></td>
        <td><?php echo dol_escape_htmltag($package->ref); ?></td>
    </tr>
    <tr>
        <td><strong>Package Name</strong></td>
        <td><?php echo dol_escape_htmltag($package->name); ?></td>
    </tr>
    <tr>
        <td><strong>Description</strong></td>
        <td><?php echo dol_escape_htmltag($package->description); ?></td>
    </tr>
    <tr>
        <td><strong>Status</strong></td>
        <td>
            <?php
            $status_color = $package->status == 'Active' ? '#4caf50' : '#999';
            $status_bg = $package->status == 'Active' ? '#e8f5e9' : '#f5f5f5';
            ?>
            <span style="display:inline-block; padding:4px 10px; border-radius:3px; background:<?php echo $status_bg; ?>; color:<?php echo $status_color; ?>; font-weight:bold;">
                <?php echo dol_escape_htmltag($package->status); ?>
            </span>
        </td>
    </tr>
    <tr>
        <td><strong>Used In</strong></td>
        <td>
            <?php if ($usage_count > 0): ?>
                <span style="color: #f57c00; font-weight: bold;"><?php echo $usage_count; ?> distribution(s)</span>
            <?php else: ?>
                <span style="color: #999;">Not used yet</span>
            <?php endif; ?>
        </td>
    </tr>
</table>

<br>
<h3>📦 Items in This Package (<?php echo count($items); ?>)</h3>

<?php if (count($items) > 0): ?>
<table class="noborder centpercent">
    <tr class="liste_titre">
        <th>Product Name</th>
        <th>Quantity</th>
        <th>Unit</th>
        <th>Preferred Vendor</th>
    </tr>
    <?php 
    $total_items = count($items);
    foreach ($items as $index => $item): 
    ?>
    <tr class="oddeven">
        <td><strong><?php echo dol_escape_htmltag($item->product_name); ?></strong></td>
        <td><?php echo dol_escape_htmltag($item->quantity); ?></td>
        <td><?php echo dol_escape_htmltag($item->unit); ?></td>
        <td>
            <?php 
            if ($item->fk_vendor_preferred && !empty($item->vendor_name)) {
                echo dol_escape_htmltag($item->vendor_name);
            } else {
                echo '<span style="color: #999;">No preference</span>';
            }
            ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <tr style="background: #f9f9f9; font-weight: bold;">
        <td colspan="4" style="text-align: right; padding: 10px;">
            <strong>Total: <?php echo $total_items; ?> items</strong>
        </td>
    </tr>
</table>
<?php else: ?>
<div class="warning" style="padding: 20px; text-align: center;">
    <p>⚠ This package has no items yet. <a href="edit_package.php?id=<?php echo $package_id; ?>">Add items now</a></p>
</div>
<?php endif; ?>

<?php llxFooter(); ?>