<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/layout.php';

use App\Auth;
use App\DocumentRepository;

Auth::requireLogin();

$accountId = (int) ($_SESSION['account_id'] ?? 0);
$history = $accountId ? DocumentRepository::listByAccount($accountId) : [];

layout_header('Document History');
?>
<h1 class="h4 mb-3">Previous Document Sets</h1>
<?php if (!$accountId): ?>
<p class="text-muted">Select a company from the home page first.</p>
<a href="<?= e(base_url('index.php')) ?>" class="btn btn-primary">Select Company</a>
<?php elseif (!$history): ?>
<p class="text-muted">No documents yet.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr><th>Reference</th><th>Type</th><th>Status</th><th>Created</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($history as $row):
            $typeLabel = DocumentRepository::docTypeLabel($row['doc_type'] ?? 'proforma');
        ?>
        <tr>
            <td><?= e($row['reference_no']) ?></td>
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
<a href="<?= e(base_url('documents/create.php')) ?>" class="btn btn-primary mt-2">+ New Document Set</a>
<?php layout_footer(); ?>
