<?php

declare(strict_types=1);

namespace App;

use PDO;

class AccountRepository
{
    public static function all(): array
    {
        $pdo = Database::connection();
        return $pdo->query('SELECT * FROM accounts ORDER BY id')->fetchAll();
    }

    public static function findBySlug(string $slug): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM accounts WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function updateLogo(int $id, string $path): void
    {
        $pdo = Database::connection();
        $pdo->prepare('UPDATE accounts SET logo_path = ? WHERE id = ?')->execute([$path, $id]);
    }

    public static function getSetting(int $accountId, string $key, ?string $default = null): ?string
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT setting_value FROM app_settings WHERE account_id = ? AND setting_key = ? LIMIT 1');
        $stmt->execute([$accountId, $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string) $val : $default;
    }

    public static function setSetting(int $accountId, string $key, string $value): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO app_settings (account_id, setting_key, setting_value)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$accountId, $key, $value]);
    }

    /** Returns the PDF top offset in mm for a given doc type (default 40). */
    public static function getTopOffset(int $accountId, string $docType): int
    {
        $key = 'pdf_top_offset_' . $docType;
        $val = self::getSetting($accountId, $key, '40');
        return max(0, (int) $val);
    }
}
