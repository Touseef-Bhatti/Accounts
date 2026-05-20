<?php



declare(strict_types=1);



require_once dirname(__DIR__) . '/includes/bootstrap.php';



use App\Auth;

use App\DocumentRepository;

use App\SuggestionService;



Auth::requireLogin();

verify_csrf();



$accountId = (int) ($_POST['account_id'] ?? 0);

$setId = (int) ($_POST['set_id'] ?? 0);

$referenceNo = trim($_POST['reference_no'] ?? '');

$docType = $_POST['document_type'] ?? 'proforma';

$action = $_POST['action'] ?? 'draft';

$email = Auth::userEmail() ?? '';

$submitToken = trim($_POST['submit_token'] ?? '');



if ($accountId <= 0 || $referenceNo === '') {

    flash('error', 'Missing account or reference number.');

    redirect(base_url('documents/create.php'));

}



if (!DocumentRepository::isValidDocType($docType)) {

    flash('error', 'Invalid document type.');

    redirect(base_url('documents/create.php'));

}



if (
    $submitToken !== ''
    && !empty($_SESSION['_save_results'][$submitToken])
    && ($_SESSION['_save_actions'][$submitToken] ?? '') === $action
) {
    redirect($_SESSION['_save_results'][$submitToken]);
}



$redirectEdit = base_url('documents/create.php' . ($setId ? '?set_id=' . $setId : '?type=' . $docType));



try {

    $setId = DocumentRepository::resolveSetId($accountId, $referenceNo, $setId, $email, $docType);


    [$doc, $lines, $valid] = collectDocumentPayload($docType, $_POST);

    if (!$valid['ok']) {

        flash('error', $valid['message']);

        redirect($redirectEdit);

    }

    DocumentRepository::saveForType($setId, $docType, normalizeDoc($doc), $lines);

    recordSuggestions($accountId, $docType, $doc, $lines);
    if ($action === 'review') {

        DocumentRepository::markCompleted($setId);

        $target = base_url('documents/review.php?set_id=' . $setId);

    } else {

        flash('success', 'Draft saved successfully.');

        $target = base_url('documents/create.php?set_id=' . $setId);

    }



    if ($submitToken !== '') {
        $_SESSION['_save_results'][$submitToken] = $target;
        $_SESSION['_save_actions'][$submitToken] = $action;
        if (count($_SESSION['_save_results']) > 20) {
            $_SESSION['_save_results'] = array_slice($_SESSION['_save_results'], -10, null, true);
            $_SESSION['_save_actions'] = array_slice($_SESSION['_save_actions'] ?? [], -10, null, true);
        }
    }

    rotate_form_submit_token();

    redirect($target);

} catch (Throwable $e) {

    if (app_is_debug()) {

        flash('error', $e->getMessage());

    } else {

        flash('error', 'Could not save document. Please try again.');

    }

    redirect($redirectEdit);

}



function groupLineItems(array $items): array
{
    $grouped = [];
    $currentRow = [];
    foreach ($items as $item) {
        foreach ($item as $key => $value) {
            if (!empty($currentRow) && array_key_exists($key, $currentRow)) {
                $grouped[] = $currentRow;
                $currentRow = [];
            }
            $currentRow[$key] = $value;
        }
    }
    if (!empty($currentRow)) {
        $grouped[] = $currentRow;
    }
    return $grouped;
}

/** @return array{0: array, 1: array, 2: array{ok: bool, message: string}} */

function collectDocumentPayload(string $docType, array $post): array
{

    return match ($docType) {

        'proforma' => collectProforma($post),

        'commercial' => collectCommercial($post),

        'packing' => collectPacking($post),

        'contract' => collectContract($post),

        'gate_pass' => collectGatePass($post),

        default => [[], [], ['ok' => false, 'message' => 'Unknown document type.']],

    };

}



function collectProforma(array $post): array
{

    $doc = $post['pi'] ?? [];
    $rawLines = $post['lines_proforma'] ?? [];
    $lines = groupLineItems($rawLines);

    if (trim($doc['invoice_no'] ?? '') === '' || trim($doc['buyer_name'] ?? '') === '') {

        return [$doc, $lines, ['ok' => false, 'message' => 'Proforma: invoice number and buyer name are required.']];

    }

    return [$doc, $lines, ['ok' => true, 'message' => '']];

}


