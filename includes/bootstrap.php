<?php

declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Run: composer install\n");
        exit(1);
    }
    http_response_code(500);
    die('<h1>Setup required</h1><p>Run <code>composer install</code> in the project folder, then open <a href="install.php">install.php</a>.</p>');
}
require_once $autoload;
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/configure.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/number_words.php';

session_start();

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = dirname(__DIR__) . '/src/' . $relative . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

try {
    App\SchemaMigrator::run();
} catch (Throwable $e) {
    error_log('SchemaMigrator: ' . $e->getMessage());
    if (app_is_debug()) {
        throw $e;
    }
}
