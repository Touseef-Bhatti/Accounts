<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

use App\AccountRepository;
use App\Auth;

Auth::requireLogin();

$accounts = AccountRepository::all();
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $accountId = (int) ($_POST['account_id'] ?? 0);

    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mime = mime_content_type($_FILES['logo']['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            $error = 'Logo must be JPG, PNG, GIF, or WebP.';
        } else {
            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                default => 'webp',
            };
            $dir = __DIR__ . '/uploads/logos';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $filename = 'logo_' . $accountId . '_' . time() . '.' . $ext;
            $dest = $dir . '/' . $filename;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                AccountRepository::updateLogo($accountId, 'uploads/logos/' . $filename);
                $message = 'Logo updated successfully.';
                $accounts = AccountRepository::all();
            } else {
                $error = 'Could not upload logo.';
            }
        }
    }

    if (isset($_POST['update_company'])) {
        $pdo = App\Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE accounts SET legal_name=?, address=?, city=?, phone=?, email=?, ntn=?, strn=?,
             bank_name=?, bank_branch=?, bank_account=?, bank_iban=?, bank_swift=?, default_currency=?
             WHERE id=?'
        );
        $stmt->execute([
            $_POST['legal_name'], $_POST['address'], $_POST['city'], $_POST['phone'], $_POST['email'],
            $_POST['ntn'], $_POST['strn'], $_POST['bank_name'], $_POST['bank_branch'], $_POST['bank_account'],
            $_POST['bank_iban'], $_POST['bank_swift'], $_POST['default_currency'], $accountId,
        ]);
        $message = 'Company details updated.';
        $accounts = AccountRepository::all();
    }

    if (isset($_POST['update_offsets'])) {
        $docTypes = ['proforma', 'commercial', 'packing', 'contract', 'gate_pass'];
        foreach ($docTypes as $docType) {
            $offsetVal = trim($_POST['pdf_top_offset_' . $docType] ?? '40');
            AccountRepository::setSetting($accountId, 'pdf_top_offset_' . $docType, $offsetVal);
        }
        $message = 'PDF print top offsets updated successfully.';
        $accounts = AccountRepository::all();
    }
}

layout_header('Settings');
?>
<h1 class="h4 mb-4">Company Settings & Logo</h1>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<?php foreach ($accounts as $acc): ?>
<div class="card mb-4 shadow-sm">
    <div class="card-header fw-semibold"><?= e($acc['name']) ?></div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <?php if ($acc['logo_path'] && is_file(__DIR__ . '/' . $acc['logo_path'])): ?>
                    <img src="<?= e(asset_url($acc['logo_path'])) ?>" alt="Logo" class="img-fluid mb-2" style="max-height:100px">
                <?php else: ?>
                    <p class="text-muted">No logo uploaded</p>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="account_id" value="<?= (int)$acc['id'] ?>">
                    <input type="file" name="logo" class="form-control form-control-sm mb-2" accept="image/*" required>
                    <button type="submit" class="btn btn-sm btn-primary">Upload Logo</button>
                </form>
            </div>
            <div class="col-md-8">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="account_id" value="<?= (int)$acc['id'] ?>">
                    <input type="hidden" name="update_company" value="1">
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">Legal Name</label>
                            <input name="legal_name" class="form-control" value="<?= e($acc['legal_name']) ?>"></div>
                        <div class="col-md-6"><label class="form-label">NTN / STRN</label>
                            <div class="input-group">
                                <input name="ntn" class="form-control" placeholder="NTN" value="<?= e($acc['ntn']) ?>">
                                <input name="strn" class="form-control" placeholder="STRN" value="<?= e($acc['strn']) ?>">
                            </div></div>
                        <div class="col-12"><label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= e($acc['address']) ?></textarea></div>
                        <div class="col-md-4"><label class="form-label">City</label>
                            <input name="city" class="form-control" value="<?= e($acc['city']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Phone</label>
                            <input name="phone" class="form-control" value="<?= e($acc['phone']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Email</label>
                            <input name="email" class="form-control" value="<?= e($acc['email']) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Bank Name / Branch</label>
                            <input name="bank_name" class="form-control mb-1" value="<?= e($acc['bank_name']) ?>">
                            <input name="bank_branch" class="form-control" value="<?= e($acc['bank_branch']) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Account / IBAN / SWIFT</label>
                            <input name="bank_account" class="form-control mb-1" value="<?= e($acc['bank_account']) ?>">
                            <input name="bank_iban" class="form-control mb-1" value="<?= e($acc['bank_iban']) ?>">
                            <input name="bank_swift" class="form-control" value="<?= e($acc['bank_swift']) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Currency</label>
                            <input name="default_currency" class="form-control" value="<?= e($acc['default_currency']) ?>"></div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Save Company Details</button>
                </form>
                <hr class="my-4">
                <h5 class="h6 mb-3 text-primary fw-semibold"><i class="bi bi-printer"></i> PDF Print Top Offsets (mm)</h5>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="account_id" value="<?= (int)$acc['id'] ?>">
                    <input type="hidden" name="update_offsets" value="1">
                    <p class="text-muted small mb-3">Adjust the top margin to skip the pre-printed letterhead area. Set to 0 if printing on plain paper.</p>
                    <div class="row g-3">
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label small">Proforma Invoice</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="pdf_top_offset_proforma" class="form-control" min="0" max="250" value="<?= (int)App\AccountRepository::getTopOffset((int)$acc['id'], 'proforma') ?>" required>
                                <span class="input-group-text">mm</span>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label small">Commercial Invoice</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="pdf_top_offset_commercial" class="form-control" min="0" max="250" value="<?= (int)App\AccountRepository::getTopOffset((int)$acc['id'], 'commercial') ?>" required>
                                <span class="input-group-text">mm</span>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label small">Packing List</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="pdf_top_offset_packing" class="form-control" min="0" max="250" value="<?= (int)App\AccountRepository::getTopOffset((int)$acc['id'], 'packing') ?>" required>
                                <span class="input-group-text">mm</span>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label small">Export Contract</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="pdf_top_offset_contract" class="form-control" min="0" max="250" value="<?= (int)App\AccountRepository::getTopOffset((int)$acc['id'], 'contract') ?>" required>
                                <span class="input-group-text">mm</span>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label small">Gate Pass</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="pdf_top_offset_gate_pass" class="form-control" min="0" max="250" value="<?= (int)App\AccountRepository::getTopOffset((int)$acc['id'], 'gate_pass') ?>" required>
                                <span class="input-group-text">mm</span>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-secondary btn-sm mt-3">Save PDF Offsets</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php layout_footer(); ?>
