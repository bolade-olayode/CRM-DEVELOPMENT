<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distribution.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distributionline.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/package.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/packageitem.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

// --- LOGIC: HANDLE SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_distribution'])) {
    // 1. Validation
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        setEventMessages("Security token expired. Please try again.", null, 'errors');
    } else {
        $db->begin();
        
        // 2. Create Distribution Header
        $distribution = new Distribution($db);
        $distribution->fk_beneficiary = GETPOST('fk_beneficiary', 'int');
        $distribution->fk_package = GETPOST('fk_package', 'int');
        $distribution->fk_warehouse = GETPOST('fk_warehouse', 'int');
        $distribution->fk_user = $user->id;
        $distribution->note = GETPOST('note', 'restricthtml');
        $distribution->status = 'Prepared';
        $distribution->payment_status = 'Pending';
        $distribution->total_amount = 0; // Calculated below
        
        $dist_id = $distribution->create($user);
        
        if ($dist_id > 0) {
            $total_value = 0;
            $items_added = 0;
            
            // 3. Fetch Items from the Selected Package
            $pkg_items = PackageItem::getAllByPackage($db, $distribution->fk_package);
            
            foreach ($pkg_items as $p_item) {
                // AUTO-ALLOCATE: Find oldest stock (FIFO)
                $avail = Distribution::getAvailableDonations($db, $p_item->product_name);
                
                if (!empty($avail) && isset($avail[0])) {
                    $source = $avail[0]; // Take the first available batch
                    
                    $line = new DistributionLine($db);
                    $line->fk_distribution = $dist_id;
                    $line->fk_donation = $source['id'];
                    $line->product_name = $p_item->product_name;
                    $line->quantity = $p_item->quantity;
                    $line->unit = $p_item->unit;
                    
                    if ($line->create($user) > 0) {
                        $items_added++;
                        // Calculate Cost
                        $price = isset($source['unit_price']) ? $source['unit_price'] : 0;
                        $total_value += ($p_item->quantity * $price);
                    }
                }
            }
            
            // 4. Finalize
            if ($items_added > 0) {
                $distribution->total_amount = $total_value;
                $distribution->update($user);
                $db->commit();
                
                // SUCCESS REDIRECT
                header("Location: view_distribution.php?id=".$dist_id."&msg=created");
                exit;
            } else {
                $db->rollback();
                setEventMessages("Stock Error: We could not find enough 'Received' inventory for the items in this package.", null, 'errors');
            }
        } else {
            $db->rollback();
            setEventMessages("Error creating distribution: ".$distribution->error, null, 'errors');
        }
    }
}

// --- VIEW: PAGE CONTENT ---
$langs->load("admin");
llxHeader('', 'New Shipment');

print '<style>
    div#id-top, #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; }
    
    .fb-container { max-width: 900px; margin: 0 auto; padding: 20px; }
    .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 40px; border: 1px solid #eee; }
    
    .form-section { margin-bottom: 25px; }
    .form-label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; font-size: 14px; }
    .form-input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
    
    .pkg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-top: 10px; }
    .pkg-card { border: 2px solid #eee; padding: 15px; border-radius: 8px; cursor: pointer; transition: all 0.2s; position: relative; }
    .pkg-card:hover { border-color: #667eea; background: #f9faff; }
    .pkg-card.selected { border-color: #667eea; background: #f0f4ff; box-shadow: 0 0 0 1px #667eea; }
    .pkg-card input { display: none; }
    .pkg-title { font-weight: bold; display: block; margin-bottom: 5px; color: #333; }
    .pkg-desc { font-size: 12px; color: #666; display: block; }
</style>';

print '<div class="fb-container">';

print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
print '<div><h1 style="margin: 0;">🚚 Quick Shipment</h1><p style="color:#888; margin: 5px 0 0 0;">Create a delivery order instantly</p></div>';
print '<a href="distributions.php" class="button" style="background:#eee; color:#333;">Cancel</a>';
print '</div>';

print '<div class="fb-card">';
print '<form method="POST" action="'.basename(__FILE__).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="create_distribution" value="1">';

// 1. Beneficiary
print '<div class="form-section">';
print '<label class="form-label">1. Select Beneficiary</label>';
print '<select name="fk_beneficiary" required class="form-input">';
print '<option value="">-- Choose Beneficiary --</option>';
$res = $db->query("SELECT rowid, firstname, lastname, ref FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries ORDER BY firstname");
while ($obj = $db->fetch_object($res)) {
    print '<option value="'.$obj->rowid.'">'.dol_escape_htmltag($obj->firstname.' '.$obj->lastname.' ('.$obj->ref.')').'</option>';
}
print '</select></div>';

// 2. Warehouse
print '<div class="form-section">';
print '<label class="form-label">2. Source Warehouse</label>';
print '<select name="fk_warehouse" required class="form-input">';
$res = $db->query("SELECT rowid, label FROM ".MAIN_DB_PREFIX."foodbank_warehouses ORDER BY label");
while ($obj = $db->fetch_object($res)) {
    print '<option value="'.$obj->rowid.'">'.dol_escape_htmltag($obj->label).'</option>';
}
print '</select></div>';

// 3. Package Selection
print '<div class="form-section">';
print '<label class="form-label">3. Select Package Template</label>';
print '<div class="pkg-grid">';

$sql = "SELECT rowid, name, description FROM ".MAIN_DB_PREFIX."foodbank_packages WHERE status = 'Active'";
$res = $db->query($sql);
if ($db->num_rows($res) > 0) {
    while ($obj = $db->fetch_object($res)) {
        print '<label class="pkg-card" onclick="selectPkg(this)">';
        print '<input type="radio" name="fk_package" value="'.$obj->rowid.'" required>';
        print '<span class="pkg-title">📦 '.dol_escape_htmltag($obj->name).'</span>';
        print '<span class="pkg-desc">'.dol_escape_htmltag(dol_trunc($obj->description, 60)).'</span>';
        print '</label>';
    }
} else {
    print '<div style="padding:15px; background:#fff3cd; color:#856404; border-radius:6px; grid-column:1/-1; text-align:center;">⚠️ No Active Packages found. Please create one first.</div>';
}
print '</div></div>';

print '<div class="form-section">';
print '<label class="form-label">Notes (Optional)</label>';
print '<textarea name="note" rows="2" class="form-input" placeholder="Delivery instructions..."></textarea>';
print '</div>';

print '<div style="margin-top: 30px; text-align: center;">';
print '<button type="submit" class="butAction" style="padding: 15px 60px; font-size: 16px; border-radius: 30px; font-weight: bold;">Create Shipment</button>';
print '</div>';

print '</form></div></div>';

print '<script>
function selectPkg(el) {
    document.querySelectorAll(".pkg-card").forEach(c => c.classList.remove("selected"));
    el.classList.add("selected");
}
</script>';

llxFooter();
?>