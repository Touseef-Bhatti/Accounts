<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Karachi');

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

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
    if (function_exists('app_is_debug') && app_is_debug()) {
        throw $e;
    }
}
