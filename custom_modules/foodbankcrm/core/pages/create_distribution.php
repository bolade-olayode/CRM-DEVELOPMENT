<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distribution.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distributionline.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/package.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/packageitem.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

// --- HANDLE SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_distribution'])) {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        setEventMessages("Security token expired. Please try again.", null, 'errors');
    } else {
        $db->begin();

        $distribution                  = new Distribution($db);
        $distribution->fk_beneficiary  = GETPOST('fk_beneficiary', 'int');
        $distribution->fk_package      = GETPOST('fk_package',     'int');
        $distribution->fk_warehouse    = GETPOST('fk_warehouse',   'int');
        $distribution->fk_user         = $user->id;
        $distribution->note            = GETPOST('note', 'restricthtml');
        $distribution->status          = 'Prepared';
        $distribution->payment_status  = 'Pending';
        $distribution->total_amount    = 0;

        $dist_id = $distribution->create($user);

        if ($dist_id > 0) {
            $total_value = 0;
            $items_added = 0;

            $pkg_items = PackageItem::getAllByPackage($db, $distribution->fk_package);

            foreach ($pkg_items as $p_item) {
                $avail = Distribution::getAvailableDonations($db, $p_item->product_name);

                if (!empty($avail) && isset($avail[0])) {
                    $source = $avail[0];

                    $line                  = new DistributionLine($db);
                    $line->fk_distribution = $dist_id;
                    $line->fk_donation     = $source['id'];
                    $line->product_name    = $p_item->product_name;
                    $line->quantity        = $p_item->quantity;
                    $line->unit            = $p_item->unit;

                    if ($line->create($user) > 0) {
                        $items_added++;
                        $price        = isset($source['unit_price']) ? $source['unit_price'] : 0;
                        $total_value += ($p_item->quantity * $price);
                    }
                }
            }

            if ($items_added > 0) {
                $distribution->total_amount = $total_value;
                $distribution->update($user);
                $db->commit();

                header("Location: view_distribution.php?id=".$dist_id."&msg=created");
                exit;
            } else {
                $db->rollback();
                setEventMessages("Stock Error: Could not find enough 'Received' inventory for the items in this package.", null, 'errors');
            }
        } else {
            $db->rollback();
            setEventMessages("Error creating distribution: ".$distribution->error, null, 'errors');
        }
    }
}

$langs->load("admin");
llxHeader('', 'New Shipment');
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

.fb-wrap { max-width: 900px; margin: 0 auto; padding: 24px 28px; font-family: var(--font); }

