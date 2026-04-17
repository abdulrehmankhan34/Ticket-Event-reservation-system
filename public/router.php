<?php

// Router script for PHP's built-in server (Symfony Runtime compatible).
//
// Usage:
//   php -S localhost:8000 -t public public/router.php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $path;

    // Let the built-in server handle existing static files directly.
    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

// Symfony Runtime expects SCRIPT_FILENAME to be the front controller.
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';

require dirname(__DIR__) . '/vendor/autoload_runtime.php';

