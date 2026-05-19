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
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO field_suggestions (account_id, field_key, field_value, use_count)
             VALUES (?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE use_count = use_count + 1, last_used_at = CURRENT_TIMESTAMP'
        )->execute([$accountId, $fieldKey, $value]);
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
        $pdo = Database::connection();
        $q = trim($q);
        if ($q === '') {
            $stmt = $pdo->prepare(
                'SELECT field_value AS value FROM field_suggestions
                 WHERE account_id = ? AND field_key = ?
                 ORDER BY use_count DESC, last_used_at DESC LIMIT ?'
            );
            $stmt->bindValue(1, $accountId, PDO::PARAM_INT);
            $stmt->bindValue(2, $fieldKey, PDO::PARAM_STR);
            $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        } else {
            $stmt = $pdo->prepare(
                'SELECT field_value AS value FROM field_suggestions
                 WHERE account_id = ? AND field_key = ? AND field_value LIKE ?
                 ORDER BY use_count DESC LIMIT ?'
            );
            $stmt->bindValue(1, $accountId, PDO::PARAM_INT);
            $stmt->bindValue(2, $fieldKey, PDO::PARAM_STR);
            $stmt->bindValue(3, '%' . $q . '%', PDO::PARAM_STR);
            $stmt->bindValue(4, $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return array_column($stmt->fetchAll(), 'value');
    }
}
