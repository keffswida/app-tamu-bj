<?php

declare(strict_types=1);

/**
 * Data masking untuk tampilan.
 *
 * Diterapkan di sisi server SEBELUM data sampai ke frontend maupun API
 * publik. Data asli hanya tersimpan di database.
 *
 * Aturan:
 * - Email   : beberapa karakter awal + "***" + domain.
 * - No. HP  : beberapa digit awal + "****" + 4 digit terakhir.
 * - IP      : segmen pertama dan terakhir terlihat, tengah disensor.
 */

function mask_email(string $email): string
{
    $at = strpos($email, '@');
    if ($at === false || $at === 0) {
        return str_repeat('*', max(3, strlen($email)));
    }

    $local = substr($email, 0, $at);
    $domain = substr($email, $at);
    $visible = strlen($local) <= 2 ? substr($local, 0, 1) : substr($local, 0, 2);

    return $visible . '***' . $domain;
}

function mask_phone(string $phone): string
{
    if (strlen($phone) <= 6) {
        return substr($phone, 0, 1) . str_repeat('*', max(3, strlen($phone) - 1));
    }

    return substr($phone, 0, 2) . '****' . substr($phone, -4);
}

function mask_ip(string $ip): string
{
    if (str_contains($ip, ':')) {
        $groups = explode(':', $ip);
        if (count($groups) < 3) {
            return str_repeat('*', strlen($ip));
        }

        return $groups[0] . ':***:***:' . $groups[count($groups) - 1];
    }

    $parts = explode('.', $ip);
    if (count($parts) !== 4) {
        return str_repeat('*', strlen($ip));
    }

    return $parts[0] . '.***.***.' . $parts[3];
}
