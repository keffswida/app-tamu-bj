# Panduan Deployment — Buku Tamu di VPS (Debian 12)

Panduan langkah demi langkah untuk men-deploy aplikasi pada VPS Debian 12
yang **sudah menjalankan Nginx dan Certbot/Let's Encrypt** untuk domain
`lksbarokah255.my.id`. Aplikasi diakses publik lewat subdirectory:

```
https://lksbarokah255.my.id/app-tamu
```

Ditujukan untuk pengguna yang masih belajar deployment VPS. Baca seluruh
panduan sebelum mulai, dan jalankan langkah secara berurutan.

> **Ringkasan arsitektur:**
>
> Internet → Cloudflare DNS → Nginx (host VPS, HTTPS) → `127.0.0.1:8081` →
> container `app` (PHP 8.3 + Apache) → container `db` (MySQL 8.4, tanpa port
> publik) → named volume `mysql_data`.

---

## 0. Prinsip Keamanan Sebelum Mulai

- **Jangan** membuat server block Nginx baru. Tambahkan `location` pada
  server block HTTPS yang sudah melayani `lksbarokah255.my.id`.
- **Jangan** mengubah document root, konfigurasi SSL, atau konfigurasi
  website lain yang sudah berjalan.
- **Jangan** menghapus volume `mysql_data` saat update normal.
- **Jangan** mengekspos port MySQL ke publik.
- Selalu jalankan `nginx -t` sebelum reload Nginx.
- Perintah berbahaya (`rm`, drop database, hapus volume) ditandai dengan
  ⚠️ dan **jangan pernah dijalankan pada deployment normal**.

---

## 1. Persiapan VPS Debian 12

Masuk ke VPS sebagai user yang punya akses `sudo`:

```bash
ssh user@IP_VPS
```

Pastikan sistem ter-update (aman, tidak mengubah konfigurasi yang ada):

```bash
sudo apt update
sudo apt upgrade -y
```

Verifikasi versi sistem:

```bash
cat /etc/os-release   # pastikan Debian 12 (bookworm)
```

---

## 2. Audit Kondisi Server (Wajib Sebelum Mengubah Apa Pun)

Server **tidak diasumsikan kosong** — periksa dulu apa yang sudah berjalan.

### 2.1 Nginx

```bash
nginx -v                          # versi Nginx
sudo systemctl status nginx       # Nginx harus aktif (running)
sudo nginx -T | grep -n "server_name"   # daftar server block yang ada
```

- Cari server block yang melayani `lksbarokah255.my.id` (biasanya di
  `/etc/nginx/sites-available/` atau `/etc/nginx/conf.d/`).
- Catat file konfigurasinya — file itu yang akan kita edit nanti.

### 2.2 Port yang sudah terpakai

```bash
sudo ss -tlnp
```

Pastikan `8081` **tidak** dipakai aplikasi lain, dan `3306` tidak sedang
dipakai di host (MySQL akan berjalan di container tanpa port host).

### 2.3 Docker & Docker Compose

```bash
docker --version
docker compose version
```

Jika belum ada, lanjut ke bagian 3. Jika sudah ada, langsung ke bagian 4.

---

## 3. Install Docker & Docker Compose (Hanya Jika Belum Ada)

Pasang dari repository resmi Docker untuk Debian:

```bash
# Install dependensi
sudo apt update
sudo apt install -y ca-certificates curl gnupg

# Tambahkan GPG key dan repository resmi Docker
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/debian/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian $(. /etc/os-release && echo $VERSION_CODENAME) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker Engine + plugin Compose
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Izinkan user saat ini menjalankan docker (login ulang setelah ini)
sudo usermod -aG docker $USER
```

Keluar lalu masuk kembali ke SSH agar izin grup berlaku, lalu verifikasi:

```bash
docker --version
docker compose version
```

---

## 4. Siapkan Source Code Aplikasi

Letakkan source di lokasi yang stabil, mis. `/opt/app-tamu`:

```bash
sudo mkdir -p /opt/app-tamu
sudo chown $USER:$USER /opt/app-tamu

# Jika source di repository Git:
cd /opt/app-tamu
git clone URL_REPOSITORY .     # atau salin file project ke sini (scp/rsync)
```

Struktur yang diharapkan (lihat README.md):

```text
/opt/app-tamu/
├── app/  public/  database/  docker/
├── Dockerfile  docker-compose.yml  .env.example
├── overview.md  deploy.md  README.md
└── .env          (dibuat pada langkah berikutnya)
```

Verifikasi file penting ada:

```bash
ls /opt/app-tamu/Dockerfile /opt/app-tamu/docker-compose.yml /opt/app-tamu/.env.example
```

---

## 5. Konfigurasi `.env`

