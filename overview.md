# Overview — Guest Application & API Connectivity Tester

## 1. Gambaran Umum

Aplikasi ini merupakan aplikasi web satu halaman (one-page web application) untuk mencatat data tamu, menampilkan daftar tamu, serta menyediakan REST API sebagai endpoint pengujian konektivitas server.

Aplikasi digunakan untuk membantu menguji apakah server VPS dapat diakses oleh pihak eksternal, khususnya Bank Jatim, melalui endpoint API yang disediakan.

Aplikasi memiliki dua fungsi utama:

1. **Guest Application:** Pengunjung mengisi form data tamu dan melihat daftar tamu yang telah tercatat.
2. **API Connectivity Tester:** Menyediakan endpoint REST API untuk menguji aksesibilitas server, penerimaan request, dan pengiriman response.

Aplikasi juga menyediakan pilihan keperluan kunjungan berupa permintaan API data tamu.

Aplikasi ini bukan sistem integrasi transaksi QRIS atau sistem pengelolaan tamu yang kompleks. Fokusnya adalah pencatatan data tamu dan pengujian konektivitas API.

## 2. Tujuan

- Menyediakan aplikasi sederhana untuk mencatat data tamu.
- Menyediakan form data tamu dalam satu halaman.
- Menampilkan daftar tamu dalam bentuk tabel.
- Menyediakan pilihan keperluan kunjungan secara manual maupun dropdown.
- Menyediakan pilihan keperluan `Minta API Data Tamu`.
- Mencatat IP address pengunjung secara otomatis.
- Menyamarkan informasi sensitif pada tampilan tabel.
- Menyediakan REST API yang dapat diakses dari luar server.
- Menjadi sarana pengujian konektivitas antara server VPS dan pihak Bank Jatim.
- Menyediakan konfigurasi Docker agar aplikasi mudah dijalankan dan dipelihara di VPS.

## 3. Teknologi

| Komponen                | Teknologi               |
| ----------------------- | ----------------------- |
| Backend                 | PHP Native 8.3          |
| Frontend                | HTML, CSS, JavaScript   |
| Database                | MySQL 8.4               |
| Database Access         | PDO                     |
| Web Server Container    | Apache                  |
| Containerization        | Docker                  |
| Container Orchestration | Docker Compose          |
| Reverse Proxy           | Nginx (host VPS)        |
| SSL                     | Certbot / Let's Encrypt |
| DNS                     | Cloudflare              |

Tidak menggunakan framework PHP seperti Laravel maupun framework frontend tambahan.

## 4. Target Deployment

Aplikasi akan di-deploy pada VPS Debian 12 yang sudah memiliki Nginx dan Certbot/Let's Encrypt.

Aplikasi berjalan di bawah domain yang sudah tersedia, bukan menggunakan subdomain baru.

**Target URL aplikasi:**

`https://lksbarokah255.my.id/app-tamu`

**Target URL API:**

- Health Check: `https://lksbarokah255.my.id/app-tamu/api/v1/health`
- Get Guests: `https://lksbarokah255.my.id/app-tamu/api/v1/guests`
- Create Guest: `https://lksbarokah255.my.id/app-tamu/api/v1/guests`

Ketentuan deployment:

- Menggunakan domain dan DNS Cloudflare yang sudah tersedia.
- Pengaturan Cloudflare dilakukan secara manual oleh pemilik VPS, cukup dengan menambahkan DNS record yang diperlukan.
- Tidak perlu membuat panduan konfigurasi Cloudflare di dalam dokumentasi deployment.
- Tidak membuat subdomain baru.
- Menggunakan server block Nginx yang sudah tersedia.
- Nginx tetap berjalan langsung pada host VPS.
- Aplikasi PHP dan Apache berjalan di dalam Docker container.
- MySQL berjalan di dalam Docker container.
- Container aplikasi hanya dapat diakses melalui `127.0.0.1:8081` dari host VPS.
- MySQL tidak mengekspos port ke publik.
- Tidak mengubah konfigurasi website lain yang sudah berjalan di VPS.

## 5. Fitur Aplikasi

### 5.1. One-Page Web Application

Aplikasi menggunakan konsep one-page web application. Seluruh fungsi utama berada dalam satu halaman tanpa perlu berpindah ke halaman lain.

