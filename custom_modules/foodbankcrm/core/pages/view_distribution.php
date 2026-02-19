<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distribution.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distributionline.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader('', 'Shipment Details');

if (!isset($_GET['id'])) { header("Location: distributions.php"); exit; }
$id = (int)$_GET['id'];

print '<style>div#id-top, #id-top { display: none !important; } .side-nav { top: 0 !important; height: 100vh !important; } #id-right { padding-top: 30px !important; } .fb-container { max-width: 900px; margin: 0 auto; padding: 20px; } .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 40px; border: 1px solid #eee; margin-bottom: 20px; } .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; } .section-title { font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; } .clean-table { width: 100%; border-collapse: collapse; margin-top: 15px; } .clean-table th { background: #f8f9fa; text-align: left; padding: 10px; } .clean-table td { padding: 10px; border-bottom: 1px solid #eee; }</style>';

// --- CATCH SUCCESS MESSAGES ---
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'created') {
        print '<div class="fb-container" style="padding-bottom:0;"><div style="background:#d4edda; color:#155724; padding:20px; border-radius:8px; border:1px solid #c3e6cb; font-size:16px;"><strong>✅ Success!</strong> Shipment has been created and inventory allocated.</div></div>';
    } elseif ($_GET['msg'] == 'updated') {
        print '<div class="fb-container" style="padding-bottom:0;"><div style="background:#d1ecf1; color:#0c5460; padding:20px; border-radius:8px; border:1px solid #bee5eb; font-size:16px;"><strong>✅ Updated!</strong> Shipment details saved successfully.</div></div>';
    }
}

$d = new Distribution($db);
$d->fetch($id);

// Helper Data
$beneficiary_name = "Unknown"; $beneficiary_address = ""; $beneficiary_phone = "";
if ($d->fk_beneficiary > 0) {
    $res = $db->query("SELECT firstname, lastname, address, phone FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE rowid=".$d->fk_beneficiary);
    if ($res && $b = $db->fetch_object($res)) {
        $beneficiary_name = $b->firstname.' '.$b->lastname;
        $beneficiary_address = $b->address;
        $beneficiary_phone = $b->phone;
    }
}
$warehouse_name = "Unknown";
if ($d->fk_warehouse > 0) {
    $res = $db->query("SELECT label FROM ".MAIN_DB_PREFIX."foodbank_warehouses WHERE rowid=".$d->fk_warehouse);
    if ($res && $w = $db->fetch_object($res)) $warehouse_name = $w->label;
}
$lines = DistributionLine::getAllByDistribution($db, $id);

print '<div class="fb-container">';
print '<div style="display: flex; justify-content: space-between; margin-bottom: 20px;"><h1>Shipment #'.$d->ref.'</h1><div><a href="distributions.php" class="button" style="background:#eee; color:#333; margin-right:10px;">Back</a><a href="edit_distribution.php?id='.$id.'" class="button" style="background:#667eea; color:white;">Edit</a></div></div>';

print '<div class="fb-card">';
print '<div class="info-grid">';
print '<div><div class="section-title">📍 Ship To</div><p><strong>'.dol_escape_htmltag($beneficiary_name).'</strong><br>'.nl2br(dol_escape_htmltag($beneficiary_address)).'<br>'.dol_escape_htmltag($beneficiary_phone).'</p></div>';
print '<div><div class="section-title">🏢 Ship From</div><p><strong>'.dol_escape_htmltag($warehouse_name).'</strong><br>Date: '.dol_print_date($db->jdate($d->date_distribution), 'dayhour').'</p></div>';
print '</div>';

print '<div class="section-title" style="margin-top:20px;">📦 Packing List</div>';
print '<table class="clean-table"><thead><tr><th>Product</th><th>Source Ref</th><th>Qty</th></tr></thead><tbody>';
foreach($lines as $line) {
    print '<tr><td>'.dol_escape_htmltag($line->product_name).'</td><td>'.dol_escape_htmltag($line->donation_ref).'</td><td>'.number_format($line->quantity).' '.$line->unit.'</td></tr>';
}
print '</tbody></table>';

if ($d->note) print '<div style="margin-top:20px; background:#f9f9f9; padding:15px; border-radius:5px;"><strong>Note:</strong> '.dol_escape_htmltag($d->note).'</div>';

// Total
print '<div style="margin-top:20px; text-align:right; font-size:18px;"><strong>Total Value:</strong> ₦'.number_format($d->total_amount, 2).'</div>';

print '</div></div>';
llxFooter();
?>