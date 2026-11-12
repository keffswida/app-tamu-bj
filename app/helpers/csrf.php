<?php

declare(strict_types=1);

/**
 * CSRF token untuk form HTML. Endpoint JSON API tidak menggunakan CSRF
 * karena bersifat stateless untuk pengujian konektivitas.
 */

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    if ($token === null || $token === '' || !isset($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}
