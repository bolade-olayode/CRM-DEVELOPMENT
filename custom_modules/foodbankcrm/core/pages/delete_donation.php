<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/donation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
$_SESSION["mainmenu"] = "foodbankcrm";
$_fb_admin_head = '<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">'
              . '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/css/admin_mobile.css">';
llxHeader($_fb_admin_head, 'Delete Donation');

// --- MODERN STYLES ---
print '<style>
    #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 50px !important; }
    
    #mainmenutd_commercial, #mainmenutd_billing, #mainmenutd_compta, 
    #mainmenutd_projet, #mainmenutd_mrp, #mainmenutd_hrm, 
    #mainmenutd_ticket, #mainmenutd_agenda, #mainmenutd_documents, #mainmenutd_bank {
        display: none !important;
    }

    .fb-container { max-width: 600px; margin: 0 auto; text-align: center; }
    
    .warning-card { 
        background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
        padding: 40px; border-top: 6px solid #dc3545; 
    }
    
    .warning-icon { font-size: 60px; margin-bottom: 20px; display: block; opacity: 0.8; }
    
    .detail-box { 
        background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 25px 0; 
        text-align: left; border: 1px solid #eee; font-size: 14px; line-height: 1.6;
    }
    
    .btn-group { display: flex; gap: 15px; justify-content: center; margin-top: 30px; }
    
    .button-danger { background-color: #dc3545; color: white; padding: 12px 30px; border-radius: 6px; border: none; font-weight: bold; text-decoration: none; cursor: pointer; }
    .button-danger:hover { background-color: #c82333; }
    
    .button-cancel { background-color: #e2e6ea; color: #495057; padding: 12px 30px; border-radius: 6px; border: 1px solid #dae0e5; text-decoration: none; font-weight: bold; }
    .button-cancel:hover { background-color: #dbe0e5; }
</style>';

$id = (int)$_GET['id'];
$d = new DonationFB($db);

print '<div class="fb-container">';

if ($d->fetch($id) <= 0) {
    print '<div class="warning-card" style="border-top-color: #666;"><h2>Donation Not Found</h2><br><a href="donations.php" class="button-cancel">Return to List</a></div>';
    llxFooter(); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // --- CONFIRMATION SCREEN ---
    print '<div class="warning-card">';
    print '<span class="warning-icon">⚠️</span>';
    print '<h2 style="margin: 0 0 10px 0; color: #dc3545;">Delete Donation?</h2>';
    print '<p style="color: #666;">This will permanently remove this inventory record.</p>';
    
    print '<div class="detail-box">';
    print '<strong>Ref:</strong> '.dol_escape_htmltag($d->ref).'<br>';
    print '<strong>Product:</strong> '.dol_escape_htmltag($d->product_name ?: $d->label).'<br>';
    print '<strong>Qty:</strong> '.number_format($d->quantity).' '.dol_escape_htmltag($d->unit);
    print '</div>';

    print '<form method="POST" action="'.basename(__FILE__).'?id='.$id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    
    print '<div class="btn-group">';
    print '<a href="donations.php" class="button-cancel">Cancel</a>';
    print '<button type="submit" name="confirm" class="button-danger">Yes, Delete</button>';
    print '</div>';
    
    print '</form>';
    print '</div>';

} else {
    // --- EXECUTE DELETE ---
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        print '<div class="error">Security token expired.</div>';
    } else {
        $res = $d->delete($user);
        
        if ($res > 0) {
            print '<div class="warning-card" style="border-top-color: #28a745;">';
            print '<span class="warning-icon">✅</span>';
            print '<h2 style="color: #28a745; margin-top:0;">Deleted Successfully</h2>';
            print '<br><a href="donations.php" class="button-cancel" style="background:#28a745; color:white; border-color:#28a745;">Back to Donations</a>';
            print '</div>';
        } else {
            print '<div class="warning-card" style="border-top-color: #ffc107;">';
            print '<span class="warning-icon">🚫</span>';
            print '<h2 style="margin-top:0;">Cannot Delete</h2>';
            print '<p style="color: #666;">This donation has likely been allocated to food packages or distributions already.</p>';
            print '<br><a href="donations.php" class="button-cancel">Back to List</a>';
            print '</div>';
        }
    }
}

print '</div>';
llxFooter();
?>