Urutan tampilan:

1. Form data tamu.
2. Tabel daftar tamu.

<escape>+--------------------------------------------------+
| FORM DATA TAMU |
| |
| Nama |
| Email |
| Nomor HP |
| |
| Keperluan Kunjungan |
| [ Tulis Manual / Pilih Keperluan ] |
| |
| [ Submit ] |
+--------------------------------------------------+
| DAFTAR TAMU |
| |
| Nama | Email | No. HP | Keperluan | IP | Updated |
| |
| ... |
+--------------------------------------------------+</escape>

Ilustrasi tersebut hanya menggambarkan susunan elemen, bukan ketentuan desain visual.

### 5.2. Form Data Tamu

Form berada pada bagian atas halaman.

Field yang dibutuhkan:

| Field               | Tipe                     | Ketentuan                   |
| ------------------- | ------------------------ | --------------------------- |
| Nama                | Text                     | Wajib diisi                 |
| Email               | Email                    | Wajib diisi dan harus valid |
| Nomor HP            | Telp/Text                | Wajib diisi                 |
| Keperluan Kunjungan | Pilihan dan input manual | Wajib diisi                 |
| IP Address          | Otomatis dari request    | Tidak diisi oleh pengunjung |

#### A. Keperluan Kunjungan

Field keperluan kunjungan menyediakan dua cara pengisian:

1. **Tulis Manual**
   - Pengunjung dapat menuliskan keperluan kunjungan sendiri.
   - Gunakan input teks atau textarea.
   - Nilai yang dimasukkan disimpan sebagai keperluan kunjungan.

2. **Pilih dari Dropdown**
   - Pengunjung dapat memilih keperluan yang telah disediakan.
   - Untuk saat ini, dropdown hanya memiliki satu pilihan:
     - `Minta API Data Tamu`
   - Nilai pilihan disimpan sebagai keperluan kunjungan.

Gunakan mekanisme pemilihan yang jelas, misalnya select atau segmented control, untuk membedakan mode pengisian manual dan dropdown.

Perilaku form:

- Ketika memilih mode manual, tampilkan input keperluan manual.
- Ketika memilih mode dropdown, tampilkan dropdown pilihan keperluan.
- Hanya nilai dari mode yang sedang aktif yang dikirim dan disimpan.
- Pengunjung tidak perlu mengisi kedua mode sekaligus.
- Validasi keperluan kunjungan tetap dilakukan di backend.

Jangan menambahkan pilihan dropdown lain tanpa kebutuhan baru yang jelas.

### 5.3. Fitur Permintaan API Data Tamu

Aplikasi menyediakan fitur untuk mencatat pengunjung yang memiliki keperluan meminta API data tamu.

Alur:

1. Pengunjung mengisi nama, email, dan nomor HP.
2. Pengunjung memilih mode dropdown pada keperluan kunjungan.
3. Pengunjung memilih `Minta API Data Tamu`.
4. Pengunjung mengirim form.
5. Sistem menyimpan data tamu dengan keperluan `Minta API Data Tamu`.
6. Data tersebut muncul pada tabel daftar tamu.

Ketentuan:

- Tidak perlu membuat halaman permintaan API terpisah.
- Tidak perlu membuat form permintaan API terpisah.
- Tidak perlu membuat sistem approval atau pengelolaan permintaan API.
- Tidak perlu membuat sistem API key atau token khusus hanya untuk fitur permintaan ini.
- Permintaan API cukup dicatat melalui field keperluan kunjungan.

Fitur ini merupakan pencatatan keperluan pengunjung, bukan pemberian akses API secara otomatis.

### 5.4. Tabel Daftar Tamu

Tabel berada tepat di bawah form pada halaman yang sama.

Tabel menampilkan data tamu yang telah tersimpan di database.

Kolom:

| Kolom               | Keterangan                                     |
| ------------------- | ---------------------------------------------- |
| Nama                | Nama lengkap pengunjung                        |
| Email               | Email dengan sebagian karakter disensor        |
| Nomor HP            | Nomor HP dengan sebagian digit disensor        |
| Keperluan Kunjungan | Keperluan yang dipilih atau ditulis pengunjung |
| IP Address          | Hanya IP segmen pertama yang tidak disensor    |
| Last Updated        | Waktu terakhir data diperbarui                 |

