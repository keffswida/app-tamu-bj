<?php

declare(strict_types=1);

/**
 * Standar response JSON untuk seluruh endpoint API:
 *
 * { "success": bool, "message": string, "data": mixed }
 */
function json_response(bool $success, string $message, mixed $data = null, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    $body = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ];

    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
