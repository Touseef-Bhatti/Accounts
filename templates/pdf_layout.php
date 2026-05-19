<?php
/** Shared PDF styles — professional export document layout */
function pdf_styles(): string
{
    return <<<'CSS'
<style>
@page { margin: 12mm 10mm; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; line-height: 1.35; }
.doc-title { text-align: center; font-size: 16px; font-weight: bold; text-transform: uppercase; margin: 8px 0 12px; border-bottom: 2px solid #1a365d; padding-bottom: 4px; color: #1a365d; }
.header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.header-table td { vertical-align: top; }
.company-block { font-size: 11px; }
.meta-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.meta-table td, .meta-table th { border: 1px solid #ccc; padding: 4px 6px; font-size: 9px; }
.meta-table th { background: #edf2f7; text-align: left; width: 22%; }
.party-box { border: 1px solid #ccc; padding: 6px; margin-bottom: 8px; }
.party-box strong { display: block; margin-bottom: 4px; color: #1a365d; }
.items { width: 100%; border-collapse: collapse; margin: 10px 0; }
.items th { background: #1a365d; color: #fff; padding: 5px; font-size: 9px; }
.items td { border: 1px solid #ccc; padding: 4px 5px; font-size: 9px; }
.totals { width: 45%; margin-left: auto; border-collapse: collapse; }
.totals td { padding: 4px 6px; border: 1px solid #ccc; }
.totals .label { font-weight: bold; background: #f7fafc; }
.footer-note { margin-top: 12px; font-size: 9px; }
.sign-row { margin-top: 30px; width: 100%; }
.sign-row td { width: 50%; padding-top: 40px; border-top: 1px solid #333; text-align: center; font-size: 9px; }
.text-pre { white-space: pre-wrap; }
.sample-doc { font-size: 10px; }
.for-company { font-size: 11px; font-weight: bold; margin: 0 0 4px; }
.sample-title { text-align: center; font-size: 16px; font-weight: bold; text-transform: uppercase; margin: 10px 0 14px; letter-spacing: 0.5px; }
.sample-split td { padding: 6px 8px; vertical-align: top; font-size: 10px; }
.sample-block { margin: 10px 0; padding: 6px 0; }
.sample-items th { background: #333; color: #fff; text-align: center; font-size: 9px; }
.sample-items td { font-size: 9px; }
.grand-row td { border-top: 2px solid #333; }
.amount-words { margin: 12px 0; font-size: 10px; }
.terms-list { margin: 6px 0 0 18px; padding: 0; }
.terms-list li { margin-bottom: 4px; }
.ci-headline, .ci-route { text-align: center; margin: 4px 0; }
.bank-table th { width: 30%; }
.sign-line { margin-top: 24px; }
.gp-company-name { text-align: center; font-size: 14px; font-weight: bold; margin: 0; }
.gp-tagline { text-align: center; font-size: 9px; margin: 0 0 8px; }
.gp-auth-text { text-align: justify; line-height: 1.5; margin: 12px 0; font-size: 9px; }
.gp-signs td { width: 33%; vertical-align: top; padding-top: 20px; font-size: 9px; border: none; }
.doc-type-card.active { background: #edf2f7; color: var(--brand); }
.pdf-preview-frame { width: 100%; min-height: 75vh; border: 1px solid #dee2e6; border-radius: 0.375rem; background: #fff; }
</style>
CSS;
}

function pdf_header(array $set, string $docTitle): string
{
    $styles = '';
    if (empty($GLOBALS['pdf_skip_styles'])) {
        $styles = pdf_styles();
        $GLOBALS['pdf_skip_styles'] = true;
    }

    $logo = \App\PdfGenerator::logoHtml($set['logo_path'] ?? null, $set['account_name'] ?? '');
    $company = $set['legal_name'] ?: $set['account_name'];
    $addr = nl2br(htmlspecialchars(trim(($set['company_address'] ?? '') . "\n" . ($set['city'] ?? '') . ', ' . ($set['country'] ?? ''))));
    $contact = htmlspecialchars(trim(($set['phone'] ?? '') . ' | ' . ($set['company_email'] ?? '')));
    $ntn = $set['ntn'] ? 'NTN: ' . htmlspecialchars($set['ntn']) : '';

    return $styles . "
    <table class='header-table'>
        <tr>
            <td width='25%'>{$logo}</td>
            <td width='50%' class='company-block'>
                <strong style='font-size:13px'>{$company}</strong><br>
                {$addr}<br>
                {$contact}<br>
                <small>{$ntn}</small>
            </td>
            <td width='25%' style='text-align:right;font-size:9px'>
                <strong>Ref:</strong> " . htmlspecialchars($set['reference_no'] ?? '') . "<br>
                <strong>Date:</strong> " . date('d-M-Y') . "
            </td>
        </tr>
    </table>
    <div class='doc-title'>{$docTitle}</div>";
}

function pdf_line_items_table(array $lines, bool $packing = false): string
{
    if ($packing) {
        $html = "<table class='items'><thead><tr><th>#</th><th>Description</th><th>Pkgs</th><th>Gross KG</th><th>Net KG</th><th>Dimensions</th></tr></thead><tbody>";
        foreach ($lines as $i => $row) {
            $html .= '<tr><td>' . ($i + 1) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['description'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($row['packages'] ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($row['gross_kg'] ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($row['net_kg'] ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['dimensions'] ?? '') . '</td></tr>';
        }
    } else {
        $html = "<table class='items'><thead><tr><th>#</th><th>Description</th><th>HS Code</th><th>Qty</th><th>Unit</th><th>Unit Price</th><th>Amount</th></tr></thead><tbody>";
        foreach ($lines as $i => $row) {
            $html .= '<tr><td>' . ($i + 1) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['description'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['hs_code'] ?? '') . '</td>';
            $html .= '<td>' . number_format((float) ($row['quantity'] ?? 0), 3) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['unit'] ?? '') . '</td>';
            $html .= '<td>' . number_format((float) ($row['unit_price'] ?? 0), 4) . '</td>';
            $html .= '<td>' . number_format((float) ($row['amount'] ?? 0), 2) . '</td></tr>';
        }
    }
    return $html . '</tbody></table>';
}
