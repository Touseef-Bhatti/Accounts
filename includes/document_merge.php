<?php

declare(strict_types=1);

/** Fill empty commercial / packing / contract fields from proforma before save. */
function merge_document_payloads(array $pi, array $ci, array $pl, array $ec, string $referenceNo): array
{
    $pi = array_map(static fn($v) => is_string($v) ? trim($v) : $v, $pi);
    $ci = array_map(static fn($v) => is_string($v) ? trim($v) : $v, $ci);
    $pl = array_map(static fn($v) => is_string($v) ? trim($v) : $v, $pl);
    $ec = array_map(static fn($v) => is_string($v) ? trim($v) : $v, $ec);

    $ref = $pi['invoice_no'] ?? $referenceNo;
    $date = $pi['invoice_date'] ?? date('Y-m-d');

    $fill = static function (array $defaults, array $data): array {
        $out = $defaults;
        foreach ($data as $k => $v) {
            if ($v !== null && $v !== '') {
                $out[$k] = $v;
            }
        }
        return $out;
    };

    $ci = $fill([
        'invoice_no' => $ref,
        'invoice_date' => $date,
        'exporter_name' => $pi['exporter_name'] ?? '',
        'exporter_address' => $pi['exporter_address'] ?? '',
        'buyer_name' => $pi['buyer_name'] ?? '',
        'buyer_address' => $pi['buyer_address'] ?? '',
        'consignee_name' => $pi['consignee_name'] ?? '',
        'consignee_address' => $pi['consignee_address'] ?? '',
        'notify_party' => $pi['notify_party'] ?? '',
        'country_origin' => $pi['country_origin'] ?? 'Pakistan',
        'country_destination' => $pi['country_destination'] ?? '',
        'port_loading' => $pi['port_loading'] ?? '',
        'port_discharge' => $pi['port_discharge'] ?? '',
        'incoterms' => $pi['incoterms'] ?? 'FOB',
        'payment_terms' => $pi['payment_terms'] ?? '',
        'shipping_marks' => $pi['shipping_marks'] ?? '',
        'currency' => $pi['currency'] ?? 'USD',
        'subtotal' => $pi['subtotal'] ?? 0,
        'freight' => $pi['freight'] ?? 0,
        'insurance' => $pi['insurance'] ?? 0,
        'total' => $pi['total'] ?? 0,
        'bank_details' => $pi['bank_details'] ?? '',
        'lc_no' => '',
        'lc_date' => '',
    ], $ci);

    $pl = $fill([
        'packing_list_no' => $ref,
        'packing_date' => $date,
        'exporter_name' => $pi['exporter_name'] ?? '',
        'exporter_address' => $pi['exporter_address'] ?? '',
        'buyer_name' => $pi['buyer_name'] ?? '',
        'buyer_address' => $pi['buyer_address'] ?? '',
        'consignee_name' => $pi['consignee_name'] ?? '',
        'consignee_address' => $pi['consignee_address'] ?? '',
        'invoice_ref' => $ref,
        'shipping_marks' => $pi['shipping_marks'] ?? '',
        'total_packages' => 0,
        'total_gross_kg' => 0,
        'total_net_kg' => 0,
        'total_cbm' => 0,
    ], $pl);

    $productDesc = $ec['product_description'] ?? '';
    if ($productDesc === '' && !empty($pi)) {
        $productDesc = 'Goods as per Proforma Invoice No. ' . $ref;
    }

    $ec = $fill([
        'contract_no' => $ref,
        'contract_date' => $date,
        'seller_name' => $pi['exporter_name'] ?? '',
        'seller_address' => $pi['exporter_address'] ?? '',
        'buyer_name' => $pi['buyer_name'] ?? '',
        'buyer_address' => $pi['buyer_address'] ?? '',
        'product_description' => $productDesc,
        'currency' => $pi['currency'] ?? 'USD',
        'payment_terms' => $pi['payment_terms'] ?? '',
        'port_loading' => $pi['port_loading'] ?? '',
        'port_discharge' => $pi['port_discharge'] ?? '',
        'total_value' => (string) ($pi['total'] ?? ''),
        'quantity' => '',
        'unit_price' => '',
        'delivery_terms' => 'As per Incoterms 2020',
        'shipment_period' => '',
        'inspection_terms' => 'SGS or mutually agreed inspection at port of loading.',
        'force_majeure' => 'Neither party shall be liable for failure due to acts of God, war, strikes, or government restrictions.',
        'arbitration' => 'Any dispute shall be settled amicably; failing which, courts at Lahore, Pakistan shall have jurisdiction.',
        'governing_law' => 'Laws of Pakistan',
    ], $ec);

    if (($ec['product_description'] ?? '') === '') {
        $ec['product_description'] = 'Goods as per Proforma Invoice No. ' . $ref;
    }

    return [$pi, $ci, $pl, $ec];
}
