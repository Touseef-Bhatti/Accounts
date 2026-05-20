<?php
/**
 * Database installer — run once after uploading or starting Docker.
 * Visit: https://yourdomain.com/install.php
 * DELETE or rename this file after successful install on production.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (env('APP_ENV') === 'production' && !filter_var(env('APP_ALLOW_INSTALL', false), FILTER_VALIDATE_BOOLEAN)) {
    http_response_code(403);
    die('Installer is disabled in production. Schema updates run automatically on each request.');
}

header('Content-Type: text/html; charset=utf-8');

$messages = [];
$errors = [];

function runSql(PDO $pdo, string $sql, string $label): void
{
    global $messages, $errors;
    try {
        $pdo->exec($sql);
        $messages[] = "OK: {$label}";
    } catch (PDOException $e) {
        $errors[] = "FAIL {$label}: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || php_sapi_name() === 'cli') {
    try {
        $pdo = App\Database::connection();
    } catch (Throwable $e) {
        $errors[] = 'Database connection failed: ' . $e->getMessage();
        $pdo = null;
    }

    if ($pdo) {
        runSql($pdo, <<<'SQL'
CREATE TABLE IF NOT EXISTS accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(64) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    legal_name VARCHAR(255) DEFAULT NULL,
    address TEXT,
    city VARCHAR(128) DEFAULT NULL,
    country VARCHAR(128) DEFAULT 'Pakistan',
    phone VARCHAR(64) DEFAULT NULL,
    email VARCHAR(128) DEFAULT NULL,
    ntn VARCHAR(64) DEFAULT NULL,
    strn VARCHAR(64) DEFAULT NULL,
    logo_path VARCHAR(512) DEFAULT NULL,
    bank_name VARCHAR(255) DEFAULT NULL,
    bank_branch VARCHAR(255) DEFAULT NULL,
    bank_account VARCHAR(128) DEFAULT NULL,
    bank_iban VARCHAR(64) DEFAULT NULL,
    bank_swift VARCHAR(32) DEFAULT NULL,
    default_currency VARCHAR(8) DEFAULT 'USD',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL, 'accounts');

        runSql($pdo, <<<'SQL'
CREATE TABLE IF NOT EXISTS authorized_emails (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL, 'authorized_emails');

        runSql($pdo, <<<'SQL'
CREATE TABLE IF NOT EXISTS otp_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_otp_email (email),
    INDEX idx_otp_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL, 'otp_codes');

        runSql($pdo, <<<'SQL'
CREATE TABLE IF NOT EXISTS document_sets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id INT UNSIGNED NOT NULL,
    reference_no VARCHAR(64) NOT NULL,
    doc_type ENUM('proforma','commercial','packing','contract','gate_pass') DEFAULT 'proforma',
    status ENUM('draft','completed') DEFAULT 'draft',
    created_by_email VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_ref_account (account_id, reference_no),
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL, 'document_sets');

        runSql($pdo, <<<'SQL'
CREATE TABLE IF NOT EXISTS proforma_invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_set_id INT UNSIGNED NOT NULL UNIQUE,
    invoice_no VARCHAR(64) NOT NULL,
    invoice_date DATE NOT NULL,
    validity_date DATE DEFAULT NULL,
    exporter_name VARCHAR(255) NOT NULL,
    exporter_address TEXT,
    buyer_name VARCHAR(255) NOT NULL,
    buyer_address TEXT,
    consignee_name VARCHAR(255) DEFAULT NULL,
    consignee_address TEXT,
    notify_party TEXT,
    country_origin VARCHAR(128) DEFAULT 'Pakistan',
    country_destination VARCHAR(128) DEFAULT NULL,
    port_loading VARCHAR(128) DEFAULT NULL,
    port_discharge VARCHAR(128) DEFAULT NULL,
    incoterms VARCHAR(32) DEFAULT 'FOB',
    payment_terms TEXT,
    shipping_marks TEXT,
    currency VARCHAR(8) DEFAULT 'USD',
    subtotal DECIMAL(15,2) DEFAULT 0,
    freight DECIMAL(15,2) DEFAULT 0,
    insurance DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) DEFAULT 0,
    bank_details TEXT,
    remarks TEXT,
    FOREIGN KEY (document_set_id) REFERENCES document_sets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL, 'proforma_invoices');

        runSql($pdo, <<<'SQL'
CREATE TABLE IF NOT EXISTS commercial_invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_set_id INT UNSIGNED NOT NULL UNIQUE,
    invoice_no VARCHAR(64) NOT NULL,
    invoice_date DATE NOT NULL,
    lc_no VARCHAR(64) DEFAULT NULL,
    lc_date DATE DEFAULT NULL,
    exporter_name VARCHAR(255) NOT NULL,
    exporter_address TEXT,
    buyer_name VARCHAR(255) NOT NULL,
    buyer_address TEXT,
    consignee_name VARCHAR(255) DEFAULT NULL,
    consignee_address TEXT,
    notify_party TEXT,
    country_origin VARCHAR(128) DEFAULT 'Pakistan',
    country_destination VARCHAR(128) DEFAULT NULL,
    port_loading VARCHAR(128) DEFAULT NULL,
    port_discharge VARCHAR(128) DEFAULT NULL,
    vessel_flight VARCHAR(128) DEFAULT NULL,
    bl_awb_no VARCHAR(128) DEFAULT NULL,
    incoterms VARCHAR(32) DEFAULT 'FOB',
    payment_terms TEXT,
    shipping_marks TEXT,
    currency VARCHAR(8) DEFAULT 'USD',
    subtotal DECIMAL(15,2) DEFAULT 0,
    freight DECIMAL(15,2) DEFAULT 0,
    insurance DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) DEFAULT 0,
    bank_details TEXT,
    remarks TEXT,
    FOREIGN KEY (document_set_id) REFERENCES document_sets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL, 'commercial_invoices');

        runSql($pdo, <<<'SQL'
CREATE TABLE IF NOT EXISTS packing_lists (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_set_id INT UNSIGNED NOT NULL UNIQUE,
    packing_list_no VARCHAR(64) NOT NULL,
    packing_date DATE NOT NULL,
    exporter_name VARCHAR(255) NOT NULL,
    exporter_address TEXT,
    buyer_name VARCHAR(255) NOT NULL,
    buyer_address TEXT,
    consignee_name VARCHAR(255) DEFAULT NULL,
    consignee_address TEXT,
    invoice_ref VARCHAR(64) DEFAULT NULL,
    container_no VARCHAR(64) DEFAULT NULL,
    seal_no VARCHAR(64) DEFAULT NULL,
    shipping_marks TEXT,
    total_packages INT DEFAULT 0,
    total_gross_kg DECIMAL(12,3) DEFAULT 0,
    total_net_kg DECIMAL(12,3) DEFAULT 0,
    total_cbm DECIMAL(12,3) DEFAULT 0,
    remarks TEXT,
    FOREIGN KEY (document_set_id) REFERENCES document_sets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL, 'packing_lists');

        runSql($pdo, <<<'SQL'
CREATE TABLE IF NOT EXISTS export_contracts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_set_id INT UNSIGNED NOT NULL UNIQUE,
    contract_no VARCHAR(64) NOT NULL,
    contract_date DATE NOT NULL,
    seller_name VARCHAR(255) NOT NULL,
    seller_address TEXT,
    buyer_name VARCHAR(255) NOT NULL,
    buyer_address TEXT,
    product_description TEXT,
    quantity VARCHAR(128) DEFAULT NULL,
    unit_price VARCHAR(128) DEFAULT NULL,
    total_value VARCHAR(128) DEFAULT NULL,
    currency VARCHAR(8) DEFAULT 'USD',
    delivery_terms TEXT,
    payment_terms TEXT,
    port_loading VARCHAR(128) DEFAULT NULL,
    port_discharge VARCHAR(128) DEFAULT NULL,
    shipment_period VARCHAR(128) DEFAULT NULL,
    inspection_terms TEXT,
    force_majeure TEXT,
    arbitration TEXT,
    governing_law VARCHAR(128) DEFAULT 'Laws of Pakistan',
    special_conditions TEXT,
    FOREIGN KEY (document_set_id) REFERENCES document_sets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL, 'export_contracts');

        runSql($pdo, <<<'SQL'
CREATE TABLE IF NOT EXISTS line_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_set_id INT UNSIGNED NOT NULL,
    doc_type ENUM('proforma','commercial','packing','gate_pass') NOT NULL,
    sort_order INT DEFAULT 0,
    description TEXT NOT NULL,
    hs_code VARCHAR(32) DEFAULT NULL,
    quantity DECIMAL(15,3) DEFAULT 0,
    unit VARCHAR(32) DEFAULT 'MT',
    unit_price DECIMAL(15,4) DEFAULT 0,
    amount DECIMAL(15,2) DEFAULT 0,
    packages INT DEFAULT NULL,
    gross_kg DECIMAL(12,3) DEFAULT NULL,
    net_kg DECIMAL(12,3) DEFAULT NULL,
    dimensions VARCHAR(128) DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    FOREIGN KEY (document_set_id) REFERENCES document_sets(id) ON DELETE CASCADE,
    INDEX idx_line_doc (document_set_id, doc_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL, 'line_items');

        runSql($pdo, <<<'SQL'
CREATE TABLE IF NOT EXISTS gate_passes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_set_id INT UNSIGNED NOT NULL UNIQUE,
    gate_pass_no VARCHAR(64) NOT NULL,
    gate_pass_date DATE NOT NULL,
    container_no VARCHAR(128) DEFAULT NULL,
    cargo_description TEXT,
    destination VARCHAR(255) DEFAULT NULL,
    vehicle_no VARCHAR(128) DEFAULT NULL,
    driver_name VARCHAR(255) DEFAULT NULL,
    driver_nic VARCHAR(64) DEFAULT NULL,
    driver_mobile VARCHAR(64) DEFAULT NULL,
    authorization_note TEXT,
    FOREIGN KEY (document_set_id) REFERENCES document_sets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL, 'gate_passes');

        // Migrations for existing installations (same logic as App\SchemaMigrator)
        try {
            App\SchemaMigrator::run();
            $messages[] = 'OK: Schema migrations applied (doc_type, gate_passes, line_items)';
        } catch (Throwable $e) {
            $errors[] = 'Schema migration: ' . $e->getMessage();
        }

        runSql($pdo, 'DROP TABLE IF EXISTS field_suggestions', 'drop field_suggestions');

        $suggestionFields = [
            'pi_invoice_no', 'ci_invoice_no', 'contract_no', 'lc_no', 'exporter_name', 
            'exporter_address', 'buyer_name', 'buyer_address', 'consignee_name', 
            'consignee_address', 'notify_party', 'country_origin', 'country_destination', 
            'port_loading', 'port_discharge', 'vessel_flight', 'bl_awb_no', 'incoterms', 
            'payment_terms', 'shipping_marks', 'bank_details', 'remarks', 'invoice_ref', 
            'container_no', 'seal_no', 'seller_name', 'seller_address', 'product_description', 
            'shipment_period', 'governing_law', 'inspection_terms', 'force_majeure', 
            'arbitration', 'special_conditions', 'cargo_description', 'vehicle_no', 
            'driver_name', 'driver_nic', 'driver_mobile', 'authorization_note', 
            'line_description', 'line_hs_code', 'line_remarks'
        ];

        foreach ($suggestionFields as $field) {
            $tableName = 'suggest_' . $field;
            runSql($pdo, "CREATE TABLE IF NOT EXISTS {$tableName} (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                account_id INT UNSIGNED NOT NULL,
                field_value TEXT NOT NULL,
                use_count INT UNSIGNED DEFAULT 1,
                last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_suggestion (account_id, field_value(191)),
                FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", "table: {$tableName}");
        }

        // Seed accounts
        $stmt = $pdo->query("SELECT COUNT(*) FROM accounts");
        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->exec(<<<'SQL'
INSERT INTO accounts (slug, name, legal_name, address, city, country, phone, email, ntn, default_currency, bank_name, bank_branch, bank_account, bank_iban, bank_swift) VALUES
('bhatti-trader', 'Bhatti Trader', 'Bhatti Trader', 'Office Address, Lahore', 'Lahore', 'Pakistan', '+92-300-0000000', 'info@bhattitrader.com', '', 'USD', 'Bank Name', 'Main Branch', '0000000000', 'PK00XXXX00000000000000', 'XXXXPKKA'),
('bhatti-chemicals', 'Bhatti Chemicals Industry', 'Bhatti Chemicals Industry (Pvt) Ltd', 'Industrial Area, Lahore', 'Lahore', 'Pakistan', '+92-300-0000001', 'info@bhattichemicals.com', '', 'USD', 'Bank Name', 'Industrial Branch', '0000000001', 'PK00XXXX00000000000001', 'XXXXPKKA')
SQL);
            $messages[] = 'OK: Seeded Bhatti Trader and Bhatti Chemicals Industry accounts';
        }

        // Sync authorized emails from .env
        $emails = array_filter(array_map('trim', explode(',', (string) env('AUTHORIZED_EMAILS', ''))));
        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $ins = $pdo->prepare('INSERT IGNORE INTO authorized_emails (email) VALUES (?)');
            $ins->execute([$email]);
        }
        if ($emails) {
            $messages[] = 'OK: Synced authorized emails from .env';
        }

        $messages[] = 'Installation complete. Delete install.php on production.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install — Bhatti Export Documents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:720px">
    <h1 class="h3 mb-4">Database Installation</h1>
    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if ($messages): ?>
        <div class="alert alert-success"><ul class="mb-0"><?php foreach ($messages as $m): ?><li><?= htmlspecialchars($m) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" class="card p-4 shadow-sm">
        <p class="text-muted">Creates all tables and seeds the two company accounts. Ensure <code>.env</code> database settings are correct.</p>
        <button type="submit" class="btn btn-primary">Run Installation</button>
        <a href="index.php" class="btn btn-outline-secondary ms-2">Go to App</a>
    </form>
</div>
</body>
</html>
