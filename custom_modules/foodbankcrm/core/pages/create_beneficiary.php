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
$created_login    = '';
$created_password = '';
$hide_form = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div style="padding:13px 18px; background:#fee2e2; color:#991b1b; border:1px solid #fecaca; border-radius:8px; margin-bottom:20px;">Security check failed: invalid CSRF token.</div>';
    } else {
        $db->begin();

        $firstname = GETPOST('firstname', 'alpha');
        $lastname  = GETPOST('lastname', 'alpha');
        $email     = GETPOST('email', 'alpha');
        $phone     = GETPOST('phone', 'alpha');

        // --- Step 1: Create a Dolibarr user account ---
        require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
        $newuser = new User($db);

        // Generate login from firstname+lastname, sanitise
        $base_login = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstname.$lastname));
        if (empty($base_login)) $base_login = 'subscriber';
        // Make it unique
        $test_login = $base_login;
        $suffix = 1;
        while (true) {
            $chk = new User($db);
            if ($chk->fetch('', $test_login) <= 0) break;
            $test_login = $base_login.$suffix++;
        }
        $created_login = $test_login;

        // Generate a 12-char password meeting Dolibarr's complexity requirements
        // Must have lowercase, uppercase, digit, and special char
        $lowers   = 'abcdefghjkmnpqrstuvwxyz';
        $uppers   = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        $digits   = '23456789';
        $specials = '!@#$%';
        $all      = $lowers.$uppers.$digits.$specials;
        // Guarantee at least one of each category
        $created_password  = $lowers[random_int(0, strlen($lowers)-1)];
        $created_password .= $uppers[random_int(0, strlen($uppers)-1)];
        $created_password .= $digits[random_int(0, strlen($digits)-1)];
        $created_password .= $specials[random_int(0, strlen($specials)-1)];
        // Fill remaining 8 chars from full pool
        for ($i = 0; $i < 8; $i++) $created_password .= $all[random_int(0, strlen($all)-1)];
        // Shuffle so the guaranteed chars aren't always first
        $created_password = str_shuffle($created_password);

        $newuser->login     = $created_login;
        $newuser->email     = $email;
        $newuser->firstname = $firstname;
        $newuser->lastname  = $lastname;
        $newuser->pass      = $created_password;
        $newuser->statut    = 1; // active

        $uid = $newuser->create($user);

        if ($uid <= 0) {
            $db->rollback();
            $notice = '<div style="padding:13px 18px; background:#fee2e2; color:#991b1b; border:1px solid #fecaca; border-radius:8px; margin-bottom:20px;">Could not create user account: '.dol_escape_htmltag($newuser->error).'</div>';
        } else {
            // Auto-grant beneficiary permissions
            foreach (array(100001, 100021, 100022) as $right_id) {
                $db->query("INSERT IGNORE INTO ".MAIN_DB_PREFIX."user_rights (entity, fk_user, fk_id) VALUES (1, ".(int)$uid.", ".(int)$right_id.")");
            }

            // --- Step 2: Create beneficiary profile ---
            $b = new Beneficiary($db);
            $b->ref            = GETPOST('ref', 'alpha');
            $b->firstname      = $firstname;
            $b->lastname       = $lastname;
            $b->email          = $email;
            $b->phone          = $phone;
            $b->address        = GETPOST('address', 'restricthtml');
            $b->household_size = (int)GETPOST('household_size', 'int');
            $b->note           = GETPOST('note', 'restricthtml');
            $b->subscription_status = GETPOST('subscription_status', 'alpha') ?: 'Pending';

            $bid = $b->create($user);

            if ($bid > 0) {
                // Link user to beneficiary record
                $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET fk_user = ".(int)$uid." WHERE rowid = ".(int)$bid);

                // Apply subscription tier
                $tier_id = GETPOST('subscription_tier', 'int');
                if ($tier_id > 0) {
                    $tier_res = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE rowid = ".(int)$tier_id);
                    if ($tier_res && $tier = $db->fetch_object($tier_res)) {
                        $start_date = date('Y-m-d');
                        $end_date   = date('Y-m-d', strtotime('+'.$tier->duration_months.' months'));
                        $db->query("UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET
                                    subscription_type = '".$db->escape($tier->tier_type)."',
                                    subscription_status = '".$db->escape($b->subscription_status)."',
                                    subscription_start_date = '".$start_date."',
                                    subscription_end_date = '".$end_date."',
                                    subscription_fee = ".(float)$tier->price."
                                    WHERE rowid = ".(int)$bid);
                    }
                }

                $db->commit();
                $hide_form = true;
                $notice = ''; // credentials shown separately below
            } else {
                $db->rollback();
                $notice = '<div style="padding:13px 18px; background:#fee2e2; color:#991b1b; border:1px solid #fecaca; border-radius:8px; margin-bottom:20px;">Error creating subscriber profile: '.dol_escape_htmltag($b->error).'</div>';
            }
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

// --- Show credentials after successful creation ---
if ($hide_form && $created_login) {
    print '<div style="background:#f0fdf4; border:2px solid #16a34a; border-radius:12px; padding:32px; text-align:center; margin-bottom:24px;">';
    print '<div style="font-size:48px; margin-bottom:12px;">✅</div>';
    print '<h2 style="color:#15803d; margin:0 0 8px;">Subscriber Created Successfully!</h2>';
    print '<p style="color:#166534; margin:0 0 24px;">The subscriber\'s Dolibarr login account has been created. Share the credentials below with them so they can log in.</p>';
    print '<div style="background:#fff; border:1px solid #bbf7d0; border-radius:8px; padding:20px; display:inline-block; min-width:300px; text-align:left; margin-bottom:24px;">';
    print '<p style="margin:0 0 12px; font-size:13px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.5px;">Login Credentials</p>';
    print '<div style="margin-bottom:10px;"><span style="font-size:12px; color:#94a3b8;">Username</span><br>';
    print '<strong style="font-size:20px; color:#1e293b; font-family:monospace;">'.dol_escape_htmltag($created_login).'</strong></div>';
    print '<div><span style="font-size:12px; color:#94a3b8;">Temporary Password</span><br>';
    print '<strong style="font-size:20px; color:#1e293b; font-family:monospace;">'.dol_escape_htmltag($created_password).'</strong></div>';
    print '</div>';
    print '<p style="color:#dc2626; font-size:13px; font-weight:600; margin:0 0 20px;">Important: Copy these credentials now. The password cannot be retrieved again.</p>';
    print '<a href="beneficiaries.php" class="button" style="background:#16a34a; color:white; padding:12px 30px; border-radius:8px; text-decoration:none; font-weight:600;">View All Subscribers</a>';
    print ' <a href="create_beneficiary.php" class="button" style="background:#eee; color:#333; padding:12px 30px; border-radius:8px; text-decoration:none; font-weight:600; margin-left:10px;">Add Another</a>';
    print '</div>';
}

if (!$hide_form) {
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