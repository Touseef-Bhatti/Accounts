<?php
require_once __DIR__ . '/pdf_layout.php';
$html = pdf_header($set, 'Packing List');
$html .= "<table class='meta-table'>
<tr><th>P/L No.</th><td>" . htmlspecialchars($doc['packing_list_no']) . "</td><th>Date</th><td>" . format_date($doc['packing_date']) . "</td></tr>
<tr><th>Invoice Ref</th><td>" . htmlspecialchars($doc['invoice_ref'] ?? '') . "</td><th>Container</th><td>" . htmlspecialchars($doc['container_no'] ?? '') . " / Seal: " . htmlspecialchars($doc['seal_no'] ?? '') . "</td></tr>
</table>";
$html .= "<table width='100%'><tr><td class='party-box' width='50%'><strong>Shipper</strong>" . nl2br(htmlspecialchars($doc['exporter_name'] . "\n" . $doc['exporter_address'])) . "</td>";
$html .= "<td class='party-box' width='50%'><strong>Consignee / Buyer</strong>" . nl2br(htmlspecialchars(($doc['consignee_name'] ?: $doc['buyer_name']) . "\n" . ($doc['consignee_address'] ?: $doc['buyer_address']))) . "</td></tr></table>";
if ($doc['shipping_marks']) {
    $html .= "<p><strong>Shipping Marks:</strong><br>" . nl2br(htmlspecialchars($doc['shipping_marks'])) . "</p>";
}
$html .= pdf_line_items_table($lines, true);
$html .= "<table class='meta-table'>
<tr><th>Total Packages</th><td>{$doc['total_packages']}</td><th>Total Gross KG</th><td>{$doc['total_gross_kg']}</td></tr>
<tr><th>Total Net KG</th><td>{$doc['total_net_kg']}</td><th>Total CBM</th><td>{$doc['total_cbm']}</td></tr>
</table>";
$html .= "<table class='sign-row'><tr><td>Prepared by</td><td>Checked by</td></tr></table>";
return $html;
