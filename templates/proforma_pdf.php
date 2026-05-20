<?php

/** @var array $set @var array $doc @var array $lines */

require_once __DIR__ . '/pdf_layout.php';



$company = htmlspecialchars($set['legal_name'] ?: $set['account_name']);

$phone = htmlspecialchars($set['phone'] ?? '');

$cur = $doc['currency'] ?? 'USD';

$incoterms = htmlspecialchars($doc['incoterms'] ?? 'CFR');

$discharge = htmlspecialchars($doc['port_discharge'] ?? $doc['country_destination'] ?? '');

$grandLabel = 'GRAND TOTAL (' . $incoterms . ($discharge ? ' ' . $discharge : '') . ')';



$html = pdf_styles();

$html .= "<div class='sample-doc'>";

$html .= "<h1 class='sample-title'>PROFORMA INVOICE</h1>";



$html .= "<table width='100%' class='sample-split'><tr>";

$html .= "<td width='50%' valign='top'><strong>EXPORTER / SELLER:</strong><br>";

$html .= nl2br(htmlspecialchars(trim($doc['exporter_name'] . "\n" . ($doc['exporter_address'] ?? ''))));

if ($phone) {

    $html .= "<br>Cell: {$phone}";

}

$html .= "</td>";

$html .= "<td width='50%' valign='top'><strong>INVOICE DETAILS:</strong><br>";

$html .= "PI No: " . htmlspecialchars($doc['invoice_no']) . "<br>";

$html .= "Date: " . format_date($doc['invoice_date']);

$html .= "</td></tr></table>";



$buyerBlock = trim($doc['buyer_name'] . "\n" . ($doc['buyer_address'] ?? ''));

$html .= "<div class='sample-block'><strong>CONSIGNEE / BUYER:</strong><br>" . nl2br(htmlspecialchars($buyerBlock));

if (!empty($doc['notify_party'])) {

    $html .= "<br>" . nl2br(htmlspecialchars($doc['notify_party']));

}

$html .= "</div>";



$html .= "<table class='items sample-items'><thead><tr>

<th>Description of Goods &amp; Specifications</th><th>Quantity</th><th>Unit Price<br>({$cur})</th><th>Total Amount<br>({$cur})</th>

</tr></thead><tbody>";

foreach ($lines as $row) {

    $html .= '<tr><td>' . htmlspecialchars($row['description'] ?? '') . '</td>';

    $html .= '<td align="center">' . number_format((float) ($row['quantity'] ?? 0), 3) . ' ' . htmlspecialchars($row['unit'] ?? '') . '</td>';

    $html .= '<td align="right">' . format_money((float) ($row['unit_price'] ?? 0), $cur) . '</td>';

    $html .= '<td align="right">' . format_money((float) ($row['amount'] ?? 0), $cur) . '</td></tr>';

}

$freight = (float) ($doc['freight'] ?? 0);

if ($freight > 0) {

    $html .= '<tr><td colspan="3" align="right">Ocean Freight Charges</td><td align="right">' . format_money($freight, $cur) . '</td></tr>';

}

$html .= '<tr class="grand-row"><td colspan="3" align="right"><strong>' . $grandLabel . '</strong></td>';

$html .= '<td align="right"><strong>' . format_money((float) ($doc['total'] ?? 0), $cur) . '</strong></td></tr>';

$html .= '</tbody></table>';



$html .= "<p class='amount-words'><strong>Amount in Words:</strong> " . htmlspecialchars(amount_in_words((float) ($doc['total'] ?? 0), $cur)) . "</p>";



$html .= "<div class='sample-block'><strong>BANKING DETAILS (FOR LC ISSUANCE)</strong><br>";

$html .= "<span class='text-pre'>" . htmlspecialchars($doc['bank_details'] ?? '') . "</span></div>";



$html .= "<div class='sample-block'><strong>TERMS &amp; CONDITIONS</strong><ul class='terms-list'>";

$html .= '<li>Payment: ' . htmlspecialchars($doc['payment_terms'] ?? '') . '</li>';

$html .= '<li>Origin: ' . htmlspecialchars($doc['country_origin'] ?? 'Pakistan') . '</li>';

$html .= '<li>Loading: ' . htmlspecialchars($doc['port_loading'] ?? '') . ', Pakistan</li>';

$html .= '<li>Discharge: ' . htmlspecialchars($doc['port_discharge'] ?? $doc['country_destination'] ?? '') . '</li>';

if (!empty($doc['validity_date'])) {

    $html .= '<li>Validity: ' . format_date($doc['validity_date']) . '</li>';

}

$html .= '</ul></div>';

$html .= '</div>';



return $html;

