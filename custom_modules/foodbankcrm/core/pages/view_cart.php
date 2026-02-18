<?php
/**
 * View Cart - CSRF FIXED
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;
$langs->load("admin");

// Security
if (!FoodbankPermissions::isBeneficiary($user, $db)) {
    accessforbidden('You do not have access to this page.');
}

// --- CART LOGIC ---

// 1. Add to Cart
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $id = (int)$_POST['id'];
    $qty = 1;
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    
    if (isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id] += $qty;
    else $_SESSION['cart'][$id] = $qty;
    
    header("Location: view_cart.php"); 
    exit;
}

// 2. Update Quantity (CSRF PROTECTED)
if (isset($_POST['update_qty'])) {
    // Check Token
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        setEventMessages("Security check failed. Please try again.", null, 'errors');
    } else {
        foreach ($_POST['qty'] as $id => $val) {
            $val = (int)$val;
            if ($val > 0) $_SESSION['cart'][$id] = $val;
            else unset($_SESSION['cart'][$id]);
        }
        setEventMessages("Cart updated successfully", null, 'mesgs');
    }
    // Refresh to clear post data
    header("Location: view_cart.php");
    exit;
}

// 3. Remove Item
if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    unset($_SESSION['cart'][$id]);
    header("Location: view_cart.php");
    exit;
}

llxHeader('', 'My Cart');

// --- CSS FIXES ---
print '<style>
    /* 1. HIDE CHROME */
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }

    /* 2. FULL WIDTH CONTAINER */
    html, body { background-color: #f8f9fa !important; margin: 0; width: 100%; overflow-x: hidden; }
    #id-right, .id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
    #id-container { width: 100% !important; margin: 0 !important; display: block !important; }
    .fiche { width: 100% !important; max-width: 100% !important; margin: 0 !important; }
    
    /* 3. CART STYLES */
    .ben-container { 
        width: 98%; 
        max-width: none; 
        margin: 0 auto; 
        padding: 30px 0; 
        font-family: "Segoe UI", sans-serif; 
    }

    .cart-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        overflow: hidden;
        border: 1px solid #eee;
    }

    .cart-table {
        width: 100%;
        border-collapse: collapse;
    }

    .cart-table th {
        background: #f8f9fa;
        text-align: left;
        padding: 20px;
        font-weight: bold;
        color: #555;
        border-bottom: 2px solid #eee;
    }

    .cart-table td {
        padding: 20px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    .cart-img {
        width: 50px;
        height: 50px;
        background: #f0f0f0;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        float: left;
        margin-right: 15px;
    }

    .qty-input {
        width: 60px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 6px;
        text-align: center;
        margin-right: 10px;
    }

    /* BUTTONS */
    .btn-update {
        background: #667eea;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        font-weight: bold;
    }
    .btn-update:hover { background: #5a6fd6; }

    .btn-remove {
        color: #dc3545;
        text-decoration: none;
        font-weight: bold;
        font-size: 13px;
        background: #ffebee;
        padding: 8px 15px;
        border-radius: 6px;
    }
    .btn-remove:hover { background: #ffcdd2; }

    .btn-checkout {
        background: #28a745;
        color: white;
        padding: 15px 40px;
        border-radius: 30px;
        border: none;
        text-decoration: none;
        font-weight: bold;
        font-size: 16px;
        display: inline-block;
        box-shadow: 0 4px 15px rgba(40,167,69,0.3);
        transition: transform 0.2s;
        cursor: pointer;
    }
    .btn-checkout:hover { transform: translateY(-2px); background: #218838; }

    .btn-shop {
        background: #fff;
        color: #667eea;
        border: 2px solid #667eea;
        padding: 13px 30px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: bold;
        font-size: 16px;
        display: inline-block;
        transition: all 0.2s;
    }
    .btn-shop:hover { background: #f3f4fd; }

    .total-row {
        background: #fcfcfc;
        text-align: right;
        padding: 30px;
        font-size: 20px;
    }
</style>';

print '<div class="ben-container">';

print '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; padding:0 10px;">
        <div><h1 style="margin:0; color:#2c3e50;">🛒 Your Cart</h1><p style="color:#7f8c8d; margin:5px 0 0 0;">Manage your items before checkout</p></div>
        <a href="dashboard_beneficiary.php" class="button" style="background:#eee; color:#333; padding:10px 20px; border-radius:20px; text-decoration:none;">Back to Dashboard</a>
       </div>';

if (empty($_SESSION['cart'])) {
    print '<div style="text-align:center; padding:80px; background:white; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.05);">
            <div style="font-size:80px; margin-bottom:20px;">🕸️</div>
            <h2 style="color:#555;">Your cart is empty</h2>
            <p style="color:#888; margin-bottom:30px;">Looks like you haven\'t added any food boxes yet.</p>
            <a href="product_catalog.php" class="btn-checkout" style="background:#667eea; box-shadow:0 4px 15px rgba(102,126,234,0.3); text-decoration:none;">Browse Packages</a>
           </div>';
} else {
    print '<form method="POST" action="view_cart.php">';
    // --- CSRF TOKEN ADDED HERE ---
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="update_qty" value="1">';
    
    print '<div class="cart-card">';
    print '<table class="cart-table">';
    print '<thead><tr><th>Package Details</th><th>Quantity</th><th>Price</th><th>Total</th><th>Action</th></tr></thead>';
    print '<tbody>';
    
    $total_cart = 0;
    
    foreach ($_SESSION['cart'] as $id => $qty) {
        $sql = "SELECT rowid, name, description FROM ".MAIN_DB_PREFIX."foodbank_packages WHERE rowid = ".(int)$id;
        $res = $db->query($sql);
        if ($obj = $db->fetch_object($res)) {
            // Calculate real price from package items
            $sql_price = "SELECT SUM(pi.quantity * pi.unit_price) as total_price FROM ".MAIN_DB_PREFIX."foodbank_package_items pi WHERE pi.fk_package = ".(int)$id;
            $res_price = $db->query($sql_price);
            $obj_price = $db->fetch_object($res_price);
            $price = ($obj_price && $obj_price->total_price) ? (float)$obj_price->total_price : 0;
            $subtotal = $price * $qty;
            $total_cart += $subtotal;
            
            print '<tr>';
            print '<td>
                    <div class="cart-img">📦</div>
                    <div style="font-weight:bold; color:#333;">'.dol_escape_htmltag($obj->name).'</div>
                    <div style="font-size:12px; color:#888;">Ref: PKG-'.str_pad($obj->rowid, 4, '0', STR_PAD_LEFT).'</div>
                   </td>';
            print '<td>
                    <input type="number" name="qty['.$id.']" value="'.$qty.'" min="1" class="qty-input">
                    <button type="submit" class="btn-update">Update</button>
                   </td>';
            print '<td>₦'.number_format($price).'</td>';
            print '<td style="font-weight:bold; color:#2c3e50;">₦'.number_format($subtotal).'</td>';
            print '<td><a href="view_cart.php?remove='.$id.'" class="btn-remove">Remove</a></td>';
            print '</tr>';
        }
    }
    
    print '</tbody>';
    print '</table>';
    
    print '<div class="total-row">';
    print 'Total Amount: <span style="font-size:28px; font-weight:800; color:#28a745; margin-left:15px;">₦'.number_format($total_cart).'</span>';
    print '</div>';
    
    print '</div>'; 
    
    print '<div style="margin-top:30px; display:flex; justify-content:space-between; align-items:center; padding:0 10px;">';
    print '<a href="product_catalog.php" class="btn-shop">← Continue Shopping</a>';
    
    // CHECKOUT BUTTON
    print '<button type="button" onclick="window.location.href=\'checkout.php\'" class="btn-checkout">Proceed to Checkout →</button>';
    
    print '</div>';
    print '</form>';
}

print '</div>';
llxFooter();
?>