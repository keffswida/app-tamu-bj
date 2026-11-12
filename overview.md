# Overview — Aplikasi Guest & API Connectivity Tester

## 1. Gambaran Umum

Aplikasi ini merupakan aplikasi web sederhana untuk menampilkan daftar nama tamu sekaligus menyediakan REST API sebagai endpoint pengujian konektivitas server.

Aplikasi akan digunakan untuk membantu menguji apakah server VPS dapat diakses oleh pihak eksternal, khususnya Bank Jatim, melalui endpoint API yang disediakan.

Aplikasi memiliki dua fungsi utama:

1. **Guest Application:** Pengguna dapat memasukkan nama tamu dan melihat daftar nama yang telah tersimpan.
2. **API Connectivity Tester:** Menyediakan endpoint API sederhana untuk menguji aksesibilitas server dan respons API dari luar server.

Aplikasi ini bukan sistem integrasi transaksi QRIS. Fokusnya hanya menyediakan endpoint sederhana yang dapat digunakan untuk menguji konektivitas, request, dan response.

## 2. Tujuan

- Menyediakan aplikasi sederhana untuk mencatat nama tamu.
- Menampilkan daftar tamu dalam bentuk grid.
- Menyediakan REST API yang dapat diakses dari luar server.
- Memastikan endpoint API dapat menerima dan memberikan respons.
- Menjadi sarana pengujian konektivitas antara server VPS dan pihak Bank Jatim.
- Memiliki konfigurasi deployment menggunakan Docker agar aplikasi mudah dijalankan dan dipelihara di VPS.

## 3. Teknologi

| Komponen | Teknologi |
|---|---|
| Backend | PHP Native 8.3 |
| Frontend | HTML, CSS, JavaScript |
| Database | MySQL 8.4 |
| Database Access | PDO |
| Web Server Container | Apache |
| Containerization | Docker |
| Container Orchestration | Docker Compose |
| Reverse Proxy | Nginx (host VPS) |
| SSL | Certbot / Let's Encrypt |

Tidak menggunakan framework PHP seperti Laravel maupun framework frontend tambahan.

## 4. Target Deployment

Aplikasi akan di-deploy pada VPS Debian 12 yang sudah memiliki Nginx dan Certbot/Let's Encrypt.

Aplikasi harus berjalan di bawah domain yang sudah tersedia, bukan menggunakan subdomain baru.

**Target URL aplikasi:**

`https://lksbarokah255.my.id/app-tamu`

**Target URL API:**

- Health Check: `https://lksbarokah255.my.id/app-tamu/api/v1/health`
- Get Guests: `https://lksbarokah255.my.id/app-tamu/api/v1/guests`
- Create Guest: `https://lksbarokah255.my.id/app-tamu/api/v1/guests`

Ketentuan deployment:

- Menggunakan domain dan sertifikat SSL yang sudah tersedia.
- Tidak membuat virtual host atau server block Nginx baru.
- Tidak membuat subdomain baru.
- Nginx tetap berjalan langsung pada host VPS.
- Aplikasi PHP dan Apache berjalan di dalam Docker container.
- MySQL berjalan di dalam Docker container.
- Container aplikasi hanya dapat diakses melalui `127.0.0.1:8081` dari host VPS.
- MySQL tidak mengekspos port ke publik.
- Tidak mengubah konfigurasi website lain yang sudah berjalan di VPS.

## 5. Fitur Aplikasi

### 5.1. Guest Application

Halaman utama aplikasi digunakan untuk memasukkan nama tamu dan menampilkan daftar tamu.

Fitur:

1. Input nama tamu.
2. Tombol submit untuk menyimpan nama.
3. Menampilkan daftar tamu dalam bentuk grid.
4. Menampilkan nama tamu yang telah tersimpan di database.
5. Menampilkan informasi apabila belum ada data tamu.
6. Memberikan feedback ketika data berhasil atau gagal disimpan.

Alur aplikasi:

1. Pengguna membuka halaman aplikasi.
2. Aplikasi mengambil daftar tamu dari database.
3. Daftar nama ditampilkan dalam bentuk grid.
4. Pengguna memasukkan nama pada input.
5. Pengguna menekan tombol submit.
6. Aplikasi melakukan validasi.
7. Jika valid, data disimpan ke database.
8. Daftar tamu diperbarui.

