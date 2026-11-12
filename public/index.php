<?php

declare(strict_types=1);

/**
 * Front controller: halaman utama (/) + routing REST API (/api/v1/...).
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Controllers\ApiController;
use App\Controllers\HomeController;
use Throwable;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = request_path();

// Catat ringkasan request (method, path, status akhir).
register_shutdown_function(static function () use ($method, $path): void {
    log_request($method, $path, http_response_code());
});

try {
    if ($path === '/') {
        HomeController::handle($method);
    }

    if (str_starts_with($path, '/api/')) {
        ApiController::route($path, $method);
    }

    http_response_code(404);
    render_view('error', [
        'title' => 'Halaman Tidak Ditemukan',
        'message' => 'Halaman yang Anda cari tidak tersedia.',
    ]);
    exit;
} catch (Throwable $e) {
    app_log('error', 'Unhandled exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);

    if (str_starts_with($path, '/api/')) {
        json_response(false, 'Internal server error', null, 500);
    }

    http_response_code(500);
    render_view('error', [
        'title' => 'Terjadi Kesalahan',
        'message' => 'Terjadi kesalahan pada server. Silakan coba lagi.',
    ]);
    exit;
}
