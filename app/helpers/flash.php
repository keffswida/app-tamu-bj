<?php

declare(strict_types=1);

/**
 * Flash message + penyimpanan sementara old input dan error form
 * (dipakai dengan pola redirect-after-POST).
 */

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * @return array{type: string, message: string}|null
 */
function flash_get(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

/**
 * @param array<string, mixed> $input
 */
function form_old_set(array $input): void
{
    $_SESSION['form_old'] = $input;
}

/**
 * @return array<string, mixed>
 */
function form_old(): array
{
    $old = $_SESSION['form_old'] ?? [];
    unset($_SESSION['form_old']);

    return $old;
}

/**
 * @param array<string, string> $errors
 */
function form_errors_set(array $errors): void
{
    $_SESSION['form_errors'] = $errors;
}

/**
 * @return array<string, string>
 */
function form_errors(): array
{
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['form_errors']);

    return $errors;
}
