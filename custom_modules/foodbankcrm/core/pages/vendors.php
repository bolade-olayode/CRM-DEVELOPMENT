<?php
/**
 * VENDOR LIST
 * Updates: Added "Back to Dashboard" button
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendor.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader('', 'Vendor Management');

print '<style>
    #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; }
    .fb-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
    .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 0; overflow: hidden; border: 1px solid #eee; }
    .clean-table { width: 100%; border-collapse: collapse; }
    .clean-table th { text-align: left; padding: 15px 20px; background: #f8f9fa; color: #666; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #eee; }
    .clean-table td { padding: 15px 20px; border-bottom: 1px solid #f5f5f5; font-size: 14px; color: #444; }
    .clean-table tr:hover { background: #fafafa; }
    
    .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; color: #fff !important; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge.green { background-color: #28a745 !important; }
    .badge.orange { background-color: #fd7e14 !important; }
    .badge.red { background-color: #dc3545 !important; }
    
    .vendor-link { font-weight: bold; color: #2c3e50; text-decoration: none; font-size: 15px; }
    .vendor-link:hover { color: #3498db; text-decoration: underline; }
    
    .action-btn { text-decoration: none; color: #555; padding: 5px 10px; border-radius: 4px; font-size: 13px; border: 1px solid #ddd; margin-right: 5px; background: #fff; }
    .action-btn:hover { background: #f0f0f0; color: #333; }
    .action-btn.delete { color: #d32f2f; border-color: #f5c6cb; }
    .action-btn.delete:hover { background: #ffebee; }
    
    /* NEW: Dashboard Button Style */
    .btn-dashboard { background: #eee; color: #333; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; border: 1px solid #ddd; }
    .btn-dashboard:hover { background: #e2e6ea; }
    
    .btn-create { background: #eee; color: #eee; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-left: 10px; }
    .btn-create:hover { background: #e2e6ea; }
</style>';

$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "foodbank_vendors ORDER BY rowid DESC";
$res = $db->query($sql);

print '<div class="fb-container">';

print '<div class="page-header">';
print '<div><h1 style="margin: 0;">🏢 Vendors</h1><p style="color:#888; margin: 5px 0 0 0;">Manage food suppliers and donors</p></div>';
print '<div>';
// --- ADDED: Back to Dashboard Button ---
print '<a href="dashboard_admin.php" class="btn-dashboard">Back to Dashboard</a>';
print '<a href="create_vendor.php" class="btn-create"> Register Vendor</a>';
print '</div>';
print '</div>';

print '<div class="fb-card">';
if ($res && $db->num_rows($res) > 0) {
    print '<table class="clean-table">';
    print '<thead><tr>
            <th>Ref</th>
            <th>Business Name</th>
            <th>Contact</th>
            <th>Phone</th> 
            <th>Email</th>
            <th>Category</th>
            <th>Status</th>
            <th style="text-align:right;">Actions</th>
           </tr></thead>';
    print '<tbody>';
    while ($obj = $db->fetch_object($res)) {
        $status_label = !empty($obj->status) ? $obj->status : 'Pending';
        $status_class = ($status_label == 'Active') ? 'green' : (($status_label == 'Inactive') ? 'red' : 'orange');

        print '<tr>';
        print '<td><small style="color:#888;">'.dol_escape_htmltag($obj->ref).'</small></td>';
        print '<td><a href="view_vendor.php?id='.$obj->rowid.'" class="vendor-link">'.dol_escape_htmltag($obj->name).'</a></td>';
        print '<td>'.dol_escape_htmltag($obj->contact_person).'</td>';
        print '<td>'.dol_escape_htmltag($obj->contact_phone).'</td>';
        print '<td>'.dol_escape_htmltag($obj->contact_email).'</td>';
        print '<td>'.dol_escape_htmltag($obj->category).'</td>';
        print '<td><span class="badge '.$status_class.'">'.$status_label.'</span></td>';
        
        print '<td style="text-align: right;">';
        print '<a href="view_vendor.php?id='.$obj->rowid.'" class="action-btn">View</a>';
        print '<a href="edit_vendor.php?id='.$obj->rowid.'" class="action-btn">✏️ Edit</a>';
        print '<a href="delete_vendor.php?id='.$obj->rowid.'" class="action-btn delete">🗑️</a>';
        print '</td>';
        print '</tr>';
    }
    print '</tbody></table>';
} else {
    print '<div style="padding:40px; text-align:center; color:#999;">No vendors found.</div>';
}
print '</div>'; print '</div>'; 
llxFooter();
?>