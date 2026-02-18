<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// Security check
if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

llxHeader('', 'Create Subscription Tier');

$notice = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $tier_name = GETPOST('tier_name', 'alpha');
        $tier_type = GETPOST('tier_type', 'alpha');
        $duration_months = GETPOST('duration_months', 'int');
        $price = GETPOST('price', 'price');
        $description = GETPOST('description', 'restricthtml');
        $benefits = GETPOST('benefits', 'restricthtml');
        
        $duration_days = (int)$duration_months * 30;
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_subscription_tiers
                (tier_name, tier_type, duration_months, duration_days, price, description, benefits, is_active)
                VALUES (
                    '".$db->escape($tier_name)."',
                    '".$db->escape($tier_type)."',
                    ".(int)$duration_months.",
                    ".(int)$duration_days.",
                    ".(float)$price.",
                    '".$db->escape($description)."',
                    '".$db->escape($benefits)."',
                    1
                )";
        
        if ($db->query($sql)) {
            $notice = '<div class="ok">Subscription tier created successfully!</div>';
        } else {
            $notice = '<div class="error">Error: '.$db->lasterror().'</div>';
        }
    }
}

print $notice;
print '<div><a href="subscription_tiers.php">← Back to Subscription Tiers</a></div><br>';

?>

<h2>Create Subscription Tier</h2>

<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
    
    <table class="border centpercent">
        <tr>
            <td width="25%"><span class="fieldrequired">Tier Name</span></td>
            <td><input class="flat" type="text" name="tier_name" required style="width: 100%;" placeholder="e.g., Basic Annual Plan"></td>
        </tr>
        <tr>
            <td><span class="fieldrequired">Tier Type</span></td>
            <td>
                <select class="flat" name="tier_type" required style="width: 100%;">
                    <option value="">-- Select Type --</option>
                    <option value="Annual">Annual (Full year membership)</option>
                    <option value="Donor">Donor (Premium supporter)</option>
                    <option value="Guest">Guest (Trial/Short-term)</option>
                </select>
            </td>
        </tr>
        <tr>
            <td><span class="fieldrequired">Duration (Months)</span></td>
            <td>
                <input class="flat" type="number" name="duration_months" required min="1" max="36" value="12" style="width: 100px;">
                <span style="color: #666; font-size: 12px; margin-left: 10px;">Typical: 12 months for Annual, 1-3 for Guest</span>
            </td>
        </tr>
        <tr>
            <td><span class="fieldrequired">Price (₦)</span></td>
            <td>
                <input class="flat" type="number" name="price" required min="0" step="0.01" placeholder="50000.00" style="width: 200px;">
                <span style="color: #666; font-size: 12px; margin-left: 10px;">Enter amount in Naira</span>
            </td>
        </tr>
        <tr>
            <td>Description</td>
            <td><textarea class="flat" name="description" rows="3" style="width: 100%;" placeholder="Brief description of this tier..."></textarea></td>
        </tr>
        <tr>
            <td>Benefits</td>
            <td>
                <textarea class="flat" name="benefits" rows="5" style="width: 100%;" placeholder="List benefits (one per line):
- Access to full product catalog
- Priority delivery
- Exclusive discounts
- Monthly reports"></textarea>
            </td>
        </tr>
    </table>
    
    <br>
    <div class="center">
        <input class="button" type="submit" value="Create Subscription Tier">
        <a class="button" href="subscription_tiers.php">Cancel</a>
    </div>
</form>

<div style="margin-top: 30px; background: #fff3e0; padding: 15px; border-radius: 5px; border-left: 4px solid #ff9800;">
    <h4 style="margin-top: 0;">💡 Pricing Guidelines</h4>
    <ul style="margin-bottom: 0;">
        <li><strong>Annual:</strong> ₦50,000 - ₦100,000 (full access, 12 months)</li>
        <li><strong>Donor:</strong> ₦100,000+ (premium support tier, 12 months)</li>
        <li><strong>Guest:</strong> ₦5,000 - ₦15,000 (trial tier, 1-3 months)</li>
    </ul>
</div>

<?php llxFooter(); ?>