Contoh tampilan data:

| Nama     | Email              | Nomor HP       | Keperluan           | IP Address     | Last Updated      |
| -------- | ------------------ | -------------- | ------------------- | -------------- | ----------------- |
| John Doe | jo\*\*\*@gmail.com | 08\*\*\*\*1234 | Minta API Data Tamu | 192.**_._**.10 | 12 Nov 2026 09:30 |
| Jane Doe | ja\*\*\*@gmail.com | 08\*\*\*\*5678 | Kunjungan kerja     | 103.**_._**.20 | 12 Nov 2026 10:15 |

Data di atas hanya ilustrasi.

#### Ketentuan Data Masking

Informasi berikut harus disensor pada tampilan tabel:

- Email.
- Nomor HP.
- IP Address.

Aturan masking:

- Email: tampilkan sebagian karakter awal dan domain secara terbatas.
- Nomor HP: tampilkan beberapa digit awal dan akhir, sensor bagian tengah.
- IP Address: sensor segmen 2 - 4 atau digit IP.

Masking hanya berlaku pada tampilan tabel aplikasi.

Data asli tetap disimpan di database agar dapat digunakan untuk kebutuhan pencatatan dan API sesuai kebijakan aksesnya.

Jangan menampilkan data asli melalui HTML, atribut tersembunyi, atau JavaScript yang dapat diperiksa dari browser hanya untuk mengakali tampilan masking.

Untuk API, jangan menganggap masking pada tabel otomatis berlaku sebagai perlindungan API. Tentukan field response sesuai kebutuhan dan kebijakan akses endpoint. Jangan mengirim informasi pribadi secara tidak perlu.

#### Last Updated

Tabel wajib memiliki kolom `Last Updated`.

Kolom ini menggunakan data `updated_at` dari database.

Ketentuan:

- Saat data pertama kali dibuat, `created_at` dan `updated_at` diisi otomatis.
- Jika data diperbarui, `updated_at` berubah mengikuti waktu perubahan terakhir.
- Format waktu pada tabel dibuat mudah dibaca.
- Tidak perlu menambahkan fitur edit data hanya demi menampilkan kolom ini.

### 5.5. REST API

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

Contoh response:

```json
{
  "success": true,
  "message": "Guest data retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "081234567890",
      "visit_purpose": "Minta API Data Tamu",
      "ip_address": "192.168.1.10",
      "created_at": "2026-11-12 09:00:00",
      "updated_at": "2026-11-12 09:30:00"
    }
  ]
}
```

Contoh tersebut merupakan struktur internal yang perlu disesuaikan dengan kebijakan akses data. Informasi pribadi tidak boleh dibuka ke pihak yang tidak berwenang. Jika API ditujukan untuk pengujian akses eksternal, pastikan field yang dikembalikan memang telah disetujui untuk dibagikan.

#### C. Create Guest

`POST /api/v1/guests`

Tujuan:

- Menguji komunikasi HTTP POST.
- Menguji penerimaan request JSON.
- Menguji proses penyimpanan data ke database.

