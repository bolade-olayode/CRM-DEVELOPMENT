<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/package.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
$_SESSION["mainmenu"] = "foodbankcrm";
$_fb_admin_head = '<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">'
              . '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/css/admin_mobile.css">';
llxHeader($_fb_admin_head, 'Delete Package');

print '<style>
    div#id-top, #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 50px !important; }
    #mainmenutd_commercial, #mainmenutd_billing, #mainmenutd_compta, 
    #mainmenutd_projet, #mainmenutd_mrp, #mainmenutd_hrm, 
    #mainmenutd_ticket, #mainmenutd_agenda, #mainmenutd_documents, #mainmenutd_bank { display: none !important; }
    
    .fb-container { max-width: 600px; margin: 0 auto; text-align: center; }
    .warning-card { background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 40px; border-top: 6px solid #dc3545; }
    .button-danger { background-color: #dc3545; color: white; padding: 12px 30px; border-radius: 6px; border: none; font-weight: bold; cursor: pointer; }
    .button-cancel { background-color: #e2e6ea; color: #495057; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: bold; }
</style>';

$id = (int)$_GET['id'];
$p = new Package($db);

print '<div class="fb-container">';

if ($p->fetch($id) <= 0) {
    print '<div class="warning-card"><h2>Package Not Found</h2><br><a href="packages.php" class="button-cancel">Return to List</a></div>';
    llxFooter(); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    print '<div class="warning-card">';
    print '<h2 style="color: #dc3545;">Delete Package?</h2>';
    print '<p>This will permanently delete the <strong>'.dol_escape_htmltag($p->name).'</strong> template.</p>';
    print '<form method="POST" action="'.basename(__FILE__).'?id='.$id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<div style="margin-top: 30px; display: flex; gap: 15px; justify-content: center;">';
    print '<a href="packages.php" class="button-cancel">Cancel</a>';
    print '<button type="submit" name="confirm" class="button-danger">Yes, Delete</button>';
    print '</div></form></div>';
} else {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) die("Invalid Token");
    
    if ($p->delete($user) > 0) {
        print '<div class="warning-card" style="border-top-color: #28a745;"><h2>Deleted Successfully</h2><br><a href="packages.php" class="button-cancel">Back to List</a></div>';
    } else {
        print '<div class="warning-card"><h2>Cannot Delete</h2><p>Error: '.$p->error.'</p><br><a href="packages.php" class="button-cancel">Back to List</a></div>';
    }
}

print '</div>';
llxFooter();
?>