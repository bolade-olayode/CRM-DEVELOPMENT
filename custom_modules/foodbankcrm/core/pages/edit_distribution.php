<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distribution.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distributionline.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$id = GETPOST('id', 'int');
if (!$id) { header("Location: distributions.php"); exit; }

$d = new Distribution($db);
if ($d->fetch($id) <= 0) { accessforbidden("Distribution not found"); }

// Handle Remove Line (CSRF via GET token)
if (isset($_GET['del_line'])) {
    if (!isset($_GET['token']) || $_GET['token'] != $_SESSION['newtoken']) {
        setEventMessages("Security check failed.", null, 'errors');
    } else {
        $line     = new DistributionLine($db);
        $line->id = (int)$_GET['del_line'];
        $line->fetch($line->id);
        $line->delete($user);
    }
    header("Location: edit_distribution.php?id=".$id); exit;
}

// Handle Update
$notice = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed.'];
    } else {
        $d->status         = GETPOST('status',         'alphanohtml');
        $d->payment_status = GETPOST('payment_status', 'alphanohtml');
        $d->note           = GETPOST('note',           'restricthtml');
        $d->update($user);

        // Add new item if supplied
        if (!empty($_POST['new_donation']) && !empty($_POST['new_qty'])) {
            $line                  = new DistributionLine($db);
            $line->fk_distribution = $id;
            $line->fk_donation     = (int)$_POST['new_donation'];
            $line->quantity        = (float)$_POST['new_qty'];

            $donations = Distribution::getAvailableDonations($db);
            foreach ($donations as $don) {
                if ($don['id'] == $line->fk_donation) {
                    $line->product_name = $don['label'];
                    $line->unit         = $don['unit'];
                    break;
                }
            }
            $line->create($user);
        }

        header("Location: view_distribution.php?id=".$id."&msg=updated"); exit;
    }
}

$langs->load("admin");
$lines     = DistributionLine::getAllByDistribution($db, $id);
$donations = Distribution::getAvailableDonations($db);

llxHeader('', 'Edit Shipment');
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

.fb-notice { padding: 13px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
.fb-notice.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

/* Items table */
.items-table { width: 100%; border-collapse: collapse; }
.items-table thead th { text-align: left; padding: 10px 14px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
.items-table tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #374151; vertical-align: middle; }
.items-table tbody tr:last-child td { border-bottom: none; }
.items-table tfoot td { padding: 12px 14px; background: #f8fafc; }

.remove-btn { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; text-decoration: none; color: #dc2626 !important; border: 1px solid #fecaca; background: #fff; transition: background .15s; }
.remove-btn:hover { background: #fef2f2; text-decoration: none !important; }

.add-row select,
.add-row input { border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; font-size: 13px; color: #1e293b; background: #fff; font-family: var(--font); }
.add-row select:focus,
.add-row input:focus { border-color: var(--accent); outline: none; }

.empty-state { text-align: center; padding: 30px; color: #94a3b8; font-size: 13px; }
</style>

<div class="fb-wrap">

    <div class="fb-page-header">
        <div>
            <h1>Edit Shipment</h1>
            <p>Ref: <strong><?php echo dol_escape_htmltag($d->ref); ?></strong></p>
        </div>
        <div>
            <a href="view_distribution.php?id=<?php echo $id; ?>" class="btn-ghost">← View Shipment</a>
        </div>
    </div>

    <?php if ($notice) : ?>
    <div class="fb-notice <?php echo $notice[0]; ?>"><?php echo $notice[1]; ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo basename(__FILE__).'?id='.$id; ?>">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">

        <!-- Status & Notes -->
        <div class="fb-card">
            <div class="fb-card-head"><h3>Shipment Status</h3></div>
            <div class="fb-card-body">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Order Status</label>
                        <select name="status">
                            <?php foreach (['Prepared','In Transit','Delivered'] as $st) : ?>
                            <option value="<?php echo $st; ?>" <?php echo $d->status==$st?'selected':''; ?>><?php echo $st; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Status</label>
                        <select name="payment_status">
                            <?php foreach (['Pending','Paid','Pay_On_Delivery'] as $ps) : ?>
                            <option value="<?php echo $ps; ?>" <?php echo $d->payment_status==$ps?'selected':''; ?>><?php echo str_replace('_',' ',$ps); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="note" rows="2"><?php echo dol_escape_htmltag($d->note); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="fb-card">
            <div class="fb-card-head"><h3>Shipment Items</h3></div>
            <div class="fb-card-body" style="padding:0;">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Source (Stock Ref)</th>
                            <th>Quantity</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($lines)) : ?>
                        <?php foreach ($lines as $line) : ?>
                        <tr>
                            <td><?php echo dol_escape_htmltag($line->product_name); ?></td>
                            <td style="color:#94a3b8; font-size:12px;"><?php echo dol_escape_htmltag($line->donation_ref); ?></td>
                            <td><?php echo number_format($line->quantity).' '.dol_escape_htmltag($line->unit); ?></td>
                            <td style="text-align:right;">
                                <a href="edit_distribution.php?id=<?php echo $id; ?>&del_line=<?php echo $line->id; ?>&token=<?php echo newToken(); ?>"
                                   class="remove-btn"
                                   onclick="return confirm('Remove this item? Stock will be restored.')">Remove</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="4" class="empty-state">No items in this shipment yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                    <?php if (!empty($donations)) : ?>
                    <tfoot>
                        <tr class="add-row">
                            <td style="color:#64748b; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;">Add Item:</td>
                            <td colspan="2">
                                <select name="new_donation" style="width:100%; margin-bottom:0;">
                                    <option value="">— Select available stock —</option>
                                    <?php foreach ($donations as $don) : ?>
                                    <option value="<?php echo $don['id']; ?>"><?php echo dol_escape_htmltag($don['label']); ?> (<?php echo $don['available']; ?> avail)</option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="text-align:right;">
                                <input type="number" name="new_qty" placeholder="Qty" step="0.01" min="0.01" style="width:90px; text-align:center;">
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn-primary">Save Changes</button>
            <a href="view_distribution.php?id=<?php echo $id; ?>" class="btn-ghost">Cancel</a>
        </div>

    </form>

</div>

<?php llxFooter(); ?>
