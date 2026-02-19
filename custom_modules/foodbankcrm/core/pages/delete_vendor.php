<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendor.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader('', 'Delete Vendor');

print '<style>
    #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 50px !important; }
    #mainmenutd_commercial, #mainmenutd_billing, #mainmenutd_compta, 
    #mainmenutd_projet, #mainmenutd_mrp, #mainmenutd_hrm, 
    #mainmenutd_ticket, #mainmenutd_agenda, #mainmenutd_documents, #mainmenutd_bank { display: none !important; }
    
    .fb-container { max-width: 600px; margin: 0 auto; text-align: center; }
    .warning-card { background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 40px; border-top: 6px solid #dc3545; }
    .warning-icon { font-size: 60px; margin-bottom: 20px; display: block; opacity: 0.8; }
    .detail-box { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 25px 0; text-align: left; border: 1px solid #eee; }
    .btn-group { display: flex; gap: 15px; justify-content: center; margin-top: 30px; }
    
    .button-danger { background-color: #dc3545; color: white; padding: 12px 30px; border-radius: 6px; border: none; font-weight: bold; text-decoration: none; cursor: pointer; }
    .button-cancel { background-color: #e2e6ea; color: #495057; padding: 12px 30px; border-radius: 6px; border: 1px solid #dae0e5; text-decoration: none; font-weight: bold; }
</style>';

if (!isset($_GET['id']) || empty($_GET['id'])) { header("Location: vendors.php"); exit; }

$id = (int)$_GET['id'];

// --- FIX: Use VendorFB instead of Vendor ---
$v = new VendorFB($db);

print '<div class="fb-container">';

if ($v->fetch($id) <= 0) {
    print '<div class="warning-card" style="border-top-color: #666;"><h2>Vendor Not Found</h2><br><a href="vendors.php" class="button-cancel">Return to List</a></div></div>';
    llxFooter(); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    print '<div class="warning-card">';
    print '<span class="warning-icon">⚠️</span>';
    print '<h2 style="margin: 0 0 10px 0; color: #dc3545;">Delete Vendor?</h2>';
    print '<p style="color: #666;">This action is permanent and cannot be undone.</p>';
    
    print '<div class="detail-box">';
    print '<strong>Business:</strong> '.dol_escape_htmltag($v->name).'<br>';
    print '<strong>Contact:</strong> '.dol_escape_htmltag($v->contact_person).'<br>';
    print '<strong>Ref ID:</strong> '.dol_escape_htmltag($v->ref);
    print '</div>';

    print '<form method="POST" action="'.basename(__FILE__).'?id='.$id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<div class="btn-group">';
    print '<a href="vendors.php" class="button-cancel">Cancel</a>';
    print '<button type="submit" name="confirm" class="button-danger">Yes, Delete Vendor</button>';
    print '</div>';
    print '</form>';
    print '</div>';

} else {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        print '<div class="error">Security token expired.</div>';
    } else {
        $res = $v->delete($user);
        if ($res > 0) {
            print '<div class="warning-card" style="border-top-color: #28a745;">';
            print '<span class="warning-icon">✅</span>';
            print '<h2 style="color: #28a745; margin-top:0;">Deleted Successfully</h2>';
            print '<br><a href="vendors.php" class="button-cancel" style="background:#28a745; color:white;">Back to Vendors</a>';
            print '</div>';
        } else {
            print '<div class="warning-card" style="border-top-color: #ffc107;">';
            print '<span class="warning-icon">🚫</span>';
            print '<h2 style="margin-top:0;">Cannot Delete Vendor</h2>';
            print '<p style="color: #666;">This vendor has linked donations and cannot be deleted safely.</p>';
            print '<br><a href="vendors.php" class="button-cancel">Back to List</a>';
            print '</div>';
        }
    }
}

print '</div>';
llxFooter();
?>