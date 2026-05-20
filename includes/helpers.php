<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url, int $status = 303): never
{
    header('Location: ' . $url, true, $status);
    exit;
}

function form_submit_token(): string
{
    if (empty($_SESSION['_form_submit_token'])) {
        $_SESSION['_form_submit_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['_form_submit_token'];
}

function rotate_form_submit_token(): string
{
    $_SESSION['_form_submit_token'] = bin2hex(random_bytes(16));
    return $_SESSION['_form_submit_token'];
}

function base_url(string $path = ''): string
{
    $base = rtrim((string) env('APP_URL', ''), '/');
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base = $scheme . '://' . $host . rtrim(str_replace('\\', '/', $script), '/');
    }
    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}

function asset_url(string $path): string
{
    return base_url($path);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $val = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $val;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals(csrf_token(), (string) $token)) {
        http_response_code(403);
        die('Invalid security token. Please refresh and try again.');
    }
}

function format_money($n, string $currency = 'USD'): string
{
    return $currency . ' ' . number_format((float) $n, 2);
}

function format_date(?string $date): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date('d-m-Y', $ts) : $date;
}

function parse_date_input(?string $date): ?string
{
    if (!$date || trim($date) === '') {
        return null;
    }
    $trimmed = trim($date);
    // Check if format is DD-MM-YYYY
    if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $trimmed, $matches)) {
        return sprintf('%04d-%02d-%02d', (int)$matches[3], (int)$matches[2], (int)$matches[1]);
    }
    $ts = strtotime($trimmed);
    return $ts ? date('Y-m-d', $ts) : null;
}

