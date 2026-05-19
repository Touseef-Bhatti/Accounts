<?php



declare(strict_types=1);



require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_once dirname(__DIR__) . '/includes/layout.php';



use App\AccountRepository;

use App\Auth;

use App\DocumentRepository;



Auth::requireLogin();



if (empty($_SESSION['account_id'])) {

    redirect(base_url('index.php'));

}



$account = AccountRepository::find((int) $_SESSION['account_id']);

if (!$account) {

    redirect(base_url('index.php'));

}



$setId = isset($_GET['set_id']) ? (int) $_GET['set_id'] : 0;

$data = $setId ? DocumentRepository::loadFull($setId) : [];

$prefill = [];



$docTypes = DocumentRepository::DOC_TYPES;

$requestedType = $_GET['type'] ?? 'proforma';

if (!DocumentRepository::isValidDocType($requestedType)) {

    $requestedType = 'proforma';

}



if ($data) {

    $docType = DocumentRepository::inferDocType($data);

    $p = $data['proforma'] ?? [];

    $c = $data['commercial'] ?? [];

    $pk = $data['packing'] ?? [];

    $ct = $data['contract'] ?? [];

    $gp = $data['gate_pass'] ?? [];

} else {

    $docType = $requestedType;

    $bankBlock = implode("\n", array_filter([

        $account['bank_name'] ? 'Bank: ' . $account['bank_name'] : '',

        $account['bank_branch'] ? 'Branch: ' . $account['bank_branch'] : '',

        $account['bank_account'] ? 'A/C: ' . $account['bank_account'] : '',

        $account['bank_iban'] ? 'IBAN: ' . $account['bank_iban'] : '',

        $account['bank_swift'] ? 'SWIFT: ' . $account['bank_swift'] : '',

    ]));

    $exporter = $account['legal_name'] ?: $account['name'];

    $expAddr = trim(($account['address'] ?? '') . "\n" . ($account['city'] ?? '') . ', ' . ($account['country'] ?? 'Pakistan'));

    $ref = 'EXP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

    $today = date('Y-m-d');

    $prefill = [

        'reference_no' => $ref,

        'exporter_name' => $exporter,

        'exporter_address' => $expAddr,

        'seller_name' => $exporter,

        'seller_address' => $expAddr,

        'bank_details' => $bankBlock,

        'currency' => $account['default_currency'] ?? 'USD',

        'country_origin' => 'Pakistan',

        'incoterms' => 'CFR',

        'invoice_date' => $today,

        'packing_date' => $today,

        'contract_date' => $today,

        'governing_law' => 'Laws of Pakistan',

        'payment_terms' => '100% irrevocable L/C at sight',

        'delivery_terms' => 'As per Incoterms 2020',

        'arbitration' => 'Any dispute shall be settled amicably; failing which, courts at Lahore, Pakistan shall have jurisdiction.',

        'force_majeure' => 'Neither party shall be liable for failure due to acts of God, war, strikes, or government restrictions.',

        'inspection_terms' => 'SGS or mutually agreed inspection at port of loading.',

    ];

    $p = [

        'invoice_no' => $ref,

        'invoice_date' => $today,

        'exporter_name' => $exporter,

        'exporter_address' => $expAddr,

        'country_origin' => 'Pakistan',

        'incoterms' => 'CFR',

        'currency' => $prefill['currency'],

        'payment_terms' => $prefill['payment_terms'],

        'bank_details' => $bankBlock,

    ];

    $c = [

        'invoice_no' => $ref,

        'invoice_date' => $today,

        'exporter_name' => $exporter,

        'exporter_address' => $expAddr,

        'currency' => $prefill['currency'],

        'bank_details' => $bankBlock,

    ];

    $pk = [

        'packing_list_no' => $ref,

        'packing_date' => $today,

        'exporter_name' => $exporter,

        'invoice_ref' => $ref,

    ];

    $ct = [

        'contract_no' => $ref,

        'contract_date' => $today,

        'seller_name' => $exporter,

        'seller_address' => $expAddr,

        'currency' => $prefill['currency'],

        'governing_law' => $prefill['governing_law'],

        'delivery_terms' => $prefill['delivery_terms'],

        'payment_terms' => $prefill['payment_terms'],

        'inspection_terms' => $prefill['inspection_terms'],

        'force_majeure' => $prefill['force_majeure'],

        'arbitration' => $prefill['arbitration'],

        'product_description' => 'Goods as per contract terms.',

    ];

    $gp = [

        'gate_pass_no' => $ref,

        'gate_pass_date' => $today,

        'cargo_description' => 'HC CONTAINING ZINC WHICH MANUFACTURED BY LOCAL RAW MATERIAL IN OUR FACTORY.',

    ];

}



