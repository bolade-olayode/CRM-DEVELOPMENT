<?php
/**
 * EDIT VENDOR PAGE (COMPLETE)
 * Updates: Includes Bank, Tax, RC, Website fields to match Schema.
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendor.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader('', 'Edit Vendor');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: vendors.php"); exit;
}

// CSS Style
print '<style>
    #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; }
    .fb-container { max-width: 900px; margin: 0 auto; padding: 0 20px; }
    .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 40px; border: 1px solid #eee; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 13px; color: #444; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
    h3 { color: #2c3e50; margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
</style>';

// Load Vendor
$v = new VendorFB($db);
$v->fetch((int) $_GET['id']);

$notice = '';
$hide_form = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed.</div>';
    } else {
        // 1. Capture Standard Fields
        $v->name = $_POST['name'];
        $v->category = $_POST['category'];
        $v->email = $_POST['email'];
        $v->phone = $_POST['phone'];
        $v->contact_person = $_POST['contact_person'];
        $v->contact_email = $_POST['contact_email'];
        $v->contact_phone = $_POST['contact_phone'];
        $v->address = $_POST['address'];
        $v->description = $_POST['description'];
        
        // 2. Capture NEW Fields (Bank, Tax, etc.)
        $v->registration_number = $_POST['rc_number'];
        $v->tax_id              = $_POST['tax_id'];
        $v->website             = $_POST['website'];
        $v->bank_name           = $_POST['bank_name'];
        $v->bank_account_number = $_POST['bank_account_number'];
        
        // 3. Save Status Manually
        $status = $_POST['status'];
        
        if ($v->update($user) > 0) {
            // Manual Status Update
            $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_vendors SET status='".$db->escape($status)."' WHERE rowid=".$v->id);
            
            $notice = '<div class="ok" style="padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; color: #155724; margin-bottom: 20px; text-align: center;">
                        <div style="font-size: 40px; margin-bottom: 10px;">✅</div>
                        <strong>Vendor Updated Successfully!</strong><br>
                        Ref: '.$v->ref.'<br><br>
                        <a href="vendors.php" class="button" style="background:#28a745; color:white; border:none; padding:10px 20px;">Return to List</a>
                        <a href="edit_vendor.php?id='.$v->id.'" class="button" style="background:#eee; color:#333; margin-left:10px;">Edit Again</a>
                       </div>';
            $hide_form = true;
        } else {
            $notice = '<div class="error">Update failed: '.$v->error.'</div>';
        }
    }
}

print '<div class="fb-container">';

if (!$hide_form) {
    print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-top: 20px;">';
    print '<div><h1 style="margin: 0;">✏️ Edit Vendor</h1><p style="color:#888; margin: 5px 0 0 0;">Update details for <strong>'.dol_escape_htmltag($v->name).'</strong></p></div>';
    print '<a href="vendors.php" class="button" style="background:#eee; color:#333;">Cancel</a>';
    print '</div>';
}

print $notice;

if (!$hide_form) {
    print '<div class="fb-card">';
    print '<form method="POST" action="'.basename(__FILE__).'?id='.(int)$v->id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';

    // SECTION 1: BUSINESS INFO
    print '<h3>Business Details</h3>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>Business Name</label><input type="text" name="name" value="'.dol_escape_htmltag($v->name).'" required></div>';
    print '<div class="form-group"><label>Category</label>';
    print '<select name="category">';
    print '<option value="">-- Select Category --</option>';
    $cats = ['Grains','Fresh Produce','Proteins','Packaged','Logistics','Beverages','Other'];
    foreach($cats as $c) { 
        $sel = ($v->category == $c) ? 'selected' : '';
        print '<option value="'.$c.'" '.$sel.'>'.$c.'</option>'; 
    }
    print '</select></div>';
    print '</div>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>RC Number</label><input type="text" name="rc_number" value="'.dol_escape_htmltag($v->registration_number).'"></div>';
    print '<div class="form-group"><label>Tax ID (TIN)</label><input type="text" name="tax_id" value="'.dol_escape_htmltag($v->tax_id).'"></div>';
    print '</div>';
    
    print '<div class="form-group"><label>Website</label><input type="url" name="website" value="'.dol_escape_htmltag($v->website).'"></div>';

    // SECTION 2: CONTACT
    print '<h3>Contact Information</h3>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>Contact Person</label><input type="text" name="contact_person" value="'.dol_escape_htmltag($v->contact_person).'"></div>';
    print '<div class="form-group"><label>Direct Email</label><input type="email" name="contact_email" value="'.dol_escape_htmltag($v->contact_email).'"></div>';
    print '</div>';
    
    print '<div class="form-grid">';
    print '<div class="form-group"><label>Direct Phone</label><input type="text" name="contact_phone" value="'.dol_escape_htmltag($v->contact_phone).'"></div>';
    print '<div class="form-group"><label>General Business Email</label><input type="email" name="email" value="'.dol_escape_htmltag($v->email).'"></div>';
    print '</div>';
    
    print '<div class="form-group"><label>Office Address</label><textarea name="address" rows="2">'.dol_escape_htmltag($v->address).'</textarea></div>';

    // SECTION 3: BANKING
    print '<h3>Banking Details</h3>';
    print '<div class="form-grid">';
    print '<div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" value="'.dol_escape_htmltag($v->bank_name).'"></div>';
    print '<div class="form-group"><label>Account Number</label><input type="text" name="bank_account_number" value="'.dol_escape_htmltag($v->bank_account_number).'"></div>';
    print '</div>';
    
    // SECTION 4: ADMIN
    print '<h3>Admin Controls</h3>';
    
    // Fetch Current Status
    $res = $db->query("SELECT status FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE rowid=".$v->id);
    $curr_status = ($res && $obj = $db->fetch_object($res)) ? $obj->status : 'Active';

    print '<div class="form-group"><label>Vendor Status</label>';
    print '<select name="status" style="font-weight:bold;">';
    print '<option value="Pending" '.($curr_status=='Pending'?'selected':'').' style="color:orange;">⏳ Pending</option>';
    print '<option value="Active" '.($curr_status=='Active'?'selected':'').' style="color:green;">✅ Active</option>';
    print '<option value="Inactive" '.($curr_status=='Inactive'?'selected':'').' style="color:red;">❌ Inactive</option>';
    print '</select></div>';

    print '<div class="form-group"><label>Internal Notes</label><textarea name="description" rows="3">'.dol_escape_htmltag($v->description).'</textarea></div>';

    print '<div style="margin-top: 30px; text-align: center;">';
    print '<button type="submit" class="butAction" style="padding: 12px 40px; font-size: 16px;">Save Changes</button>';
    print '</div>';

    print '</form>';
    print '</div>';
}

print '</div>';
llxFooter();
?>