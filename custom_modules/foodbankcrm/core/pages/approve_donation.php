<?php
/**
 * Approve a pending donation/inventory entry — sets status to 'Received'.
 * Admin only. Accessed via view_donation.php.
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/donation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$id = GETPOST('id', 'int');
if (!$id) {
    header('Location: donations.php');
    exit;
}

$donation = new DonationFB($db);
if ($donation->fetch($id) <= 0) {
    header('Location: donations.php');
    exit;
}

// Only pending donations can be approved
if ($donation->status !== 'Pending') {
    setEventMessages('This entry is already '.$donation->status.' and cannot be approved again.', null, 'warnings');
    header('Location: view_donation.php?id='.$id);
    exit;
}

$donation->status = 'Received';
$result = $donation->update($user);

$langs->load("admin");
$_SESSION["mainmenu"] = "foodbankcrm";

if ($result > 0) {
    setEventMessages('Inventory entry approved and marked as Received.', null, 'mesgs');
} else {
    setEventMessages('Failed to approve: '.$donation->error, null, 'errors');
}

header('Location: view_donation.php?id='.$id);
exit;