```bash
cd /opt/app-tamu
cp .env.example .env
nano .env
```

Isi yang wajib diganti:

| Variabel              | Isi                                                              |
| --------------------- | ---------------------------------------------------------------- |
| `DB_PASSWORD`         | Password aplikasi MySQL (buat yang kuat, mis. `openssl rand -hex 16`) |
| `MYSQL_ROOT_PASSWORD` | Password root MySQL (buat yang kuat dan beda dari di atas)       |
| `DB_DATABASE`         | `guest_app` (boleh dibiarkan)                                    |
| `DB_USERNAME`         | `guest_user` (boleh dibiarkan)                                   |
| `APP_BASE_PATH`       | `/app-tamu` (wajib)                                              |

Contoh perintah untuk membuat password acak:

```bash
openssl rand -hex 16
```

Periksa izin file (kredensial, jangan bisa dibaca semua orang):

```bash
chmod 600 .env
```

> File `.env` tidak boleh masuk Git (sudah di-ignore lewat `.gitignore`).
> Simpan salinan kredensial di tempat aman (mis. password manager).

---

## 6. Build dan Jalankan Docker Compose

```bash
cd /opt/app-tamu
docker compose up -d --build
```

Penjelasan: `--build` membuat image aplikasi dari Dockerfile, `-d` menjalankan
container di background. Saat pertama kali, image `mysql:8.4` akan diunduh
(butuh waktu).

---

## 7. Periksa Container Aplikasi dan Database

```bash
docker compose ps
```

Yang diharapkan: container `guest-app` dan `guest-db` berstatus
`Up (healthy)` / `Up`. Jika status `restarting` atau `unhealthy`, lihat log:

```bash
docker compose logs app      # log aplikasi (Apache + migrasi schema)
docker compose logs db       # log MySQL
```

Entrypoint container `app` menjalankan migrasi schema otomatis
(`database/migrate.php`) dan menunggu MySQL siap dengan retry — log migrasi
yang berhasil terlihat seperti:

```
[migrate] Schema database berhasil diterapkan.
```

> **Penting:** port publik yang dibuka hanya `127.0.0.1:8081` (localhost
> saja). MySQL tidak punya port host sama sekali. Ini sesuai rancangan —
> akses MySQL hanya lewat jaringan internal Docker.

---

## 8. Periksa Persistent Volume

```bash
docker volume ls                          # pastikan ada mysql_data
docker volume inspect app-tamu-bj_mysql_data   # nama bisa mengandung prefix folder
```

Data MySQL tersimpan di named volume sehingga **tetap ada** ketika container
dihentikan, dihapus, atau dibuat ulang.

> ⚠️ **Jangan pernah** menjalankan `docker compose down -v` pada deployment
> normal — `-v` akan **menghapus seluruh data tamu**.

---

## 9. Uji Aplikasi Melalui Localhost (Sebelum Sentuh Nginx)

Aplikasi harus terbukti berfungsi dulu sebelum dikonfigurasi ke domain:

```bash
# Halaman utama (Apache menerima prefix /app-tamu secara langsung)
curl -sS http://127.0.0.1:8081/app-tamu/ | head -20

# Health check API
curl -sS http://127.0.0.1:8081/api/v1/health
# Diharapkan: {"success":true,"message":"Application is running",...}
```

Jika halaman tidak muncul:

```bash
docker compose logs app | tail -50
```

Penyebab umum: MySQL belum siap (tunggu & coba lagi), kredensial `.env`
salah, atau port `8081` dipakai proses lain (lihat bagian 2.2).

---

## 10. Konfigurasi Nginx (Server Block yang Sudah Ada)

### 10.1 Temukan dan backup file server block

Dari audit bagian 2.1, ketahui file server block HTTPS untuk
`lksbarokah255.my.id` (contoh: `/etc/nginx/sites-available/lksbarokah255`).

```bash
# Selalu backup sebelum mengedit
sudo cp /etc/nginx/sites-available/NAMA_FILE /etc/nginx/sites-available/NAMA_FILE.bak-$(date +%F)
```

### 10.2 Tambahkan `location` untuk `/app-tamu`

Edit file server block tersebut:

```bash
sudo nano /etc/nginx/sites-available/NAMA_FILE
```

**Di dalam** blok `server { ... }` yang sudah ada (yang punya `listen 443 ssl`
dan `server_name lksbarokah255.my.id`), tambahkan:

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

Penjelasan singkat:

- `location = /app-tamu` → redirect tanpa slash ke `/app-tamu/` agar URL
  konsisten.
- `proxy_pass .../` dengan **trailing slash** → Nginx menghapus prefix
  `/app-tamu/` saat meneruskan request ke Apache (mis. `/app-tamu/api/v1/health`
  menjadi `/api/v1/health`).
