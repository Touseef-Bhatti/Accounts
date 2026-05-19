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
}