### 5.2. REST API

API digunakan untuk menguji konektivitas dan komunikasi HTTP antara server dengan pihak eksternal.

#### A. Health Check

`GET /api/v1/health`

Tujuan:
- Menguji apakah server dapat diakses.
- Memastikan aplikasi PHP dapat memberikan respons.
- Memastikan koneksi database dapat digunakan.

Contoh response berhasil:

```json
{
  "success": true,
  "message": "Application is running",
  "data": {
    "application": "Guest App",
    "database": "connected"
  }
}
```

Jika database tidak dapat diakses, API harus memberikan response error yang sesuai.

#### B. Get Guests

`GET /api/v1/guests`

Tujuan:
- Mengambil daftar tamu dari database.
- Menguji komunikasi HTTP GET.
- Menguji response JSON.

Contoh response berhasil:

```json
{
  "success": true,
  "message": "Guest data retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "created_at": "2026-11-11 10:00:00"
    }
  ]
}
```

#### C. Create Guest

`POST /api/v1/guests`

Tujuan:
- Menguji komunikasi HTTP POST.
- Menguji penerimaan request JSON.
- Menguji proses penyimpanan data ke database.

Contoh request:

```json
{
  "name": "John Doe"
}
```

Contoh response berhasil:

```json
{
  "success": true,
  "message": "Guest created successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "created_at": "2026-11-11 10:00:00"
  }
}
```

### 5.3. Standar Response API

Semua endpoint API harus menggunakan format JSON yang konsisten.

Response minimal memiliki struktur:

```json
{
  "success": true,
  "message": "Response message",
  "data": {}
}
```

Ketentuan HTTP status:

| Status | Penggunaan |
|---|---|
| 200 | Request berhasil |
| 201 | Data berhasil dibuat |
| 400 | Request tidak valid |
| 404 | Endpoint tidak ditemukan |
| 405 | HTTP method tidak didukung |
| 422 | Validasi gagal |
| 500 | Internal server error |
| 503 | Layanan atau database tidak tersedia |

Pastikan header response API menggunakan `Content-Type: application/json`.

## 6. Database

Database menggunakan MySQL 8.4.

Nama database dan kredensial harus dikonfigurasi melalui environment variable.

### 6.1. Tabel Guests

Nama tabel: `guests`

| Kolom | Tipe Data | Keterangan |
|---|---|---|
| id | BIGINT UNSIGNED | Primary Key, Auto Increment |
| name | VARCHAR(150) | Nama tamu |
| created_at | TIMESTAMP | Waktu data dibuat |

Ketentuan:
- `id` sebagai primary key.
- `name` wajib diisi.
- Panjang nama maksimal 150 karakter.
- `created_at` otomatis mencatat waktu penyimpanan.

### 6.2. Database Connection

Gunakan PDO untuk koneksi database.

Konfigurasi database harus berasal dari environment variable:

- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

Di lingkungan Docker, `DB_HOST` mengarah ke nama service MySQL pada Docker Compose, bukan `localhost`.

Gunakan PDO prepared statements untuk seluruh query yang melibatkan input pengguna.

## 7. Validasi dan Penanganan Error

### 7.1. Validasi Input Nama

Ketentuan:
- Nama tidak boleh kosong.
- Nama harus berupa string.
- Nama harus di-trim sebelum diproses.
- Nama maksimal 150 karakter.
- Input yang hanya berisi spasi harus ditolak.

### 7.2. Penanganan Error

Aplikasi harus menangani kondisi berikut:

- Database tidak dapat dihubungi.
- Query database gagal.
- Request JSON tidak valid.
- Request tidak memiliki field `name`.
- Nama kosong.
- Nama melebihi batas karakter.
- Endpoint tidak ditemukan.
- HTTP method tidak sesuai.
- Terjadi internal server error.

Jangan menampilkan detail error database, kredensial, maupun informasi internal server kepada pengguna atau pihak eksternal.

## 8. Keamanan Dasar

Meskipun aplikasi hanya digunakan untuk pengujian, tetap terapkan keamanan dasar.

