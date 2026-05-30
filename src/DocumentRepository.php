<?php



declare(strict_types=1);



namespace App;



use PDO;



class DocumentRepository

{

    public const DOC_TYPES = ['proforma', 'commercial', 'packing', 'contract', 'gate_pass'];



    public static function isValidDocType(string $type): bool

    {

        return in_array($type, self::DOC_TYPES, true);

    }



    public static function docTypeLabel(string $type): string

    {

        return match ($type) {

            'proforma' => 'Proforma Invoice',

            'commercial' => 'Commercial Invoice',

            'packing' => 'Packing List',

            'contract' => 'Export Contract',

            'gate_pass' => 'Gate Pass',

            default => ucfirst(str_replace('_', ' ', $type)),

        };

    }



    public static function createSet(int $accountId, string $referenceNo, string $email, string $docType = 'proforma'): int

    {

        if (!self::isValidDocType($docType)) {

            $docType = 'proforma';

        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare(

            'INSERT INTO document_sets (account_id, reference_no, doc_type, created_by_email, status)

             VALUES (?, ?, ?, ?, ?)'

        );

        $stmt->execute([$accountId, $referenceNo, $docType, $email, 'draft']);

        return (int) $pdo->lastInsertId();

    }



    public static function setDocType(int $setId, string $docType): void

    {

        if (!self::isValidDocType($docType)) {

            return;

        }

        $pdo = Database::connection();

        $pdo->prepare('UPDATE document_sets SET doc_type = ? WHERE id = ?')->execute([$docType, $setId]);

    }



    public static function findSetIdByReference(int $accountId, string $referenceNo): ?int

    {

        $pdo = Database::connection();

        $stmt = $pdo->prepare(

            'SELECT id FROM document_sets WHERE account_id = ? AND reference_no = ? LIMIT 1'

        );

        $stmt->execute([$accountId, $referenceNo]);

        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;

    }



    /** Reuse existing set by id or reference — never duplicate on resubmit/refresh. */

    public static function resolveSetId(int $accountId, string $referenceNo, int $setId, string $email, string $docType = 'proforma'): int

    {

        if ($setId > 0) {

            $set = self::getSet($setId);

            if ($set && (int) $set['account_id'] === $accountId) {

                self::setDocType($setId, $docType);

                return $setId;

            }

        }



        $existing = self::findSetIdByReference($accountId, $referenceNo);

        if ($existing !== null) {

            self::setDocType($existing, $docType);

            return $existing;

        }



        return self::createSet($accountId, $referenceNo, $email, $docType);

    }



    public static function getSet(int $id): ?array

    {

        $pdo = Database::connection();

        $stmt = $pdo->prepare(

            'SELECT ds.*, a.name AS account_name, a.slug AS account_slug, a.logo_path, a.legal_name,

                    a.address AS company_address, a.city, a.country, a.phone, a.email AS company_email,

                    a.ntn, a.strn, a.bank_name, a.bank_branch, a.bank_account, a.bank_iban, a.bank_swift,

                    a.default_currency

             FROM document_sets ds

             JOIN accounts a ON a.id = ds.account_id

             WHERE ds.id = ?'

        );

        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;

    }


    public static function listByAccount(
        int $accountId,
        ?string $type = null,
        ?string $startDate = null,
        ?string $endDate = null,
        string $sortBy = 'created_at',
        string $sortOrder = 'DESC',
        int $limit = 100
    ): array {
        $pdo = Database::connection();

        $sql = 'SELECT id, reference_no, doc_type, status, created_at FROM document_sets WHERE account_id = ?';
        $params = [$accountId];

        if ($type !== null && $type !== '') {
            $sql .= ' AND doc_type = ?';
            $params[] = $type;
        }

        if ($startDate !== null && $startDate !== '') {
            $sql .= ' AND DATE(created_at) >= ?';
            $params[] = $startDate;
        }

        if ($endDate !== null && $endDate !== '') {
            $sql .= ' AND DATE(created_at) <= ?';
            $params[] = $endDate;
        }

        $allowedSortCols = ['created_at', 'doc_type', 'reference_no'];
        if (!in_array($sortBy, $allowedSortCols, true)) {
            $sortBy = 'created_at';
        }

        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql .= " ORDER BY {$sortBy} {$sortOrder} LIMIT ?";

        $stmt = $pdo->prepare($sql);

        $paramIndex = 1;
        foreach ($params as $val) {
            $stmt->bindValue($paramIndex++, $val);
        }
        $stmt->bindValue($paramIndex, $limit, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }



    public static function markCompleted(int $setId): void

    {

        $pdo = Database::connection();

        $pdo->prepare('UPDATE document_sets SET status = ? WHERE id = ?')->execute(['completed', $setId]);

    }



    public static function inferDocType(array $data): string

    {

        $stored = $data['set']['doc_type'] ?? '';

        if ($stored !== '' && self::isValidDocType($stored)) {

            return $stored;

        }

        foreach (['proforma', 'commercial', 'packing', 'contract', 'gate_pass'] as $type) {

            $key = $type === 'gate_pass' ? 'gate_pass' : $type;

            if (!empty($data[$key])) {

                return $type;

            }

        }

        return 'proforma';

    }



    /**

     * @param array<string, mixed> $doc

     * @param list<array<string, mixed>> $lineItems

     */

    public static function saveForType(int $setId, string $docType, array $doc, array $lineItems): void

    {

        if (!self::isValidDocType($docType)) {

            throw new \InvalidArgumentException('Invalid document type.');

        }

        self::setDocType($setId, $docType);



        match ($docType) {

            'proforma' => self::saveProforma($setId, $doc),

            'commercial' => self::saveCommercial($setId, $doc),

            'packing' => self::savePacking($setId, $doc),

            'contract' => self::saveContract($setId, $doc),

            'gate_pass' => self::saveGatePass($setId, $doc),

        };



        if ($docType !== 'contract') {

            self::saveLineItems($setId, $docType, $lineItems);

        }

    }



    public static function saveProforma(int $setId, array $d): void

    {

        $pdo = Database::connection();

        $pdo->prepare('DELETE FROM proforma_invoices WHERE document_set_id = ?')->execute([$setId]);

        $pdo->prepare(

            'INSERT INTO proforma_invoices (

                document_set_id, invoice_no, invoice_date, validity_date,

                exporter_name, exporter_address, buyer_name, buyer_address,

                consignee_name, consignee_address, notify_party,

                country_origin, country_destination, port_loading, port_discharge,

                incoterms, payment_terms, shipping_marks, currency,

                subtotal, freight, insurance, total, bank_details, remarks

            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'

        )->execute([

            $setId,

            $d['invoice_no'] ?? '',

            $d['invoice_date'] ?? date('Y-m-d'),

            self::emptyDate($d['validity_date'] ?? null),

            $d['exporter_name'] ?? '',

            $d['exporter_address'] ?? '',

            $d['buyer_name'] ?? '',

            $d['buyer_address'] ?? '',

            $d['consignee_name'] ?? null,

            $d['consignee_address'] ?? null,

            $d['notify_party'] ?? null,

            $d['country_origin'] ?? 'Pakistan',

            $d['country_destination'] ?? '',

            $d['port_loading'] ?? '',

            $d['port_discharge'] ?? '',

            $d['incoterms'] ?? 'FOB',

            $d['payment_terms'] ?? '',

            $d['shipping_marks'] ?? '',

            $d['currency'] ?? 'USD',

            $d['subtotal'] ?? 0,

            $d['freight'] ?? 0,

            $d['insurance'] ?? 0,

            $d['total'] ?? 0,

            $d['bank_details'] ?? '',

            $d['remarks'] ?? null,

        ]);

    }



    public static function saveCommercial(int $setId, array $d): void

    {

        $pdo = Database::connection();

        $pdo->prepare('DELETE FROM commercial_invoices WHERE document_set_id = ?')->execute([$setId]);

        $pdo->prepare(

            'INSERT INTO commercial_invoices (

                document_set_id, invoice_no, invoice_date, lc_no, lc_date,

                exporter_name, exporter_address, buyer_name, buyer_address,

                consignee_name, consignee_address, notify_party,

                country_origin, country_destination, port_loading, port_discharge,

                vessel_flight, bl_awb_no, incoterms, payment_terms, shipping_marks, currency,

                subtotal, freight, insurance, total, bank_details, remarks

            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'

        )->execute([

            $setId,

            $d['invoice_no'] ?? '',

            $d['invoice_date'] ?? date('Y-m-d'),

            $d['lc_no'] ?? null,

            self::emptyDate($d['lc_date'] ?? null),

            $d['exporter_name'] ?? '',

            $d['exporter_address'] ?? '',

            $d['buyer_name'] ?? '',

            $d['buyer_address'] ?? '',

            $d['consignee_name'] ?? null,

            $d['consignee_address'] ?? null,

            $d['notify_party'] ?? null,

            $d['country_origin'] ?? 'Pakistan',

            $d['country_destination'] ?? '',

            $d['port_loading'] ?? '',

            $d['port_discharge'] ?? '',

            $d['vessel_flight'] ?? null,

            $d['bl_awb_no'] ?? null,

            $d['incoterms'] ?? 'FOB',

            $d['payment_terms'] ?? '',

            $d['shipping_marks'] ?? '',

            $d['currency'] ?? 'USD',

            $d['subtotal'] ?? 0,

            $d['freight'] ?? 0,

            $d['insurance'] ?? 0,

            $d['total'] ?? 0,

            $d['bank_details'] ?? '',

            $d['remarks'] ?? null,

        ]);

    }



    public static function savePacking(int $setId, array $d): void

    {

        $pdo = Database::connection();

        $pdo->prepare('DELETE FROM packing_lists WHERE document_set_id = ?')->execute([$setId]);

        $pdo->prepare(

            'INSERT INTO packing_lists (

                document_set_id, packing_list_no, packing_date,

                exporter_name, exporter_address, buyer_name, buyer_address,

                consignee_name, consignee_address, invoice_ref,

                container_no, seal_no, shipping_marks,

                total_packages, total_gross_kg, total_net_kg, total_cbm, remarks

            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'

        )->execute([

            $setId,

            $d['packing_list_no'] ?? '',

            $d['packing_date'] ?? date('Y-m-d'),

            $d['exporter_name'] ?? '',

            $d['exporter_address'] ?? '',

            $d['buyer_name'] ?? '',

            $d['buyer_address'] ?? '',

            $d['consignee_name'] ?? null,

            $d['consignee_address'] ?? null,

            $d['invoice_ref'] ?? null,

            $d['container_no'] ?? null,

            $d['seal_no'] ?? null,

            $d['shipping_marks'] ?? '',

            $d['total_packages'] ?? 0,

            $d['total_gross_kg'] ?? 0,

            $d['total_net_kg'] ?? 0,

            $d['total_cbm'] ?? 0,

            $d['remarks'] ?? null,

        ]);

    }



    public static function saveContract(int $setId, array $d): void

    {

        $pdo = Database::connection();

        $pdo->prepare('DELETE FROM export_contracts WHERE document_set_id = ?')->execute([$setId]);

        $pdo->prepare(

            'INSERT INTO export_contracts (

                document_set_id, contract_no, contract_date,

                seller_name, seller_address, buyer_name, buyer_address,

                product_description, quantity, unit_price, total_value, currency,

                delivery_terms, payment_terms, port_loading, port_discharge,

                shipment_period, inspection_terms, force_majeure, arbitration,

                governing_law, special_conditions

            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'

        )->execute([

            $setId,

            $d['contract_no'] ?? '',

            $d['contract_date'] ?? date('Y-m-d'),

            $d['seller_name'] ?? '',

            $d['seller_address'] ?? '',

            $d['buyer_name'] ?? '',

            $d['buyer_address'] ?? '',

            $d['product_description'] ?? '',

            $d['quantity'] ?? '',

            $d['unit_price'] ?? '',

            $d['total_value'] ?? '',

            $d['currency'] ?? 'USD',

            $d['delivery_terms'] ?? '',

            $d['payment_terms'] ?? '',

            $d['port_loading'] ?? '',

            $d['port_discharge'] ?? '',

            $d['shipment_period'] ?? '',

            $d['inspection_terms'] ?? '',

            $d['force_majeure'] ?? '',

            $d['arbitration'] ?? '',

            $d['governing_law'] ?? 'Laws of Pakistan',

            $d['special_conditions'] ?? null,

        ]);

    }



    public static function saveGatePass(int $setId, array $d): void

    {

        $pdo = Database::connection();

        $pdo->prepare('DELETE FROM gate_passes WHERE document_set_id = ?')->execute([$setId]);

        $pdo->prepare(

            'INSERT INTO gate_passes (

                document_set_id, gate_pass_no, gate_pass_date,

                container_no, cargo_description, destination,

                vehicle_no, driver_name, driver_nic, driver_mobile, authorization_note

            ) VALUES (?,?,?,?,?,?,?,?,?,?,?)'

        )->execute([

            $setId,

            $d['gate_pass_no'] ?? '',

            $d['gate_pass_date'] ?? date('Y-m-d'),

            $d['container_no'] ?? null,

            $d['cargo_description'] ?? null,

            $d['destination'] ?? null,

            $d['vehicle_no'] ?? null,

            $d['driver_name'] ?? null,

            $d['driver_nic'] ?? null,

            $d['driver_mobile'] ?? null,

            $d['authorization_note'] ?? null,

        ]);

    }



    public static function saveLineItems(int $setId, string $docType, array $items): void
    {

        $pdo = Database::connection();

        $pdo->prepare('DELETE FROM line_items WHERE document_set_id = ? AND doc_type = ?')

            ->execute([$setId, $docType]);


        $hasRemarks = self::lineItemsHaveRemarksColumn($pdo);


        if ($hasRemarks) {

            $stmt = $pdo->prepare(

                'INSERT INTO line_items (

                    document_set_id, doc_type, sort_order, description, hs_code,

                    quantity, unit, unit_price, amount, packages, gross_kg, net_kg, dimensions, remarks

                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'

            );

        } else {

            $stmt = $pdo->prepare(

                'INSERT INTO line_items (

                    document_set_id, doc_type, sort_order, description, hs_code,

                    quantity, unit, unit_price, amount, packages, gross_kg, net_kg, dimensions

                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'

            );

        }


        foreach ($items as $i => $item) {

            if (trim((string) ($item['description'] ?? '')) === '') {

                continue;

            }

            // Normalize numeric fields (like quantity, unit_price, amount)
            $quantity = isset($item['quantity']) ? (float) str_replace(',', '', (string) $item['quantity']) : 0;
            $unitPrice = isset($item['unit_price']) ? (float) str_replace(',', '', (string) $item['unit_price']) : 0;
            $amount = isset($item['amount']) ? (float) str_replace(',', '', (string) $item['amount']) : 0;
            
            $params = [

                $setId, $docType, $i,

                $item['description'], $item['hs_code'] ?? null,

                $quantity, $item['unit'] ?? 'MT', $unitPrice, $amount,

                $item['packages'] ?? null, $item['gross_kg'] ?? null, $item['net_kg'] ?? null,

                $item['dimensions'] ?? null,

            ];

            if ($hasRemarks) {

                $params[] = $item['remarks'] ?? null;

            }

            $stmt->execute($params);

        }

    }



    private static function lineItemsHaveRemarksColumn(PDO $pdo): bool

    {

        static $cached = null;

        if ($cached !== null) {

            return $cached;

        }

        try {

            $cached = (bool) $pdo->query("SHOW COLUMNS FROM line_items LIKE 'remarks'")->fetch();

        } catch (\PDOException) {

            $cached = false;

        }

        return $cached;

    }



    public static function getProforma(int $setId): ?array

    {

        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM proforma_invoices WHERE document_set_id = ?');

        $stmt->execute([$setId]);

        return $stmt->fetch() ?: null;

    }



    public static function getCommercial(int $setId): ?array

    {

        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM commercial_invoices WHERE document_set_id = ?');

        $stmt->execute([$setId]);

        return $stmt->fetch() ?: null;

    }



    public static function getPacking(int $setId): ?array

    {

        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM packing_lists WHERE document_set_id = ?');

        $stmt->execute([$setId]);

        return $stmt->fetch() ?: null;

    }



    public static function getContract(int $setId): ?array

    {

        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM export_contracts WHERE document_set_id = ?');

        $stmt->execute([$setId]);

        return $stmt->fetch() ?: null;

    }



    public static function getGatePass(int $setId): ?array

    {

        $pdo = Database::connection();

        try {

            $stmt = $pdo->prepare('SELECT * FROM gate_passes WHERE document_set_id = ?');

            $stmt->execute([$setId]);

            return $stmt->fetch() ?: null;

        } catch (\PDOException) {

            return null;

        }

    }



    public static function getLineItems(int $setId, string $docType): array

    {

        $pdo = Database::connection();

        $stmt = $pdo->prepare(

            'SELECT * FROM line_items WHERE document_set_id = ? AND doc_type = ? ORDER BY sort_order'

        );

        $stmt->execute([$setId, $docType]);

        return $stmt->fetchAll();

    }



    public static function loadFull(int $setId): array

    {

        $set = self::getSet($setId);

        if (!$set) {

            return [];

        }

        return [

            'set' => $set,

            'proforma' => self::getProforma($setId),

            'commercial' => self::getCommercial($setId),

            'packing' => self::getPacking($setId),

            'contract' => self::getContract($setId),

            'gate_pass' => self::getGatePass($setId),

            'lines_proforma' => self::getLineItems($setId, 'proforma'),

            'lines_commercial' => self::getLineItems($setId, 'commercial'),

            'lines_packing' => self::getLineItems($setId, 'packing'),

            'lines_gate_pass' => self::getLineItems($setId, 'gate_pass'),

        ];

    }



    private static function emptyDate(mixed $value): ?string

    {

        $value = is_string($value) ? trim($value) : $value;

        return ($value === null || $value === '') ? null : (string) $value;

    }

}