Contoh request:

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "081234567890",
  "visit_purpose": "Minta API Data Tamu"
}
```

IP address dideteksi oleh backend dari request yang diterima server. Jangan menerima IP address dari field request sebagai sumber yang tepercaya.

Contoh response:

```json
{
  "success": true,
  "message": "Guest created successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "081234567890",
    "visit_purpose": "Minta API Data Tamu",
    "created_at": "2026-11-12 09:00:00",
    "updated_at": "2026-11-12 09:00:00"
  }
}
```

### 5.6. Standar Response API

Semua endpoint API menggunakan format JSON yang konsisten.

Struktur dasar:

```json
{
  "success": true,
  "message": "Response message",
  "data": {}
}
```

Ketentuan HTTP status:

| Status | Penggunaan                           |
| ------ | ------------------------------------ |
| 200    | Request berhasil                     |
| 201    | Data berhasil dibuat                 |
| 400    | Request tidak valid                  |
| 404    | Endpoint tidak ditemukan             |
| 405    | HTTP method tidak didukung           |
| 422    | Validasi gagal                       |
| 500    | Internal server error                |
| 503    | Layanan atau database tidak tersedia |

Pastikan header response API menggunakan `Content-Type: application/json`.

## 6. Database

Database menggunakan MySQL 8.4.

Nama database dan kredensial harus dikonfigurasi melalui environment variable.

### 6.1. Tabel Guests

Nama tabel: `guests`

| Kolom         | Tipe Data       | Keterangan                            |
| ------------- | --------------- | ------------------------------------- |
| id            | BIGINT UNSIGNED | Primary Key, Auto Increment           |
| name          | VARCHAR(150)    | Nama tamu                             |
| email         | VARCHAR(255)    | Email tamu                            |
| phone         | VARCHAR(30)     | Nomor HP tamu                         |
| visit_purpose | TEXT            | Keperluan kunjungan                   |
| ip_address    | VARCHAR(45)     | IP address pengunjung, mendukung IPv4 |
| created_at    | TIMESTAMP       | Waktu data dibuat                     |
| updated_at    | TIMESTAMP       | Waktu terakhir data diperbarui        |

Ketentuan:

- `id` sebagai primary key.
- `name`, `email`, `phone`, dan `visit_purpose` wajib diisi.
- Email harus divalidasi formatnya.
- `ip_address` dicatat otomatis oleh backend.
- Gunakan `VARCHAR(45)` untuk mendukung IPv4 dan IPv6.
- `created_at` dan `updated_at` dikelola otomatis.
- Jangan menambahkan kolom atau tabel lain tanpa kebutuhan yang jelas.

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

### 7.1. Validasi Form

| Field               | Validasi                                                |
| ------------------- | ------------------------------------------------------- |
| Nama                | Wajib, string, maksimal 150 karakter                    |
| Email               | Wajib, format email valid, maksimal 255 karakter        |
| Nomor HP            | Wajib, format nomor HP yang wajar, maksimal 30 karakter |
| Keperluan Kunjungan | Wajib                                                   |
| Keperluan Manual    | Wajib diisi jika mode manual dipilih                    |
| Dropdown Keperluan  | Hanya menerima pilihan yang telah ditentukan            |
| IP Address          | Diambil dari request server, bukan input pengunjung     |

Jika mode dropdown dipilih, backend hanya menerima nilai yang telah diizinkan, yaitu `Minta API Data Tamu`.

Jika mode manual dipilih, backend menerima teks keperluan yang telah melalui validasi panjang dan sanitasi yang sesuai.

Jangan hanya mengandalkan validasi JavaScript. Backend tetap menjadi sumber validasi utama.

### 7.2. Penanganan Error

Aplikasi harus menangani kondisi berikut:

- Database tidak dapat dihubungi.
- Query database gagal.
- Request JSON tidak valid.
- Request tidak memiliki field wajib.
- Nama kosong.
- Email tidak valid.
- Nomor HP tidak valid.
- Keperluan kunjungan kosong.
- Pilihan dropdown tidak dikenal.
- Request menggunakan HTTP method yang salah.
- Endpoint tidak ditemukan.
- Terjadi internal server error.

Jangan menampilkan detail error database, kredensial, maupun informasi internal server kepada pengguna atau pihak eksternal.

## 8. Keamanan Dasar dan Privasi

Terapkan keamanan dasar meskipun aplikasi digunakan untuk pengujian konektivitas.

- Gunakan PDO prepared statements untuk mencegah SQL Injection.
- Lakukan HTML escaping saat menampilkan data tamu.
- Validasi seluruh input dari frontend maupun API.
- Jangan menyimpan kredensial database langsung di source code.
- Gunakan file `.env` untuk konfigurasi sensitif.
- Jangan memasukkan `.env` ke Git.
- Sediakan `.env.example` tanpa kredensial asli.
- Gunakan error handling yang tidak membocorkan informasi internal.
- Batasi akses MySQL hanya melalui jaringan internal Docker.
- Jangan mengekspos port database ke internet.
- Terapkan CSRF token untuk form HTML.
- Jangan mempercayai IP dari header request yang dapat dimanipulasi sembarang klien.

### 8.1. IP Address Detection

IP address dicatat otomatis oleh backend.

Ketentuan:

- Jika aplikasi menerima request langsung, gunakan alamat remote client.
- Jika request melewati reverse proxy yang dipercaya, gunakan konfigurasi trusted proxy untuk mendapatkan IP client sebenarnya.
- Jika Cloudflare menggunakan proxy, konfigurasi Nginx harus mempercayai header IP Cloudflare hanya dari rentang IP Cloudflare yang resmi dan terpelihara.
- Jangan mengambil `X-Forwarded-For` atau `CF-Connecting-IP` secara sembarang tanpa memvalidasi sumber proxy.
- Jangan menerima IP address dari body request sebagai sumber yang tepercaya.

Tujuannya agar IP yang disimpan tidak sekadar IP proxy dan tidak mudah dipalsukan oleh pengunjung.

### 8.2. Data Masking

Data asli tetap disimpan di database. Masking diterapkan pada tampilan tabel.

Email, nomor HP, dan IP address harus disensor secara konsisten.

Jangan mengirim data sensitif ke frontend jika tidak diperlukan untuk menampilkan tabel.

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
   - Menyimpan data tamu.
   - Menggunakan named volume agar data tetap tersimpan ketika container dihentikan atau dibuat ulang.

Arsitektur:

```text
Internet
   |
   v
