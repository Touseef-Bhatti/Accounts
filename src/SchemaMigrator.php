<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

/** Applies idempotent schema updates for existing databases. */
class SchemaMigrator
{
    private static bool $ran = false;

    public static function run(): void
    {
        if (self::$ran) {
            return;
        }
        self::$ran = true;

        try {
            $pdo = Database::connection();
        } catch (PDOException) {
            return;
        }

        self::migrateDocumentSets($pdo);
        self::migrateLineItems($pdo);
        self::migrateGatePasses($pdo);
        self::migrateFieldSuggestions($pdo);
        self::migrateAuthorizedEmails($pdo);
        self::migrateLoginAttempts($pdo);
        self::migrateAppSettings($pdo);
    }
    
    private static function migrateLoginAttempts(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'login_attempts')) {
            return;
        }
        $pdo->exec(<<<'SQL'
CREATE TABLE login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attempt_email (email),
    INDEX idx_attempt_ip (ip_address),
    INDEX idx_attempt_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
        );
        $stmt->execute([$table, $column]);

        return (bool) $stmt->fetchColumn();
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1'
        );
        $stmt->execute([$table]);

        return (bool) $stmt->fetchColumn();
    }

    private static function migrateDocumentSets(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'document_sets')) {
            return;
        }
        if (!self::columnExists($pdo, 'document_sets', 'doc_type')) {
            $pdo->exec(
                "ALTER TABLE document_sets ADD COLUMN doc_type
                 ENUM('proforma','commercial','packing','contract','gate_pass')
                 NOT NULL DEFAULT 'proforma' AFTER reference_no"
            );
        }
    }

    private static function migrateLineItems(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'line_items')) {
            return;
        }
        if (!self::columnExists($pdo, 'line_items', 'remarks')) {
            $pdo->exec('ALTER TABLE line_items ADD COLUMN remarks TEXT DEFAULT NULL AFTER dimensions');
        }
        try {
            $pdo->exec(
                "ALTER TABLE line_items MODIFY COLUMN doc_type
                 ENUM('proforma','commercial','packing','gate_pass') NOT NULL"
            );
        } catch (PDOException) {
            // Enum already includes gate_pass or column differs — safe to ignore
        }
    }

    private static function migrateGatePasses(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'gate_passes')) {
            return;
        }
        if (!self::tableExists($pdo, 'document_sets')) {
            return;
        }
        $pdo->exec(<<<'SQL'
CREATE TABLE gate_passes (
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
SQL);
    }

    private static function migrateFieldSuggestions(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'field_suggestions')) {
            $pdo->exec('DROP TABLE IF EXISTS field_suggestions');
        }
        if (!self::tableExists($pdo, 'accounts')) {
            return;
        }

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
            if (!self::tableExists($pdo, $tableName)) {
                $pdo->exec("CREATE TABLE {$tableName} (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    account_id INT UNSIGNED NOT NULL,
                    field_value TEXT NOT NULL,
                    use_count INT UNSIGNED DEFAULT 1,
                    last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uk_suggestion (account_id, field_value(191)),
                    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }
        }
    }
    
    private static function migrateAuthorizedEmails(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'authorized_emails')) {
            return;
        }
        if (!self::columnExists($pdo, 'authorized_emails', 'name')) {
            $pdo->exec('ALTER TABLE authorized_emails ADD COLUMN name VARCHAR(255) DEFAULT NULL AFTER email');
        }
        if (!self::columnExists($pdo, 'authorized_emails', 'password')) {
            $pdo->exec('ALTER TABLE authorized_emails ADD COLUMN password VARCHAR(255) DEFAULT NULL AFTER name');
        }
    }

    private static function migrateAppSettings(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'app_settings')) {
            return;
        }
        $pdo->exec(<<<'SQL'
CREATE TABLE app_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id INT UNSIGNED NOT NULL,
    setting_key VARCHAR(128) NOT NULL,
    setting_value TEXT DEFAULT NULL,
    UNIQUE KEY uk_account_key (account_id, setting_key),
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }
}
