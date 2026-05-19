<?php

declare(strict_types=1);

/**
 * Application runtime configuration (errors, session).
 * Loaded from bootstrap after env.php.
 */
function app_is_debug(): bool
{
    return filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
}

$debug = app_is_debug();

ini_set('log_errors', '1');
error_reporting(E_ALL);

if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

$sessionLifetime = (int) env('SESSION_LIFETIME', 7200);
if ($sessionLifetime > 0) {
    ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}

set_exception_handler(static function (Throwable $e) use ($debug): void {
    error_log($e->__toString());
    if (headers_sent()) {
        echo $debug ? $e->getMessage() : 'An error occurred.';
        exit(1);
    }
    http_response_code(500);
    if ($debug) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $e->getMessage() . "\n\n" . $e->getTraceAsString();
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';
        echo '<h1>Something went wrong</h1><p>Please try again or contact support.</p>';
        echo '</body></html>';
    }
    exit(1);
});
