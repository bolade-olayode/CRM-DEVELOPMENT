<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/foodbankcrm/class/beneficiary.class.php';

$langs->load("foodbankcrm@foodbankcrm");
llxHeader('', $langs->trans("Beneficiaries"));

$action = GETPOST('action','alpha');
$bf = new Beneficiary($db);

// Create
if ($action === 'create') {
    $bf->ref = 'BEN-'.time();
    $bf->firstname = GETPOST('firstname','alpha');
    $bf->lastname = GETPOST('lastname','alpha');
    $bf->phone = GETPOST('phone','alpha');
    $bf->email = GETPOST('email','alpha');
    $bf->address = GETPOST('address','alpha');
    $bf->note = GETPOST('note','alpha');
    $res = $bf->create();
    if ($res) setEventMessage("Beneficiary created");
    else setEventMessages($bf->errors, 'errors');
}

// Listing
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries ORDER BY rowid DESC LIMIT 200";
$resql = $db->query($sql);

print '<h2>Beneficiaries</h2>';
print '<a href="?action=new">Create beneficiary</a>';

if ($action === 'new') {
    print '<form method="post">';
    print '<input type="hidden" name="action" value="create">';
    print 'First name: <input name="firstname"><br>';
    print 'Last name: <input name="lastname"><br>';
    print 'Phone: <input name="phone"><br>';
    print 'Email: <input name="email"><br>';
    print 'Address: <input name="address"><br>';
    print 'Note: <textarea name="note"></textarea><br>';
    print '<button type="submit">Create</button>';
    print '</form>';
}

print '<table border="1" cellpadding="5"><thead><tr><th>ID</th><th>Ref</th><th>Name</th><th>Phone</th><th>Email</th></tr></thead><tbody>';
if ($resql) {
    while ($row = $db->fetch_object($resql)) {
        print '<tr>';
        print '<td>'.$row->rowid.'</td>';
        print '<td>'.$row->ref.'</td>';
        print '<td>'.$row->firstname.' '.$row->lastname.'</td>';
        print '<td>'.$row->phone.'</td>';
        print '<td>'.$row->email.'</td>';
        print '</tr>';
    }
}
print '</tbody></table>';

llxFooter();
