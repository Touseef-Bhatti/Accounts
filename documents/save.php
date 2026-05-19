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

    recordSuggestions($accountId, $docType, $doc);



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

    $lines = $post['lines_proforma'] ?? [];

    if (trim($doc['invoice_no'] ?? '') === '' || trim($doc['buyer_name'] ?? '') === '') {

        return [$doc, $lines, ['ok' => false, 'message' => 'Proforma: invoice number and buyer name are required.']];

    }

    return [$doc, $lines, ['ok' => true, 'message' => '']];

}



function collectCommercial(array $post): array

{

    $doc = $post['ci'] ?? [];

    $lines = $post['lines_commercial'] ?? [];

    if (trim($doc['invoice_no'] ?? '') === '' || trim($doc['buyer_name'] ?? '') === '') {

        return [$doc, $lines, ['ok' => false, 'message' => 'Commercial: invoice number and buyer name are required.']];

    }

    return [$doc, $lines, ['ok' => true, 'message' => '']];

}



function collectPacking(array $post): array

{

    $doc = $post['pl'] ?? [];

    $lines = $post['lines_packing'] ?? [];

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

    $lines = $post['lines_gate_pass'] ?? [];

    if (trim($doc['gate_pass_no'] ?? '') === '') {

        return [$doc, $lines, ['ok' => false, 'message' => 'Gate pass: reference number is required.']];

    }

    return [$doc, $lines, ['ok' => true, 'message' => '']];

}



function normalizeDoc(array $d): array

{

    foreach (['subtotal', 'freight', 'insurance', 'total', 'total_gross_kg', 'total_net_kg', 'total_cbm', 'total_packages'] as $n) {

        if (isset($d[$n])) {

            $d[$n] = (float) str_replace(',', '', (string) $d[$n]);

        }

    }

    foreach (['invoice_date', 'validity_date', 'lc_date', 'packing_date', 'contract_date', 'gate_pass_date'] as $dt) {

        if (isset($d[$dt]) && $d[$dt] === '') {

            $d[$dt] = null;

        }

    }

    return $d;

}



function recordSuggestions(int $accountId, string $docType, array $doc): void

{

    $map = match ($docType) {

        'proforma' => [

            'exporter_name' => $doc['exporter_name'] ?? null,

            'exporter_address' => $doc['exporter_address'] ?? null,

            'buyer_name' => $doc['buyer_name'] ?? null,

            'buyer_address' => $doc['buyer_address'] ?? null,

            'consignee_name' => $doc['consignee_name'] ?? null,

            'consignee_address' => $doc['consignee_address'] ?? null,

            'notify_party' => $doc['notify_party'] ?? null,

            'country_destination' => $doc['country_destination'] ?? null,

            'port_loading' => $doc['port_loading'] ?? null,

            'port_discharge' => $doc['port_discharge'] ?? null,

            'payment_terms' => $doc['payment_terms'] ?? null,

            'pi_invoice_no' => $doc['invoice_no'] ?? null,

        ],

        'commercial' => [

            'exporter_name' => $doc['exporter_name'] ?? null,

            'buyer_name' => $doc['buyer_name'] ?? null,

            'ci_invoice_no' => $doc['invoice_no'] ?? null,

            'lc_no' => $doc['lc_no'] ?? null,

        ],

        'packing' => [

            'container_no' => $doc['container_no'] ?? null,

        ],

        'gate_pass' => [

            'container_no' => $doc['container_no'] ?? null,

            'country_destination' => $doc['destination'] ?? null,

        ],

        default => [

            'buyer_name' => $doc['buyer_name'] ?? null,

        ],

    };

    SuggestionService::recordMany($accountId, $map);

}

