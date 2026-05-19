<?php



declare(strict_types=1);



require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_once dirname(__DIR__) . '/includes/layout.php';



use App\Auth;

use App\DocumentRepository;



Auth::requireLogin();



$setId = (int) ($_GET['set_id'] ?? 0);

$data = DocumentRepository::loadFull($setId);



if (!$data || empty($data['set'])) {

    flash('error', 'Document set not found.');

    redirect(base_url('index.php'));

}



$set = $data['set'];

$docType = DocumentRepository::inferDocType($data);

$docLabel = DocumentRepository::docTypeLabel($docType);



$hasDoc = match ($docType) {

    'proforma' => !empty($data['proforma']),

    'commercial' => !empty($data['commercial']),

    'packing' => !empty($data['packing']),

    'contract' => !empty($data['contract']),

    'gate_pass' => !empty($data['gate_pass']),

    default => false,

};



if (!$hasDoc) {

    flash('error', 'No document data found for this set.');

    redirect(base_url('documents/create.php?set_id=' . $setId));

}



$previewUrl = base_url('documents/pdf.php?set_id=' . $setId . '&type=' . urlencode($docType));

$downloadUrl = base_url('documents/pdf.php?set_id=' . $setId . '&type=' . urlencode($docType) . '&download=1');



layout_header('Review — ' . $docLabel);

?>

<div class="review-toolbar d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">

    <div>

        <h1 class="h4 mb-0">Review &amp; Download</h1>

        <p class="text-muted small mb-0"><?= e($set['account_name']) ?> · <?= e($docLabel) ?> · <?= e($set['reference_no']) ?></p>

    </div>

    <div class="d-flex flex-wrap gap-2">

        <a href="<?= e(base_url('documents/create.php?set_id=' . $setId)) ?>" class="btn btn-outline-secondary">← Edit</a>

        <a href="<?= e($downloadUrl) ?>" class="btn btn-success btn-lg" id="downloadPdfBtn">Download PDF</a>

    </div>

</div>



<div class="card shadow-sm mb-4">

    <div class="card-header py-2 small text-muted">PDF preview (matches downloaded file)</div>

    <div class="card-body p-0">

        <iframe class="pdf-preview-frame" src="<?= e($previewUrl) ?>" title="<?= e($docLabel) ?> preview"></iframe>

    </div>

</div>



<a href="<?= e(base_url('documents/create.php?type=' . urlencode($docType))) ?>" class="btn btn-primary">Create New Document</a>

<a href="<?= e(base_url('documents/history.php')) ?>" class="btn btn-outline-secondary ms-2">Document History</a>

<?php layout_footer(); ?>

