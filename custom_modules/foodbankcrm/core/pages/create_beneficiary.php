<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
llxHeader('', 'Create Subscriber');

// --- MODERN UI STYLES ---
print '<style>
    /* Hide Dolibarr Top Bar */
    #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; }
    
    .fb-container { max-width: 900px; margin: 0 auto; padding: 0 20px; }
    .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 40px; border: 1px solid #eee; }
    
    /* Form Inputs */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 13px; color: #444; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; transition: border-color 0.2s; }
    .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: #667eea; outline: none; }
    
    /* --- MODERN PLAN SELECTION CARDS --- */
    .plan-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
        gap: 20px; 
        margin-top: 15px; 
    }
    
    .plan-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
    
    .plan-card { 
        display: block;
        background: #fff;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        padding: 25px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }
    
    .plan-card:hover { border-color: #b3b3b3; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    
    .plan-option input[type="radio"]:checked + .plan-card {
        border-color: #667eea;
        background-color: #f5f7ff;
        box-shadow: 0 0 0 1px #667eea;
    }
    
    .plan-name { font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: #666; margin-bottom: 10px; display: block; font-weight: 600; }
    .plan-price { font-size: 28px; font-weight: 800; color: #333; margin-bottom: 5px; display: block; }
    .plan-duration { font-size: 13px; color: #888; margin-bottom: 15px; display: block; }
    .plan-feature { font-size: 13px; color: #555; border-top: 1px solid #eee; padding-top: 10px; margin-top: 10px; display: block; }
    
    .check-icon {
        position: absolute; top: 10px; right: 10px; width: 20px; height: 20px;
        background: #667eea; color: white; border-radius: 50%; display: none;
        align-items: center; justify-content: center; font-size: 12px; font-weight: bold;
    }
    
    .plan-option input[type="radio"]:checked + .plan-card .check-icon { display: flex; }

    .info-box { margin-top: 30px; background: #e8f5e9; padding: 20px; border-radius: 8px; border-left: 4px solid #4caf50; font-size: 14px; line-height: 1.5; color: #2e7d32; }
</style>';

$notice = '';

// --- LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $b = new Beneficiary($db);
        $b->ref = $_POST['ref']; 
        $b->firstname = $_POST['firstname'];
        $b->lastname = $_POST['lastname'];
        $b->email = $_POST['email'];
        $b->phone = $_POST['phone'];
        $b->address = $_POST['address'];
        $b->household_size = (int)$_POST['household_size'];
        $b->note = $_POST['note'];
        
        // Capture Status Selection
        $selected_status = GETPOST('subscription_status', 'alpha');
        $b->subscription_status = $selected_status ? $selected_status : 'Pending';
        
        $res = $b->create($user);
        
        if ($res > 0) {
            // Update subscription fields
            $tier_id = GETPOST('subscription_tier', 'int');
            if ($tier_id > 0) {
                $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE rowid = ".(int)$tier_id;
                $tier_res = $db->query($sql);
                if ($tier_res) {
                    $tier = $db->fetch_object($tier_res);
                    if ($tier) {
                        $start_date = date('Y-m-d');
                        $end_date = date('Y-m-d', strtotime('+'.$tier->duration_months.' months'));
                        
                        $sql_upd = "UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET 
                                    subscription_type = '".$db->escape($tier->tier_type)."',
                                    subscription_status = '".$db->escape($b->subscription_status)."', 
                                    subscription_start_date = '".$start_date."',
                                    subscription_end_date = '".$end_date."',
                                    subscription_fee = ".(float)$tier->price."
                                    WHERE rowid = ".(int)$res;
                        $db->query($sql_upd);
                    }
                }
            }
            
            // Redirect with message
            setEventMessages("Subscriber created successfully", null, 'mesgs');
            header("Location: beneficiaries.php");
            exit;
        } else {
            $notice = '<div class="error">Error creating subscriber: '.dol_escape_htmltag($b->error).'</div>';
        }
    }
}

// Get Tiers
$subscription_tiers = array();
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE is_active = 1 ORDER BY price ASC";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $subscription_tiers[] = $obj;
    }
}

print '<div class="fb-container">';

// HEADER
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-top: 20px;">';
print '<div><h1 style="margin: 0;">👤 New Subscriber</h1><p style="color:#888; margin: 5px 0 0 0;">Create a new beneficiary account</p></div>';
print '<div>';
print '<a href="beneficiaries.php" class="button" style="background:#eee; color:#333; margin-right: 10px;">Cancel</a>';
print '<a href="dashboard_admin.php" class="button" style="background:#333; color:#fff;">Back to Dashboard</a>';
print '</div>';
print '</div>';

print $notice;

if (!isset($hide_form)) {
    print '<div class="fb-card">';
    print '<form method="POST" action="'.basename(__FILE__).'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';

    print '<h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 25px;">Personal Information</h3>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>First Name <span style="color:red">*</span></label><input type="text" name="firstname" required placeholder="e.g. John"></div>';
    print '<div class="form-group"><label>Last Name <span style="color:red">*</span></label><input type="text" name="lastname" required placeholder="e.g. Doe"></div>';
    print '</div>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>Email</label><input type="email" name="email" placeholder="john@example.com"></div>';
    print '<div class="form-group"><label>Phone</label><input type="text" name="phone" placeholder="080..."></div>';
    print '</div>';

    print '<div class="form-group"><label>Address</label><textarea name="address" rows="2" placeholder="Residential address"></textarea></div>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>Household Size</label><input type="number" name="household_size" min="1" value="1"></div>';
    print '<div class="form-group"><label>Reference ID (Optional)</label><input type="text" name="ref" placeholder="Auto-generated if empty"></div>';
    print '</div>';
    
    print '<div class="form-group"><label>Internal Note</label><textarea name="note" rows="2" placeholder="Admin notes..."></textarea></div>';

    // MODERN SUBSCRIPTION SECTION
    print '<h3 style="margin-top: 40px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">💳 Select Subscription Plan</h3>';

    // NEW: STATUS TOGGLE
    print '<div class="form-group" style="max-width: 300px; margin-bottom: 20px;">';
    print '<label>Initial Status</label>';
    print '<select name="subscription_status" style="font-weight:bold; color: #444;">';
    print '<option value="Pending" selected>⏳ Pending (Awaiting Payment)</option>';
    print '<option value="Active" style="color: green;">✅ Active (Payment Received)</option>';
    print '</select>';
    print '</div>';

    if (count($subscription_tiers) > 0) {
        print '<div class="plan-grid">';
        foreach ($subscription_tiers as $tier) {
            print '<label class="plan-option">';
            // Hidden Radio Input
            print '<input type="radio" name="subscription_tier" value="'.$tier->rowid.'" required>';
            
            // Visible Card
            print '<div class="plan-card">';
            print '<div class="check-icon">✓</div>';
            print '<span class="plan-name">'.dol_escape_htmltag($tier->tier_name).'</span>';
            print '<span class="plan-price">₦'.number_format($tier->price).'</span>';
            print '<span class="plan-duration">per '.$tier->duration_months.' months</span>';
            if ($tier->description) {
                print '<span class="plan-feature">'.dol_escape_htmltag($tier->description).'</span>';
            }
            print '</div>';
            print '</label>';
        }
        print '</div>';
    } else {
        print '<div style="padding: 15px; background: #fff3cd; color: #856404; border-radius: 5px;">No subscription tiers found. The Default "Standard Access" will be applied.</div>';
    }

    print '<div style="margin-top: 40px; text-align: center;">';
    print '<button type="submit" class="butAction" style="padding: 15px 50px; font-size: 16px; font-weight: bold; border-radius: 30px;">Create Subscriber</button>';
    print '</div>';

    print '</form>';
    print '</div>'; // End Card

    // Process Info Box
    if (count($subscription_tiers) > 0) {
        print '<div class="info-box">';
        print '<h4 style="margin-top: 0; margin-bottom: 10px;">💡 Status Guide</h4>';
        print '<ul style="margin-bottom: 0; padding-left: 20px;">';
        print '<li><strong>Pending:</strong> Choose this if you are sending an invoice or waiting for a bank transfer.</li>';
        print '<li><strong>Active:</strong> Choose this if they have already paid cash or you want to grant immediate access.</li>';
        print '</ul>';
        print '</div>';
    }
}

print '</div>'; // End Container

llxFooter();
?>