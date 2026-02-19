<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

llxHeader('', 'Edit Subscription Tier');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Tier ID is missing.</div>';
    print '<div><a href="subscription_tiers.php">← Back</a></div>';
    llxFooter(); exit;
}

$tier_id = (int) $_GET['id'];
$notice = '';

// Fetch tier
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE rowid = ".$tier_id;
$res = $db->query($sql);
$tier = $db->fetch_object($res);

if (!$tier) {
    print '<div class="error">Tier not found.</div>';
    print '<div><a href="subscription_tiers.php">← Back</a></div>';
    llxFooter(); exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed.</div>';
    } else {
        $tier_name = GETPOST('tier_name', 'alpha');
        $tier_type = GETPOST('tier_type', 'alpha');
        $duration_months = GETPOST('duration_months', 'int');
        $price = GETPOST('price', 'price');
        $description = GETPOST('description', 'restricthtml');
        $benefits = GETPOST('benefits', 'restricthtml');
        
        $duration_days = (int)$duration_months * 30;
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_subscription_tiers SET
                tier_name = '".$db->escape($tier_name)."',
                tier_type = '".$db->escape($tier_type)."',
                duration_months = ".(int)$duration_months.",
                duration_days = ".(int)$duration_days.",
                price = ".(float)$price.",
                description = '".$db->escape($description)."',
                benefits = '".$db->escape($benefits)."'
                WHERE rowid = ".$tier_id;
        
        if ($db->query($sql)) {
            $notice = '<div class="ok">Subscription tier updated successfully!</div>';
            // Refresh tier data
            $res = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE rowid = ".$tier_id);
            $tier = $db->fetch_object($res);
        } else {
            $notice = '<div class="error">Error: '.$db->lasterror().'</div>';
        }
    }
}

print $notice;
print '<div><a href="subscription_tiers.php">← Back to Subscription Tiers</a></div><br>';

?>

<h2>Edit Subscription Tier</h2>

<form method="POST" action="<?php echo basename(__FILE__).'?id='.$tier_id; ?>">
    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
    
    <table class="border centpercent">
        <tr>
            <td width="25%"><span class="fieldrequired">Tier Name</span></td>
            <td><input class="flat" type="text" name="tier_name" value="<?php echo dol_escape_htmltag($tier->tier_name); ?>" required style="width: 100%;"></td>
        </tr>
        <tr>
            <td><span class="fieldrequired">Tier Type</span></td>
            <td>
                <select class="flat" name="tier_type" required style="width: 100%;">
                    <option value="Annual" <?php echo $tier->tier_type == 'Annual' ? 'selected' : ''; ?>>Annual</option>
                    <option value="Donor" <?php echo $tier->tier_type == 'Donor' ? 'selected' : ''; ?>>Donor</option>
                    <option value="Guest" <?php echo $tier->tier_type == 'Guest' ? 'selected' : ''; ?>>Guest</option>
                </select>
            </td>
        </tr>
        <tr>
            <td><span class="fieldrequired">Duration (Months)</span></td>
            <td><input class="flat" type="number" name="duration_months" value="<?php echo $tier->duration_months; ?>" required min="1" max="36" style="width: 100px;"></td>
        </tr>
        <tr>
            <td><span class="fieldrequired">Price (₦)</span></td>
            <td><input class="flat" type="number" name="price" value="<?php echo $tier->price; ?>" required min="0" step="0.01" style="width: 200px;"></td>
        </tr>
        <tr>
            <td>Description</td>
            <td><textarea class="flat" name="description" rows="3" style="width: 100%;"><?php echo dol_escape_htmltag($tier->description); ?></textarea></td>
        </tr>
        <tr>
            <td>Benefits</td>
            <td><textarea class="flat" name="benefits" rows="5" style="width: 100%;"><?php echo dol_escape_htmltag($tier->benefits); ?></textarea></td>
        </tr>
    </table>
    
    <br>
    <div class="center">
        <input class="button" type="submit" value="Update Tier">
        <a class="button" href="subscription_tiers.php">Cancel</a>
    </div>
</form>

<?php llxFooter(); ?>