<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendor.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader('', 'Register Vendor');

// --- MODERN UI STYLES ---
print '<style>
    #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; }
    .fb-container { max-width: 900px; margin: 0 auto; padding: 0 20px; }
    .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 40px; border: 1px solid #eee; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 13px; color: #444; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; transition: border-color 0.2s; }
    .form-group input:focus { border-color: #667eea; outline: none; }
</style>';

$notice = '';
$hide_form = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed.</div>';
    } else {
        $v = new VendorFB($db);
        $v->ref = $_POST['ref'];
        $v->name = $_POST['name'];
        $v->category = $_POST['category'];
        $v->contact_person = $_POST['contact_person'];
        $v->contact_email = $_POST['contact_email'];
        $v->contact_phone = $_POST['contact_phone'];
        $v->phone = $_POST['phone'];
        $v->email = $_POST['email'];
        $v->address = $_POST['address'];
        $v->description = $_POST['description'];
        $v->status = $_POST['status']; // New Status Field
        
        $res = $v->create($user);
        
        if ($res > 0) {
            // SUCCESS MESSAGE
            $notice = '<div class="ok" style="padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; color: #155724; margin-bottom: 20px; text-align: center;">
                        <div style="font-size: 40px; margin-bottom: 10px;">✅</div>
                        <strong>Vendor Registered Successfully!</strong><br>
                        Ref: '.$v->ref.'<br><br>
                        <a href="vendors.php" class="button" style="background:#28a745; color:white; border:none; padding:10px 20px;">View Vendors</a>
                        <a href="create_vendor.php" class="button" style="background:#eee; color:#333; margin-left:10px;">Add Another</a>
                       </div>';
            $hide_form = true;
        } else {
            $notice = '<div class="error">Error creating vendor: '.dol_escape_htmltag($v->error).'</div>';
        }
    }
}

print '<div class="fb-container">';

// Hide header on success screen for cleaner look
if (!$hide_form) {
    print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-top: 20px;">';
    print '<div><h1 style="margin: 0;">🏢 New Vendor</h1><p style="color:#888; margin: 5px 0 0 0;">Register a new food supplier</p></div>';
    print '<div>';
    print '<a href="vendors.php" class="button" style="background:#eee; color:#333; margin-right: 10px;">Cancel</a>';
    print '<a href="dashboard_admin.php" class="button" style="background:#333; color:#fff;">Back to Dashboard</a>';
    print '</div>';
    print '</div>';
}

print $notice;

if (!$hide_form) {
    print '<div class="fb-card">';
    print '<form method="POST" action="'.basename(__FILE__).'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';

    print '<h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 25px;">Business Details</h3>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>Business Name <span style="color:red">*</span></label><input type="text" name="name" required placeholder="e.g. ABC Foods Ltd"></div>';
    
    // CATEGORY DROPDOWN
    print '<div class="form-group"><label>Category</label>';
    print '<select name="category">';
    print '<option value="">-- Select Category --</option>';
    $cats = ['Grains','Vegetables','Proteins','Dairy','Beverages','Packaged Foods','Other'];
    foreach($cats as $c) { print '<option value="'.$c.'">'.$c.'</option>'; }
    print '</select></div>';
    print '</div>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>Business Email</label><input type="email" name="email" placeholder="info@abcfoods.com"></div>';
    print '<div class="form-group"><label>Business Phone</label><input type="text" name="phone" placeholder="01-123456"></div>';
    print '</div>';

    print '<div class="form-group"><label>Address</label><textarea name="address" rows="2" placeholder="Office/Warehouse address"></textarea></div>';

    print '<h3 style="margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 25px;">Contact Manager</h3>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>Contact Person Name</label><input type="text" name="contact_person" placeholder="e.g. Mr. John Doe"></div>';
    print '<div class="form-group"><label>Direct Phone</label><input type="text" name="contact_phone" placeholder="080..."></div>';
    print '</div>';
    print '<div class="form-group"><label>Direct Email</label><input type="email" name="contact_email" placeholder="john.doe@abcfoods.com"></div>';

    // STATUS TOGGLE
    print '<h3 style="margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 25px;">Status</h3>';
    print '<div class="form-group" style="max-width: 300px;">';
    print '<select name="status" style="font-weight:bold;">';
    print '<option value="Active" selected style="color:green;">✅ Active (Approved)</option>';
    print '<option value="Inactive" style="color:red;">❌ Inactive (Pending Review)</option>';
    print '</select>';
    print '</div>';

    print '<div class="form-group"><label>Internal Notes</label><textarea name="description" rows="3" placeholder="Additional details about this vendor..."></textarea></div>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>Reference ID (Optional)</label><input type="text" name="ref" placeholder="Auto-generated if empty"></div>';
    print '</div>';

    print '<div style="margin-top: 30px; text-align: center;">';
    print '<button type="submit" class="butAction" style="padding: 12px 40px; font-size: 16px;">Register Vendor</button>';
    print '</div>';

    print '</form>';
    print '</div>'; 
}

print '</div>'; 
llxFooter();
?>