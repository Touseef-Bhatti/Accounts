<?php
require_once __DIR__ . '/includes/bootstrap.php';
use App\DocumentRepository;
use App\Database;

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

// Test groupLineItems
$testRawLines = [
    ['description' => 'Zinc'],
    ['hs_code' => '7901'],
    ['quantity' => '10'],
    ['unit' => 'MT'],
    ['unit_price' => '2500'],
    ['amount' => '25000'],
    ['description' => 'Lead'],
    ['hs_code' => '7801'],
    ['quantity' => '5'],
    ['unit' => 'MT'],
    ['unit_price' => '2000'],
    ['amount' => '10000'],
];
echo "Testing groupLineItems function:\n";
echo "Raw lines:\n";
print_r($testRawLines);
echo "\nGrouped lines:\n";
print_r(groupLineItems($testRawLines));
echo "\n---\n";

// Get the last document set
$pdo = Database::connection();
$stmt = $pdo->query("SELECT id, reference_no, doc_type FROM document_sets ORDER BY id DESC LIMIT 1");
$set = $stmt->fetch();

if (!$set) {
    die("No document sets found\n");
}

echo "Last document set: ID {$set['id']}, Ref: {$set['reference_no']}, Type: {$set['doc_type']}\n";

// Get line items for this set
$lineItems = DocumentRepository::getLineItems((int)$set['id'], $set['doc_type']);
echo "\nLine items found: " . count($lineItems) . "\n";

if (count($lineItems) > 0) {
    echo "\nFirst line item:\n";
    print_r($lineItems[0]);
}

// Get the proforma/commercial/etc document
$doc = null;
if ($set['doc_type'] === 'proforma') {
    $doc = DocumentRepository::getProforma((int)$set['id']);
} elseif ($set['doc_type'] === 'commercial') {
    $doc = DocumentRepository::getCommercial((int)$set['id']);
}

if ($doc) {
    echo "\nDocument data (subtotal/total):\n";
    echo "Subtotal: " . ($doc['subtotal'] ?? 0) . "\n";
    echo "Total: " . ($doc['total'] ?? 0) . "\n";
}
