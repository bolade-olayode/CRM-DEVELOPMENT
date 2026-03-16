<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$_SESSION["mainmenu"] = "foodbankcrm";
llxHeader('', 'Create Subscription Tier');

$notice = '';
$hide_form = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed: invalid token.'];
    } else {
        $tier_name            = GETPOST('tier_name', 'alphanohtml');
        $tier_type            = GETPOST('tier_type', 'alphanohtml');
        $duration_months      = (int)GETPOST('duration_months', 'int');
        $price                = (float)str_replace(',', '.', GETPOST('price', 'alpha'));
        $description          = GETPOST('description', 'restricthtml');
        $benefits             = GETPOST('benefits', 'restricthtml');
        $can_place_orders     = (int)(GETPOST('can_place_orders', 'int') == '1');
        $max_orders_per_month = max(0, (int)GETPOST('max_orders_per_month', 'int'));
        $duration_days        = $duration_months * 30;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_subscription_tiers
                (tier_name, tier_type, duration_months, duration_days, price, description, benefits,
                 can_place_orders, max_orders_per_month, is_active)
                VALUES (
                    '".$db->escape($tier_name)."',
                    '".$db->escape($tier_type)."',
                    ".$duration_months.",
                    ".$duration_days.",
                    ".$price.",
                    '".$db->escape($description)."',
                    '".$db->escape($benefits)."',
                    ".$can_place_orders.",
                    ".$max_orders_per_month.",
                    1
                )";

        if ($db->query($sql)) {
            $notice = ['success', 'Subscription tier <strong>'.dol_escape_htmltag($tier_name).'</strong> created successfully!'];
            $hide_form = true;
        } else {
            $notice = ['error', 'Error: '.$db->lasterror()];
        }
    }
}
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

/* Page header */
.fb-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
.fb-page-header h1 { margin: 0; font-size: 24px; font-weight: 800; color: #1e293b; }
.fb-page-header p  { margin: 4px 0 0; color: #64748b; font-size: 14px; }
.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none !important; border: none; cursor: pointer; transition: background .2s; }
.btn-primary:hover { background: var(--accent-dark); text-decoration: none !important; }
.btn-ghost  { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none !important; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; text-decoration: none !important; }

/* Notice */
.fb-notice { padding: 14px 18px; border-radius: 10px; margin-bottom: 24px; font-size: 14px; font-weight: 500; }
.fb-notice.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.fb-notice.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

/* Card */
.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); padding: 36px 40px; }

/* Form */
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

