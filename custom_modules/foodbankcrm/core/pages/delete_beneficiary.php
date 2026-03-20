<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
$_SESSION["mainmenu"] = "foodbankcrm";
$_fb_admin_head = '<link rel="icon" type="image/png" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/img/favicon.png">'
              . '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/foodbankcrm/css/admin_mobile.css">';
llxHeader($_fb_admin_head, 'Delete Subscriber');

// --- MODERN UI STYLES ---
print '<style>
    /* Hide Top Bar & Clean Sidebar */
    #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 50px !important; }
    
    #mainmenutd_commercial, #mainmenutd_billing, #mainmenutd_compta, 
    #mainmenutd_projet, #mainmenutd_mrp, #mainmenutd_hrm, 
    #mainmenutd_ticket, #mainmenutd_agenda, #mainmenutd_documents, #mainmenutd_bank {
        display: none !important;
    }

    /* Page Layout */
    .fb-container { max-width: 600px; margin: 0 auto; text-align: center; }
    
    /* Warning Card */
    .warning-card { 
        background: #fff; 
        border-radius: 12px; 
        box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
        padding: 40px; 
        border-top: 6px solid #dc3545; 
    }
    
    .warning-icon { 
        font-size: 60px; 
        margin-bottom: 20px; 
        display: block; 
        opacity: 0.8;
    }
    
    .detail-box { 
        background: #f9f9f9; 
        padding: 20px; 
        border-radius: 8px; 
        margin: 25px 0; 
        text-align: left; 
        border: 1px solid #eee; 
        font-size: 14px;
        line-height: 1.6;
    }
    
    .btn-group { display: flex; gap: 15px; justify-content: center; margin-top: 30px; }
    
    .button-danger { 
        background-color: #dc3545; 
        color: white; 
        padding: 12px 30px; 
        border-radius: 6px; 
        border: none; 
        font-weight: bold; 
        text-decoration: none; 
        cursor: pointer;
        font-size: 14px;
        transition: background 0.2s;
    }
    .button-danger:hover { background-color: #c82333; }
    
    .button-cancel { 
        background-color: #e2e6ea; 
        color: #495057; 
        padding: 12px 30px; 
        border-radius: 6px; 
        border: 1px solid #dae0e5; 
        text-decoration: none; 
        font-weight: bold; 
        font-size: 14px;
        transition: background 0.2s;
    }
    .button-cancel:hover { background-color: #dbe0e5; }
</style>';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: beneficiaries.php"); exit;
}

$id = (int)$_GET['id'];
$b = new Beneficiary($db);

print '<div class="fb-container">';

// FETCH CHECK
if ($b->fetch($id) <= 0) {
    print '<div class="warning-card" style="border-top-color: #666;">';
    print '<div style="font-size: 40px; margin-bottom: 20px;">❓</div>';
    print '<h2>Subscriber Not Found</h2>';
    print '<p style="color: #666;">This subscriber may have already been deleted.</p>';
    print '<br><a href="beneficiaries.php" class="button-cancel">Return to List</a>';
    print '</div>';
    print '</div>';
    llxFooter(); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // === CONFIRMATION SCREEN ===
    print '<div class="warning-card">';
    print '<span class="warning-icon">⚠️</span>';
    print '<h2 style="margin: 0 0 10px 0; color: #dc3545;">Delete Subscriber?</h2>';
    print '<p style="color: #666; margin: 0;">This action is <strong>permanent</strong> and cannot be undone.</p>';
    
    print '<div class="detail-box">';
    print '<strong>Name:</strong> '.dol_escape_htmltag($b->firstname.' '.$b->lastname).'<br>';
    print '<strong>Email:</strong> '.($b->email ? dol_escape_htmltag($b->email) : '<span style="color:#999; font-style:italic;">No email</span>').'<br>';
    print '<strong>Ref ID:</strong> '.dol_escape_htmltag($b->ref);
    print '</div>';

    print '<form method="POST" action="'.basename(__FILE__).'?id='.$id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    
    print '<div class="btn-group">';
    print '<a href="beneficiaries.php" class="button-cancel">Cancel</a>';
    print '<button type="submit" name="confirm" class="button-danger">Yes, Delete Subscriber</button>';
    print '</div>';
    
    print '</form>';
    print '</div>';

} else {
    // === DELETE ACTION ===
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        print '<div class="error">Security token expired. Please try again.</div>';
    } else {
        // Attempt Delete
        $res = $b->delete($user);
        
        if ($res > 0) {
            // SUCCESS
            print '<div class="warning-card" style="border-top-color: #28a745;">';
            print '<span class="warning-icon">✅</span>';
            print '<h2 style="color: #28a745; margin-top:0;">Deleted Successfully</h2>';
            print '<p style="color: #666;">The subscriber account has been removed.</p>';
            print '<br><a href="beneficiaries.php" class="button-cancel" style="background:#28a745; color:white; border-color:#28a745;">Back to Subscribers</a>';
            print '</div>';
        } else {
            // FAILURE (Likely Foreign Key Constraint)
            print '<div class="warning-card" style="border-top-color: #ffc107;">';
            print '<span class="warning-icon">🚫</span>';
            print '<h2 style="margin-top:0;">Cannot Delete Subscriber</h2>';
            print '<p style="color: #666; margin-bottom: 20px;">This subscriber has linked data (such as Food Distributions or Payments) and cannot be deleted safely to preserve records.</p>';
            
            print '<div class="detail-box" style="background: #fff3cd; border-color: #ffeeba; color: #856404;">';
            print '<strong>Tip:</strong> Instead of deleting, try editing the subscriber and setting their status to <strong>Inactive</strong>.';
            print '</div>';
            
            print '<div class="btn-group">';
            print '<a href="beneficiaries.php" class="button-cancel">Back to List</a>';
            print '<a href="edit_beneficiary.php?id='.$id.'" class="button-danger" style="background:#667eea;">Edit Subscriber</a>';
            print '</div>';
            print '</div>';
        }
    }
}

print '</div>'; // End Container
llxFooter();
?>  