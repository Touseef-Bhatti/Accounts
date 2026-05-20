<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function connection(bool $withoutDb = false): PDO
    {
        if (self::$pdo !== null && !$withoutDb) {
            return self::$pdo;
        }

        $host = (string) env('DB_HOST', 'localhost');
        $port = (string) env('DB_PORT', '3306');
        $name = (string) env('DB_NAME', 'bhatti_accounts');
        $user = (string) env('DB_USER', 'root');
        $pass = (string) env('DB_PASS', '');

        $dsn = $withoutDb
            ? "mysql:host={$host};port={$port};charset=utf8mb4"
            : "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pdo->exec("SET time_zone = '+05:00'");
        } catch (PDOException $e) {
            throw new PDOException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }

        if (!$withoutDb) {
            self::$pdo = $pdo;
        }

        return $pdo;
    }
}