/* Type options */
.type-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.type-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
.type-card { display: block; border: 2px solid #e2e8f0; border-radius: 10px; padding: 16px; cursor: pointer; transition: all .2s; text-align: center; }
.type-card:hover { border-color: #a5b4fc; background: #fafbff; }
.type-option input[type="radio"]:checked + .type-card { border-color: var(--accent); background: var(--accent-light); }
.type-card .type-icon { font-size: 24px; margin-bottom: 6px; display: block; }
.type-card .type-name { font-weight: 700; font-size: 14px; color: #1e293b; display: block; }
.type-card .type-desc { font-size: 11px; color: #64748b; margin-top: 3px; display: block; }

/* Pricing guide */
.guide-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 18px 22px; margin-top: 28px; }
.guide-box h4 { margin: 0 0 10px; font-size: 13px; color: #92400e; }
.guide-box ul { margin: 0; padding-left: 20px; color: #78350f; font-size: 13px; line-height: 1.9; }

/* Order access toggle */
.order-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.order-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
.order-card { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; border: 2px solid #e2e8f0; border-radius: 10px; padding: 16px 18px; cursor: pointer; transition: all .2s; }
.order-card:hover { border-color: #a5b4fc; background: #fafbff; }
.order-option input[type="radio"]:checked + .order-card { border-color: var(--accent); background: var(--accent-light); }
.order-card .oc-icon { font-size: 22px; }
.order-card .oc-title { font-weight: 700; font-size: 14px; color: #1e293b; }
.order-card .oc-desc  { font-size: 12px; color: #64748b; line-height: 1.4; }

/* Success panel */
.success-panel { background: #f0fdf4; border: 2px solid #16a34a; border-radius: var(--radius); padding: 40px; text-align: center; }
.success-panel .icon { font-size: 52px; margin-bottom: 12px; }
.success-panel h2 { color: #15803d; margin: 0 0 8px; font-size: 22px; }
.success-panel p { color: #166534; margin: 0 0 24px; font-size: 15px; }
</style>

<div class="fb-wrap">

    <div class="fb-page-header">
        <div>
            <h1>💳 New Subscription Tier</h1>
            <p>Define a membership plan for beneficiaries</p>
        </div>
        <div>
            <a href="subscription_tiers.php" class="btn-ghost">← Back to Tiers</a>
        </div>
    </div>

    <?php if ($notice) : ?>
    <div class="fb-notice <?php echo $notice[0]; ?>"><?php echo $notice[1]; ?></div>
    <?php endif; ?>

    <?php if ($hide_form) : ?>
    <div class="success-panel">
        <div class="icon">✅</div>
        <h2>Tier Created Successfully!</h2>
        <p>Your new subscription tier is now active and visible to beneficiaries.</p>
        <a href="subscription_tiers.php" class="btn-ghost">View All Tiers</a>
        <a href="create_subscription_tier.php" class="btn-primary" style="margin-left:8px;">+ Create Another</a>
    </div>

    <?php else : ?>
    <div class="fb-card">
        <form method="POST" action="<?php echo basename(__FILE__); ?>">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">

            <p class="form-section">Basic Information</p>

            <div class="form-group">
                <label>Tier Name <span class="required">*</span></label>
                <input type="text" name="tier_name" required placeholder="e.g. Annual Family Plan" value="<?php echo dol_escape_htmltag(GETPOST('tier_name')); ?>">
            </div>

            <div class="form-group">
                <label>Tier Type <span class="required">*</span></label>
                <input type="text" name="tier_type" required list="tier-type-list" placeholder="Annual, Donor, Guest…" value="<?php echo dol_escape_htmltag(GETPOST('tier_type')); ?>">
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
                    <input type="number" name="duration_months" required min="1" max="60" value="<?php echo (int)GETPOST('duration_months') ?: 12; ?>">
                    <div class="hint">12 for annual, 1–3 for short-term</div>
                </div>
                <div class="form-group">
                    <label>Price (₦) <span class="required">*</span></label>
                    <input type="number" name="price" required min="0" step="0.01" placeholder="50000.00" value="<?php echo dol_escape_htmltag(GETPOST('price')); ?>">
                </div>
            </div>

            <p class="form-section">Details</p>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="2" placeholder="A brief summary of this plan…"><?php echo dol_escape_htmltag(GETPOST('description')); ?></textarea>
            </div>

            <div class="form-group">
                <label>Benefits</label>
                <textarea name="benefits" rows="4" placeholder="- Full product catalog access&#10;- Priority delivery&#10;- Monthly food box"><?php echo dol_escape_htmltag(GETPOST('benefits')); ?></textarea>
                <div class="hint">List one benefit per line. Shown to beneficiaries during signup.</div>
            </div>

            <p class="form-section">Order Permissions</p>

            <div class="form-group">
                <label>Can members on this tier place food orders? <span class="required">*</span></label>
                <div class="order-toggle">
                    <label class="order-option">
                        <input type="radio" name="can_place_orders" value="1" <?php echo GETPOST('can_place_orders') === '0' ? '' : 'checked'; ?>>
                        <div class="order-card">
                            <span class="oc-icon">✅</span>
                            <span class="oc-title">Can Place Orders</span>
                            <span class="oc-desc">Members can browse packages and place food orders in the app</span>
                        </div>
                    </label>
                    <label class="order-option">
                        <input type="radio" name="can_place_orders" value="0" <?php echo GETPOST('can_place_orders') === '0' ? 'checked' : ''; ?>>
                        <div class="order-card">
                            <span class="oc-icon">👁️</span>
                            <span class="oc-title">Browse Only</span>
                            <span class="oc-desc">Members can view packages but cannot checkout — app will prompt them to upgrade</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Max Orders per Month</label>
                <input type="number" name="max_orders_per_month" min="0" value="<?php echo max(0, (int)GETPOST('max_orders_per_month')); ?>" placeholder="0">
                <div class="hint">How many orders a member can place each calendar month. Set to <strong>0</strong> for unlimited.</div>
            </div>

            <div style="margin-top: 28px; display: flex; justify-content: flex-end; gap: 12px;">
                <a href="subscription_tiers.php" class="btn-ghost">Cancel</a>
                <button type="submit" class="btn-primary">Create Tier</button>
            </div>
        </form>
    </div>

    <div class="guide-box">
        <h4>💡 Pricing Guidelines</h4>
        <ul>
            <li><strong>Annual:</strong> ₦50,000 – ₦100,000 &nbsp;(12 months, full access)</li>
            <li><strong>Donor:</strong> ₦100,000+ &nbsp;(12 months, premium support tier)</li>
            <li><strong>Guest:</strong> ₦5,000 – ₦15,000 &nbsp;(1–3 months, trial)</li>
        </ul>
    </div>
    <?php endif; ?>

</div>

<?php llxFooter(); ?>
