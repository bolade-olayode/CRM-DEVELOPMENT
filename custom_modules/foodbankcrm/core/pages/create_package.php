<?php
/**
 * CREATE PACKAGE — FoodbankCRM Admin
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/package.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/packageitem.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('Administrator rights required.');
}

$langs->load("admin");
$_SESSION["mainmenu"] = "foodbankcrm";

$notice    = '';
$hide_form = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['newtoken']) {
        $notice = ['error', 'Security check failed. Please try again.'];
    } else {
        // ── Handle image upload ──────────────────────────────────────────────
        $image_url = trim($_POST['image_url'] ?? '');

        if (!empty($_FILES['package_image']['name']) && $_FILES['package_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo         = finfo_open(FILEINFO_MIME_TYPE);
            $mime          = finfo_file($finfo, $_FILES['package_image']['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowed_types)) {
                $notice = ['error', 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.'];
            } elseif ($_FILES['package_image']['size'] > 2 * 1024 * 1024) {
                $notice = ['error', 'Image must be under 2 MB.'];
            } else {
                $upload_dir = DOL_DOCUMENT_ROOT . '/custom/foodbankcrm/img/packages/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $ext       = pathinfo($_FILES['package_image']['name'], PATHINFO_EXTENSION);
                $filename  = 'pkg_' . uniqid() . '.' . strtolower($ext);
                $dest      = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['package_image']['tmp_name'], $dest)) {
                    $image_url = DOL_URL_ROOT . '/custom/foodbankcrm/img/packages/' . $filename;
                } else {
                    $notice = ['error', 'Failed to save uploaded image.'];
                }
            }
        }

        if (!isset($notice[0]) || $notice[0] !== 'error') {
        $p              = new Package($db);
        $p->ref         = trim($_POST['ref'] ?? '');
        $p->name        = trim($_POST['name'] ?? '');
        $p->description = trim($_POST['description'] ?? '');
        $p->status      = in_array($_POST['status'] ?? '', ['Active','Draft','Discontinued']) ? $_POST['status'] : 'Active';
        $p->image_url   = $image_url;

        $pid = $p->create($user);

        if ($pid > 0) {
            $count = 0;
            if (!empty($_POST['product_name'])) {
                foreach ($_POST['product_name'] as $k => $iname) {
                    if (empty(trim($iname))) continue;
                    $item               = new PackageItem($db);
                    $item->fk_package   = $pid;
                    $item->product_name = trim($iname);
                    $item->quantity     = (float)($_POST['quantity'][$k] ?? 1);
                    $item->unit         = trim($_POST['unit'][$k] ?? 'units');
                    $item->unit_price   = (float)($_POST['unit_price'][$k] ?? 0);
                    $item->create($user);
                    $count++;
                }
            }
            $notice    = ['success', "Package <strong>" . dol_escape_htmltag($p->name) . "</strong> created with {$count} item(s). <a href='packages.php' style='color:#15803d;text-decoration:underline;'>View all packages →</a>"];
            $hide_form = true;
        } else {
            $notice = ['error', 'Error creating package: ' . $p->error];
        }
        } // end upload-error guard
    }
}

llxHeader('', 'Create Package');
?>
<style>
:root {
    --accent:       #4f46e5;
    --accent-light: #e0e7ff;
    --accent-dark:  #3730a3;
    --green:        #10b981;
    --surface:      #f8fafc;
    --radius:       14px;
    --shadow:       0 2px 8px rgba(0,0,0,.07);
    --shadow-md:    0 6px 20px rgba(0,0,0,.10);
    --font:         "Segoe UI", Roboto, Arial, sans-serif;
}

#id-top { display: none !important; }
.side-nav, .side-nav-vert { top: 0 !important; height: 100vh !important; }
#id-right { padding-top: 0 !important; background: var(--surface) !important; min-height: 100vh; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }

.cp-wrap { max-width: 900px; margin: 0 auto; padding: 32px 28px 60px; font-family: var(--font); }

/* ── Header ── */
.cp-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
.cp-header h1 { margin: 0; font-size: 24px; font-weight: 800; color: #1e293b; }
.cp-header p  { margin: 4px 0 0; color: #64748b; font-size: 14px; }
.btn-primary { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff !important; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: background .2s; border: none; cursor: pointer; }
.btn-primary:hover { background: var(--accent-dark); }
.btn-ghost   { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #475569 !important; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none; border: 1px solid #e2e8f0; margin-right: 8px; transition: background .15s; }
.btn-ghost:hover { background: #f1f5f9; }

/* ── Notice ── */
.cp-notice { padding: 14px 18px; border-radius: 10px; margin-bottom: 24px; font-size: 14px; font-weight: 500; }
.cp-notice.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.cp-notice.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

/* ── Cards ── */
.cp-card {
    background: #fff; border-radius: var(--radius);
    box-shadow: var(--shadow-md); margin-bottom: 20px; overflow: hidden;
}
.cp-card-header {
    padding: 18px 24px 16px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: 10px;
}
.cp-card-header h2 { margin: 0; font-size: 15px; font-weight: 700; color: #1e293b; }
.cp-card-header span { font-size: 13px; color: #94a3b8; margin-left: auto; }
.cp-card-body { padding: 24px; }

/* ── Form fields ── */
.field-grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 18px; }
.field-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; }
@media(max-width:640px) { .field-grid-2, .field-grid-3 { grid-template-columns: 1fr; } }

.form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
.form-group:last-child { margin-bottom: 0; }
.form-group label { font-size: 13px; font-weight: 600; color: #374151; }
.form-group label .req { color: #ef4444; margin-left: 2px; }
.form-group label .hint { font-weight: 400; color: #94a3b8; margin-left: 6px; font-size: 12px; }

.form-control {
    padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px;
    font-size: 14px; color: #1e293b; background: #fff;
    transition: border-color .15s, box-shadow .15s;
    font-family: var(--font);
    width: 100%; box-sizing: border-box;
}
.form-control:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-light); }
textarea.form-control { resize: vertical; min-height: 80px; }

.status-chips { display: flex; gap: 10px; flex-wrap: wrap; }
.status-chip input[type=radio] { display: none; }
.status-chip label {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 20px; border: 1.5px solid #e2e8f0;
    font-size: 13px; font-weight: 600; color: #64748b; cursor: pointer;
    transition: all .15s;
}
.status-chip input[type=radio]:checked + label { border-color: currentColor; }
.status-chip.active  input[type=radio]:checked + label { color: #15803d; background: #dcfce7; border-color: #86efac; }
.status-chip.draft   input[type=radio]:checked + label { color: #92400e; background: #fef3c7; border-color: #fcd34d; }
.status-chip.disc    input[type=radio]:checked + label { color: #991b1b; background: #fee2e2; border-color: #fca5a5; }

/* ── Items table ── */
.item-table { width: 100%; border-collapse: collapse; }
.item-table thead th {
    text-align: left; padding: 10px 12px;
    background: #f8fafc; color: #64748b; font-size: 11px;
    text-transform: uppercase; letter-spacing: .5px; font-weight: 600;
    border-bottom: 2px solid #e2e8f0;
}
.item-table tbody td { padding: 8px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.item-table tbody tr:last-child td { border-bottom: none; }
.item-table tbody tr:hover { background: #fafbff; }

.item-input {
    padding: 9px 11px; border: 1px solid #e2e8f0; border-radius: 7px;
    font-size: 13px; color: #1e293b; width: 100%; box-sizing: border-box;
    font-family: var(--font);
}
.item-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 2px var(--accent-light); }

.btn-remove {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 6px;
    background: #fef2f2; border: 1px solid #fecaca;
    color: #ef4444; cursor: pointer; font-size: 16px; font-weight: 700;
    transition: background .15s;
}
.btn-remove:hover { background: #fee2e2; }

.btn-add-row {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 16px; border-radius: 8px;
    background: var(--accent-light); color: var(--accent-dark) !important;
    font-size: 13px; font-weight: 600; border: none; cursor: pointer;
    transition: background .15s;
}
.btn-add-row:hover { background: #c7d2fe; }

/* ── Total bar ── */
.total-bar {
    display: flex; align-items: center; justify-content: flex-end; gap: 12px;
    padding: 14px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0;
    border-radius: 0 0 var(--radius) var(--radius);
}
.total-bar .total-label { font-size: 13px; color: #64748b; font-weight: 500; }
.total-bar .total-val   { font-size: 18px; font-weight: 800; color: #1e293b; min-width: 120px; text-align: right; }

/* ── Submit bar ── */
.submit-bar {
    display: flex; align-items: center; justify-content: flex-end; gap: 12px;
    padding-top: 8px;
}

/* ── Image upload widget ── */
.img-upload-wrap { display: flex; flex-direction: column; gap: 10px; }
.img-drop-zone {
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;
    border: 2px dashed #cbd5e1; border-radius: 10px; padding: 24px 16px;
    cursor: pointer; transition: border-color .15s, background .15s; background: #f8fafc;
}
.img-drop-zone:hover, .img-drop-zone.drag-over {
    border-color: var(--accent); background: var(--accent-light);
}
.img-drop-text { font-size: 13px; font-weight: 600; color: #475569; }
.img-drop-hint  { font-size: 11px; color: #94a3b8; }
.img-preview-box {
    position: relative; border-radius: 10px; overflow: hidden;
    border: 1px solid #e2e8f0; background: #f8fafc;
    max-height: 180px; display: flex; align-items: center; justify-content: center;
}
.img-preview-box img { max-width: 100%; max-height: 180px; object-fit: contain; display: block; }
.img-clear-btn {
    position: absolute; top: 6px; right: 6px;
    width: 26px; height: 26px; border-radius: 50%; border: none;
    background: rgba(0,0,0,.55); color: #fff; font-size: 16px; font-weight: 700;
    cursor: pointer; line-height: 1; display: flex; align-items: center; justify-content: center;
}
.img-clear-btn:hover { background: rgba(0,0,0,.8); }
.img-url-row { display: flex; align-items: center; gap: 10px; }
.img-url-or { font-size: 12px; color: #94a3b8; white-space: nowrap; flex-shrink: 0; }

/* ── Success state ── */
.success-state { text-align: center; padding: 48px 32px; }
.success-state .check-icon { width: 72px; height: 72px; border-radius: 50%; background: #dcfce7; display: flex; align-items: center; justify-content: center; font-size: 36px; margin: 0 auto 20px; }
.success-state h2 { margin: 0 0 8px; font-size: 22px; font-weight: 800; color: #1e293b; }
.success-state p  { margin: 0 0 24px; color: #64748b; font-size: 14px; }
</style>

<div class="cp-wrap">

    <!-- Header -->
    <div class="cp-header">
        <div>
            <h1>📦 New Package</h1>
            <p>Create a food box template with items and pricing</p>
        </div>
        <div>
            <a href="packages.php" class="btn-ghost">← All Packages</a>
            <a href="dashboard_admin.php" class="btn-ghost">Dashboard</a>
        </div>
    </div>

    <?php if ($notice): ?>
    <div class="cp-notice <?php echo $notice[0]; ?>"><?php echo $notice[1]; ?></div>
    <?php endif; ?>

    <?php if ($hide_form): ?>
    <!-- Success state -->
    <div class="cp-card">
        <div class="success-state">
            <div class="check-icon">✓</div>
            <h2>Package Created!</h2>
            <p>Your package has been saved and is ready to use.</p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <a href="create_package.php" class="btn-ghost">+ Create Another</a>
                <a href="packages.php" class="btn-primary">View All Packages</a>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Form -->
    <form method="POST" action="<?php echo basename(__FILE__); ?>" id="pkgForm" enctype="multipart/form-data">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">

        <!-- Package Details -->
        <div class="cp-card">
            <div class="cp-card-header">
                <h2>Package Details</h2>
            </div>
            <div class="cp-card-body">
                <div class="field-grid-2">
                    <div class="form-group">
                        <label>Package Name <span class="req">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               placeholder="e.g. Family Relief Box" value="<?php echo dol_escape_htmltag($_POST['name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Reference <span class="hint">Optional — auto-generated if empty</span></label>
                        <input type="text" name="ref" class="form-control"
                               placeholder="PKG2025-0001" value="<?php echo dol_escape_htmltag($_POST['ref'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="2"
                              placeholder="Brief description of what this package contains..."><?php echo dol_escape_htmltag($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Package Image <span class="hint">Optional — shown in mobile app</span></label>
                    <div class="img-upload-wrap" id="imgUploadWrap">
                        <!-- Preview -->
                        <div class="img-preview-box" id="imgPreviewBox" style="display:none;">
                            <img id="imgPreview" src="" alt="Preview">
                            <button type="button" class="img-clear-btn" onclick="clearImage()" title="Remove image">×</button>
                        </div>
                        <!-- Drop zone -->
                        <label class="img-drop-zone" id="imgDropZone" for="package_image">
                            <svg width="28" height="28" fill="none" stroke="#94a3b8" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 20M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="img-drop-text">Click to upload or drag &amp; drop</span>
                            <span class="img-drop-hint">JPG, PNG, WEBP · max 2 MB</span>
                            <input type="file" name="package_image" id="package_image" accept="image/*" style="display:none;" onchange="previewImage(this)">
                        </label>
                        <!-- URL fallback -->
                        <div class="img-url-row">
                            <span class="img-url-or">or paste a URL</span>
                            <input type="url" name="image_url" id="imageUrlInput" class="form-control"
                                   placeholder="https://example.com/image.jpg"
                                   value="<?php echo dol_escape_htmltag($_POST['image_url'] ?? ''); ?>"
                                   oninput="previewUrl(this.value)">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <div class="status-chips">
                        <div class="status-chip active">
                            <input type="radio" name="status" id="s_active" value="Active" checked>
                            <label for="s_active">✅ Active</label>
                        </div>
                        <div class="status-chip draft">
                            <input type="radio" name="status" id="s_draft" value="Draft">
                            <label for="s_draft">📝 Draft</label>
                        </div>
                        <div class="status-chip disc">
                            <input type="radio" name="status" id="s_disc" value="Discontinued">
                            <label for="s_disc">🚫 Discontinued</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Package Items -->
        <div class="cp-card">
            <div class="cp-card-header">
                <h2>Package Items</h2>
                <span id="itemCount">1 item</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="item-table" id="itemTable">
                    <thead>
                        <tr>
                            <th style="min-width:200px;">Product / Item Name</th>
                            <th style="width:100px;">Qty</th>
                            <th style="width:120px;">Unit</th>
                            <th style="width:140px;">Est. Price (₦)</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemBody">
                        <?php
                        $saved_names  = $_POST['product_name'] ?? [''];
                        $saved_qtys   = $_POST['quantity']     ?? [1];
                        $saved_units  = $_POST['unit']         ?? ['kg'];
                        $saved_prices = $_POST['unit_price']   ?? [0];
                        foreach ($saved_names as $k => $iname):
                        ?>
                        <tr>
                            <td><input type="text"   name="product_name[]" class="item-input" required placeholder="e.g. Rice"
                                       value="<?php echo dol_escape_htmltag($iname); ?>"></td>
                            <td><input type="number" name="quantity[]"     class="item-input qty-input"   min="0.01" step="0.01"
                                       value="<?php echo dol_escape_htmltag($saved_qtys[$k] ?? 1); ?>"></td>
                            <td><input type="text"   name="unit[]"         class="item-input" list="unit-list" placeholder="kg, units…"
                                       value="<?php echo dol_escape_htmltag($saved_units[$k] ?? 'kg'); ?>"></td>
                            <td><input type="number" name="unit_price[]"   class="item-input price-input" min="0"    step="0.01"
                                       value="<?php echo dol_escape_htmltag($saved_prices[$k] ?? 0); ?>"></td>
                            <td><button type="button" class="btn-remove" onclick="removeRow(this)" title="Remove">×</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <datalist id="unit-list">
                    <option value="kg"><option value="g"><option value="units">
                    <option value="packs"><option value="bags"><option value="boxes">
                    <option value="cartons"><option value="litres"><option value="crates">
                    <option value="loaves"><option value="dozens"><option value="tonnes">
                </datalist>
            </div>

            <div style="padding:16px 20px; border-top:1px solid #f1f5f9;">
                <button type="button" class="btn-add-row" onclick="addRow()">+ Add Item</button>
            </div>

            <div class="total-bar">
                <span class="total-label">Estimated Package Total</span>
                <span class="total-val" id="grandTotal">₦0.00</span>
            </div>
        </div>

        <!-- Submit -->
        <div class="submit-bar">
            <a href="packages.php" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary" style="padding:12px 32px;font-size:15px;">
                Create Package
            </button>
        </div>

    </form>
    <?php endif; ?>

</div>

<script>
// ── Row management ────────────────────────────────────────────────────────────
function addRow() {
    var tbody = document.getElementById('itemBody');
    var first = tbody.rows[0];
    var row   = first.cloneNode(true);
    row.querySelectorAll('input').forEach(function(inp) {
        if (inp.name.startsWith('quantity')) inp.value = '1';
        else if (inp.name.startsWith('unit_price')) inp.value = '0';
        else if (inp.name.startsWith('unit')) inp.value = 'kg';
        else inp.value = '';
        inp.classList.remove('is-invalid');
    });
    tbody.appendChild(row);
    row.querySelector('input').focus();
    updateCount();
    recalcTotal();
}

function removeRow(btn) {
    var tbody = document.getElementById('itemBody');
    if (tbody.rows.length <= 1) {
        alert('A package must have at least one item.');
        return;
    }
    btn.closest('tr').remove();
    updateCount();
    recalcTotal();
}

function updateCount() {
    var n = document.getElementById('itemBody').rows.length;
    document.getElementById('itemCount').textContent = n + ' item' + (n !== 1 ? 's' : '');
}

// ── Running total ─────────────────────────────────────────────────────────────
function recalcTotal() {
    var total = 0;
    document.querySelectorAll('#itemBody tr').forEach(function(row) {
        var qty   = parseFloat(row.querySelector('.qty-input')?.value   || 0);
        var price = parseFloat(row.querySelector('.price-input')?.value || 0);
        total += qty * price;
    });
    document.getElementById('grandTotal').textContent = '₦' + total.toLocaleString('en-NG', {minimumFractionDigits:2, maximumFractionDigits:2});
}

// Attach live listeners on existing + future rows
document.getElementById('itemBody').addEventListener('input', function(e) {
    if (e.target.classList.contains('qty-input') || e.target.classList.contains('price-input')) {
        recalcTotal();
    }
});

// Init
updateCount();
recalcTotal();

// ── Image upload preview ──────────────────────────────────────────────────────
function previewImage(input) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('imgPreview').src = e.target.result;
        document.getElementById('imgPreviewBox').style.display = 'flex';
        document.getElementById('imgDropZone').style.display = 'none';
        // Clear manual URL since a file was chosen
        document.getElementById('imageUrlInput').value = '';
    };
    reader.readAsDataURL(input.files[0]);
}

function previewUrl(url) {
    if (!url) {
        document.getElementById('imgPreviewBox').style.display = 'none';
        document.getElementById('imgDropZone').style.display = 'flex';
        return;
    }
    document.getElementById('imgPreview').src = url;
    document.getElementById('imgPreviewBox').style.display = 'flex';
    document.getElementById('imgDropZone').style.display = 'none';
    // Clear file input since URL was typed
    document.getElementById('package_image').value = '';
}

function clearImage() {
    document.getElementById('imgPreview').src = '';
    document.getElementById('imgPreviewBox').style.display = 'none';
    document.getElementById('imgDropZone').style.display = 'flex';
    document.getElementById('package_image').value = '';
    document.getElementById('imageUrlInput').value = '';
}

// Drag-and-drop onto the drop zone
(function() {
    var zone = document.getElementById('imgDropZone');
    if (!zone) return;
    zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', function()  { zone.classList.remove('drag-over'); });
    zone.addEventListener('drop', function(e) {
        e.preventDefault();
        zone.classList.remove('drag-over');
        var files = e.dataTransfer.files;
        if (files && files[0]) {
            var inp = document.getElementById('package_image');
            // DataTransfer lets us assign dropped files to the input
            var dt = new DataTransfer();
            dt.items.add(files[0]);
            inp.files = dt.files;
            previewImage(inp);
        }
    });
})();

// If page reloads with a URL already set (validation error), show preview
(function() {
    var urlVal = document.getElementById('imageUrlInput').value;
    if (urlVal) previewUrl(urlVal);
})();
</script>

<?php llxFooter(); ?>
