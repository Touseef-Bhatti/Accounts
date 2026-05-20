<?php

declare(strict_types=1);

namespace App;

use PDO;

class SuggestionService
{
    public static function record(int $accountId, string $fieldKey, ?string $value): void
    {
        $value = trim((string) $value);
        if ($value === '' || strlen($value) > 2000) {
            return;
        }

        $fieldKey = preg_replace('/[^a-zA-Z0-9_]/', '', $fieldKey);
        if ($fieldKey === '') {
            return;
        }

        $tableName = 'suggest_' . strtolower($fieldKey);
        $pdo = Database::connection();

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO {$tableName} (account_id, field_value, use_count)
                 VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE use_count = use_count + 1, last_used_at = CURRENT_TIMESTAMP"
            );
            $stmt->execute([$accountId, $value]);
        } catch (\PDOException $e) {
            // Check if table doesn't exist (SQLSTATE 42S02)
            if ($e->getCode() === '42S02' || strpos($e->getMessage(), 'doesn\'t exist') !== false) {
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS {$tableName} (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        account_id INT UNSIGNED NOT NULL,
                        field_value TEXT NOT NULL,
                        use_count INT UNSIGNED DEFAULT 1,
                        last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY uk_suggestion (account_id, field_value(191)),
                        FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                    $stmt = $pdo->prepare(
                        "INSERT INTO {$tableName} (account_id, field_value, use_count)
                         VALUES (?, ?, 1)
                         ON DUPLICATE KEY UPDATE use_count = use_count + 1, last_used_at = CURRENT_TIMESTAMP"
                    );
                    $stmt->execute([$accountId, $value]);
                } catch (\Throwable $ex) {
                    // Ignore failure to create/retry to keep app running
                }
            }
        }
    }

    public static function recordMany(int $accountId, array $fields): void
    {
        foreach ($fields as $key => $value) {
            if (is_string($key) && $value !== null && $value !== '') {
                self::record($accountId, $key, (string) $value);
            }
        }
    }

    public static function search(int $accountId, string $fieldKey, string $q = '', int $limit = 15): array
    {
        $fieldKey = preg_replace('/[^a-zA-Z0-9_]/', '', $fieldKey);
        if ($fieldKey === '') {
            return [];
        }

        $tableName = 'suggest_' . strtolower($fieldKey);
        $pdo = Database::connection();
        $q = trim($q);

        try {
            if ($q === '') {
                $stmt = $pdo->prepare(
                    "SELECT field_value AS value FROM {$tableName}
                     WHERE account_id = ?
                     ORDER BY use_count DESC, last_used_at DESC LIMIT ?"
                );
                $stmt->bindValue(1, $accountId, PDO::PARAM_INT);
                $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare(
                    "SELECT field_value AS value FROM {$tableName}
                     WHERE account_id = ? AND field_value LIKE ?
                     ORDER BY use_count DESC LIMIT ?"
                );
                $stmt->bindValue(1, $accountId, PDO::PARAM_INT);
                $stmt->bindValue(2, '%' . $q . '%', PDO::PARAM_STR);
                $stmt->bindValue(3, $limit, PDO::PARAM_INT);
            }
            $stmt->execute();
            return array_column($stmt->fetchAll(), 'value');
        } catch (\PDOException $e) {
            // Return empty array if query fails (e.g. table doesn't exist)
            return [];
        }
    }
}
