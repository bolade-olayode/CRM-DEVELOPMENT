<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/donation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader('', 'Edit Donation');

if (!isset($_GET['id']) || empty($_GET['id'])) { header("Location: donations.php"); exit; }

// --- FIXED CSS TO HIDE TOP BAR ---
print '<style>
    /* FORCE HIDE TOP BAR */
    div#id-top, #id-top { display: none !important; }
    
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; }
    
    #mainmenutd_commercial, #mainmenutd_billing, #mainmenutd_compta, 
    #mainmenutd_projet, #mainmenutd_mrp, #mainmenutd_hrm, 
    #mainmenutd_ticket, #mainmenutd_agenda, #mainmenutd_documents, #mainmenutd_bank {
        display: none !important;
    }

    .fb-container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
    .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 40px; border: 1px solid #eee; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 13px; color: #444; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
</style>';

$d = new DonationFB($db);
$d->fetch((int) $_GET['id']);

$notice = '';
$hide_form = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed.</div>';
    } else {
        // Collect Data
        $d->product_name = $_POST['product_name'];
        $d->category = $_POST['category'];
        $d->quantity = (int)$_POST['quantity'];
        $d->unit = $_POST['unit'];
        $d->fk_vendor = (int)$_POST['fk_vendor'];
        $d->fk_warehouse = (int)$_POST['fk_warehouse'];
        $d->note = $_POST['note'];
        $d->status = $_POST['status']; 
        
        // Execute Update
        if ($d->update($user) > 0) {
            $notice = '<div class="ok" style="padding:20px; background:#d4edda; border:1px solid #c3e6cb; border-radius:8px; color:#155724; text-align:center; margin-bottom:20px;">
                        <div style="font-size:40px; margin-bottom:10px;">✅</div>
                        <strong>Donation Updated Successfully!</strong><br>
                        Ref: '.$d->ref.'<br><br>
                        <a href="donations.php" class="button" style="background:#28a745; color:white; border:none; padding:10px 20px;">Return to List</a>
                        <a href="edit_donation.php?id='.$d->id.'" class="button" style="background:#eee; color:#333; margin-left:10px;">Edit Again</a>
                       </div>';
            $hide_form = true;
        } else {
            $notice = '<div class="error">Update failed: '.$d->error.'</div>';
        }
    }
}

// Fetch Data
$vendors = []; $res = $db->query("SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors"); while($o=$db->fetch_object($res)) $vendors[]=$o;
$warehouses = []; $res = $db->query("SELECT rowid, label FROM ".MAIN_DB_PREFIX."foodbank_warehouses"); while($o=$db->fetch_object($res)) $warehouses[]=$o;

print '<div class="fb-container">';

if (!$hide_form) {
    print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-top: 20px;">';
    print '<div><h1 style="margin: 0;">✏️ Edit Donation</h1><p style="color:#888; margin: 5px 0 0 0;">Ref: <strong>'.dol_escape_htmltag($d->ref).'</strong></p></div>';
    print '<a href="donations.php" class="button" style="background:#eee; color:#333;">Cancel</a>';
    print '</div>';
}

print $notice;

if (!$hide_form) {
    print '<div class="fb-card">';
    print '<form method="POST" action="'.basename(__FILE__).'?id='.(int)$d->id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';

    print '<h3 style="margin: 0 0 25px 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">📦 Product Details</h3>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>Product Name</label><input type="text" name="product_name" value="'.dol_escape_htmltag($d->product_name ?: $d->label).'" required></div>';
    print '<div class="form-group"><label>Category</label>';
    print '<select name="category">';
    $cats = ['Grains','Vegetables','Proteins','Dairy','Beverages','Packaged Foods','Other'];
    foreach($cats as $c) { $sel=($d->category==$c)?'selected':''; print '<option value="'.$c.'" '.$sel.'>'.$c.'</option>'; }
    print '</select></div>';
    print '</div>';

    print '<div class="form-grid" style="grid-template-columns: 1fr 1fr;">';
    print '<div class="form-group"><label>Quantity</label><input type="number" name="quantity" value="'.$d->quantity.'" required min="1"></div>';
    print '<div class="form-group"><label>Unit</label>';
    print '<select name="unit">';
    $units = ['kg'=>'Kilograms','bags'=>'Bags','cartons'=>'Cartons','liters'=>'Liters','units'=>'Units','crates'=>'Crates'];
    foreach($units as $val=>$label) { 
        $sel = ($d->unit == $val) ? 'selected' : '';
        print '<option value="'.$val.'" '.$sel.'>'.$label.'</option>'; 
    }
    print '</select></div>';
    print '</div>';

    print '<h3 style="margin: 30px 0 25px 0; border-bottom: 1px solid #eee; padding-bottom: 10px; color:#667eea;">👑 Admin Controls</h3>';

    print '<div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">';
    
    // Vendor
    print '<div class="form-group"><label>Vendor</label><select name="fk_vendor">';
    foreach($vendors as $v) { $sel=($d->fk_vendor==$v->rowid)?'selected':''; print '<option value="'.$v->rowid.'" '.$sel.'>'.dol_escape_htmltag($v->name).'</option>'; }
    print '</select></div>';

    // Warehouse
    print '<div class="form-group"><label>Warehouse</label><select name="fk_warehouse">';
    foreach($warehouses as $w) { $sel=($d->fk_warehouse==$w->rowid)?'selected':''; print '<option value="'.$w->rowid.'" '.$sel.'>'.dol_escape_htmltag($w->label).'</option>'; }
    print '</select></div>';

    // Status
    print '<div class="form-group"><label>Status</label><select name="status" style="font-weight:bold;">';
    $stats = ['Pending'=>'orange', 'Received'=>'green', 'Allocated'=>'blue', 'Rejected'=>'red'];
    foreach($stats as $s=>$col) { $sel=($d->status==$s)?'selected':''; print '<option value="'.$s.'" '.$sel.' style="color:'.$col.'">'.$s.'</option>'; }
    print '</select></div>';
    print '</div>';

    print '<div class="form-group"><label>Notes</label><textarea name="note" rows="2">'.dol_escape_htmltag($d->note).'</textarea></div>';

    print '<div style="margin-top: 30px; text-align: center;">';
    print '<button type="submit" class="butAction" style="padding: 12px 40px; font-size: 16px;">Save Changes</button>';
    print '</div>';

    print '</form>';
    print '</div>';
}

print '</div>';
llxFooter();
?>