Cloudflare DNS / Proxy (sesuai konfigurasi DNS)
   |
   v
Existing Nginx (Host VPS)
   |
   | HTTPS :443
   | SSL handled by existing Certbot
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
- Memiliki restart policy yang sesuai untuk deployment VPS.

Ketentuan service database:

- Menggunakan MySQL 8.4.
- Menggunakan environment variable untuk kredensial dan nama database.
- Menggunakan named volume `mysql_data`.
- Tidak mempublikasikan port MySQL ke host maupun internet.
- Menggunakan health check agar kesiapan database dapat diperiksa.

### 9.3. Persistent Storage

Gunakan named volume:

`mysql_data`

Data database harus tetap tersedia meskipun container dihentikan, dihapus, atau dibuat ulang.

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

### 9.5. File Docker

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

### 10.1. Konfigurasi Base Path

Gunakan konfigurasi terpusat:

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

Contoh URL publik yang benar:

```text
/app-tamu/assets/css/style.css
/app-tamu/assets/js/app.js
/app-tamu/api/v1/health
/app-tamu/api/v1/guests
```

Jangan menggunakan path absolut yang langsung mengarah ke root domain seperti `/assets/...` atau `/api/v1/...` jika path tersebut seharusnya berada di bawah `/app-tamu`.

Pastikan base path tidak ditambahkan dua kali.

### 10.2. Konfigurasi Nginx Host VPS

Nginx tetap berjalan langsung di host VPS.

Tambahkan konfigurasi lokasi aplikasi pada server block HTTPS yang sudah melayani `lksbarokah255.my.id`.

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

Penggunaan trailing slash pada `proxy_pass` membuat Nginx menghilangkan prefix `/app-tamu/` ketika meneruskan request ke Apache.

Contoh:

| Request Publik                   | Request ke Apache       |
| -------------------------------- | ----------------------- |
| `/app-tamu/`                     | `/`                     |
| `/app-tamu/api/v1/health`        | `/api/v1/health`        |
| `/app-tamu/api/v1/guests`        | `/api/v1/guests`        |
| `/app-tamu/assets/css/style.css` | `/assets/css/style.css` |

Aplikasi PHP dan Apache memproses request internal tanpa prefix `/app-tamu`, tetapi harus menghasilkan URL publik dengan prefix tersebut.

Jika Cloudflare proxy aktif, konfigurasi trusted proxy dan real IP harus diperhatikan agar IP client dapat dicatat dengan benar. Jangan mengubah konfigurasi global Nginx tanpa audit terlebih dahulu.

### 10.3. SSL dan HTTPS

SSL tetap ditangani oleh Nginx pada host VPS menggunakan sertifikat Certbot/Let's Encrypt yang sudah tersedia.

Ketentuan:

