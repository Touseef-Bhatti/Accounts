<?php
require_once __DIR__ . '/pdf_layout.php';
$html = pdf_header($set, 'Export Sales Contract');
$html .= "<table class='meta-table'>
<tr><th>Contract No.</th><td>" . htmlspecialchars($doc['contract_no']) . "</td><th>Date</th><td>" . format_date($doc['contract_date']) . "</td></tr>
</table>";
$html .= "<table width='100%'><tr>
<td width='50%' class='party-box'><strong>Seller</strong>" . nl2br(htmlspecialchars($doc['seller_name'] . "\n" . $doc['seller_address'])) . "</td>
<td width='50%' class='party-box'><strong>Buyer</strong>" . nl2br(htmlspecialchars($doc['buyer_name'] . "\n" . $doc['buyer_address'])) . "</td>
</tr></table>";

$sections = [
    'Product Description' => $doc['product_description'],
    'Quantity' => $doc['quantity'],
    'Unit Price' => $doc['unit_price'],
    'Total Value' => $doc['total_value'] . ' ' . $doc['currency'],
    'Delivery Terms' => $doc['delivery_terms'],
    'Payment Terms' => $doc['payment_terms'],
    'Port of Loading' => $doc['port_loading'],
    'Port of Discharge' => $doc['port_discharge'],
    'Shipment Period' => $doc['shipment_period'],
    'Inspection' => $doc['inspection_terms'],
    'Force Majeure' => $doc['force_majeure'],
    'Arbitration' => $doc['arbitration'],
    'Governing Law' => $doc['governing_law'],
];
foreach ($sections as $title => $text) {
    if (!$text) continue;
    $html .= "<p><strong>{$title}:</strong><br><span class='text-pre'>" . htmlspecialchars((string)$text) . "</span></p>";
}
if ($doc['special_conditions']) {
    $html .= "<p><strong>Special Conditions:</strong><br><span class='text-pre'>" . htmlspecialchars($doc['special_conditions']) . "</span></p>";
}
$html .= "<table class='sign-row'><tr><td>Seller Signature & Stamp</td><td>Buyer Signature & Stamp</td></tr></table>";
$html .= "<p style='font-size:8px;margin-top:20px'>This contract is made in duplicate, each party retaining one original.</p>";
return $html;
