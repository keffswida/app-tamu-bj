<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;

/**
 * Akses data tamu. Seluruh query memakai prepared statements.
 *
 * Data sensitif (email, phone, ip_address) hanya keluar dari layer ini
 * dalam bentuk ter-mask — data asli tetap di database.
 */
final class GuestService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public static function make(): self
    {
        return new self(Database::connection());
    }

    /**
     * Simpan tamu baru dan kembalikan record lengkapnya.
     *
     * @param array{name: string, email: string, phone: string, visit_purpose: string, ip_address: string} $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO guests (name, email, phone, visit_purpose, ip_address)
             VALUES (:name, :email, :phone, :visit_purpose, :ip_address)'
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':phone' => $data['phone'],
            ':visit_purpose' => $data['visit_purpose'],
            ':ip_address' => $data['ip_address'],
        ]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM guests WHERE id = :id');
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch();

        return $row === false ? [] : $row;
    }

    /**
     * Daftar tamu dengan field sensitif ter-mask, terbaru di atas.
     *
     * Dipakai oleh tabel halaman utama DAN endpoint GET /api/v1/guests —
     * sesuai kebijakan akses: endpoint publik tanpa autentikasi tidak
     * mengembalikan data pribadi asli.
     *
     * @return list<array<string, mixed>>
     */
    public function listMasked(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, email, phone, visit_purpose, ip_address, created_at, updated_at
             FROM guests
             ORDER BY id DESC'
        );

        $rows = $stmt->fetchAll();

        return array_map(static function (array $row): array {
            $row['email'] = mask_email((string) $row['email']);
            $row['phone'] = mask_phone((string) $row['phone']);
            $row['ip_address'] = mask_ip((string) $row['ip_address']);

            return $row;
        }, $rows);
    }
}