- Tidak perlu membuat sertifikat SSL baru.
- Tidak perlu menjalankan Certbot ulang hanya untuk aplikasi ini.
- Container Apache tidak perlu menangani SSL.
- Akses publik tetap menggunakan HTTPS.
- Komunikasi dari Nginx ke container aplikasi menggunakan HTTP melalui localhost.

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
├── overview.md
├── deploy.md
└── README.md
```

Struktur dapat disesuaikan selama tetap sederhana, konsisten, dan tidak mengubah ruang lingkup aplikasi.

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

| No. | Skenario                                | Hasil yang Diharapkan                                       |
| --- | --------------------------------------- | ----------------------------------------------------------- |
| 1   | Membuka halaman aplikasi                | Halaman one-page berhasil ditampilkan                       |
| 2   | Mengisi seluruh form dengan data valid  | Data berhasil disimpan                                      |
| 3   | Mengisi keperluan secara manual         | Keperluan tersimpan sesuai input                            |
| 4   | Memilih `Minta API Data Tamu`           | Keperluan tersimpan sesuai pilihan                          |
| 5   | Menampilkan daftar tamu                 | Data tampil dalam tabel                                     |
| 6   | Menampilkan email, nomor HP, dan IP     | Ketiganya tampil dalam kondisi tersensor                    |
| 7   | Menampilkan Last Updated                | Waktu pembaruan tampil dengan benar                         |
| 8   | Memanggil health check                  | API memberikan HTTP 200                                     |
| 9   | Memanggil GET guests                    | API memberikan daftar tamu sesuai kebijakan akses           |
| 10  | Memanggil POST guests dengan data valid | API memberikan HTTP 201                                     |
| 11  | Mengakses aplikasi melalui HTTPS        | Aplikasi dapat diakses                                      |
| 12  | Mengakses aset melalui subdirectory     | Seluruh aset berhasil dimuat                                |
| 13  | Restart container aplikasi              | Aplikasi berjalan kembali                                   |
| 14  | Restart container database              | Koneksi pulih setelah database siap dan data tetap tersedia |

### 13.2. Negative Case

| No. | Skenario                               | Hasil yang Diharapkan                                  |
| --- | -------------------------------------- | ------------------------------------------------------ |
| 1   | Nama kosong                            | Validasi gagal                                         |
| 2   | Email kosong                           | Validasi gagal                                         |
| 3   | Email tidak valid                      | Validasi gagal                                         |
| 4   | Nomor HP kosong                        | Validasi gagal                                         |
| 5   | Nomor HP tidak valid                   | Validasi gagal                                         |
| 6   | Keperluan kunjungan kosong             | Validasi gagal                                         |
| 7   | Pilihan dropdown tidak dikenal         | Request ditolak                                        |
| 8   | Mengirim request tanpa field wajib     | API memberikan error validasi                          |
| 9   | Mengirim JSON tidak valid              | API memberikan HTTP 400                                |
| 10  | Menggunakan HTTP method yang salah     | API memberikan HTTP 405                                |
| 11  | Mengakses endpoint yang tidak tersedia | API memberikan HTTP 404                                |
| 12  | Database tidak dapat diakses           | API memberikan error tanpa membocorkan detail internal |
| 13  | Input mengandung payload SQL Injection | Input tidak dieksekusi sebagai SQL                     |
| 14  | Input mengandung HTML/JavaScript       | Konten tidak dieksekusi saat ditampilkan               |

### 13.3. Edge Case

| No. | Skenario                              | Hasil yang Diharapkan                         |
| --- | ------------------------------------- | --------------------------------------------- |
| 1   | Database belum memiliki data          | Tabel menampilkan kondisi kosong              |
| 2   | Nama memiliki spasi di awal/akhir     | Spasi di-trim                                 |
| 3   | Email memiliki huruf kapital          | Ditangani secara konsisten                    |
| 4   | Nomor HP memiliki format berbeda      | Ditangani sesuai aturan validasi              |
| 5   | Keperluan manual sangat panjang       | Ditolak jika melewati batas yang ditentukan   |
| 6   | Keperluan mengandung karakter khusus  | Data ditampilkan dengan aman                  |
| 7   | Nama mengandung karakter Unicode      | Data dapat disimpan dan ditampilkan           |
| 8   | IP menggunakan IPv6                   | IP dapat disimpan                             |
| 9   | Request dikirim berulang kali         | Setiap request diproses sesuai validasi       |
| 10  | Data diperbarui                       | `updated_at` berubah                          |
| 11  | Aplikasi diakses tanpa trailing slash | Browser diarahkan ke `/app-tamu/`             |
| 12  | Aplikasi diakses melalui subdirectory | Form, aset, navigasi, dan API tetap berfungsi |

## 14. Dokumentasi Project

AI CLI wajib menghasilkan tiga dokumen utama:

### 14.1. `overview.md`

Dokumen ini berisi gambaran dan spesifikasi aplikasi, meliputi:

- Tujuan aplikasi.
- Teknologi.
- Fitur.
- Struktur halaman.
- Form data tamu.
- Tabel daftar tamu.
- REST API.
- Database.
- Validasi.
- Keamanan.
- Docker setup.
- Target deployment.
- Skenario pengujian.
- Definition of Done.

`overview.md` menjadi acuan utama pengembangan aplikasi.

### 14.2. `deploy.md`

AI CLI wajib membuat dokumen khusus deployment bernama `deploy.md`.

Dokumen ini berisi panduan deployment aplikasi pada VPS, terpisah dari `overview.md`.

Panduan hanya mencakup proses di sisi VPS. Konfigurasi Cloudflare tidak perlu dijelaskan secara rinci karena pengaturan DNS record dilakukan secara manual oleh pemilik VPS.

Isi minimal `deploy.md`:

1. Persiapan VPS Debian 12.
2. Pemeriksaan kebutuhan dan kondisi server.
3. Pemeriksaan Docker dan Docker Compose.
4. Persiapan source code aplikasi.
5. Konfigurasi `.env`.
6. Build dan menjalankan Docker Compose.
7. Pemeriksaan container aplikasi dan database.
8. Pemeriksaan persistent volume.
9. Konfigurasi Nginx pada server block yang sudah ada.
10. Konfigurasi reverse proxy untuk `/app-tamu`.
11. Penanganan trusted proxy dan IP address jika diperlukan.
12. Pemeriksaan konfigurasi Nginx menggunakan `nginx -t`.
13. Reload Nginx secara aman.
14. Pengujian aplikasi melalui localhost.
15. Pengujian aplikasi melalui domain publik.
16. Pengujian endpoint API.
17. Pemeriksaan log aplikasi dan database.
18. Prosedur update atau redeployment aplikasi.
19. Prosedur restart dan troubleshooting.
20. Catatan keamanan dan backup database.

Ketentuan penulisan `deploy.md`:

- Ditujukan untuk pengguna yang masih belajar deployment VPS.
- Gunakan langkah berurutan dan instruksi yang dapat langsung dijalankan.
- Sertakan command yang diperlukan.
- Jelaskan fungsi command secara singkat.
- Bedakan perintah yang dijalankan di VPS dengan konfigurasi yang harus diedit.
- Jelaskan lokasi file konfigurasi yang perlu diperiksa.
- Jangan mengasumsikan server masih kosong.
- Audit kondisi Nginx, Docker, port, dan layanan yang sudah berjalan sebelum perubahan.
- Jangan membuat konfigurasi yang dapat mengganggu website lain.
- Jangan menghapus database atau Docker volume saat proses update normal.
- Sertakan cara verifikasi setelah setiap tahap penting.
- Jika ada konfigurasi yang berpotensi mengganggu layanan lain, jelaskan risikonya sebelum menyarankan perubahan.

### 14.3. `README.md`

Berisi informasi singkat project, kebutuhan menjalankan aplikasi secara lokal, dan referensi menuju `overview.md` serta `deploy.md`.

Jangan menduplikasi seluruh isi kedua dokumen tersebut.

## 15. Deployment Workflow

Gambaran umum deployment pada VPS:

1. Pemilik VPS memastikan DNS record domain sudah diarahkan ke VPS melalui Cloudflare.
2. Periksa kondisi VPS Debian 12.
3. Periksa Nginx dan SSL yang sudah berjalan.
4. Pastikan Docker dan Docker Compose tersedia.
5. Siapkan source code aplikasi.
6. Siapkan `.env`.
7. Build dan jalankan container.
8. Pastikan container aplikasi dan database berjalan.
9. Uji aplikasi melalui `127.0.0.1:8081`.
10. Tambahkan konfigurasi `/app-tamu` pada server block HTTPS yang sudah tersedia.
11. Periksa konfigurasi menggunakan `nginx -t`.
12. Jika valid, reload Nginx.
13. Uji aplikasi melalui URL publik.
14. Uji seluruh endpoint API.
15. Periksa logging dan pastikan aplikasi berjalan dengan benar.

Detail langkah dan command deployment wajib ditulis di `deploy.md`, bukan dijabarkan panjang di `overview.md`.

## 16. Definition of Done

Aplikasi dinyatakan selesai apabila:

- Aplikasi menggunakan PHP Native dan MySQL.
- Aplikasi menggunakan konsep one-page web.
- Form data tamu tersedia di bagian atas halaman.
- Tabel daftar tamu tersedia di bawah form.
- Form memiliki field nama, email, nomor HP, dan keperluan kunjungan.
- Keperluan kunjungan mendukung input manual dan dropdown.
- Dropdown hanya menyediakan pilihan `Minta API Data Tamu`.
- Data IP address dicatat otomatis oleh backend.
- Email, nomor HP, dan IP address disensor pada tabel.
- Tabel menampilkan kolom Last Updated.
- Data tersimpan di MySQL.
- Endpoint health check dapat diakses.
- Endpoint GET guests dapat diakses.
- Endpoint POST guests dapat digunakan.
- Seluruh endpoint memberikan response JSON yang konsisten.
- Validasi frontend dan backend berjalan.
- Keamanan dasar diterapkan.
- Positive case, negative case, dan edge case telah diuji.
- Aplikasi dapat dijalankan menggunakan Docker Compose.
- Data database tetap tersimpan menggunakan named volume.
- MySQL tidak terekspos ke internet.
- Container aplikasi hanya dipublikasikan pada `127.0.0.1:8081`.
- Aplikasi dapat diakses melalui `https://lksbarokah255.my.id/app-tamu`.
- Seluruh aset, form, navigasi, dan endpoint API menggunakan base path `/app-tamu`.
- SSL tetap menggunakan konfigurasi yang sudah ada.
- Konfigurasi Nginx tidak mengganggu website lain.
- `overview.md`, `deploy.md`, dan `README.md` tersedia dan sesuai implementasi.

