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
}