- Gunakan PDO prepared statements untuk mencegah SQL Injection.
- Lakukan HTML escaping saat menampilkan nama tamu untuk mencegah XSS.
- Validasi seluruh input dari frontend maupun API.
- Jangan menyimpan kredensial database langsung di source code.
- Gunakan file `.env` untuk konfigurasi sensitif.
- Jangan memasukkan `.env` ke Git.
- Sediakan `.env.example` tanpa kredensial asli.
- Gunakan error handling yang tidak membocorkan informasi internal.
- Batasi akses MySQL hanya melalui jaringan internal Docker.
- Jangan mengekspos port database ke internet.

Untuk form HTML, gunakan mekanisme CSRF token sederhana. Endpoint API yang menerima JSON harus mengikuti kebutuhan autentikasi dan validasi yang sesuai dengan konteks pengujian, tanpa menambahkan sistem autentikasi yang tidak diperlukan.

## 9. Docker Setup

Aplikasi harus dapat dijalankan menggunakan Docker Compose.

### 9.1. Arsitektur Container

Gunakan dua container utama:

1. **app**
   - PHP 8.3.
   - Apache.
   - Menjalankan aplikasi PHP Native.
   - Terhubung ke container MySQL melalui jaringan internal Docker.

2. **db**
   - MySQL 8.4.
   - Menyimpan seluruh data tamu.
   - Menggunakan named volume agar data tetap tersimpan ketika container dihentikan atau dibuat ulang.

Arsitektur:

```text
Internet
   |
   v
Existing Nginx (Host VPS)
   |
   | HTTPS :443
   | SSL handled by Certbot
   |
   | /app-tamu/*
   v
127.0.0.1:8081
   |
   v
Docker Container: app
PHP 8.3 + Apache
   |
   | Internal Docker Network
   v
Docker Container: db
MySQL 8.4
   |
   v
Named Volume: mysql_data
```

### 9.2. Docker Compose

Gunakan `docker-compose.yml` untuk mengatur service aplikasi dan database.

Ketentuan service aplikasi:

- Menggunakan Dockerfile milik aplikasi.
- Menggunakan PHP 8.3 dan Apache.
- Port container Apache `80` dipetakan ke `127.0.0.1:8081` pada host.
- Menggunakan environment variable dari `.env`.
- Terhubung ke service database melalui internal Docker network.
- Memiliki konfigurasi restart policy yang sesuai untuk deployment VPS.

Ketentuan service database:

- Menggunakan MySQL 8.4.
- Menggunakan environment variable untuk kredensial dan nama database.
- Menggunakan named volume `mysql_data`.
- Tidak mempublikasikan port MySQL ke host maupun internet.
- Menggunakan health check agar kesiapan database dapat diperiksa.

### 9.3. Persistent Storage

Gunakan named volume:

`mysql_data`

Tujuannya agar data database tetap tersimpan meskipun container dihentikan, dihapus, atau dibuat ulang.

Jangan menggunakan perintah yang menghapus volume database saat deployment normal.

### 9.4. Environment Configuration

Sediakan:

- `.env`
- `.env.example`

Variabel minimal:

```env
APP_ENV=production
APP_BASE_PATH=/app-tamu

DB_HOST=db
DB_PORT=3306
DB_DATABASE=guest_app
DB_USERNAME=guest_user
DB_PASSWORD=change_this_password
MYSQL_ROOT_PASSWORD=change_root_password
```

Nilai kredensial pada contoh hanya placeholder dan wajib diganti sebelum deployment.

Jangan menyimpan `.env` di repository Git.

### 9.5. File Docker yang Dibutuhkan

Minimal sediakan:

```text
Dockerfile
docker-compose.yml
.dockerignore
.env.example
```

Jika dibutuhkan, sediakan konfigurasi Apache terpisah untuk memastikan document root dan URL routing aplikasi berjalan dengan benar.

## 10. Konfigurasi URL Subdirectory

Aplikasi harus berjalan pada subdirectory `/app-tamu` di domain yang sudah tersedia.

Target publik:

`https://lksbarokah255.my.id/app-tamu`

Bukan:

- `https://app-tamu.lksbarokah255.my.id`
- `https://lksbarokah255.my.id/`