## 17. Instruksi untuk AI CLI

Sebelum melakukan implementasi:

1. Baca seluruh isi `overview.md`.
2. Pahami tujuan aplikasi dan batasan teknologinya.
3. Periksa kondisi project dan struktur file yang sudah ada.
4. Identifikasi kebutuhan implementasi berdasarkan dokumen ini.
5. Jangan menambahkan fitur di luar ruang lingkup.
6. Jangan menggunakan framework PHP atau framework frontend tambahan.
7. Jangan melakukan overengineering.
8. Pastikan seluruh URL internal konsisten menggunakan `APP_BASE_PATH`.
9. Pastikan konfigurasi Docker, Apache, dan Nginx saling sesuai.
10. Pastikan masking data benar-benar diterapkan pada data yang dikirim ke frontend.
11. Pastikan pencatatan IP memperhatikan trusted proxy.
12. Buat `deploy.md` sebagai panduan deployment VPS yang terpisah.
13. Buat `README.md` sebagai dokumentasi pengantar.
14. Pastikan dokumentasi sesuai dengan implementasi aktual, bukan sekadar menyalin rencana.

Untuk deployment VPS:

- Lakukan audit konfigurasi Nginx yang sudah berjalan terlebih dahulu.
- Identifikasi server block HTTPS untuk `lksbarokah255.my.id`.
- Periksa apakah sudah terdapat konfigurasi atau lokasi `/app-tamu`.
- Jangan membuat server block baru.
- Jangan mengubah document root website utama.
- Jangan mengubah konfigurasi SSL yang sudah tersedia.
- Jangan mengubah konfigurasi website lain.
- Jangan mengubah port aplikasi tanpa alasan teknis yang jelas.
- Jika terdapat potensi konflik konfigurasi, jelaskan terlebih dahulu.
- Pastikan `nginx -t` berhasil sebelum reload.

**Prioritas utama:** buat aplikasi one-page yang sederhana, dapat mencatat data tamu dengan benar, menjaga privasi informasi pada tampilan tabel, mencatat keperluan permintaan API, dan menyediakan endpoint yang dapat diuji dari luar server melalui domain yang sudah tersedia.
