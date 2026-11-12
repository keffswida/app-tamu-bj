<?php

declare(strict_types=1);

/**
 * Loader .env minimal.
 *
 * Nilai yang sudah ada di environment (misalnya diinjeksi oleh Docker
 * Compose) tidak pernah ditimpa oleh file .env.
 */
function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        // Lepaskan tanda kutip di sekeliling nilai.
        $length = strlen($value);
        if ($length >= 2) {
            $first = $value[0];
            $last = $value[$length - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
}

/**
 * Baca environment variable dengan fallback.
 */
function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);

    return $value === false ? $default : $value;
}
