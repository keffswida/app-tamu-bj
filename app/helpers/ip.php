<?php

declare(strict_types=1);

/**
 * Deteksi IP address klien.
 *
 * Reverse proxy tepercaya (Nginx di host VPS) meneruskan request lewat
 * localhost dan mengisi X-Real-IP. Header IP klien hanya dipercaya jika peer
 * langsungnya adalah proxy tepercaya — jangan pernah mengambil
 * X-Forwarded-For / CF-Connecting-IP dari sumber sembarang.
 */
function client_ip(): string
{
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';

    if (in_array($remote, ['127.0.0.1', '::1'], true)) {
        $forwarded = $_SERVER['HTTP_X_REAL_IP'] ?? '';
        $valid = filter_var($forwarded, FILTER_VALIDATE_IP);
        if ($valid !== false) {
            return $valid;
        }
    }

    if (filter_var($remote, FILTER_VALIDATE_IP) !== false) {
        return $remote;
    }

    return '0.0.0.0';
}
