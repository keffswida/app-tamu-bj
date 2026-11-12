<?php

declare(strict_types=1);

/**
 * HTML escaping untuk seluruh data yang ditampilkan ke pengguna.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Render file view dengan data.
 *
 * @param array<string, mixed> $data
 */
function render_view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/views/' . $name . '.php';
}
