<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/package.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader('', 'Manage Packages');

// --- MODERN STYLES ---
print '<style>
    div#id-top, #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; }
    
    #mainmenutd_commercial, #mainmenutd_billing, #mainmenutd_compta, 
    #mainmenutd_projet, #mainmenutd_mrp, #mainmenutd_hrm, 
    #mainmenutd_ticket, #mainmenutd_agenda, #mainmenutd_documents, #mainmenutd_bank {
        display: none !important;
    }

    .fb-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
    
    .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); padding: 0; overflow: hidden; border: 1px solid #eee; }
    .clean-table { width: 100%; border-collapse: collapse; }
    .clean-table th { text-align: left; padding: 15px 20px; background: #f8f9fa; color: #666; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #eee; }
    .clean-table td { padding: 15px 20px; border-bottom: 1px solid #f5f5f5; font-size: 14px; color: #444; }
    .clean-table tr:hover { background: #fafafa; }
    
    .badge { padding: 5px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; color: white !important; display: inline-block; }
    .badge.green { background-color: #28a745 !important; }
    .badge.gray { background-color: #6c757d !important; }
    
    .item-bubble { background: #e3f2fd; color: #1976d2; padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: bold; }
    
    .action-link { font-size: 13px; color: #667eea; text-decoration: none; font-weight: 500; margin-left: 10px; }
    .action-link.delete { color: #dc3545; }
</style>';

$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."foodbank_package_items WHERE fk_package = p.rowid) as item_count,
        (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE fk_package = p.rowid) as usage_count
        FROM ".MAIN_DB_PREFIX."foodbank_packages p
        ORDER BY p.rowid DESC";
$res = $db->query($sql);

print '<div class="fb-container">';

print '<div class="page-header">';
print '<div><h1 style="margin: 0;">📦 Packages</h1><p style="color:#888; margin: 5px 0 0 0;">Manage food box templates</p></div>';
print '<div>';
print '<a class="butAction" href="create_package.php" style="padding: 10px 20px;">+ Create Package</a>';
print '<a class="button" href="dashboard_admin.php" style="margin-left: 10px; background:#eee; color:#333;">Back to Dashboard</a>';
print '</div>';
print '</div>';

print '<div class="fb-card">';
if ($res && $db->num_rows($res) > 0) {
    print '<table class="clean-table">';
    print '<thead><tr><th>Ref</th><th>Package Name</th><th>Description</th><th>Items</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>';
    print '<tbody>';

    while ($obj = $db->fetch_object($res)) {
        $status_class = ($obj->status == 'Active') ? 'green' : 'gray';
        
        print '<tr>';
        print '<td><strong>'.dol_escape_htmltag($obj->ref).'</strong></td>';
        print '<td>'.dol_escape_htmltag($obj->name).'</td>';
        print '<td>'.dol_escape_htmltag(dol_trunc($obj->description, 50)).'</td>';
        print '<td><span class="item-bubble">'.$obj->item_count.' items</span></td>';
        print '<td><span class="badge '.$status_class.'">'.$obj->status.'</span></td>';
        
        print '<td style="text-align: right;">';
        print '<a href="edit_package.php?id='.$obj->rowid.'" class="action-link">Edit</a>';
        print '<a href="delete_package.php?id='.$obj->rowid.'" class="action-link delete">Delete</a>';
        print '</td>';
        print '</tr>';
    }
    print '</tbody></table>';
} else {
    print '<div style="text-align: center; padding: 60px; color: #999;">';
    print '<div style="font-size: 40px; margin-bottom: 10px;">📦</div>';
    print 'No packages found. <a href="create_package.php" style="font-weight:bold">Create the first one</a>.';
    print '</div>';
}
print '</div>'; 
print '</div>'; 

llxFooter();
?>