### 10.1. Konfigurasi Base Path Aplikasi

Gunakan satu konfigurasi terpusat:

`APP_BASE_PATH=/app-tamu`

Semua URL internal aplikasi harus menggunakan base path tersebut.

Hal yang wajib diperhatikan:

- URL CSS dan JavaScript.
- URL gambar atau aset statis.
- Form action.
- Fetch API.
- Redirect.
- Link navigasi.
- URL endpoint API.
- URL internal lainnya.

Jangan menggunakan path absolut yang langsung mengarah ke root domain seperti `/assets/...` atau `/api/v1/...` jika path tersebut seharusnya berada di bawah `/app-tamu`.

Contoh URL yang benar:

```text
/app-tamu/assets/css/style.css
/app-tamu/assets/js/app.js
/app-tamu/api/v1/health
/app-tamu/api/v1/guests
```

Pastikan base path tidak ditambahkan dua kali dan tidak menyebabkan URL ganda seperti `/app-tamu/app-tamu/...`.

### 10.2. Konfigurasi Nginx Host VPS

Nginx tetap berjalan langsung di host VPS.

Tambahkan konfigurasi lokasi aplikasi pada server block HTTPS yang sudah melayani:

`lksbarokah255.my.id`

Jangan membuat server block baru.

Contoh konfigurasi:

```nginx
location = /app-tamu {
    return 301 /app-tamu/;
}

location ^~ /app-tamu/ {
    proxy_pass http://127.0.0.1:8081/;
    proxy_http_version 1.1;

    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-Prefix /app-tamu;

    proxy_connect_timeout 10s;
    proxy_send_timeout 30s;
    proxy_read_timeout 30s;
}
```

**Catatan penting mengenai `proxy_pass`:**

Penggunaan trailing slash pada:

`proxy_pass http://127.0.0.1:8081/;`

membuat Nginx meneruskan request dengan menghilangkan prefix `/app-tamu/`.

Contoh:

| Request Publik | Request ke Apache |
|---|---|
| `/app-tamu/` | `/` |
| `/app-tamu/api/v1/health` | `/api/v1/health` |
| `/app-tamu/api/v1/guests` | `/api/v1/guests` |
| `/app-tamu/assets/css/style.css` | `/assets/css/style.css` |

Karena itu, aplikasi PHP dan Apache harus memproses request internal tanpa prefix `/app-tamu`, tetapi menghasilkan URL publik dengan prefix tersebut.

Jangan mengubah konfigurasi root website, SSL, atau lokasi lain yang sudah berjalan.

### 10.3. SSL dan HTTPS

SSL tetap ditangani oleh Nginx pada host VPS menggunakan sertifikat Certbot/Let's Encrypt yang sudah tersedia.

Ketentuan:

- Tidak perlu membuat sertifikat SSL baru.
- Tidak perlu menjalankan Certbot ulang hanya untuk aplikasi ini.
- Container Apache tidak perlu menangani SSL.
- Akses publik tetap menggunakan HTTPS.
- Komunikasi dari Nginx ke container aplikasi menggunakan HTTP melalui localhost.

### 10.4. Pemeriksaan Konfigurasi Nginx

Sebelum melakukan reload:

```bash
sudo nginx -t
```

Jika konfigurasi valid, lakukan reload:

```bash
sudo systemctl reload nginx
```

Jangan melakukan reload jika pemeriksaan konfigurasi gagal.

## 11. Struktur Project

Gunakan struktur sederhana dan mudah dipahami.

Contoh:

```text
guest-app/
├── app/
│   ├── config/
│   │   └── database.php
│   ├── controllers/
│   ├── services/
│   ├── helpers/
│   └── views/
├── public/
│   ├── index.php
│   ├── .htaccess
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   └── api/
├── database/
│   └── schema.sql
├── logs/
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
├── .env
├── .env.example
├── README.md
└── overview.md
```

Struktur tersebut merupakan acuan. AI CLI boleh menyesuaikan detail implementasi selama tetap sederhana, konsisten, dan tidak mengubah ruang lingkup aplikasi.

## 12. Logging

Sediakan logging sederhana untuk membantu proses pengujian dan troubleshooting.

Catat informasi seperti:

