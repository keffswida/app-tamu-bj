#!/usr/bin/env bash
#
# Uji E2E aplikasi — jalankan setelah aplikasi berjalan.
#
#   local  : ./scripts/test.sh
#   publik : ./scripts/test.sh https://lksbarokah255.my.id/app-tamu
#
# Menguji positive, negative, dan edge case sesuai overview.md bagian 13.
# Memakai curl + grep (tanpa ketergantungan jq).

set -u

BASE="${1:-http://127.0.0.1:8081}"
COOKIES="$(mktemp)"
PASS=0
FAIL=0

say()  { printf '%s\n' "$*"; }
ok()   { PASS=$((PASS + 1)); printf '  [PASS] %s\n' "$*"; }
bad()  { FAIL=$((FAIL + 1)); printf '  [FAIL] %s\n' "$*"; }

check_status() { # desc expected actual
  if [ "$2" = "$3" ]; then ok "$1 (HTTP $3)"; else bad "$1 (diharapkan $2, dapat $3)"; fi
}
check_contains() { # desc haystack needle
  if printf '%s' "$2" | grep -qF "$3"; then ok "$1"; else bad "$1 ('$3' tidak ditemukan)"; fi
}
check_not_contains() { # desc haystack needle
  if printf '%s' "$2" | grep -qF "$3"; then bad "$1 ('$3' masih muncul)"; else ok "$1"; fi
}

say "== Uji terhadap: $BASE =="

# ---------- Positive case ----------
say "-- Positive case --"

status=$(curl -sS -o /tmp/t_health.json -w "%{http_code}" "$BASE/api/v1/health")
check_status "GET /api/v1/health" 200 "$status"
body=$(cat /tmp/t_health.json)
check_contains "health: success=true" "$body" '"success":true'
check_contains "health: database connected" "$body" '"database":"connected"'

status=$(curl -sS -o /tmp/t_guests.json -w "%{http_code}" "$BASE/api/v1/guests")
check_status "GET /api/v1/guests" 200 "$status"
body=$(cat /tmp/t_guests.json)
check_contains "guests: success=true" "$body" '"success":true'

