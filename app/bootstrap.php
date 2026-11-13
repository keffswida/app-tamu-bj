<?php

declare(strict_types=1);

/**
 * Bootstrap aplikasi: env, helpers, autoloader sederhana, timezone, session.
 */

require __DIR__ . '/helpers/env.php';
load_env(dirname(__DIR__) . '/.env');

require __DIR__ . '/helpers/view.php';
require __DIR__ . '/helpers/url.php';
require __DIR__ . '/helpers/response.php';
require __DIR__ . '/helpers/logger.php';
require __DIR__ . '/helpers/ip.php';
require __DIR__ . '/helpers/masking.php';
require __DIR__ . '/helpers/validation.php';
require __DIR__ . '/helpers/csrf.php';
require __DIR__ . '/helpers/flash.php';

// Autoloader sederhana untuk namespace App\ (tanpa Composer).
// Direktori project memakai huruf kecil (app/config, app/controllers, ...)
// sehingga lookup nama file dicoba persis lalu versi lowercase.
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $segments = explode('\\', $relative);
    $classFile = array_pop($segments);
    $dir = strtolower(implode('/', $segments));
    $base = __DIR__ . '/' . ($dir !== '' ? $dir . '/' : '');

    foreach ([$classFile, strtolower($classFile)] as $name) {
        $file = $base . $name . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

error_reporting(E_ALL);
ini_set('display_errors', '0');   // Jangan bocorkan detail error ke klien.
ini_set('log_errors', '1');

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta') ?? 'Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => ($_SERVER['HTTPS'] ?? '') === 'on'
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
        'path' => '/',
    ]);
    session_start();
}
