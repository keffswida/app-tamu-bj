<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Services\GuestService;
use Throwable;

/**
 * REST API v1 — pengujian konektivitas dan komunikasi HTTP.
 *
 * Seluruh response JSON konsisten: { success, message, data } dengan
 * Content-Type: application/json dan HTTP status yang sesuai.
 */
final class ApiController
{
    public static function route(string $path, string $method): never
    {
        if ($path === '/api/v1/health') {
            self::requireMethod($method, ['GET']);
            self::health();
        }

        if ($path === '/api/v1/guests') {
            self::requireMethod($method, ['GET', 'POST']);
            if ($method === 'GET') {
                self::listGuests();
            }
            self::createGuest();
        }

        json_response(false, 'Endpoint not found', null, 404);
    }

    /**
     * @param list<string> $allowed
     */
    private static function requireMethod(string $method, array $allowed): void
    {
        if (!in_array($method, $allowed, true)) {
            header('Allow: ' . implode(', ', $allowed));
            json_response(false, 'Method not allowed', null, 405);
        }
    }

    private static function health(): never
    {
        $status = 200;
        $database = 'connected';

        try {
            Database::connection()->query('SELECT 1');
        } catch (Throwable $e) {
            $status = 503;
            $database = 'disconnected';
            app_log('error', 'Health check: database tidak tersedia', ['message' => $e->getMessage()]);
        }

        json_response(
            $status === 200,
            $status === 200 ? 'Application is running' : 'Service unavailable',
            ['application' => 'Guest App', 'database' => $database],
            $status
        );
    }

    private static function listGuests(): never
    {
        try {
            $guests = GuestService::make()->listMasked();
        } catch (Throwable $e) {
            app_log('error', 'GET /api/v1/guests gagal', ['message' => $e->getMessage()]);
            json_response(false, 'Database unavailable', null, 503);
        }

        json_response(true, 'Guest data retrieved successfully', $guests, 200);
    }

    private static function createGuest(): never
    {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw === false ? '' : $raw, true);

        if (!is_array($input)) {
            json_response(false, 'Invalid JSON payload', null, 400);
        }

        $result = validate_guest($input, 'api');
        if ($result['errors'] !== []) {
            json_response(false, 'Validation failed', ['errors' => $result['errors']], 422);
        }

        $data = $result['data'];
        $data['ip_address'] = client_ip();

        try {
            $row = GuestService::make()->create($data);
        } catch (Throwable $e) {
            app_log('error', 'POST /api/v1/guests gagal', ['message' => $e->getMessage()]);
            json_response(false, 'Database unavailable', null, 503);
        }

        json_response(true, 'Guest created successfully', $row, 201);
    }
}
