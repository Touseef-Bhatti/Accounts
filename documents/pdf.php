<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Auth;
use App\DocumentRepository;
use App\PdfGenerator;

Auth::requireLogin();

$setId = (int) ($_GET['set_id'] ?? 0);
$type = $_GET['type'] ?? '';
$download = isset($_GET['download']) && $_GET['download'] === '1';
$inline = !$download;
$hasDompdf = class_exists('Dompdf\\Dompdf');

if ($download && !$hasDompdf) {
    require_once dirname(__DIR__) . '/includes/layout.php';
    layout_header('PDF Download Not Available');
    ?>
    <div class="container py-5">
        <div class="alert alert-warning">
            <h4>PDF Download Not Available</h4>
            <p>PDF generation requires the <code>dompdf</code> library, which is not installed on this server.</p>
            <p>You can still:</p>
            <ul>
                <li>View the document in <a href="<?= e(base_url('documents/review.php?set_id=' . $setId)) ?>">review mode</a></li>
                <li>Print the document using your browser's print function</li>
            </ul>
            <a href="<?= e(base_url('documents/review.php?set_id=' . $setId)) ?>" class="btn btn-primary">← Back to Review</a>
        </div>
    </div>
    <?php
    layout_footer();
    exit;
}

$data = DocumentRepository::loadFull($setId);
if (!$data || empty($data['set'])) {
    http_response_code(404);
    die('Document set not found.');
}

$set = $data['set'];
$ref = preg_replace('/[^a-zA-Z0-9_-]/', '_', $set['reference_no']);

if ($type === 'all') {
    http_response_code(400);
    die('Combined PDF export is disabled. Download one document at a time from the review page.');
}

if ($type === '' || !DocumentRepository::isValidDocType($type)) {
    $type = DocumentRepository::inferDocType($data);
}

// Set the PDF top offset for the current doc type
$GLOBALS['pdf_top_offset'] = \App\AccountRepository::getTopOffset((int) $set['account_id'], $type);

require_once dirname(__DIR__) . '/templates/pdf_layout.php';

$renderers = [
    'proforma' => function () use ($data, $set) {
        $doc = $data['proforma'];
        if (!$doc) {
            return '';
        }
        $lines = $data['lines_proforma'];
        return include dirname(__DIR__) . '/templates/proforma_pdf.php';
    },
    'commercial' => function () use ($data, $set) {
        $doc = $data['commercial'];
        if (!$doc) {
            return '';
        }
        $lines = $data['lines_commercial'];
        return include dirname(__DIR__) . '/templates/commercial_pdf.php';
    },
    'packing' => function () use ($data, $set) {
        $doc = $data['packing'];
        if (!$doc) {
            return '';
        }
        $lines = $data['lines_packing'];
        return include dirname(__DIR__) . '/templates/packing_pdf.php';
    },
    'contract' => function () use ($data, $set) {
        $doc = $data['contract'];
        if (!$doc) {
            return '';
        }
        return include dirname(__DIR__) . '/templates/contract_pdf.php';
    },
    'gate_pass' => function () use ($data, $set) {
        $doc = $data['gate_pass'];
        if (!$doc) {
            return '';
        }
        $lines = $data['lines_gate_pass'];
        return include dirname(__DIR__) . '/templates/gate_pass_pdf.php';
    },
];

if (!isset($renderers[$type])) {
    http_response_code(400);
    die('Invalid document type.');
}

unset($GLOBALS['pdf_skip_styles']);
$html = $renderers[$type]();

if (!is_string($html) || trim($html) === '') {
    http_response_code(404);
    die('Document not found in this set.');
}

try {
    PdfGenerator::render($html, "{$type}_{$ref}.pdf", $inline);
} catch (Throwable $e) {
    error_log('PDF generation failed: ' . $e->getMessage());
    http_response_code(500);
    die(app_is_debug() ? $e->getMessage() : 'PDF generation failed. Please try again.');
}