- Waktu request.
- HTTP method.
- Endpoint yang diakses.
- HTTP status code.
- Pesan error apabila terjadi kegagalan.

Jangan mencatat password, kredensial database, atau data sensitif lainnya.

Log aplikasi harus dapat diperiksa melalui mekanisme logging yang tersedia di lingkungan Docker.

## 13. Skenario Pengujian

Pengujian harus mencakup positive case, negative case, dan edge case.

### 13.1. Positive Case

| No. | Skenario | Hasil yang Diharapkan |
|---|---|---|
| 1 | Membuka halaman aplikasi | Halaman berhasil ditampilkan |
| 2 | Menambahkan nama tamu valid | Data berhasil disimpan |
| 3 | Menampilkan daftar tamu | Data tampil dalam grid |
| 4 | Memanggil health check | API memberikan HTTP 200 |
| 5 | Memanggil GET guests | API memberikan daftar tamu |
| 6 | Memanggil POST guests dengan nama valid | API memberikan HTTP 201 |
| 7 | Mengakses aplikasi melalui HTTPS | Aplikasi dapat diakses |
| 8 | Mengakses API melalui domain publik | API memberikan response JSON |
| 9 | Mengakses aset CSS dan JavaScript | Aset berhasil dimuat melalui `/app-tamu` |
| 10 | Menghentikan dan menjalankan ulang container | Aplikasi berjalan kembali dan data tetap tersedia |

### 13.2. Negative Case

| No. | Skenario | Hasil yang Diharapkan |
|---|---|---|
| 1 | Mengirim nama kosong | Validasi gagal |
| 2 | Mengirim nama hanya berisi spasi | Validasi gagal |
| 3 | Mengirim nama melebihi 150 karakter | Validasi gagal |
| 4 | Mengirim request tanpa field `name` | API memberikan error validasi |
| 5 | Mengirim JSON tidak valid | API memberikan HTTP 400 |
| 6 | Menggunakan HTTP method yang salah | API memberikan HTTP 405 |
| 7 | Mengakses endpoint yang tidak tersedia | API memberikan HTTP 404 |
| 8 | Database tidak dapat diakses | API memberikan error yang sesuai tanpa membocorkan detail internal |
| 9 | Mengirim input yang mengandung payload SQL Injection | Input tidak dieksekusi sebagai SQL |
| 10 | Mengirim input HTML/JavaScript | Konten tidak dieksekusi saat ditampilkan |

### 13.3. Edge Case

| No. | Skenario | Hasil yang Diharapkan |
|---|---|---|
| 1 | Database belum memiliki data | Aplikasi menampilkan kondisi data kosong |
| 2 | Nama memiliki spasi di awal/akhir | Spasi di-trim |
| 3 | Nama mengandung karakter khusus | Data tetap ditangani dengan aman |
| 4 | Nama mengandung karakter Unicode | Data dapat disimpan dan ditampilkan |
| 5 | Request dikirim berulang kali | Setiap request diproses sesuai validasi |
| 6 | Container aplikasi restart | Aplikasi dapat diakses kembali |
| 7 | Container database restart | Koneksi pulih setelah database siap |
| 8 | Aplikasi diakses tanpa trailing slash | Browser diarahkan ke `/app-tamu/` |
| 9 | Aplikasi diakses melalui subdirectory | Seluruh halaman, aset, dan endpoint tetap menggunakan base path yang benar |

## 14. Deployment Workflow

Deployment dilakukan pada VPS Debian 12.

Urutan deployment:

1. Periksa kondisi VPS dan konfigurasi Nginx yang sedang berjalan.
2. Periksa apakah port `8081` sudah digunakan.
3. Siapkan project aplikasi.
4. Siapkan file `.env` berdasarkan `.env.example`.
5. Build dan jalankan container menggunakan Docker Compose.
6. Pastikan container aplikasi dan database berjalan dengan baik.
7. Uji aplikasi melalui `127.0.0.1:8081` dari host VPS.
8. Tambahkan konfigurasi `/app-tamu` pada server block HTTPS yang sudah ada.
9. Jalankan `nginx -t`.
10. Jika valid, reload Nginx.
11. Uji akses melalui domain publik menggunakan HTTPS.
12. Uji seluruh endpoint API melalui URL publik.

