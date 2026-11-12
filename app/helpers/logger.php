<?php

declare(strict_types=1);

/**
 * Logging sederhana.
 *
 * Ditulis ke stderr (terlihat lewat `docker compose logs`) dan ke
 * logs/app.log di root project. Tidak pernah mencatat password, kredensial
 * database, atau data sensitif lainnya.
 *
 * @param array<string, mixed> $context
 */
function app_log(string $level, string $message, array $context = []): void
{
    $payload = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $line = sprintf(
        '[%s] %s: %s%s',
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        $context !== [] && is_string($payload) ? ' ' . $payload : ''
    );

    // stderr -> Apache error log -> `docker compose logs app`
    error_log($line);

    $dir = dirname(__DIR__, 2) . '/logs';
    if (is_dir($dir) || @mkdir($dir, 0775, true)) {
        @file_put_contents($dir . '/app.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Catat ringkasan request: method, endpoint, dan status code.
 */
function log_request(string $method, string $path, ?int $status = null): void
{
    $context = ['method' => $method, 'path' => $path];
    if ($status !== null) {
        $context['status'] = $status;
    }

    app_log('info', 'request', $context);
}
