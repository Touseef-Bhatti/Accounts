<?php

/** @var array $set @var array $doc @var array $lines */

require_once __DIR__ . '/pdf_layout.php';



$company = htmlspecialchars(strtoupper($set['legal_name'] ?: $set['account_name']));

$phone = htmlspecialchars($set['phone'] ?? '');

$email = htmlspecialchars($set['company_email'] ?? '');

$ref = htmlspecialchars($doc['gate_pass_no'] ?? $set['reference_no'] ?? '');

$date = format_date($doc['gate_pass_date'] ?? date('Y-m-d'));



$container = htmlspecialchars($doc['container_no'] ?? '___________');

$cargo = htmlspecialchars($doc['cargo_description'] ?? 'the following cargo');

$destination = htmlspecialchars($doc['destination'] ?? '___________');

$vehicle = htmlspecialchars($doc['vehicle_no'] ?? '___________');

$driver = htmlspecialchars($doc['driver_name'] ?? '___________');

$nic = htmlspecialchars($doc['driver_nic'] ?? '___________');

$mobile = htmlspecialchars($doc['driver_mobile'] ?? '___________');



$html = pdf_styles();

$html .= "<div class='sample-doc gate-pass-doc'>";

$html .= "<p class='gp-company-name'>{$company}</p>";

$html .= "<p class='gp-tagline'>(MANUFACTURING OF ZINC METAL &amp; ASH PRODUCTS)</p>";

if ($phone) {

    $html .= "<p>TEL: {$phone}</p>";

}

if ($email) {

    $html .= "<p>EMAIL: {$email}</p>";

}

$html .= "<table width='100%'><tr><td><strong>REF #:</strong> {$ref}</td><td align='right'><strong>DATE:</strong> {$date}</td></tr></table>";

$html .= "<h1 class='sample-title'>GATE PASS</h1>";

$html .= "<p>This is to authorize the bearer to bring OUT the following item/s:</p>";



$html .= "<table class='items sample-items'><thead><tr>

<th>ITEMS DESCRIPTION</th><th>QUANTITY</th><th>UNIT</th><th>REMARKS</th>

</tr></thead><tbody>";

if ($lines) {

    foreach ($lines as $row) {

        $html .= '<tr><td>' . htmlspecialchars($row['description'] ?? '') . '</td>';

        $html .= '<td align="center">' . htmlspecialchars((string) ($row['quantity'] ?? '')) . '</td>';

        $html .= '<td align="center">' . htmlspecialchars($row['unit'] ?? '') . '</td>';

        $html .= '<td>' . htmlspecialchars($row['remarks'] ?? '') . '</td></tr>';

    }

} else {

    $html .= '<tr><td colspan="4">&nbsp;</td></tr>';

}

$html .= '</tbody></table>';



$html .= "<p class='gp-auth-text'>WE HEREBY CONFIRM THAT OUR CARGO INTO CONTAINER # {$container} CONTAINING {$cargo}.

AND IT IS ALSO IN OUR KNOWLEDGE THAT IS BEING EXPORTED TO {$destination} WITH OUR PERMISSION.

AND THIS CARGO IS MOVED THROUGH VEHICLE # {$vehicle} AND DRIVER NAME: {$driver} DRIVER NIC # {$nic} AND MOBILE # {$mobile}.

CUSTOM CLEARANCE UNDER SUPERVISION OF KARACHI OFFICE.</p>";



if (!empty($doc['authorization_note'])) {

    $html .= '<p>' . nl2br(htmlspecialchars($doc['authorization_note'])) . '</p>';

}



$html .= "<table width='100%' class='sign-row gp-signs'><tr>

<td><strong>SECURITY INCHARGE</strong><br>Sign:___________</td>

<td><strong>APPROVED BY</strong><br>Sign:___________</td>

<td><strong>RELEASED BY</strong><br>Sign:___________</td>

</tr></table>";

$html .= '</div>';



return $html;

