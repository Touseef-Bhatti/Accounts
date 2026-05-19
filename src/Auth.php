<?php

declare(strict_types=1);

namespace App;

use PDO;

class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['user_email']);
    }

    public static function userEmail(): ?string
    {
        return $_SESSION['user_email'] ?? null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            \redirect(base_url('login.php'));
        }
    }

    public static function login(string $email): void
    {
        $_SESSION['user_email'] = strtolower(trim($email));
        session_regenerate_id(true);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function isAuthorizedEmail(string $email): bool
    {
        $email = strtolower(trim($email));
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT 1 FROM authorized_emails WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            return true;
        }

        $fromEnv = array_filter(array_map('strtolower', array_map('trim', explode(',', (string) env('AUTHORIZED_EMAILS', '')))));
        return in_array($email, $fromEnv, true);
    }

    public static function createOtp(string $email): string
    {
        $email = strtolower(trim($email));
        $length = (int) env('OTP_LENGTH', 6);
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= (string) random_int(0, 9);
        }

        $hash = password_hash($otp, PASSWORD_DEFAULT);
        $minutes = (int) env('OTP_EXPIRY_MINUTES', 15);
        $expires = date('Y-m-d H:i:s', time() + $minutes * 60);
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM otp_codes WHERE email = ? AND used_at IS NULL')->execute([$email]);
        $pdo->prepare('INSERT INTO otp_codes (email, otp_hash, expires_at, ip_address) VALUES (?, ?, ?, ?)')
            ->execute([$email, $hash, $expires, $ip]);

        return $otp;
    }

    public static function verifyOtp(string $email, string $otp): bool
    {
        $email = strtolower(trim($email));
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT id, otp_hash, expires_at FROM otp_codes
             WHERE email = ? AND used_at IS NULL ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }
        if (strtotime($row['expires_at']) < time()) {
            return false;
        }
        if (!password_verify($otp, $row['otp_hash'])) {
            return false;
        }

        $pdo->prepare('UPDATE otp_codes SET used_at = NOW() WHERE id = ?')->execute([$row['id']]);
        return true;
    }
}