- `X-Forwarded-Prefix` → dipakai aplikasi untuk membuat URL publik yang
  benar (asset, form action, endpoint API).
- `X-Real-IP` → dipakai aplikasi untuk mencatat IP pengunjung.

**Jangan** membuat `server { }` baru, jangan ubah baris `listen`/`ssl`,
jangan ubah `location /` yang melayani website utama.

---

## 11. Trusted Proxy & IP Address (Jika Cloudflare Proxy Aktif)

Aplikasi hanya mempercayai header `X-Real-IP` jika request datang dari
`127.0.0.1` (Nginx). Jika DNS di Cloudflare memakai mode **Proxied**
(ikon awan oranye), maka Nginx harus diberi tahu rentang IP resmi
Cloudflare agar `$remote_addr` menjadi IP pengunjung asli, bukan IP edge
Cloudflare.

Tambahkan ini **di dalam** `location ^~ /app-tamu/` yang sama (bukan global):

```nginx
    # Rentang IP resmi Cloudflare (perbarui dari
    # https://www.cloudflare.com/ips/ bila berubah)
    set_real_ip_from 173.245.48.0/20;
    set_real_ip_from 103.21.244.0/22;
    set_real_ip_from 103.22.200.0/22;
    set_real_ip_from 103.31.4.0/22;
    set_real_ip_from 141.101.64.0/18;
    set_real_ip_from 108.162.192.0/18;
    set_real_ip_from 190.93.240.0/20;
    set_real_ip_from 188.114.96.0/20;
    set_real_ip_from 197.234.240.0/22;
    set_real_ip_from 198.41.128.0/17;
    set_real_ip_from 162.158.0.0/15;
    set_real_ip_from 104.16.0.0/13;
    set_real_ip_from 104.24.0.0/14;
    set_real_ip_from 172.64.0.0/13;
    set_real_ip_from 131.0.72.0/22;
    real_ip_header CF-Connecting-IP;
```

> **Risiko yang perlu dipahami:** memercayai rentang IP ini berarti klien
> mana pun yang berada di jaringan Cloudflare bisa memalsukan IP yang
> tercatat. Itu sebabnya hanya rentang resmi Cloudflare yang dipercaya, dan
> konfigurasi diletakkan khusus di location aplikasi ini — tidak mengubah
> perilaku website lain.
>
> Jika Cloudflare memakai mode **DNS only** (ikon abu-abu), **lewati bagian
> ini** — IP yang tercatat sudah benar tanpa konfigurasi tambahan.

---

## 12. Periksa Konfigurasi Nginx & Reload Aman

```bash
sudo nginx -t
```

`nginx -t` memeriksa seluruh konfigurasi (termasuk website lain). Output
harus:

```
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

Jika ada error: baca pesannya, perbaiki, jalankan `nginx -t` lagi.
**Jangan reload sebelum test sukses.** Setelah sukses:

```bash
sudo systemctl reload nginx
```

`reload` memuat ulang konfigurasi tanpa memutus koneksi yang sedang berjalan.

> Jika `nginx -t` melaporkan error yang tidak terkait aplikasi ini (mis.
> kesalahan pada server block website lain), **jangan mencoba memperbaiki
> website lain** tanpa persetujuan pemiliknya — konsultasikan dulu.

---

## 13. Uji Melalui Domain Publik

```bash
# Halaman aplikasi
curl -sSI https://lksbarokah255.my.id/app-tamu/ | head -5
# Diharapkan HTTP/2 200

# Tanpa trailing slash harus redirect ke /app-tamu/
curl -sSI https://lksbarokah255.my.id/app-tamu | head -5
# Diharapkan HTTP/2 301 dengan Location: /app-tamu/

# Aset statis ikut terload
curl -sS -o /dev/null -w "%{http_code}\n" https://lksbarokah255.my.id/app-tamu/assets/css/style.css
# Diharapkan 200
```

Buka juga lewat browser: `https://lksbarokah255.my.id/app-tamu/` — form,
tabel, CSS, dan JavaScript harus tampil normal (tanpa error console).

---

## 14. Uji Seluruh Endpoint API

### Health check

```bash
curl -sS https://lksbarokah255.my.id/app-tamu/api/v1/health
# {"success":true,"message":"Application is running","data":{"application":"Guest App","database":"connected"}}
```

### GET guests

```bash
curl -sS https://lksbarokah255.my.id/app-tamu/api/v1/guests
# {"success":true,"message":"Guest data retrieved successfully","data":[...]}
# Perhatikan: email/phone/ip_address dalam kondisi ter-mask.
```

### POST guests (simpan tamu)

