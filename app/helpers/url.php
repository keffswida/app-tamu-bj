<?php

declare(strict_types=1);

/**
 * Resolusi base path publik.
 *
 * Nginx menghapus prefix /app-tamu sebelum meneruskan request ke Apache dan
 * mengirim prefix aslinya lewat header X-Forwarded-Prefix. Jika header itu
 * ada, header menjadi acuan utama untuk pembuatan URL. Jika tidak ada,
 * gunakan APP_BASE_PATH dari environment (akses langsung ke container).
 */
function public_base_path(): string
{
    $prefix = trim($_SERVER['HTTP_X_FORWARDED_PREFIX'] ?? '');
    if ($prefix !== '') {
        $base = '/' . trim($prefix, '/');

        return $base === '/' ? '' : $base;
    }

    $envBase = trim(env('APP_BASE_PATH') ?? '');
    if ($envBase !== '') {
        $base = '/' . trim($envBase, '/');

        return $base === '/' ? '' : $base;
    }

    return '';
}

/**
 * Path request relatif terhadap aplikasi (prefix base path dibuang jika ada).
 */
function request_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = public_base_path();

    if ($base !== '') {
        if ($uri === $base) {
            return '/';
        }
        if (str_starts_with($uri, $base . '/')) {
            $uri = substr($uri, strlen($base));
        }
    }

    return $uri === '' ? '/' : $uri;
}

/**
 * URL publik lengkap (sudah termasuk base path). Jangan oper base path ke
 * fungsi ini karena akan menghasilkan prefix ganda.
 */
function app_url(string $path = ''): string
{
    return public_base_path() . '/' . ltrim($path, '/');
}

/**
 * URL aset statis publik.
 */
function asset_url(string $path): string
{
    return app_url('assets/' . ltrim($path, '/'));
}

/**
 * Redirect 303 (PRG) ke path internal aplikasi.
 */
function redirect_to(string $path): never
{
    header('Location: ' . app_url($path));
    http_response_code(303);
    exit;
}
