<?php
/**
 * My Orders - View Order History (Modern UI)
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

// Reset redirect flag if present
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

// --- MODERN UI CSS ---
print '<style>
    /* 1. HIDE DOLIBARR CHROME */
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header {
        display: none !important;
    }
    
    /* 2. LAYOUT RESET */
    html, body { background-color: #f8f9fa !important; margin: 0; width: 100%; overflow-x: hidden; }
    #id-right, .id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
    .fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }

    /* 3. PAGE CONTAINER */
    .ben-container { width: 95%; max-width: 1200px; margin: 0 auto; padding: 40px 20px; font-family: "Segoe UI", sans-serif; }

    /* 4. CARD GRID SYSTEM */
    .orders-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
    }

    /* 5. ORDER CARD STYLING */
    .order-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border: 1px solid #f0f0f0;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .order-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    
    /* 6. BADGES */
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
        color: white;
    }

    /* 7. FORM ELEMENTS */
    .filter-select {
        padding: 12px 20px;
        border: 1px solid #ddd;
        border-radius: 30px;
        font-size: 14px;
        background: white;
        cursor: pointer;
        outline: none;
        min-width: 200px;
    }
    
    /* 8. BUTTONS */
    .btn-view {
        background: #f8f9fa;
        color: #333;
        text-decoration: none;
        padding: 12px;
        border-radius: 8px;
        text-align: center;
        font-weight: 600;
        transition: background 0.2s;
        display: block;
        margin-top: 20px;
    }
    .btn-view:hover { background: #e9ecef; color: #000; }
    
    .btn-back {
        text-decoration: none;
        color: #666;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .btn-back:hover { color: #333; }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .orders-grid { grid-template-columns: 1fr; }
        .header-flex { flex-direction: column; align-items: flex-start; gap: 15px; }
    }
</style>';

print '<div class="ben-container">';

// --- HEADER ---
print '<div class="header-flex" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">';
print '<div>';
print '<h1 style="margin: 0 0 5px 0; color: #2c3e50;">📦 My Orders</h1>';
print '<p style="margin: 0; color: #666;">Track your past and current requests</p>';
print '</div>';
print '<a href="dashboard_beneficiary.php" class="btn-back"><span>←</span> Back to Dashboard</a>';
print '</div>';

// --- FILTER BAR ---
print '<div style="margin-bottom: 30px; display: flex; justify-content: flex-end;">';
print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'">';
print '<select name="status" class="filter-select" onchange="this.form.submit()">';
print '<option value="">Show All Orders</option>';
print '<option value="Pending" '.($status_filter == 'Pending' ? 'selected' : '').'>⚪ Pending</option>';
print '<option value="Prepared" '.($status_filter == 'Prepared' ? 'selected' : '').'>🟡 Prepared</option>';
print '<option value="Bundled" '.($status_filter == 'Bundled' ? 'selected' : '').'>🔵 Bundled</option>';
print '<option value="In Transit" '.($status_filter == 'In Transit' ? 'selected' : '').'>🟣 In Transit</option>';
print '<option value="Delivered" '.($status_filter == 'Delivered' ? 'selected' : '').'>🟢 Delivered</option>';
print '</select>';
print '</form>';
print '</div>';

// --- FETCH ORDERS ---
$sql = "SELECT d.rowid, d.ref, d.date_distribution, d.status, d.payment_method, 
        d.total_amount, d.note, d.payment_status
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
        print '<div class="orders-grid">';
        
        while ($obj = $db->fetch_object($resql)) {
            // Status Logic
            $status_colors = array(
                'Pending' => '#6c757d',    // Grey
                'Prepared' => '#ffc107',   // Yellow
                'Bundled' => '#17a2b8',    // Teal
                'In Transit' => '#6f42c1', // Purple
                'Delivered' => '#28a745',  // Green
            );
            $bg_color = $status_colors[$obj->status] ?? '#6c757d';
            
            // Payment Logic
            $pay_icon = ($obj->payment_method == 'pay_now') ? '💳' : '💵';
            $pay_text = ($obj->payment_method == 'pay_now') ? 'Paystack' : 'Cash on Delivery';
            
            // Payment Status Warning
            $payment_warning = '';
            if ($obj->payment_status != 'Paid' && $obj->payment_status != 'Success' && $obj->payment_method == 'pay_now') {
                 $payment_warning = '<div style="margin-top:5px; font-size:12px; color:#dc3545; font-weight:bold;">⚠️ Payment Pending</div>';
            }
            
            // --- CARD START ---
            print '<div class="order-card">';
            
            // Top: Ref & Status
            print '<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">';
            print '<div>';
            print '<div style="font-weight: bold; font-size: 18px; color: #333;">'.dol_escape_htmltag($obj->ref).'</div>';
            print '<div style="color: #999; font-size: 13px; margin-top: 4px;">'.dol_print_date($db->jdate($obj->date_distribution), 'day').'</div>';
            print '</div>';
            print '<span class="status-badge" style="background-color: '.$bg_color.';">'.dol_escape_htmltag($obj->status).'</span>';
            print '</div>';
            
            // Middle: Details
            print '<div style="flex-grow: 1;">';
            
            // Total Amount
            print '<div style="margin-bottom: 15px;">';
            print '<div style="font-size: 12px; text-transform: uppercase; color: #999; font-weight: bold; letter-spacing: 0.5px;">Total Amount</div>';
            print '<div style="font-size: 24px; font-weight: bold; color: #2c3e50;">₦'.number_format($obj->total_amount, 2).'</div>';
            print '</div>';
            
            // Payment Method
            print '<div style="display: flex; align-items: center; gap: 8px; color: #555; font-size: 14px;">';
            print '<span>'.$pay_icon.'</span>';
            print '<span>'.$pay_text.'</span>';
            print '</div>';
            print $payment_warning;

            // Note Preview (Truncated)
            if ($obj->note) {
                $note_short = substr($obj->note, 0, 50) . (strlen($obj->note) > 50 ? '...' : '');
                print '<div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 6px; font-size: 13px; color: #666;">';
                print '📝 '.dol_escape_htmltag($note_short);
                print '</div>';
            }
            print '</div>'; // End Middle
            
            // Bottom: Action Button
            // If payment pending, show Pay Now, else View Details
            if ($obj->payment_status != 'Paid' && $obj->payment_status != 'Success' && $obj->payment_method == 'pay_now') {
                print '<a href="process_order_payment.php?order_id='.$obj->rowid.'" class="btn-view" style="background:#28a745; color:white;">💳 Pay Now</a>';
            } else {
                print '<a href="view_order.php?id='.$obj->rowid.'" class="btn-view">View Details</a>';
            }
            
            print '</div>'; // End Card
        }
        print '</div>'; // End Grid
        
    } else {
        // EMPTY STATE
        print '<div style="text-align: center; padding: 80px 20px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">';
        print '<div style="font-size: 80px; margin-bottom: 20px; opacity: 0.5;">📦</div>';
        print '<h2 style="margin: 0 0 10px 0; color: #333;">No Orders Found</h2>';
        print '<p style="color: #666; font-size: 16px; margin-bottom: 30px;">You haven\'t placed any orders yet.</p>';
        print '<a href="product_catalog.php" class="btn-view" style="display: inline-block; background: #667eea; color: white; padding: 12px 30px;">Start Shopping</a>';
        print '</div>';
    }
} else {
    print '<div class="ben-error">Error loading orders: '.$db->lasterror().'</div>';
}

print '</div>'; // End Container

llxFooter();
?>