$refNo = $data['set']['reference_no'] ?? ($prefill['reference_no'] ?? '');

$linesP = $data['lines_proforma'] ?? [['description' => '', 'hs_code' => '', 'quantity' => '', 'unit' => 'MT', 'unit_price' => '', 'amount' => '']];

$linesC = $data['lines_commercial'] ?? [['description' => '', 'hs_code' => '', 'quantity' => '', 'unit' => 'MT', 'unit_price' => '', 'amount' => '']];

$linesPk = $data['lines_packing'] ?? [['description' => '', 'packages' => '', 'gross_kg' => '', 'net_kg' => '', 'dimensions' => '']];

$linesGp = $data['lines_gate_pass'] ?? [['description' => '', 'quantity' => '', 'unit' => 'KG', 'remarks' => '']];



function v(array $arr, string $key, $default = '') {

    return $arr[$key] ?? $default;

}



$typeLabels = [

    'proforma' => 'Proforma Invoice',

    'commercial' => 'Commercial Invoice',

    'packing' => 'Packing List',

    'contract' => 'Export Contract',

    'gate_pass' => 'Gate Pass',

];



layout_header('Create ' . ($typeLabels[$docType] ?? 'Document') . ' — ' . $account['name'], 'create');

?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">

    <div>

        <h1 class="h4 mb-0">Create Export Document</h1>

        <p class="text-muted small mb-0"><?= e($account['name']) ?> · Ref: <strong id="displayRef"><?= e($refNo) ?></strong></p>

    </div>

    <a href="<?= e(base_url('index.php')) ?>" class="btn btn-outline-secondary btn-sm">← Change Company</a>

</div>



<?php if (!$setId): ?>

<div class="card shadow-sm mb-4">

    <div class="card-body">

        <p class="small text-muted mb-3">Select the document type to create. Only this document will be saved and downloaded.</p>

        <div class="row g-2 doc-type-picker">

            <?php foreach ($docTypes as $t): ?>

            <div class="col-6 col-md">

                <label class="doc-type-card btn btn-outline-secondary w-100 py-3 <?= $docType === $t ? 'active border-primary' : '' ?>">

                    <input type="radio" name="document_type_pick" value="<?= e($t) ?>" class="d-none" <?= $docType === $t ? 'checked' : '' ?>>

                    <span class="d-block fw-semibold small"><?= e($typeLabels[$t]) ?></span>

                </label>

            </div>

            <?php endforeach; ?>

        </div>

    </div>

</div>

<?php endif; ?>



<form action="<?= e(base_url('documents/save.php')) ?>" method="post" id="docForm" enctype="multipart/form-data">

    <?= csrf_field() ?>

    <input type="hidden" name="account_id" value="<?= (int) $account['id'] ?>">

    <input type="hidden" name="set_id" value="<?= $setId ?>">

    <input type="hidden" name="reference_no" id="reference_no" value="<?= e($refNo) ?>">

    <input type="hidden" name="document_type" id="document_type" value="<?= e($docType) ?>">

    <input type="hidden" name="action" id="form_action" value="draft">

    <input type="hidden" name="submit_token" value="<?= e(form_submit_token()) ?>">



    <?php

    match ($docType) {

        'proforma' => include __DIR__ . '/partials/form_proforma.php',

        'commercial' => include __DIR__ . '/partials/form_commercial.php',

        'packing' => include __DIR__ . '/partials/form_packing.php',

        'contract' => include __DIR__ . '/partials/form_contract.php',

        'gate_pass' => include __DIR__ . '/partials/form_gate_pass.php',

        default => include __DIR__ . '/partials/form_proforma.php',

    };

    ?>



    <div class="sticky-bottom-bar card shadow-lg border-primary">

        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center py-3">

            <span class="text-muted small">Creating: <strong><?= e($typeLabels[$docType] ?? $docType) ?></strong> only</span>

            <div>

                <button type="submit" class="btn btn-outline-secondary" data-action="draft">Save Draft</button>

                <button type="submit" class="btn btn-primary btn-lg" data-action="review">Review &amp; Download →</button>

            </div>

        </div>

    </div>

</form>

<script>

document.querySelectorAll('.doc-type-card').forEach(card => {

    card.addEventListener('click', () => {

        const val = card.querySelector('input')?.value;

        if (val && !<?= $setId ? 'true' : 'false' ?>) {

            window.location.href = '<?= e(base_url('documents/create.php')) ?>?type=' + encodeURIComponent(val);

        }

    });

});

</script>

<?php layout_footer(true); ?>

