<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/layout.php';

use App\Auth;
use App\DocumentRepository;

Auth::requireLogin();

$accountId = (int) ($_SESSION['account_id'] ?? 0);
$selectedType = $_GET['type'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$buyerSearch = trim($_GET['buyer'] ?? '');
$sort = $_GET['sort'] ?? 'date_desc';

$filtersActive = ($selectedType !== '' || $startDate !== '' || $endDate !== '' || $buyerSearch !== '' || $sort !== 'date_desc');

// Parse sort parameter
$sortBy = 'created_at';
$sortOrder = 'DESC';

switch ($sort) {
    case 'date_asc':
        $sortBy = 'created_at';
        $sortOrder = 'ASC';
        break;
    case 'type_asc':
        $sortBy = 'doc_type';
        $sortOrder = 'ASC';
        break;
    case 'type_desc':
        $sortBy = 'doc_type';
        $sortOrder = 'DESC';
        break;
    case 'ref_asc':
        $sortBy = 'reference_no';
        $sortOrder = 'ASC';
        break;
    case 'ref_desc':
        $sortBy = 'reference_no';
        $sortOrder = 'DESC';
        break;
    case 'date_desc':
    default:
        $sortBy = 'created_at';
        $sortOrder = 'DESC';
        break;
}

$history = [];
if ($accountId) {
    $history = DocumentRepository::listByAccount(
        $accountId,
        $selectedType !== '' ? $selectedType : null,
        $startDate !== '' ? $startDate : null,
        $endDate !== '' ? $endDate : null,
        $buyerSearch !== '' ? $buyerSearch : null,
        $sortBy,
        $sortOrder
    );
}

layout_header('Document History');
?>
<h1 class="h4 mb-3">Previous Document Sets</h1>
<?php if (!$accountId): ?>
<p class="text-muted">Select a company from the home page first.</p>
<a href="<?= e(base_url('index.php')) ?>" class="btn btn-primary">Select Company</a>
<?php else: ?>

<form method="get" class="row g-2 mb-4 align-items-end p-3 bg-light rounded border">
    <div class="col-md-4">
        <label for="buyer" class="form-label small fw-semibold text-muted">Search by Buyer</label>
        <input type="text" name="buyer" id="buyer" class="form-control form-control-sm" placeholder="Enter buyer name..." value="<?= e($buyerSearch) ?>">
    </div>
    <div class="col-md-2">
        <label for="type" class="form-label small fw-semibold text-muted">Filter by Type</label>
        <select name="type" id="type" class="form-select form-select-sm">
            <option value="">All Types</option>
            <?php foreach (DocumentRepository::DOC_TYPES as $t): ?>
                <option value="<?= e($t) ?>" <?= $selectedType === $t ? 'selected' : '' ?>><?= e(DocumentRepository::docTypeLabel($t)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label for="start_date" class="form-label small fw-semibold text-muted">From Date</label>
        <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" value="<?= e($startDate) ?>">
    </div>
    <div class="col-md-2">
        <label for="end_date" class="form-label small fw-semibold text-muted">To Date</label>
        <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" value="<?= e($endDate) ?>">
    </div>
    <div class="col-md-2">
        <label for="sort" class="form-label small fw-semibold text-muted">Sort By</label>
        <select name="sort" id="sort" class="form-select form-select-sm">
            <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Date: Newest First</option>
            <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Date: Oldest First</option>
            <option value="type_asc" <?= $sort === 'type_asc' ? 'selected' : '' ?>>Type: A-Z</option>
            <option value="type_desc" <?= $sort === 'type_desc' ? 'selected' : '' ?>>Type: Z-A</option>
            <option value="ref_asc" <?= $sort === 'ref_asc' ? 'selected' : '' ?>>Reference: A-Z</option>
            <option value="ref_desc" <?= $sort === 'ref_desc' ? 'selected' : '' ?>>Reference: Z-A</option>
        </select>
    </div>
    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
        <?php if ($filtersActive): ?>
            <a href="?" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
        <?php endif; ?>
        <button type="submit" class="btn btn-sm btn-primary px-3">Apply</button>
    </div>
</form>

<?php if (!$history): ?>
<div class="text-center py-4 border rounded bg-white">
    <p class="text-muted mb-2">No documents found matching your criteria.</p>
    <?php if ($filtersActive): ?>
        <a href="?" class="btn btn-sm btn-secondary">Clear All Filters</a>
    <?php else: ?>
        <p class="text-muted mb-0">No documents yet.</p>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr><th>Reference</th><th>Buyer</th><th>Type</th><th>Status</th><th>Created</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($history as $row):
            $typeLabel = DocumentRepository::docTypeLabel($row['doc_type'] ?? 'proforma');
        ?>
        <tr>
            <td><?= e($row['reference_no']) ?></td>
            <td><?= e($row['buyer_name'] ?? '') ?></td>
            <td><span class="badge bg-info text-dark"><?= e($typeLabel) ?></span></td>
            <td><span class="badge bg-<?= $row['status'] === 'completed' ? 'success' : 'secondary' ?>"><?= e($row['status']) ?></span></td>
            <td><?= e(date('d M Y H:i', strtotime($row['created_at']))) ?></td>
            <td>
                <a href="<?= e(base_url('documents/create.php?set_id=' . $row['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                <a href="<?= e(base_url('documents/review.php?set_id=' . $row['id'])) ?>" class="btn btn-sm btn-outline-success">Review / PDF</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php endif; ?>
<a href="<?= e(base_url('documents/create.php')) ?>" class="btn btn-primary mt-2">+ New Document Set</a>
<?php layout_footer(); ?>
