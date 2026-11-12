<?php

declare(strict_types=1);

namespace App\Config;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Koneksi PDO dari environment variable. Detail koneksi tidak pernah
 * dibocorkan ke klien (exception diganti pesan generik).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = env('DB_HOST', '127.0.0.1') ?? '127.0.0.1';
        $port = env('DB_PORT', '3306') ?? '3306';
        $name = env('DB_DATABASE', '') ?? '';
        $user = env('DB_USERNAME', '') ?? '';
        $password = env('DB_PASSWORD', '') ?? '';

        if ($name === '' || $user === '') {
            throw new RuntimeException('Database configuration is incomplete.');
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

        try {
            self::$pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Sinkronkan time zone sesi MySQL dengan timezone aplikasi agar
            // kolom TIMESTAMP (created_at / updated_at) kembali dalam waktu
            // lokal yang benar.
            $timezone = env('APP_TIMEZONE', 'Asia/Jakarta') ?? 'Asia/Jakarta';
            $offset = (new DateTimeImmutable('now', new DateTimeZone($timezone)))->format('P');
            self::$pdo->exec("SET time_zone = '" . $offset . "'");
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed.', 0, $e);
        }

        return self::$pdo;
    }
}
