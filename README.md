# Buku Tamu — Guest Application & API Connectivity Tester

Aplikasi web satu halaman untuk mencatat data tamu dan menyediakan REST API
sebagai sarana pengujian konektivitas server VPS oleh pihak eksternal
(mis. Bank Jatim).

## Fitur

- Form data tamu: nama, email, nomor HP, dan keperluan kunjungan
  (input manual atau dropdown `Minta API Data Tamu`).
- Tabel daftar tamu dengan masking email, nomor HP, dan IP address
  (data asli hanya tersimpan di database).
- IP address pengunjung dicatat otomatis oleh backend (aware terhadap
  reverse proxy).
- REST API untuk pengujian konektivitas:
  - `GET  /api/v1/health` — health check aplikasi + database
  - `GET  /api/v1/guests` — daftar tamu (field sensitif ter-mask)
  - `POST /api/v1/guests` — simpan tamu baru (JSON)

## Teknologi

PHP Native 8.3 · MySQL 8.4 · PDO · Apache (container) · Docker Compose ·
Nginx + Certbot (host VPS)

Tanpa framework PHP maupun framework frontend.

## Menjalankan Secara Lokal

Prasyarat: Docker + Docker Compose.

```bash
# 1. Siapkan konfigurasi environment
cp .env.example .env

# 2. Build dan jalankan container (app + MySQL)
docker compose up -d --build

# 3. Buka aplikasi
#    http://127.0.0.1:8081/app-tamu/
#    (atau http://127.0.0.1:8081/ — kedua-duanya berfungsi)

# 4. Uji API
curl http://127.0.0.1:8081/api/v1/health
curl http://127.0.0.1:8081/api/v1/guests
```

> **Penting:** ganti nilai kredensial database di `.env` sebelum deployment.
> File `.env` tidak boleh di-commit ke Git.

## Struktur Project

```text
app/          backend (config, controllers, services, helpers, views)
public/       front controller, aset statis
database/     schema.sql + skrip migrasi otomatis
docker/       konfigurasi Apache, PHP, entrypoint container
logs/         log aplikasi (runtime)
```

## Dokumentasi

- [overview.md](overview.md) — spesifikasi lengkap aplikasi (acuan utama).
- [deploy.md](deploy.md) — panduan deployment di VPS (Nginx, Docker, SSL).