```bash
curl -sS -X POST https://lksbarokah255.my.id/app-tamu/api/v1/guests \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","phone":"081234567890","visit_purpose":"Minta API Data Tamu"}'
# {"success":true,"message":"Guest created successfully","data":{...}, ...} — status 201
```

### Uji penanganan error

```bash
# JSON tidak valid -> 400
curl -sS -o /dev/null -w "%{http_code}\n" -X POST https://lksbarokah255.my.id/app-tamu/api/v1/guests -d "bukan-json"

# Field wajib hilang -> 422
curl -sS -o /dev/null -w "%{http_code}\n" -X POST https://lksbarokah255.my.id/app-tamu/api/v1/guests -H "Content-Type: application/json" -d '{}'

# Method salah -> 405
curl -sS -o /dev/null -w "%{http_code}\n" -X PUT https://lksbarokah255.my.id/app-tamu/api/v1/guests

# Endpoint tidak ada -> 404
curl -sS -o /dev/null -w "%{http_code}\n" https://lksbarokah255.my.id/app-tamu/api/v1/tidak-ada
```

Hasil akhirnya harus terlihat juga di tabel halaman utama (data baru + Last
Updated, dengan masking).

---

## 15. Pemeriksaan Log

```bash
# Log aplikasi (Apache error log + migrasi + log request)
docker compose logs -f app

# Log aplikasi versi file (di dalam container)
docker compose exec app tail -50 logs/app.log

# Log MySQL
docker compose logs db
```

Catatan: log tidak pernah memuat password, kredensial database, atau data
pribadi tamu — hanya waktu request, method, endpoint, dan status code.

---

## 16. Prosedur Update / Redeploy

```bash
cd /opt/app-tamu

# 1. Ambil versi terbaru source (atau salin file baru)
git pull

# 2. Build ulang dan ganti container aplikasi
docker compose up -d --build

# 3. Periksa status
docker compose ps
curl -sS https://lksbarokah255.my.id/app-tamu/api/v1/health
```

> **Tanpa** `down -v`: volume `mysql_data` tidak tersentuh, data tamu aman.

---

## 17. Restart & Troubleshooting

| Gejala                          | Kemungkinan penyebab & solusi                                             |
| ------------------------------- | ------------------------------------------------------------------------- |
| `502 Bad Gateway` dari Nginx    | Container `app` mati/belum siap → `docker compose ps` dan `docker compose logs app` |
| Halaman tampil tanpa CSS        | Cek URL asset lewat View Source; pastikan `X-Forwarded-Prefix` di Nginx ada |
| API `503` / `database unavailable` | MySQL belum siap → tunggu healthcheck hijau, cek `docker compose logs db` |
| Port `8081` bentrok             | Proses lain memakai port → ganti sisi host (mis. `127.0.0.1:8082:80`) di compose **dan** di Nginx |
| `403 Forbidden`                 | Hak akses file di volume/bind mount; pastikan file dibaca user `www-data` |
| Data tidak tersimpan            | Cek `.env` (DB_*), volume, dan `docker compose logs app` |

### Restart aplikasi / database

```bash
docker compose restart app     # aplikasi saja
docker compose restart db      # database (tunggu healthy sebelum mengetes)
```

Setelah `restart db`, koneksi pulih otomatis setelah MySQL siap — data tetap
ada karena tersimpan di volume.

---

## 18. Keamanan & Backup Database

### 18.1 Backup rutin (mysqldump lewat container)

```bash
mkdir -p ~/backups
docker compose exec db mysqldump \
  -u guest_user -p"$(grep '^DB_PASSWORD=' .env | cut -d= -f2)" guest_app \
  > ~/backups/guest_app-$(date +%F).sql
```

Simpan file backup di luar VPS (mis. unduh lewat `scp`) secara berkala.

### 18.2 Restore (hanya saat diperlukan)

```bash
docker compose exec -T db mysql -u guest_user -p"PASSWORD" guest_app < backup.sql
```

### 18.3 Checklist keamanan

- [ ] `DB_PASSWORD` dan `MYSQL_ROOT_PASSWORD` sudah diganti dari placeholder.
- [ ] `.env` tidak ter-commit ke Git (`git status` bersih).
- [ ] MySQL tanpa port publik (`docker compose ps` tidak menampilkan `3306`).
- [ ] Hanya `127.0.0.1:8081` yang dipetakan untuk container app.
- [ ] `nginx -t` selalu sukses sebelum reload.
- [ ] Email / nomor HP / IP tampil ter-mask di tabel dan di API.

---

## 19. Referensi

- [overview.md](overview.md) — spesifikasi lengkap aplikasi.
- [README.md](README.md) — pengantar project & cara menjalankan lokal.