### 14.1. Perintah Docker

Build dan jalankan aplikasi:

```bash
docker compose up -d --build
```

Melihat status container:

```bash
docker compose ps
```

Melihat log aplikasi:

```bash
docker compose logs -f app
```

Melihat log database:

```bash
docker compose logs -f db
```

Menghentikan container:

```bash
docker compose down
```

Perintah `docker compose down` tidak boleh disertai opsi penghapusan volume pada proses penghentian normal.

### 14.2. Pengujian Endpoint

Uji dari host VPS:

```bash
curl -i http://127.0.0.1:8081/
```

Uji health check melalui domain publik:

```bash
curl -i https://lksbarokah255.my.id/app-tamu/api/v1/health
```

Uji pengambilan daftar tamu:

```bash
curl -i https://lksbarokah255.my.id/app-tamu/api/v1/guests
```

Uji penambahan tamu:

```bash
curl -i -X POST \
  https://lksbarokah255.my.id/app-tamu/api/v1/guests \
  -H "Content-Type: application/json" \
  -d '{"name":"Testing Bank Jatim"}'
```

Pastikan response sesuai dengan standar API yang telah ditentukan.

## 15. Definition of Done

Aplikasi dinyatakan selesai apabila:

- Aplikasi menggunakan PHP Native dan MySQL.
- Pengguna dapat menambahkan nama tamu.
- Daftar tamu tampil dalam bentuk grid.
- Data tersimpan di MySQL.
- Endpoint health check dapat diakses.
- Endpoint GET guests dapat diakses.
- Endpoint POST guests dapat digunakan.
- Seluruh endpoint memberikan response JSON yang konsisten.
- Validasi input berjalan.
- Positive case, negative case, dan edge case telah diuji.
- Aplikasi dapat dijalankan menggunakan Docker Compose.
- Data database tetap tersimpan menggunakan named volume.
- MySQL tidak terekspos ke internet.
- Container aplikasi hanya dipublikasikan pada `127.0.0.1:8081`.
- Aplikasi dapat diakses melalui `https://lksbarokah255.my.id/app-tamu`.
- Seluruh aset, form, navigasi, dan endpoint API menggunakan base path `/app-tamu` dengan benar.
- SSL tetap menggunakan konfigurasi Certbot yang sudah ada.
- Konfigurasi Nginx tidak mengganggu website lain yang sudah berjalan.

## 16. Instruksi untuk AI CLI

Sebelum melakukan implementasi:

1. Baca seluruh isi `overview.md`.
2. Pahami tujuan aplikasi dan batasan teknologinya.
3. Periksa kondisi project dan struktur file yang sudah ada.
4. Identifikasi kebutuhan implementasi berdasarkan dokumen ini.
5. Jangan menambahkan fitur di luar ruang lingkup yang ditentukan.
6. Jangan menggunakan framework PHP atau framework frontend tambahan.
7. Jangan melakukan overengineering.
8. Pastikan seluruh URL internal aplikasi konsisten menggunakan `APP_BASE_PATH`.
9. Pastikan konfigurasi Docker, Apache, dan Nginx saling sesuai.
10. Pastikan konfigurasi Nginx yang sudah ada tidak terganggu.

Untuk deployment VPS:

- Lakukan audit konfigurasi Nginx yang sudah berjalan terlebih dahulu.
- Identifikasi server block HTTPS untuk `lksbarokah255.my.id`.
- Periksa apakah sudah terdapat konfigurasi atau lokasi `/app-tamu`.
- Jangan membuat server block baru.
- Jangan mengubah document root website utama.
- Jangan mengubah konfigurasi SSL yang sudah tersedia.
- Jangan mengubah konfigurasi website lain.
- Jangan mengubah port aplikasi tanpa alasan teknis yang jelas.
- Jika terdapat potensi konflik konfigurasi, jelaskan terlebih dahulu sebelum melakukan perubahan.
- Pastikan `nginx -t` berhasil sebelum reload.

**Prioritas utama:** implementasikan aplikasi sesederhana mungkin, pastikan API dapat diakses dari luar server melalui domain yang sudah tersedia, dan pastikan deployment Docker tidak mengganggu layanan lain pada VPS.