function collectCommercial(array $post): array
{

    $doc = $post['ci'] ?? [];
    $rawLines = $post['lines_commercial'] ?? [];
    $lines = groupLineItems($rawLines);

    if (trim($doc['invoice_no'] ?? '') === '' || trim($doc['buyer_name'] ?? '') === '') {

        return [$doc, $lines, ['ok' => false, 'message' => 'Commercial: invoice number and buyer name are required.']];

    }

    return [$doc, $lines, ['ok' => true, 'message' => '']];

}


function collectPacking(array $post): array
{

    $doc = $post['pl'] ?? [];
    $rawLines = $post['lines_packing'] ?? [];
    $lines = groupLineItems($rawLines);

    if (trim($doc['packing_list_no'] ?? '') === '' || trim($doc['exporter_name'] ?? '') === '') {

        return [$doc, $lines, ['ok' => false, 'message' => 'Packing list: list number and exporter are required.']];

    }

    return [$doc, $lines, ['ok' => true, 'message' => '']];

}


function collectContract(array $post): array
{

    $doc = $post['ec'] ?? [];

    if (trim($doc['contract_no'] ?? '') === '' || trim($doc['buyer_name'] ?? '') === '') {

        return [$doc, [], ['ok' => false, 'message' => 'Contract: contract number and buyer name are required.']];

    }

    return [$doc, [], ['ok' => true, 'message' => '']];

}


function collectGatePass(array $post): array
{

    $doc = $post['gp'] ?? [];
    $rawLines = $post['lines_gate_pass'] ?? [];
    $lines = groupLineItems($rawLines);

    if (trim($doc['gate_pass_no'] ?? '') === '') {

        return [$doc, $lines, ['ok' => false, 'message' => 'Gate pass: reference number is required.']];

    }

    return [$doc, $lines, ['ok' => true, 'message' => '']];

}



function normalizeDoc(array $d): array
{

    foreach (['subtotal', 'freight', 'insurance', 'total', 'total_gross_kg', 'total_net_kg', 'total_cbm', 'total_packages', 'quantity', 'unit_price', 'total_value'] as $n) {

        if (isset($d[$n])) {

            $d[$n] = (float) str_replace(',', '', (string) $d[$n]);

        }

    }

    foreach (['invoice_date', 'validity_date', 'lc_date', 'packing_date', 'contract_date', 'gate_pass_date'] as $dt) {
        if (isset($d[$dt])) {
            $d[$dt] = parse_date_input($d[$dt]);
        }
    }

    return $d;

}



function recordSuggestions(int $accountId, string $docType, array $doc, array $lines = []): void

{

    // Exclude noise, dates, numbers, IDs, and fields with high unique random values
    $exclude = [
        'id', 'document_set_id', 'invoice_date', 'validity_date', 'lc_date', 
        'packing_date', 'contract_date', 'gate_pass_date', 'created_at', 'updated_at',
        'reference_no', 'invoice_no', 'packing_list_no', 'contract_no', 'gate_pass_no'
    ];

    // Map of specific doc fields to unified suggestion keys
    $keyMap = [
        'destination' => 'country_destination',
        'delivery_terms' => 'incoterms',
    ];

    // Record all fields in the main document payload dynamically
    foreach ($doc as $key => $value) {
        if (is_string($value) && trim($value) !== '') {
            if (!in_array($key, $exclude, true)) {
                $fieldKey = $keyMap[$key] ?? $key;
                // If it is a generic field name (like matching across doc types), it will group suggestions beautifully!
                SuggestionService::record($accountId, $fieldKey, $value);
            }
        }
    }

    // Record line item descriptions, HS codes, and remarks dynamically
    foreach ($lines as $line) {
        if (is_array($line)) {
            foreach ($line as $col => $val) {
                if (is_string($val) && trim($val) !== '') {
                    if (in_array($col, ['description', 'hs_code', 'remarks'], true)) {
                        SuggestionService::record($accountId, 'line_' . $col, $val);
                    }
                }
            }
        }
    }

}

