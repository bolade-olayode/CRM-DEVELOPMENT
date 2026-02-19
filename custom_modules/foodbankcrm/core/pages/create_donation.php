<?php
/**
 * Create Inventory/Supply Log - ENTREPRENEUR FOCUSED
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/donation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php'; 

$langs->load("admin");

// --- 1. SECURITY & ROLE CHECK ---
$is_admin = FoodbankPermissions::isAdmin($user);
$is_vendor = FoodbankPermissions::isVendor($user, $db);

// If neither, kick them out
if (!$is_admin && !$is_vendor) {
    accessforbidden('You do not have access to this page.');
}

// If Vendor, get their ID
$current_vendor_id = 0;
$current_vendor_name = '';
if ($is_vendor) {
    $sql = "SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
    $res = $db->query($sql);
    if ($res && $obj = $db->fetch_object($res)) {
        $current_vendor_id = $obj->rowid;
        $current_vendor_name = $obj->name;
    }
}

// --- 2. HEADER ---
llxHeader('', 'Add Inventory');

// --- 3. MODERN CSS ---
print '<style>
    /* 1. HIDE CHROME */
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header { display: none !important; }
    
    /* 2. LAYOUT */
    html, body { background-color: #f8f9fa !important; margin: 0; width: 100%; overflow-x: hidden; }
    #id-right, .id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
    .fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }

    /* 3. CONTAINER */
    .fb-container { width: 95%; max-width: 1000px; margin: 0 auto; padding: 40px 20px; font-family: "Segoe UI", sans-serif; }

    /* 4. CARDS & FORMS */
    .fb-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 40px; border: 1px solid #eee; }
    
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px; }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: #444; }
    .form-control { 
        width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; 
        font-size: 15px; box-sizing: border-box; transition: border 0.2s;
    }
    .form-control:focus { border-color: #667eea; outline: none; }
    
    /* 5. BUTTONS */
    .btn-submit {
        background: #667eea; color: white; border: none; padding: 14px 40px; border-radius: 30px; 
        font-weight: bold; cursor: pointer; font-size: 16px; box-shadow: 0 4px 12px rgba(102,126,234,0.3);
        transition: transform 0.1s;
    }
    .btn-submit:hover { transform: translateY(-2px); }

    .btn-outline {
        background: white; color: #666; border: 1px solid #ddd; padding: 10px 20px; border-radius: 30px; 
        text-decoration: none; font-weight: bold; display: inline-block;
    }

    .btn-logout {
        background: white; color: #dc3545; border: 1px solid #dc3545; 
        padding: 8px 16px; border-radius: 30px; text-decoration: none; 
        font-weight: bold; font-size: 13px; display: inline-flex; align-items: center; gap: 5px;
    }
    .btn-logout:hover { background: #dc3545; color: white; }

    /* 6. ALERTS */
    .alert-box { padding: 20px; border-radius: 12px; margin-bottom: 30px; text-align: center; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>';

$notice = '';
$hide_form = false;

// --- 4. HANDLE SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="alert-box alert-error">Security check failed.</div>';
    } else {
        $d = new DonationFB($db);
        $d->ref = $_POST['ref'];
        $d->product_name = $_POST['product_name'];
        $d->category = $_POST['category'];
        $d->quantity = (int)$_POST['quantity'];
        $d->unit = $_POST['unit'];
        $d->fk_warehouse = (int)$_POST['fk_warehouse'];
        $d->note = $_POST['note'];
        
        // --- SECURE LOGIC ---
        if ($is_admin) {
            // Admin can choose any vendor and any status
            $d->fk_vendor = (int)$_POST['fk_vendor']; 
            $d->status = $_POST['status']; 
        } else {
            // Vendor is LOCKED to themselves and PENDING status
            $d->fk_vendor = $current_vendor_id; 
            $d->status = 'Pending'; 
        }
        
        $d->date_donation = dol_now();
        
        if ($d->create($user) > 0) {
            $dashboard_link = $is_admin ? "donations.php" : "dashboard_vendor.php";
            $btn_text = $is_admin ? "View All Stock" : "Back to Dashboard";
            
            $notice = '<div class="alert-box alert-success">
                        <div style="font-size: 40px; margin-bottom: 10px;">📦</div>
                        <h2 style="margin: 0 0 10px 0;">Inventory Logged Successfully!</h2>
                        <p>Reference: <strong>'.$d->ref.'</strong> has been saved.</p>
                        <div style="margin-top: 20px;">
                            <a href="'.$dashboard_link.'" class="btn-submit" style="text-decoration:none;">'.$btn_text.'</a>
                            <a href="create_donation.php" class="btn-outline" style="margin-left:10px;">+ Add Another</a>
                        </div>
                       </div>';
            $hide_form = true;
        } else {
            $notice = '<div class="alert-box alert-error">Error: '.$d->error.'</div>';
        }
    }
}

// Fetch Dropdowns
$vendors = [];
// SECURITY: Only fetch list of vendors if user is ADMIN
if ($is_admin) {
    $res = $db->query("SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors ORDER BY name");
    while($o = $db->fetch_object($res)) $vendors[] = $o;
}

$warehouses = [];
$res = $db->query("SELECT rowid, label FROM ".MAIN_DB_PREFIX."foodbank_warehouses ORDER BY label");
while($o = $db->fetch_object($res)) $warehouses[] = $o;

// --- 5. DISPLAY PAGE ---
print '<div class="fb-container">';

if (!$hide_form) {
    $back_link = $is_admin ? "donations.php" : "dashboard_vendor.php";
    
    // Header Row
    print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
    print '<div>';
    print '<h1 style="margin: 0; color: #2c3e50;">📦 Add Inventory</h1>';
    print '<p style="color: #666; margin: 5px 0 0 0;">Record new supply into the system</p>';
    print '</div>';
    
    // Actions
    print '<div style="display: flex; gap: 10px; align-items: center;">';
    print '<a href="'.$back_link.'" class="btn-outline">Cancel</a>';
    print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="btn-logout"><span>🚪</span> Logout</a>';
    print '</div>';
    print '</div>';
}

print $notice;

if (!$hide_form) {
    print '<div class="fb-card">';
    print '<form method="POST" action="'.basename(__FILE__).'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';

    print '<h3 style="margin: 0 0 25px 0; border-bottom: 1px solid #eee; padding-bottom: 10px; color: #2c3e50;">📋 Product Details</h3>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>Product Name</label><input type="text" name="product_name" class="form-control" required placeholder="e.g. Premium White Rice 50kg"></div>';
    print '<div class="form-group"><label>Category</label>';
    print '<select name="category" class="form-control">';
    print '<option value="">-- Select Category --</option>';
    $cats = ['Grains','Vegetables','Proteins','Dairy','Beverages','Packaged Foods','Other'];
    foreach($cats as $c) { print '<option value="'.$c.'">'.$c.'</option>'; }
    print '</select></div>';
    print '</div>';

    print '<div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">';
    print '<div class="form-group"><label>Quantity</label><input type="number" name="quantity" class="form-control" required min="1" value="1"></div>';
    print '<div class="form-group"><label>Unit</label><select name="unit" class="form-control"><option value="kg">kg</option><option value="bags">bags</option><option value="cartons">cartons</option><option value="liters">liters</option><option value="units">units</option></select></div>';
    
    // --- STATUS FIELD (Admin Only) ---
    if ($is_admin) {
        print '<div class="form-group"><label>Status</label><select name="status" class="form-control" style="font-weight:bold;">';
        print '<option value="Received" selected style="color:green;">✅ Received (In Stock)</option>';
        print '<option value="Pending" style="color:orange;">⏳ Pending (Expected)</option>';
        print '</select></div>';
    } else {
        // Vendors don't see this, strictly Pending
        print '<input type="hidden" name="status" value="Pending">';
    }
    print '</div>';

    print '<h3 style="margin: 30px 0 25px 0; border-bottom: 1px solid #eee; padding-bottom: 10px; color: #2c3e50;">🏭 Source & Storage</h3>';

    print '<div class="form-grid">';
    
    // --- VENDOR FIELD (Smart Logic) ---
    print '<div class="form-group"><label>Supply Source (Vendor)</label>';
    if ($is_admin) {
        // Admin sees dropdown
        print '<select name="fk_vendor" class="form-control" required>';
        print '<option value="">-- Select Vendor --</option>';
        foreach($vendors as $v) { print '<option value="'.$v->rowid.'">'.dol_escape_htmltag($v->name).'</option>'; }
        print '</select>';
    } else {
        // Vendor sees ONLY their name (Read Only)
        print '<input type="text" value="'.dol_escape_htmltag($current_vendor_name).'" class="form-control" disabled style="background:#f9f9f9; color:#666; font-weight:bold;">';
        print '<input type="hidden" name="fk_vendor" value="'.$current_vendor_id.'">';
    }
    print '</div>';

    print '<div class="form-group"><label>Target Warehouse</label><select name="fk_warehouse" class="form-control">';
    print '<option value="">-- Select Warehouse --</option>';
    foreach($warehouses as $w) { print '<option value="'.$w->rowid.'">'.dol_escape_htmltag($w->label).'</option>'; }
    print '</select></div>';
    print '</div>';

    print '<div class="form-group"><label>Notes / Condition</label><textarea name="note" class="form-control" rows="3" placeholder="Any details about expiry, condition, or batch number..."></textarea></div>';

    print '<div class="form-group"><label>Reference ID (Optional)</label><input type="text" name="ref" class="form-control" placeholder="Leave empty to auto-generate"></div>';

    print '<div style="margin-top: 30px; text-align: center;">';
    print '<button type="submit" class="btn-submit">💾 Save Inventory Log</button>';
    print '</div>';

    print '</form>';
    print '</div>';
}

print '</div>';
llxFooter();
?>