status=$(curl -sS -o /tmp/t_post.json -w "%{http_code}" -X POST "$BASE/api/v1/guests" \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","phone":"081234567890","visit_purpose":"Minta API Data Tamu"}')
check_status "POST /api/v1/guests valid" 201 "$status"
body=$(cat /tmp/t_post.json)
check_contains "POST: success=true" "$body" '"success":true'
check_contains "POST: ip_address tercatat otomatis" "$body" '"ip_address"'

status=$(curl -sS -o /tmp/t_page.html -w "%{http_code}" "$BASE/")
check_status "GET halaman utama" 200 "$status"
body=$(cat /tmp/t_page.html)
check_contains "halaman: form nama" "$body" 'name="name"'
check_contains "halaman: tabel daftar tamu" "$body" 'Daftar Tamu'

status=$(curl -sS -o /tmp/t_asset.css -w "%{http_code}" "$BASE/assets/css/style.css")
check_status "GET asset CSS" 200 "$status"
status=$(curl -sS -o /tmp/t_asset.js -w "%{http_code}" "$BASE/assets/js/app.js")
check_status "GET asset JS" 200 "$status"

# Form manual (dengan CSRF, lewat cookie)
csrf=$(curl -sS -c "$COOKIES" "$BASE/" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
if [ -n "$csrf" ]; then ok "csrf token diperoleh dari halaman"; else bad "csrf token tidak ditemukan"; fi
status=$(curl -sS -o /tmp/t_form.html -w "%{http_code}" -b "$COOKIES" -c "$COOKIES" -X POST "$BASE/" \
  --data-urlencode "csrf_token=$csrf" \
  --data-urlencode "name=Jane Doe" \
  --data-urlencode "email=jane@example.com" \
  --data-urlencode "phone=081298765432" \
  --data-urlencode "purpose_mode=manual" \
  --data-urlencode "purpose_custom=Kunjungan kerja")
check_status "POST form (manual) redirect" 303 "$status"
body=$(cat /tmp/t_form.html)

# Form dropdown (nilai diizinkan)
csrf=$(curl -sS -b "$COOKIES" -c "$COOKIES" "$BASE/" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
status=$(curl -sS -o /dev/null -w "%{http_code}" -b "$COOKIES" -c "$COOKIES" -X POST "$BASE/" \
  --data-urlencode "csrf_token=$csrf" \
  --data-urlencode "name=Bank Jatim" \
  --data-urlencode "email=test@bankjatim.co.id" \
  --data-urlencode "phone=0311111111" \
  --data-urlencode "purpose_mode=dropdown" \
  --data-urlencode "visit_purpose=Minta API Data Tamu")
check_status "POST form (dropdown valid) redirect" 303 "$status"

# ---------- Masking ----------
say "-- Data masking --"
body=$(curl -sS "$BASE/")
check_contains "tabel: email ter-mask" "$body" 'ja***@example.com'
check_contains "tabel: nomor HP ter-mask" "$body" '08****5432'
check_not_contains "tabel: email asli tidak bocor" "$body" 'jane@example.com'
check_not_contains "tabel: nomor HP asli tidak bocor" "$body" '081298765432'

body=$(curl -sS "$BASE/api/v1/guests")
check_contains "API: email ter-mask" "$body" 'ja***@example.com'
check_not_contains "API: email asli tidak bocor" "$body" 'jane@example.com'

# ---------- Negative case ----------
say "-- Negative case --"

status=$(curl -sS -o /dev/null -w "%{http_code}" -X POST "$BASE/api/v1/guests" -d "ini-bukan-json")
check_status "POST JSON tidak valid -> 400" 400 "$status"

status=$(curl -sS -o /tmp/t_empty.json -w "%{http_code}" -X POST "$BASE/api/v1/guests" -H "Content-Type: application/json" -d '{}')
check_status "POST tanpa field wajib -> 422" 422 "$status"
check_contains "422: pesan validasi" "$(cat /tmp/t_empty.json)" 'Validation failed'

status=$(curl -sS -o /dev/null -w "%{http_code}" -X POST "$BASE/api/v1/guests" \
  -H "Content-Type: application/json" \
  -d '{"name":"X","email":"bukan-email","phone":"081234567890","visit_purpose":"Tes"}')
check_status "POST email tidak valid -> 422" 422 "$status"

status=$(curl -sS -o /dev/null -w "%{http_code}" -X POST "$BASE/api/v1/guests" \
  -H "Content-Type: application/json" \
  -d '{"name":"X","email":"x@example.com","phone":"123","visit_purpose":"Tes"}')
check_status "POST nomor HP tidak valid -> 422" 422 "$status"

status=$(curl -sS -o /dev/null -w "%{http_code}" -X POST "$BASE/api/v1/guests" \
  -H "Content-Type: application/json" \
  -d '{"name":"X","email":"x@example.com","phone":"081234567890","visit_purpose":""}')
check_status "POST keperluan kosong -> 422" 422 "$status"

status=$(curl -sS -o /dev/null -w "%{http_code}" -X PUT "$BASE/api/v1/guests")
check_status "PUT /guests -> 405" 405 "$status"

status=$(curl -sS -o /dev/null -w "%{http_code}" -X POST "$BASE/api/v1/health")
check_status "POST /health -> 405" 405 "$status"

status=$(curl -sS -o /dev/null -w "%{http_code}" "$BASE/api/v1/tidak-ada")
check_status "Endpoint tidak ada -> 404" 404 "$status"

# Form: dropdown di-tamper dengan nilai tidak dikenal
csrf=$(curl -sS -b "$COOKIES" -c "$COOKIES" "$BASE/" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
status=$(curl -sS -o /dev/null -w "%{http_code}" -b "$COOKIES" -X POST "$BASE/" \
  --data-urlencode "csrf_token=$csrf" \
  --data-urlencode "name=Attacker" \
  --data-urlencode "email=a@example.com" \
  --data-urlencode "phone=081234567890" \
  --data-urlencode "purpose_mode=dropdown" \
  --data-urlencode "visit_purpose=Nilai-Ilegal")
check_status "Form: dropdown tidak dikenal ditolak (redirect)" 303 "$status"
body=$(curl -sS -b "$COOKIES" "$BASE/")
check_contains "Form: pesan pilihan tidak dikenal" "$body" 'Pilihan keperluan tidak dikenal'

# Form: tanpa CSRF -> ditolak
status=$(curl -sS -o /dev/null -w "%{http_code}" -b "$COOKIES" -X POST "$BASE/" \
  --data-urlencode "name=No Csrf" \
  --data-urlencode "email=nocsrf@example.com" \
  --data-urlencode "phone=081234567890" \
  --data-urlencode "purpose_mode=manual" \
  --data-urlencode "purpose_custom=Tes tanpa token")
check_status "Form: tanpa CSRF ditolak (redirect)" 303 "$status"
body=$(curl -sS -b "$COOKIES" "$BASE/")
check_contains "Form: pesan sesi tidak valid" "$body" 'Sesi form tidak valid'
check_not_contains "Form: data tanpa CSRF tidak tersimpan" "$body" 'nocsrf@example.com'

# ---------- Keamanan (SQL Injection & XSS) ----------
say "-- SQL Injection & XSS --"

status=$(curl -sS -o /dev/null -w "%{http_code}" -X POST "$BASE/api/v1/guests" \
  -H "Content-Type: application/json" \
  -d '{"name":"Robert\" OR 1=1 --","email":"sql@example.com","phone":"081234567890","visit_purpose":"Tes SQLi"}')
check_status "POST dengan payload SQLi diterima sbg data" 201 "$status"
status=$(curl -sS -o /dev/null -w "%{http_code}" "$BASE/api/v1/guests")
check_status "tabel guests masih berfungsi setelah SQLi" 200 "$status"

status=$(curl -sS -o /dev/null -w "%{http_code}" -X POST "$BASE/api/v1/guests" \
  -H "Content-Type: application/json" \
  -d '{"name":"<script>alert(1)</script>","email":"xss@example.com","phone":"081234567890","visit_purpose":"Tes XSS"}')
check_status "POST dengan payload XSS diterima sbg data" 201 "$status"
body=$(curl -sS "$BASE/")
check_not_contains "halaman: script XSS tidak dieksekusi (raw)" "$body" '<script>alert(1)</script>'
check_contains "halaman: payload XSS ter-escape" "$body" '&lt;script&gt;'

# ---------- Edge case ----------
say "-- Edge case --"

status=$(curl -sS -o /dev/null -w "%{http_code}" -X POST "$BASE/api/v1/guests" \
  -H "Content-Type: application/json" \
  -d '{"name":"  Spasi Nama  ","email":"SPASI@EXAMPLE.COM","phone":"0812-3456-7890","visit_purpose":"Tes spasi"}')
check_status "POST nama ber-spasi & email kapital" 201 "$status"
body=$(curl -sS "$BASE/api/v1/guests")
check_contains "email dinormalisasi lowercase (ter-mask)" "$body" 'sp***@example.com'

status=$(curl -sS -o /dev/null -w "%{http_code}" -X POST "$BASE/api/v1/guests" \
  -H "Content-Type: application/json" \
  -d '{"name":"عبد الرحمن","email":"unicode@example.com","phone":"081234567890","visit_purpose":"Tes Unicode"}')
check_status "POST nama Unicode" 201 "$status"
body=$(curl -sS "$BASE/")
check_contains "nama Unicode tersimpan & tampil" "$body" 'عبد الرحمن'

status=$(curl -sS -o /dev/null -w "%{http_code}" -X POST "$BASE/api/v1/guests" \
  -H "Content-Type: application/json" \
  -d '{"name":"Panjang","email":"panjang@example.com","phone":"081234567890","visit_purpose":"012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890"}')
check_status "POST keperluan >500 karakter -> 422" 422 "$status"

# Trailing slash: akses /app-tamu/ tanpa slash (jika BASE tanpa prefix).
# Sesuai spec edge case: browser diarahkan (301) ke /app-tamu/.
if [ "$BASE" = "http://127.0.0.1:8081" ]; then
  status=$(curl -sS -o /dev/null -w "%{http_code}" "http://127.0.0.1:8081/app-tamu")
  check_status "akses /app-tamu (tanpa slash) redirect ke trailing slash" 301 "$status"
  status=$(curl -sS -o /dev/null -w "%{http_code}" "http://127.0.0.1:8081/app-tamu/")
  check_status "akses /app-tamu/ (dengan slash)" 200 "$status"
fi

# ---------- Ringkasan ----------
rm -f "$COOKIES" /tmp/t_*.json /tmp/t_*.html /tmp/t_*.css /tmp/t_*.js
say ""
say "======================================"
say "PASS: $PASS  FAIL: $FAIL"
if [ "$FAIL" -eq 0 ]; then say "Semua pengujian lulus."; exit 0; else exit 1; fi
