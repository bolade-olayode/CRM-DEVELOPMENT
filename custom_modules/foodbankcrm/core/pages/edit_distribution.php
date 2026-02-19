<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distribution.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distributionline.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

// --- LOGIC FIRST ---
$id = GETPOST('id', 'int');
if (!$id) { header("Location: distributions.php"); exit; }

$d = new Distribution($db);
if ($d->fetch($id) <= 0) { accessforbidden("Distribution not found"); }

// Handle Delete Item (CSRF protected)
if (isset($_GET['del_line'])) {
    if (!isset($_GET['token']) || $_GET['token'] != $_SESSION['newtoken']) {
        setEventMessages("Security check failed. Please try again.", null, 'errors');
    } else {
        $line = new DistributionLine($db);
        $line->id = (int)$_GET['del_line'];
        $line->fetch($line->id);
        $line->delete($user);
    }
    header("Location: edit_distribution.php?id=".$id); exit;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['token'] == $_SESSION['newtoken']) {
        $d->status = $_POST['status'];
        $d->payment_status = $_POST['payment_status'];
        $d->note = $_POST['note'];
        $d->update($user);

        // Add Item Logic
        if (!empty($_POST['new_donation']) && !empty($_POST['new_qty'])) {
            $line = new DistributionLine($db);
            $line->fk_distribution = $id;
            $line->fk_donation = (int)$_POST['new_donation'];
            $line->quantity = (float)$_POST['new_qty'];
            
            // Fetch product info
            $donations = Distribution::getAvailableDonations($db);
            foreach($donations as $don) {
                if ($don['id'] == $line->fk_donation) {
                    $line->product_name = $don['label'];
                    $line->unit = $don['unit'];
                    break;
                }
            }
            $line->create($user);
        }
        
        // Redirect with Success
        header("Location: view_distribution.php?id=".$id."&msg=updated");
        exit;
    }
}

// --- VIEW SECOND ---
$langs->load("admin");
llxHeader('', 'Edit Shipment');

print '<style>div#id-top, #id-top { display: none !important; } .side-nav { top: 0 !important; height: 100vh !important; } #id-right { padding-top: 30px !important; } .fb-container { max-width: 900px; margin: 0 auto; padding: 20px; } .fb-card { background: #fff; border-radius: 8px; padding: 40px; border: 1px solid #eee; } .clean-table { width: 100%; border-collapse: collapse; margin-top: 15px; } .clean-table th { background: #f8f9fa; text-align: left; padding: 10px; border-bottom: 2px solid #eee; } .clean-table td { padding: 10px; border-bottom: 1px solid #eee; } input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }</style>';

$lines = DistributionLine::getAllByDistribution($db, $id);
$donations = Distribution::getAvailableDonations($db);

print '<div class="fb-container">';
print '<div style="display: flex; justify-content: space-between; margin-bottom: 20px;"><h1>✏️ Edit Shipment #'.$d->ref.'</h1><a href="view_distribution.php?id='.$id.'" class="button" style="background:#eee; color:#333;">Cancel</a></div>';

print '<div class="fb-card">';
print '<form method="POST" action="'.basename(__FILE__).'?id='.$id.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

// Status & Payment
print '<div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">';
print '<div><label style="font-weight:bold; display:block; margin-bottom:5px;">Status</label><select name="status"><option value="Prepared"'.($d->status=='Prepared'?' selected':'').'>Prepared</option><option value="In Transit"'.($d->status=='In Transit'?' selected':'').'>In Transit</option><option value="Delivered"'.($d->status=='Delivered'?' selected':'').'>Delivered</option></select></div>';
print '<div><label style="font-weight:bold; display:block; margin-bottom:5px;">Payment</label><select name="payment_status"><option value="Pending"'.($d->payment_status=='Pending'?' selected':'').'>Pending</option><option value="Paid"'.($d->payment_status=='Paid'?' selected':'').'>Paid</option></select></div>';
print '</div>';

print '<label style="font-weight:bold; display:block; margin-bottom:5px;">Notes</label><textarea name="note" rows="2">'.dol_escape_htmltag($d->note).'</textarea>';

// Items Table
print '<h3 style="margin-top:30px; border-bottom:1px solid #eee; padding-bottom:5px;">📦 Shipment Items</h3>';
print '<table class="clean-table"><thead><tr><th>Product</th><th>Source (Donation)</th><th>Qty</th><th>Action</th></tr></thead><tbody>';

if (count($lines) > 0) {
    foreach($lines as $line) {
        print '<tr>';
        print '<td>'.dol_escape_htmltag($line->product_name).'</td>';
        print '<td>'.dol_escape_htmltag($line->donation_ref).'</td>';
        print '<td>'.number_format($line->quantity).' '.$line->unit.'</td>';
        print '<td><a href="edit_distribution.php?id='.$id.'&del_line='.$line->id.'&token='.newToken().'" style="color:red; font-size:12px; font-weight:bold;" onclick="return confirm(\'Remove this item? Stock will be restored.\')">Remove</a></td>';
        print '</tr>';
    }
} else {
    print '<tr><td colspan="4" style="text-align:center; color:#999; padding:20px;">No items in this shipment yet.</td></tr>';
}

// Add New Row
print '<tr style="background:#f9f9f9;">';
print '<td><strong>Add Item:</strong></td>';
print '<td><select name="new_donation" style="padding:8px;"><option value="">-- Select Inventory --</option>';
foreach($donations as $don) { print '<option value="'.$don['id'].'">'.$don['label'].' ('.$don['available'].' avail)</option>'; }
print '</select></td>';
print '<td><input type="number" name="new_qty" placeholder="Qty" step="0.01" style="width:80px; padding:8px;"></td>';
print '<td></td>';
print '</tr>';
print '</tbody></table>';

print '<div style="text-align: center; margin-top: 30px;"><button type="submit" class="butAction" style="padding: 12px 40px;">Save Changes</button></div>';

print '</form></div></div>';
llxFooter();
?>