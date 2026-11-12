<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\GuestService;
use Throwable;

/**
 * Halaman utama (one-page): form data tamu + tabel daftar tamu.
 * Memakai pola redirect-after-POST untuk mencegah double submit.
 */
final class HomeController
{
    public static function handle(string $method): never
    {
        if ($method === 'GET') {
            self::index();
        }
        if ($method === 'POST') {
            self::submit();
        }

        http_response_code(405);
        header('Allow: GET, POST');
        render_view('error', [
            'title' => 'Method Not Allowed',
            'message' => 'HTTP method tidak didukung.',
        ]);
        exit;
    }

    private static function index(): never
    {
        $flash = flash_get();
        $old = form_old();
        $errors = form_errors();

        $guests = [];
        $dbError = null;
        try {
            $guests = GuestService::make()->listMasked();
        } catch (Throwable $e) {
            app_log('error', 'Gagal memuat daftar tamu', ['message' => $e->getMessage()]);
            $dbError = 'Data tamu sementara tidak dapat dimuat. Silakan coba lagi.';
        }

        render_view('home', [
            'flash' => $flash,
            'old' => $old,
            'errors' => $errors,
            'guests' => $guests,
            'dbError' => $dbError,
        ]);
        exit;
    }

    private static function submit(): never
    {
        if (!csrf_verify($_POST['csrf_token'] ?? null)) {
            flash_set('error', 'Sesi form tidak valid. Silakan muat ulang halaman dan coba lagi.');
            redirect_to('/');
        }

        $result = validate_guest($_POST, 'form');

        if ($result['errors'] !== []) {
            form_errors_set($result['errors']);
            form_old_set($_POST);
            flash_set('error', 'Data tidak valid. Periksa kembali isian form.');
            redirect_to('/');
        }

        $data = $result['data'];
        $data['ip_address'] = client_ip();

        try {
            GuestService::make()->create($data);
        } catch (Throwable $e) {
            app_log('error', 'Gagal menyimpan data tamu', ['message' => $e->getMessage()]);
            form_old_set($_POST);
            flash_set('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
            redirect_to('/');
        }

        flash_set('success', 'Terima kasih, data tamu berhasil dicatat.');
        redirect_to('/');
    }
}
