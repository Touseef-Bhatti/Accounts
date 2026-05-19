<?php

/** @var array $set @var array $doc @var array $lines */

require_once __DIR__ . '/pdf_layout.php';



$cur = $doc['currency'] ?? 'USD';

$hsCode = '';

foreach ($lines as $row) {

    if (!empty($row['hs_code'])) {

        $hsCode = $row['hs_code'];

        break;

    }

}



$fromPort = htmlspecialchars($doc['port_loading'] ?? 'KARACHI PORT, PAKISTAN');

$toPort = htmlspecialchars($doc['port_discharge'] ?? $doc['country_destination'] ?? '');

$incoterms = htmlspecialchars($doc['incoterms'] ?? 'CFR');



$html = pdf_styles();

$html .= "<div class='sample-doc'>";

$html .= "<h1 class='sample-title'>COMMERCIAL INVOICE</h1>";

$html .= "<p class='ci-headline'><strong>INVOICE NUMBER:</strong> " . htmlspecialchars($doc['invoice_no']);

$html .= " &nbsp; <strong>DATED:</strong> " . format_date($doc['invoice_date']) . "</p>";

$html .= "<p class='ci-route'><strong>FROM:</strong> {$fromPort} &nbsp; <strong>TO:</strong> {$toPort} ({$incoterms})</p>";

if ($hsCode !== '') {

    $html .= "<p><strong>HS CODE:</strong> " . htmlspecialchars($hsCode) . "</p>";

}



$consignee = trim(($doc['consignee_name'] ?: $doc['buyer_name']) . "\n" . ($doc['consignee_address'] ?: $doc['buyer_address'] ?? ''));

$html .= "<div class='sample-block'><strong>CONSIGNEE:</strong><br>" . nl2br(htmlspecialchars($consignee)) . "</div>";



$html .= "<table class='items sample-items'><thead><tr>

<th>Sr.</th><th>DESCRIPTION</th><th>QUANTITY (MT)</th><th>RATE ({$cur})</th><th>AMOUNT ({$cur})</th>

</tr></thead><tbody>";

foreach ($lines as $i => $row) {

    $html .= '<tr><td>' . ($i + 1) . '</td>';

    $html .= '<td>' . htmlspecialchars($row['description'] ?? '') . '</td>';

    $html .= '<td align="center">' . number_format((float) ($row['quantity'] ?? 0), 3) . '</td>';

    $html .= '<td align="right">' . number_format((float) ($row['unit_price'] ?? 0), 2) . '</td>';

    $html .= '<td align="right">' . number_format((float) ($row['amount'] ?? 0), 2) . '</td></tr>';

}

$html .= '</tbody></table>';



$html .= "<p class='amount-words'><strong>• TOTAL VALUE:</strong> " . htmlspecialchars(amount_in_words((float) ($doc['total'] ?? 0), $cur)) . "</p>";



if (!empty($doc['payment_terms'])) {

    $html .= "<p><strong>• PAYMENT TERMS:</strong><br>" . nl2br(htmlspecialchars($doc['payment_terms'])) . "</p>";

}



$html .= "<p><strong>Payment Instructions ({$cur} ACCOUNT)</strong></p>";

$html .= "<table class='meta-table bank-table'><tr><th>BANK NAME</th><td>" . htmlspecialchars($set['bank_name'] ?? '') . "</td></tr>";

$html .= "<tr><th>ACCOUNT TITLE</th><td>" . htmlspecialchars($set['legal_name'] ?: $set['account_name']) . "</td></tr>";

$html .= "<tr><th>IBAN NUMBER</th><td>" . htmlspecialchars($set['bank_iban'] ?? '') . "</td></tr>";

$html .= "<tr><th>SWIFT CODE</th><td>" . htmlspecialchars($set['bank_swift'] ?? '') . "</td></tr></table>";



if (!empty($doc['bank_details'])) {

    $html .= "<p class='text-pre'>" . htmlspecialchars($doc['bank_details']) . "</p>";

}



$html .= "<ul class='terms-list'>";

$html .= '<li>Certified that the goods are of Pakistan origin.</li>';

if (!empty($doc['shipping_marks'])) {

    $html .= '<li>Shipping marks: ' . htmlspecialchars($doc['shipping_marks']) . '</li>';

}

$html .= '<li>Certified that the goods are processed by "' . htmlspecialchars($set['account_name']) . '"</li>';

$html .= '</ul>';



$html .= "<p class='sign-line'>Authorized Signatory: _______________________</p>";

$html .= '</div>';



return $html;

