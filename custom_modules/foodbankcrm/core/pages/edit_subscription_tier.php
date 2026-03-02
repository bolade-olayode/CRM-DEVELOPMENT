<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: subscription_tiers.php"); exit;
}

$tier_id = (int)$_GET['id'];
$notice  = '';

// Fetch tier
$res  = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE rowid = ".$tier_id);
$tier = $res ? $db->fetch_object($res) : null;
if (!$tier) {
    header("Location: subscription_tiers.php"); exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed: invalid token.'];
    } else {
        $tier_name       = GETPOST('tier_name', 'alphanohtml');
        $tier_type       = GETPOST('tier_type', 'alphanohtml');
        $duration_months = (int)GETPOST('duration_months', 'int');
        $price           = (float)str_replace(',', '.', GETPOST('price', 'alpha'));
        $description     = GETPOST('description', 'restricthtml');
        $benefits        = GETPOST('benefits', 'restricthtml');
        $duration_days   = $duration_months * 30;

        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_subscription_tiers SET
                tier_name       = '".$db->escape($tier_name)."',
                tier_type       = '".$db->escape($tier_type)."',
                duration_months = ".$duration_months.",
                duration_days   = ".$duration_days.",
                price           = ".$price.",
                description     = '".$db->escape($description)."',
                benefits        = '".$db->escape($benefits)."'
                WHERE rowid = ".$tier_id;

        if ($db->query($sql)) {
            $notice = ['success', 'Tier updated successfully!'];
            // Refresh
            $res  = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE rowid = ".$tier_id);
            $tier = $db->fetch_object($res);
        } else {
            $notice = ['error', 'Error: '.$db->lasterror()];
        }
    }
}

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Edit Subscription Tier');
?>
<style>
:root {
    --accent:       #4f46e5;
    --accent-light: #e0e7ff;
    --accent-dark:  #3730a3;
    --surface:      #f8fafc;
    --radius:       12px;
    --shadow:       0 4px 12px rgba(0,0,0,0.06);
    --font:         "Segoe UI", Roboto, Arial, sans-serif;
}
#id-top { display: none !important; }
.side-nav, .side-nav-vert { top: 0 !important; height: 100vh !important; }
#id-right { padding-top: 30px !important; background: var(--surface) !important; min-height: 100vh; }
.fiche { max-width: 100% !important; margin: 0 !important; }

.fb-wrap  { max-width: 760px; margin: 0 auto; padding: 24px 28px; font-family: var(--font); }
.fb-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
.fb-page-header h1 { margin: 0; font-size: 24px; font-weight: 800; color: #1e293b; }
.fb-page-header p  { margin: 4px 0 0; color: #64748b; font-size: 14px; }

.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none !important; border: none; cursor: pointer; transition: background .2s; }
.btn-primary:hover { background: var(--accent-dark); text-decoration: none !important; }
.btn-ghost  { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none !important; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; text-decoration: none !important; }

.fb-notice { padding: 14px 18px; border-radius: 10px; margin-bottom: 24px; font-size: 14px; font-weight: 500; }
.fb-notice.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.fb-notice.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); padding: 36px 40px; }
.form-section { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #94a3b8; margin: 28px 0 16px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; }
.form-section:first-of-type { margin-top: 0; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 7px; font-weight: 600; font-size: 13px; color: #374151; }
.form-group input,
.form-group select,
.form-group textarea { width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; color: #1e293b; box-sizing: border-box; transition: border-color .2s, box-shadow .2s; background: #fff; font-family: var(--font); }
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color: var(--accent); outline: none; box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
.hint { font-size: 12px; color: #94a3b8; margin-top: 5px; }
.required { color: #ef4444; }

/* Meta bar */
.meta-bar { display: flex; gap: 16px; flex-wrap: wrap; background: var(--accent-light); border-radius: 10px; padding: 14px 18px; margin-bottom: 24px; }
.meta-item { font-size: 13px; color: var(--accent-dark); }
.meta-item strong { font-weight: 700; }
</style>

<div class="fb-wrap">

    <div class="fb-page-header">
        <div>
            <h1>✏️ Edit Tier</h1>
            <p>Update subscription plan details</p>
        </div>
        <div>
            <a href="subscription_tiers.php" class="btn-ghost">← Back to Tiers</a>
        </div>
    </div>

    <!-- Meta bar -->
    <div class="meta-bar">
        <div class="meta-item">Tier ID: <strong>#<?php echo $tier->rowid; ?></strong></div>
        <div class="meta-item">Current Price: <strong>₦<?php echo number_format($tier->price, 2); ?></strong></div>
        <div class="meta-item">Status: <strong><?php echo $tier->is_active ? 'Active' : 'Inactive'; ?></strong></div>
        <div class="meta-item">Type: <strong><?php echo dol_escape_htmltag($tier->tier_type); ?></strong></div>
    </div>

    <?php if ($notice) : ?>
    <div class="fb-notice <?php echo $notice[0]; ?>"><?php echo $notice[1]; ?></div>
    <?php endif; ?>

    <div class="fb-card">
        <form method="POST" action="<?php echo basename(__FILE__).'?id='.$tier_id; ?>">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">

            <p class="form-section">Basic Information</p>

            <div class="form-group">
                <label>Tier Name <span class="required">*</span></label>
                <input type="text" name="tier_name" required value="<?php echo dol_escape_htmltag($tier->tier_name); ?>">
            </div>

            <div class="form-group">
                <label>Tier Type <span class="required">*</span></label>
                <input type="text" name="tier_type" required list="tier-type-list" value="<?php echo dol_escape_htmltag($tier->tier_type); ?>">
                <datalist id="tier-type-list">
                    <option value="Annual">
                    <option value="Donor">
                    <option value="Guest">
                    <option value="Corporate">
                    <option value="Student">
                </datalist>
                <div class="hint">Type freely or pick a suggestion. Multiple tiers can share the same type.</div>
            </div>

            <p class="form-section">Pricing &amp; Duration</p>

            <div class="form-grid">
                <div class="form-group">
                    <label>Duration (Months) <span class="required">*</span></label>
                    <input type="number" name="duration_months" required min="1" max="60" value="<?php echo (int)$tier->duration_months; ?>">
                </div>
                <div class="form-group">
                    <label>Price (₦) <span class="required">*</span></label>
                    <input type="number" name="price" required min="0" step="0.01" value="<?php echo (float)$tier->price; ?>">
                </div>
            </div>

            <p class="form-section">Details</p>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="2"><?php echo dol_escape_htmltag($tier->description); ?></textarea>
            </div>

            <div class="form-group">
                <label>Benefits</label>
                <textarea name="benefits" rows="4"><?php echo dol_escape_htmltag($tier->benefits); ?></textarea>
                <div class="hint">List one benefit per line.</div>
            </div>

            <div style="margin-top: 28px; display: flex; justify-content: flex-end; gap: 12px;">
                <a href="subscription_tiers.php" class="btn-ghost">Cancel</a>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>

</div>

<?php llxFooter(); ?>
