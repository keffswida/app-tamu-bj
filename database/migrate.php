<?php

declare(strict_types=1);

/**
 * Migrasi schema database — dijalankan saat container app mulai.
 *
 * Menunggu database siap (retry) lalu menerapkan database/schema.sql.
 * Statement bersifat idempoten (CREATE TABLE IF NOT EXISTS) sehingga aman
 * dijalankan ulang pada setiap restart / redeploy.
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Config\Database;

const MIGRATE_ATTEMPTS = 30;
const MIGRATE_DELAY_SECONDS = 2;

/** @var \PDO|null $pdo */
$pdo = null;

for ($i = 1; $i <= MIGRATE_ATTEMPTS; $i++) {
    try {
        $pdo = Database::connection();
        break;
    } catch (Throwable $e) {
        if ($i === MIGRATE_ATTEMPTS) {
            fwrite(
                STDERR,
                sprintf("[%s] ERROR: database tidak dapat dihubungi setelah %d percobaan: %s\n", date('Y-m-d H:i:s'), MIGRATE_ATTEMPTS, $e->getMessage())
            );
            exit(1);
        }
        fwrite(
            STDERR,
            sprintf("[%s] INFO: database belum siap (percobaan %d/%d, %s), mencoba lagi dalam %ds...\n", date('Y-m-d H:i:s'), $i, MIGRATE_ATTEMPTS, $e->getMessage(), MIGRATE_DELAY_SECONDS)
        );
        sleep(MIGRATE_DELAY_SECONDS);
    }
}

if (!$pdo instanceof \PDO) {
    fwrite(STDERR, "ERROR: koneksi database gagal.\n");
    exit(1);
}

$schema = file_get_contents(__DIR__ . '/schema.sql');
if ($schema === false) {
    fwrite(STDERR, "ERROR: database/schema.sql tidak ditemukan.\n");
    exit(1);
}

$pdo->exec($schema);
echo sprintf("[%s] Schema database berhasil diterapkan.\n", date('Y-m-d H:i:s'));
exit(0);
