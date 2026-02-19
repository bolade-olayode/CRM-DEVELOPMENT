<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader('', 'Delete Shipment');

print '<style>div#id-top, #id-top { display: none !important; } .side-nav { top: 0 !important; height: 100vh !important; } #id-right { padding-top: 50px !important; } .fb-container { max-width: 600px; margin: 0 auto; text-align: center; } .warning-card { background: #fff; border-radius: 12px; padding: 40px; border-top: 6px solid #dc3545; box-shadow: 0 10px 25px rgba(0,0,0,0.1); } .button-danger { background: #dc3545; color: white; padding: 12px 30px; border: none; font-weight: bold; cursor: pointer; border-radius: 5px; } .button-cancel { background: #eee; color: #333; padding: 12px 30px; text-decoration: none; font-weight: bold; border-radius: 5px; }</style>';

$id = (int)$_GET['id'];
$d = new Distribution($db);

print '<div class="fb-container">';

if ($d->fetch($id) <= 0) {
    print '<div class="warning-card"><h2>Record Not Found</h2><br><a href="distributions.php" class="button-cancel">Return to List</a></div>';
    llxFooter(); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    print '<div class="warning-card">';
    print '<h2 style="color: #dc3545;">Delete Shipment?</h2>';
    print '<p>Are you sure you want to delete <strong>'.$d->ref.'</strong>?</p>';
    print '<p style="color: #666; font-size: 13px;">Note: This will restore the inventory allocated to this order.</p>';
    print '<form method="POST" action="'.basename(__FILE__).'?id='.$id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<div style="margin-top: 30px; display: flex; gap: 15px; justify-content: center;">';
    print '<a href="distributions.php" class="button-cancel">Cancel</a>';
    print '<button type="submit" name="confirm" class="button-danger">Yes, Delete</button>';
    print '</div></form></div>';
} else {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) die("Invalid Token");
    
    if ($d->delete($user) > 0) {
        print '<div class="warning-card" style="border-top-color: #28a745;"><h2>Deleted Successfully</h2><p>Inventory has been restored.</p><br><a href="distributions.php" class="button-cancel">Back to List</a></div>';
    } else {
        print '<div class="warning-card"><h2>Error</h2><p>'.$d->error.'</p><br><a href="distributions.php" class="button-cancel">Back to List</a></div>';
    }
}

print '</div>';
llxFooter();
?>