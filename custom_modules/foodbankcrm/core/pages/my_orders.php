<?php
/**
 * My Orders - View Order History
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

// Reset redirect flag
if (isset($_SESSION['foodbank_checked'])) {
    $_SESSION['foodbank_checked'] = false;
}

$langs->load("admin");

// Security check - beneficiary only
$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);

if (!$user_is_beneficiary) {
    accessforbidden('You do not have access to orders.');
}

// Get beneficiary ID
$sql_ben = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res_ben = $db->query($sql_ben);
$beneficiary = $db->fetch_object($res_ben);
$beneficiary_id = $beneficiary->rowid;

// Get filter
$status_filter = GETPOST('status', 'alpha');

llxHeader('', 'My Orders');

// Hide left menu and make FULL WIDTH
echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }
</style>';

print '<div style="width: 100%; padding: 30px; box-sizing: border-box;">';

print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<h1 style="margin: 0;">📦 My Orders</h1>';
print '<a href="dashboard_beneficiary.php" class="butAction">← Back to Dashboard</a>';
print '</div>';

// Status filter
print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" style="margin-bottom: 25px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<label style="margin-right: 15px; font-weight: bold; font-size: 15px;">Filter by Status:</label>';
print '<select name="status" onchange="this.form.submit()" style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 15px;">';
print '<option value="">All Orders</option>';
print '<option value="Prepared" '.($status_filter == 'Prepared' ? 'selected' : '').'>Prepared</option>';
print '<option value="Packed" '.($status_filter == 'Packed' ? 'selected' : '').'>Packed</option>';
print '<option value="Ready" '.($status_filter == 'Ready' ? 'selected' : '').'>Ready for Pickup</option>';
print '<option value="Collected" '.($status_filter == 'Collected' ? 'selected' : '').'>Collected</option>';
print '<option value="Delivered" '.($status_filter == 'Delivered' ? 'selected' : '').'>Delivered</option>';
print '</select>';
print '</form>';

// Query orders - CORRECTED COLUMN NAME
$sql = "SELECT d.rowid, d.ref, d.date_distribution, d.status, d.payment_method, 
        d.total_amount, d.note
        FROM ".MAIN_DB_PREFIX."foodbank_distributions d
        WHERE d.fk_beneficiary = ".(int)$beneficiary_id;

if ($status_filter) {
    $sql .= " AND d.status = '".$db->escape($status_filter)."'";
}

$sql .= " ORDER BY d.date_distribution DESC";

$resql = $db->query($sql);

if ($resql) {
    $num = $db->num_rows($resql);
    
    if ($num > 0) {
        print '<div style="display: grid; gap: 25px;">';
        
        while ($obj = $db->fetch_object($resql)) {
            // Status color
            $status_colors = array(
                'Prepared' => '#ffc107',
                'Packed' => '#17a2b8',
                'Ready' => '#6f42c1',
                'Collected' => '#fd7e14',
                'Delivered' => '#28a745'
            );
            $color = $status_colors[$obj->status] ?? '#6c757d';
            
            print '<div style="background: white; border-left: 5px solid '.$color.'; border-radius: 8px; padding: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
            
            print '<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">';
            print '<div>';
            print '<h3 style="margin: 0 0 8px 0; font-size: 20px;">Order '.dol_escape_htmltag($obj->ref).'</h3>';
            print '<p style="margin: 0; color: #666; font-size: 15px;">'.dol_print_date($db->jdate($obj->date_distribution), 'day').'</p>';
            print '</div>';
            print '<div style="background: '.$color.'; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; font-size: 14px;">';
            print dol_escape_htmltag($obj->status);
            print '</div>';
            print '</div>';
            
            print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">';
            
            if ($obj->payment_method) {
                print '<div>';
                print '<div style="color: #666; font-size: 13px; margin-bottom: 5px;">Payment Method</div>';
                print '<div style="font-weight: bold; font-size: 15px;">'.dol_escape_htmltag($obj->payment_method).'</div>';
                print '</div>';
            }
            
            print '<div>';
            print '<div style="color: #666; font-size: 13px; margin-bottom: 5px;">Total Amount</div>';
            print '<div style="font-weight: bold; font-size: 22px; color: #28a745;">₦'.number_format($obj->total_amount, 0).'</div>';
            print '</div>';
            
            print '</div>';
            
            if ($obj->note) {
                print '<div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px;">';
                print '<strong style="font-size: 14px;">Notes:</strong><br>';
                print '<span style="font-size: 14px;">'.nl2br(dol_escape_htmltag($obj->note)).'</span>';
                print '</div>';
            }
            
            print '<a href="view_order.php?id='.$obj->rowid.'" class="butAction" style="margin: 0; padding: 10px 20px; font-size: 15px;">View Details</a>';
            
            print '</div>';
        }
        
        print '</div>';
    } else {
        print '<div style="text-align: center; padding: 80px 20px; background: white; border-radius: 8px;">';
        print '<div style="font-size: 80px; margin-bottom: 20px;">📦</div>';
        print '<h2 style="margin: 0 0 10px 0;">No Orders Found</h2>';
        print '<p style="color: #666; font-size: 16px; margin-bottom: 30px;">You haven\'t placed any orders yet.</p>';
        print '<a href="product_catalog.php" class="butAction" style="padding: 12px 24px; font-size: 16px;">BROWSE PRODUCTS</a>';
        print '</div>';
    }
} else {
    print '<div class="error">Error loading orders: '.$db->lasterror().'</div>';
}

print '</div>';

llxFooter();
?>