.fb-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
.fb-page-header h1 { margin: 0; font-size: 22px; font-weight: 800; color: #1e293b; }
.fb-page-header p  { margin: 4px 0 0; color: #64748b; font-size: 14px; }

.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none !important; border: none; cursor: pointer; font-family: var(--font); transition: background .2s; }
.btn-primary:hover { background: var(--accent-dark); text-decoration: none !important; }
.btn-ghost { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none !important; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; text-decoration: none !important; }

.fb-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; margin-bottom: 20px; }
.fb-card-head { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; }
.fb-card-head h3 { margin: 0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #94a3b8; }
.fb-card-body { padding: 24px; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px; }
.form-group label { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
.form-group input,
.form-group select,
.form-group textarea { border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; font-size: 14px; color: #1e293b; background: #fff; width: 100%; box-sizing: border-box; font-family: var(--font); transition: border-color .15s, box-shadow .15s; }
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(79,70,229,.1); outline: none; }

/* Package cards */
.pkg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; margin-top: 10px; }
.pkg-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
.pkg-card { display: block; background: #fff; border: 2px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; cursor: pointer; transition: all .2s; position: relative; }
.pkg-card:hover { border-color: var(--accent); background: #fafbff; }
.pkg-option input[type="radio"]:checked + .pkg-card { border-color: var(--accent); background: var(--accent-light); box-shadow: 0 0 0 1px var(--accent); }
.pkg-card .check-icon { position: absolute; top: 10px; right: 10px; width: 20px; height: 20px; background: var(--accent); color: #fff; border-radius: 50%; display: none; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; }
.pkg-option input[type="radio"]:checked + .pkg-card .check-icon { display: flex; }
.pkg-name { font-weight: 700; color: #1e293b; font-size: 14px; display: block; margin-bottom: 5px; }
.pkg-desc { font-size: 12px; color: #64748b; display: block; }

.step-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--accent); margin-bottom: 6px; display: block; }

.warn-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 18px; color: #92400e; font-size: 13px; }
</style>

<div class="fb-wrap">

    <div class="fb-page-header">
        <div>
            <h1>New Shipment</h1>
            <p>Create a food box delivery order for a subscriber</p>
        </div>
        <div>
            <a href="distributions.php" class="btn-ghost">← Back to Shipments</a>
        </div>
    </div>

    <div class="fb-card">
        <div class="fb-card-head"><h3>Shipment Details</h3></div>
        <div class="fb-card-body">
            <form method="POST" action="<?php echo basename(__FILE__); ?>">
                <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                <input type="hidden" name="create_distribution" value="1">

                <span class="step-label">Step 1 — Subscriber</span>
                <div class="form-group">
                    <label>Select Beneficiary <span style="color:#ef4444;">*</span></label>
                    <select name="fk_beneficiary" required>
                        <option value="">— Choose Beneficiary —</option>
                        <?php
                        $res = $db->query("SELECT rowid, firstname, lastname, ref FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries ORDER BY firstname");
                        while ($obj = $db->fetch_object($res)) {
                            echo '<option value="'.$obj->rowid.'">'.dol_escape_htmltag($obj->firstname.' '.$obj->lastname.' ('.$obj->ref.')').'</option>';
                        }
                        ?>
                    </select>
                </div>

                <span class="step-label">Step 2 — Source Warehouse</span>
                <div class="form-group">
                    <label>Warehouse <span style="color:#ef4444;">*</span></label>
                    <select name="fk_warehouse" required>
                        <option value="">— Select Warehouse —</option>
                        <?php
                        $res = $db->query("SELECT rowid, label FROM ".MAIN_DB_PREFIX."foodbank_warehouses ORDER BY label");
                        while ($obj = $db->fetch_object($res)) {
                            echo '<option value="'.$obj->rowid.'">'.dol_escape_htmltag($obj->label).'</option>';
                        }
                        ?>
                    </select>
                </div>

                <span class="step-label">Step 3 — Package Template</span>
                <div class="form-group">
                    <label>Select Package <span style="color:#ef4444;">*</span></label>
                </div>

                <?php
                $sql = "SELECT rowid, name, description FROM ".MAIN_DB_PREFIX."foodbank_packages WHERE status='Active'";
                $res = $db->query($sql);
                if ($res && $db->num_rows($res) > 0) : ?>
                <div class="pkg-grid">
                    <?php while ($pkg = $db->fetch_object($res)) : ?>
                    <label class="pkg-option">
                        <input type="radio" name="fk_package" value="<?php echo $pkg->rowid; ?>" required>
                        <div class="pkg-card" onclick="">
                            <div class="check-icon">✓</div>
                            <span class="pkg-name">📦 <?php echo dol_escape_htmltag($pkg->name); ?></span>
                            <span class="pkg-desc"><?php echo dol_escape_htmltag(dol_trunc($pkg->description, 70)); ?></span>
                        </div>
                    </label>
                    <?php endwhile; ?>
                </div>
                <?php else : ?>
                <div class="warn-box">⚠️ No active packages found. Please <a href="create_package.php" style="color:var(--accent); font-weight:600;">create a package</a> first.</div>
                <?php endif; ?>

                <div class="form-group" style="margin-top:22px;">
                    <label>Notes (Optional)</label>
                    <textarea name="note" rows="2" placeholder="Delivery instructions or special requests..."></textarea>
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="submit" class="btn-primary">Create Shipment</button>
                    <a href="distributions.php" class="btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</div>

<?php llxFooter(); ?>
