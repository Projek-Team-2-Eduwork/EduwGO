# Deploy EduwGo: Render + Aiven

> **Status: siap deploy, BELUM DEPLOY ke Render.**
> - Database Aiven MySQL **sudah** dimigrasi dan di-seed: roles, cabang, tipe kendaraan, motor, settings,
>   1 superadmin; **tanpa booking** dan tanpa `UserSeeder`. Nomor WhatsApp admin di settings sudah `6283159110614`.
>   Jangan jalankan `migrate:fresh` / `db:seed` lagi ke DB itu.
> - **Satu migrasi belum ada di Aiven**: `2026_10_23_000000_create_pending_registrations_table` (PR #67, registrasi
>   dibuat setelah OTP benar). Tanpa tabel ini pendaftaran akun baru gagal. Jalankan sekali (bagian 4.1).
> - **Ganti password superadmin setelah login pertama** (Profil → ubah kata sandi).
> - Kode deploy sudah ada di repo: `docker/production/` (Dockerfile, entrypoint, Apache, php.ini), `.dockerignore`,
>   `render.yaml`, endpoint `POST /cron/run`, dukungan CA MySQL (`MYSQL_ATTR_SSL_CA`).
> - Image sudah diuji lokal: `docker build` sukses, container menjawab 200 di `/up`, `/login`, `/`, `/kendaraan`,
>   foto motor, dan aset Vite (bagian 9).
> - Setelah PR di-merge ke `main`, deploy tidak butuh edit kode: cukup langkah dashboard di bagian 3–6.

## 1. Arsitektur

EduwGo adalah **monolit Laravel + Blade (SSR) + Alpine**, tanpa SPA (`docs/KEPUTUSAN.md`), jadi **tidak dipisah FE/BE**.

```
Pengguna ──HTTPS──> Render Web Service (Docker, plan free, region Singapore)
                    Apache + PHP 8.3 + Laravel, aset Vite & foto motor ikut di image
                       │
                       │ TLS, diverifikasi dengan CA Aiven (Secret File aiven-ca.pem)
                       ▼
                    Aiven for MySQL (free)

Xendit ───────POST /webhooks/xendit  (header x-callback-token)──────> Render
cron-job.org ─POST /cron/run tiap menit (Authorization: Bearer CRON_TOKEN)─> Render
```

| Komponen | Pilihan | Catatan |
|---|---|---|
| Aplikasi | Render Web Service, runtime Docker, free | `docker/production/Dockerfile`, health check `/up` |
| Database | Aiven MySQL free | Wajib TLS; CA lewat Secret File |
| Scheduler | cron-job.org → `POST /cron/run` | Render free tidak punya cron job |
| Queue | `QUEUE_CONNECTION=sync` | Render free tidak punya worker |
| Foto motor | `public/images/vehicles/*.jpg` (ikut git & image) | Upload admin tidak permanen (bagian 8) |
| Log | `LOG_CHANNEL=stderr` | Tab **Logs** di Render |

## 2. Yang perlu disiapkan sebelum klik

Simpan semua di password manager tim, **jangan** di chat grup / commit / `.env` harian.

| Data | Cara dapat |
|---|---|
| Host, port, database, user, password Aiven | Aiven Console → service MySQL → **Overview** → *Connection information* |
| `ca.pem` Aiven | Halaman Overview yang sama → **CA certificate** → Download |
| `APP_KEY` baru khusus demo | Lokal: `./vendor/bin/sail artisan key:generate --show` (jangan pakai key lokal) |
| `CRON_TOKEN` | Lokal: `php -r "echo bin2hex(random_bytes(32));"` |
| `XENDIT_SECRET_KEY` (test, `xnd_development_*`) | Dashboard Xendit → Settings → API Keys |
| `XENDIT_CALLBACK_TOKEN` | Dashboard Xendit → Settings → Webhooks → *Verification token* |
| Login SMTP Brevo, SMTP key, sender terverifikasi | Brevo → SMTP & API / Senders (bagian 7.4) |

**Keamanan:** setelah deploy, login sebagai superadmin lalu **langsung ganti password** lewat Profil, walaupun
password awal sudah kuat (pernah dibagikan saat setup). Jangan menjalankan `UserSeeder` ke Aiven; bila suatu saat
terpaksa, seeder menolak jalan di production tanpa `SEED_USER_PASSWORD` (min. 12 karakter, PR #66).

## 3. Buat Web Service di Render

Pilih **satu** cara. Cara A memakai `render.yaml` (lebih sedikit ketik).

### Cara A: Blueprint (`render.yaml`)
1. Login https://dashboard.render.com → tombol **New +** → **Blueprint**.
2. Hubungkan GitHub bila diminta, pilih repo `Projek-Team-2-Eduwork/EduwGO`, branch `main`.
3. Render membaca `render.yaml` dan menampilkan service `eduwgo` (Docker, Free, Singapore).
4. Isi semua variabel yang diminta (yang `sync: false`), lihat tabel bagian 4.
   `APP_URL` boleh diisi sementara `https://eduwgo.onrender.com`, dibetulkan di langkah 3.C.
5. Klik **Apply** / **Deploy Blueprint**. Lanjut ke 3.C.

### Cara B: Manual
1. **New +** → **Web Service** → pilih repo `Projek-Team-2-Eduwork/EduwGO`.
2. Isi:
   - **Name**: `eduwgo` · **Region**: Singapore · **Branch**: `main` · **Root Directory**: kosong
   - **Language / Runtime**: Docker · **Instance Type**: Free
3. Buka **Advanced**:
   - **Dockerfile Path**: `./docker/production/Dockerfile`
   - **Docker Build Context Directory**: `.`
   - **Health Check Path**: `/up`
   - **Auto-Deploy**: On Commit
4. **Environment Variables**: isi semua dari tabel bagian 4 (bisa pakai *Add from .env* dengan teks yang disusun
   di password manager, jangan dari `.env` lokal).
5. Klik **Deploy Web Service**.

### 3.C Langkah lanjutan (dua cara)
1. **Secret File CA**: service `eduwgo` → **Environment** → bagian **Secret Files** → **Add Secret File**
   - Filename: `aiven-ca.pem`
   - Contents: tempel seluruh isi `ca.pem` (termasuk baris `-----BEGIN CERTIFICATE-----` / `-----END CERTIFICATE-----`)
   - **Save Changes**. File muncul di container sebagai `/etc/secrets/aiven-ca.pem`, cocok dengan
     `MYSQL_ATTR_SSL_CA=/etc/secrets/aiven-ca.pem`. Entrypoint menyalinnya ke `/tmp/mysql-ca.pem` agar terbaca Apache.
   - Alternatif tanpa Secret File: hapus `MYSQL_ATTR_SSL_CA`, isi env `MYSQL_SSL_CA_BASE64` dengan
     `base64 < ca.pem | tr -d '\n'` (atau `MYSQL_SSL_CA_PEM` dengan teks PEM). Entrypoint menulisnya ke file.
2. Catat URL service di bagian atas halaman (mis. `https://eduwgo-xxxx.onrender.com`).
   Bila beda dengan `APP_URL`, ubah `APP_URL` di **Environment** → **Save, rebuild, and deploy**.
3. Tunggu status **Live**. Tab **Logs** harus memuat `Caching framework bootstrap, configuration, and metadata` dan tanpa
   `PERINGATAN: file CA MySQL tidak terbaca`.

## 4. Environment variable Render

Cocok dengan `.env.example` dan `render.yaml`. "Rahasia" = `sync: false` di `render.yaml`, isi di dashboard.

| Variabel | Nilai | Rahasia | Keterangan |
|---|---|---|---|
| `APP_NAME` | `EduwGo` | | |
| `APP_ENV` | `production` | | Mematikan `/styleguide` |
| `APP_DEBUG` | `false` | | Wajib false di URL publik |
| `APP_KEY` | `base64:...` | ya | Key baru khusus demo |
| `APP_URL` | `https://<service>.onrender.com` | ya | Dipakai redirect Xendit & link email |
| `APP_TIMEZONE` / `APP_LOCALE` / `APP_FALLBACK_LOCALE` / `APP_FAKER_LOCALE` | `Asia/Jakarta` / `id` / `en` / `id_ID` | | |
| `LOG_CHANNEL` / `LOG_LEVEL` | `stderr` / `info` | | |
| `DB_CONNECTION` | `mysql` | | |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | dari Aiven | ya | |
| `MYSQL_ATTR_SSL_CA` | `/etc/secrets/aiven-ca.pem` | | Path Secret File (bagian 3.C) |
| `SESSION_DRIVER` | `cookie` | | Sesi di cookie terenkripsi, tanpa query DB (7.6). Jangan `file` (disk container hilang) |
| `SESSION_SECURE_COOKIE` | `true` | | Cookie sesi hanya lewat HTTPS |
| `CACHE_STORE` | `file` | | Cache per container; hilang saat deploy/restart (7.6) |
| `QUEUE_CONNECTION` | `sync` | | Tanpa worker |
| `FILESYSTEM_DISK` | `local` | | |
| `MAIL_MAILER` / `MAIL_SCHEME` / `MAIL_HOST` / `MAIL_PORT` | `smtp` / `smtp` / `smtp-relay.brevo.com` / `2525` | ya | Brevo; port 2525 karena 25/465/587 diblokir Render free (7.4) |
| `MAIL_USERNAME` / `MAIL_PASSWORD` / `MAIL_FROM_ADDRESS` | SMTP login Brevo (`xxxx@smtp-brevo.com`) / SMTP key / sender terverifikasi | ya | Jangan pakai email Gmail sebagai username; alamat pengirim harus sama dengan sender yang sudah diverifikasi di Brevo |
| `MAIL_FROM_NAME` | `EduwGo` | | |
| `XENDIT_MODE` | `payment_request` | | `invoice` = rollback (`docs/pembayaran-xendit.md`) |
| `XENDIT_SECRET_KEY` | `xnd_development_*` | ya | |
| `XENDIT_CALLBACK_TOKEN` | dari dashboard Xendit | ya | |
| `CRON_TOKEN` | acak 64 hex | ya | Sama dengan header di cron-job.org |
| `RUN_MIGRATIONS` | `false` | | `true` = `migrate --force` tiap start. Seeder tidak pernah otomatis |
| `PORT` | jangan diisi | | Diisi Render (default 10000); Apache listen di `$PORT` |

Tidak perlu: `REDIS_*`, `MEMCACHED_HOST`, `AWS_*`, variabel Sail (`APP_PORT`, `FORWARD_*`), `DB_URL`.

### 4.1 Migrasi `pending_registrations` (PR #67) dan migrasi berikutnya

Aiven sudah berisi skema sebelum PR #67. Migrasi baru wajib dijalankan **sekali** sebelum pendaftaran dibuka.
`migrate --force` hanya menambah tabel yang belum ada; data (roles, cabang, motor, settings, superadmin) tidak disentuh.
Pilih satu:

- **Lewat Render (tanpa laptop):** service → **Environment** → ubah `RUN_MIGRATIONS` menjadi `true` →
  **Save, rebuild, and deploy**. Di tab **Logs** cari `2026_10_23_000000_create_pending_registrations_table ... DONE`
  (atau `Nothing to migrate`). Lalu **kembalikan `RUN_MIGRATIONS=false`** dan simpan lagi.
- **Dari laptop PIC:** env Aiven sementara di shell (bagian 10), `php artisan migrate --force`.

Cek: `php artisan migrate:status` (cara laptop) menampilkan migrasi itu `Ran`, dan halaman `/register` bisa mengirim
kode OTP. Migrasi berikutnya di masa depan memakai cara yang sama.

## 5. Webhook Xendit

1. Dashboard Xendit (mode **Test**) → **Settings** → **Webhooks**.
2. Pada event yang dipakai (`docs/pembayaran-xendit.md`: Payments API `payment.capture` dan `payment.failure`;
   tambah *Invoices* bila `XENDIT_MODE=invoice`), ganti URL tunnel menjadi:
   `https://<service>.onrender.com/webhooks/xendit`
3. Klik **Test and save** / **Save**. Respons 200 = beres. 403 = token tidak cocok.
4. Salin **Verification token** dari halaman yang sama ke env Render `XENDIT_CALLBACK_TOKEN` (harus sama persis),
   simpan & deploy. Controller memverifikasi header `x-callback-token` dengan `hash_equals`.
5. Satu URL per akun/mode: setelah diarahkan ke Render, tes webhook lokal butuh mengganti URL lagi ke tunnel.
   Sepakati dulu di tim.

## 6. Cron eksternal (cron-job.org)

Render free tidak menjalankan scheduler Laravel. `routes/console.php` (`bookings:expire` tiap 5 menit,
`bookings:flag-overdue` tiap jam, `bookings:remind` harian 08:00, `model:prune` untuk `PendingRegistration` tiap jam)
dipicu dari luar lewat `POST /cron/run`.

1. Daftar/login https://cron-job.org → **Create cronjob**.
2. Tab **Common**:
   - Title: `EduwGo schedule:run`
   - URL: `https://<service>.onrender.com/cron/run`
   - Execution schedule: **Every 1 minute**
   - Zona waktu akun: Asia/Jakarta (Settings akun cron-job.org)
3. Tab **Advanced**:
   - Request method: **POST**
   - Headers: `Authorization` = `Bearer <CRON_TOKEN>` dan `Accept` = `application/json`
   - Request body: kosong (default `schedule:run`)
   - Timeout: maksimum yang tersedia
4. Aktifkan notifikasi bila gagal → **Create** → klik **Test run**. Hasil yang benar: HTTP 200,
   `{"status":"ok","task":"schedule:run",...}`.

Cara kerja endpoint (`routes/web.php`, `VerifyCronToken`, `CronRunRequest`, `CronController`):
- Tanpa/dengan token salah → 401. `CRON_TOKEN` kosong di server → selalu 401. GET → 405. Throttle 30/menit.
- Body opsional `{"task": "..."}`, hanya `schedule:run` (default), `bookings:expire`, `bookings:flag-overdue`,
  `bookings:remind`. Lainnya 422.
- Ping tiap menit membuat `schedule:run` sama persis dengan cron Linux biasa, dan membuat service **tidak pernah tidur**
  (≈744 jam/bulan, masih di bawah kuota 750 jam; jangan buat service free kedua di workspace yang sama).
- Hemat kuota (service boleh tidur): ganti dengan 3 job eksplisit, karena `schedule:run` bisa melewatkan jadwal bila
  menit bergeser akibat cold start: `{"task":"bookings:expire"}` tiap 10 menit, `{"task":"bookings:flag-overdue"}`
  tiap jam, `{"task":"bookings:remind"}` harian 08:00. `bookings:remind` **tidak** idempoten, jangan lebih dari sekali sehari.
  Mode ini tidak menjalankan `model:prune` (pendaftaran tertunda kedaluwarsa tidak dibersihkan otomatis; tidak memengaruhi
  fungsi, hanya baris sisa di tabel). Mode default tiap menit sudah mencakupnya.

Uji manual:
```bash
curl -X POST https://<service>.onrender.com/cron/run \
  -H "Authorization: Bearer $CRON_TOKEN" -H "Accept: application/json"
```

## 7. Batasan Render free

> Sesuai dokumentasi Render saat ditulis; cek ulang https://render.com/docs/free.

### 7.1 Sleep 15 menit + cold start
Tanpa trafik 15 menit, service tidur; request berikutnya menunggu container bangun (bisa sekitar satu menit).
Dengan cron tiap menit (bagian 6) service tetap bangun. Tanpa cron itu: buka URL beberapa menit sebelum demo.

### 7.2 750 jam/bulan per workspace
Cukup untuk satu service yang selalu bangun.

### 7.3 Tanpa disk persisten
Filesystem container hilang setiap deploy, restart, dan bangun dari sleep. Aman: kode, `public/build`, foto seeder.
Hilang: upload admin (bagian 8) dan file log (`storage/logs`, termasuk channel `xendit` di `config/logging.php`).

### 7.4 Tanpa worker; SMTP
- `QUEUE_CONNECTION=sync`: listener `SendBookingStatusEmail` jalan langsung di request checkout/webhook.
  SMTP lambat/gagal ikut membuat request itu lambat/gagal.
- Sejak 26 September 2025 Render memblokir koneksi keluar ke port SMTP 25, 465, dan 587 di web service free
  (changelog Render: "Free web services will no longer allow outbound traffic to SMTP ports"). Gmail SMTP hanya
  mendukung 465/587, jadi di Render free ia timeout dan email kode verifikasi tidak terkirim. Karena itu
  produksi memakai **Brevo SMTP di port 2525** (tidak diblokir, gratis 300 email/hari, tanpa paket Composer baru).
  Gmail tetap bisa dipakai untuk lokal.
- Setup Brevo (sekali, dikerjakan manual oleh pemilik akun):
  1. Daftar di brevo.com (paket gratis).
  2. **Senders, Domains & Dedicated IPs → Senders → Add a sender**: isi From Name `EduwGo` dan From Email, lalu
     klik link/kode verifikasi yang dikirim ke email itu. Status harus terverifikasi sebelum bisa mengirim.
     Bila Brevo menolak alamat gratis seperti `@gmail.com` ("Invalid email"), verifikasi domain sendiri (SPF/DKIM)
     dan pakai alamat dari domain itu.
  3. **SMTP & API → SMTP**: catat **Login** (`xxxx@smtp-brevo.com`), lalu **Generate a new SMTP key**
     (Standard, kedaluwarsa 1 tahun; key hanya tampil sekali).
  4. Isi env Render: `MAIL_HOST=smtp-relay.brevo.com`, `MAIL_PORT=2525`, `MAIL_SCHEME=smtp`,
     `MAIL_USERNAME=<Login>`, `MAIL_PASSWORD=<SMTP key>`, `MAIL_FROM_ADDRESS=<sender terverifikasi>`.
     Jangan commit atau kirim key lewat chat. Bila bocor, hapus key di Brevo lalu buat baru.
- Uji login tanpa mengirim email: `php artisan tinker --execute='app("mail.manager")->mailer("smtp")->getSymfonyTransport()->start(); echo "OK";'`.
  Login berhasil belum menjamin email masuk inbox: pastikan sender terverifikasi, dan cek folder spam
  (pengirim berupa alamat gratis lewat Brevo bisa masuk spam).
- Cadangan bila email tetap gagal: `MAIL_MAILER=log` (pendaftar baru tidak menerima kode verifikasi, jadi
  tidak bisa checkout; pakai akun yang sudah terverifikasi), atau naik ke instance Render berbayar
  (port SMTP dibuka lagi) / VPS.

### 7.5 Aiven free
Koneksi wajib TLS. Kapasitas dan koneksi terbatas; service free bisa dimatikan otomatis bila lama tidak aktif.
Cek dashboard Aiven sebelum demo dan nyalakan lagi bila perlu.

### 7.6 Latensi DB lintas region (sesi & cache)
Web service di Singapura, DB Aiven di Bengaluru: setiap query membayar round-trip jaringan (halaman sempat
1,7–3,5 detik, sedangkan `/up` tanpa DB 0,12 detik). Karena itu:

- `SESSION_DRIVER=cookie`: isi sesi disimpan di cookie terenkripsi (`APP_KEY`), tanpa `select/update sessions`
  per request. Trade-off: batas cookie ~4 KB (teks panjang `content_terms`/`notes` tidak di-flash sebagai old input,
  `bootstrap/app.php`), sesi tidak bisa dicabut dari server (logout perangkat lain tidak ada; akun nonaktif tetap
  ditendang middleware `EnsureUserIsActive`), dan ganti `APP_KEY` = semua orang logout. Tabel `sessions` tidak dipakai.
- `CACHE_STORE=file`: cache di disk container. Hilang saat deploy/restart/bangun dari sleep, dan tidak dibagi antar
  instance. Aman di sini karena isinya turunan (settings dibaca ulang dari DB, hitungan rate limiter login/OTP/throttle
  mulai dari nol setelah restart) dan Render free hanya satu instance. Bila nanti >1 instance, pindah ke Redis.
- `setting()` membaca seluruh tabel `settings` sekali per request (memo di container), dan cabang/tipe kendaraan
  dipakai ulang dari `BranchContext` tanpa eager load kedua. Jumlah query (lokal, `DB::listen`, sesi cookie + cache file):

| Halaman | Sebelum | Sesudah |
|---|---|---|
| `/` | 22 | 5 |
| `/kendaraan` | 18 | 5 |
| `/kendaraan/{slug}` | 19 | 5 |
| `/login` | 6 | 0 |
| `/tentang` | 13 | 1 |
| `/admin/dashboard` (superadmin) | 25 | 16 |
| `/admin/pesanan` | 27 | 10 |

## 8. Gambar

- **AVIF + fallback**: foto motor seeder (`images/vehicles/*.jpg`) dan hero (`images/home/hero-road*.webp`) punya
  versi `.avif` di sebelahnya, disajikan lewat `<picture>` (`<x-picture>` / `avif_url()`); JPG/WebP tetap sebagai
  fallback. Total 2,46 MB (JPG+WebP) → 0,81 MB (AVIF), -67%. `og.png` dan `lockup.png` (email) tetap PNG karena crawler sosial
  dan klien email belum mendukung AVIF. Upload admin tetap JPG/PNG/WebP: GD di image tidak punya `imageavif`.

- **Foto motor seeder ikut ter-deploy**: `public/images/vehicles/*.jpg` (10 file + `CREDITS.md`) ter-track di git,
  tidak di-ignore `.gitignore` maupun `.dockerignore`, dan disajikan Apache (diuji: `/images/vehicles/honda-beat.jpg` 200).
  `VehicleSeeder` menyimpan path `images/vehicles/<slug>.jpg`, jadi DB Aiven langsung menunjuk ke file itu.
- **Upload admin tidak permanen**: foto kendaraan (`Admin/VehicleController::uploadAndResizeImage`) dan logo/favicon
  (`Admin/SettingController`) ditulis ke disk `public` (`storage/app/public`, path `storage/...`). Di Render free file itu
  hilang saat restart/deploy/bangun dari sleep, sementara path-nya tetap di DB, sehingga gambar rusak.
  - Demo: jangan upload foto/logo; foto baru tambahkan ke `public/images/vehicles` + seeder lewat PR.
  - Permanen (PR terpisah): disk `s3` ke Cloudflare R2 (butuh `league/flysystem-aws-s3-v3`, refactor
    `uploadAndResizeImage` dari `$disk->path()` ke `$disk->put()`, dan `asset()` → `Storage::url()` dengan fallback path
    seeder), atau Cloudinary (paket `cloudinary-labs/cloudinary-laravel`, refactor serupa).

## 9. Image Docker

`docker/production/` (tidak mengubah `docker/8.3` dan `compose.yaml` milik Sail):
- `Dockerfile` multi-stage: `base` (`php:8.3-apache-bookworm`, ekstensi `pdo_mysql zip exif pcntl bcmath gd opcache`,
  `mbstring` bawaan), `vendor` (`composer install --no-dev`), `assets` (Node 22, `npm ci && npm run build`),
  final (`dump-autoload --optimize`, `package:discover`, `storage:link`, izin `www-data`).
- `entrypoint.sh`: siapkan CA MySQL (Secret File atau `MYSQL_SSL_CA_PEM`/`MYSQL_SSL_CA_BASE64`) → `php artisan optimize`
  (cache config/route/view/event saat start karena env baru ada di runtime) → `migrate --force` **hanya** bila
  `RUN_MIGRATIONS=true` → `apache2-foreground`.
- `apache.conf` (DocumentRoot `public/`, listen `${PORT}`, log ke stdout/stderr), `php.ini` (opcache, upload 4 MB).
- `.dockerignore` di root: `.env`, `vendor`, `node_modules`, `public/build`, cache/log lokal tidak ikut.

Hasil uji lokal (Docker, image `eduwgo-prod:test`, env produksi ke MySQL Sail, setelah merge `origin/dev` PR #66–#68):

| Cek | Hasil |
|---|---|
| `docker build -f docker/production/Dockerfile .` | sukses |
| `/up`, `/login`, `/`, `/register`, `/kendaraan`, `/kendaraan/honda-beat` | 200 |
| `/images/vehicles/*.jpg`, `/build/manifest.json`, `/fonts/fonts.css` | 200 |
| `/styleguide` (production) | 404 |
| `GET /cron/run` / `POST` tanpa token / token salah / `task` ilegal / token benar | 405 / 401 / 401 / 422 / 200 |
| `POST /webhooks/xendit` tanpa token / token benar | 403 / 200 (tidak kena CSRF/auth) |
| Di balik proxy (`X-Forwarded-Proto: https`, `X-Forwarded-Host`) | URL aset `https://<host>/build/...`, header HSTS ada |
| Cookie `eduwgo-session` & `XSRF-TOKEN` dengan `SESSION_SECURE_COOKIE=true` | `secure; httponly; samesite=lax` |
| CA via Secret File (mount read-only) dan via `MYSQL_SSL_CA_BASE64` | opsi PDO `/tmp/mysql-ca.pem`, TLS dengan verifikasi aktif |
| `RUN_MIGRATIONS=true` | menjalankan `create_pending_registrations_table ... DONE` |
| Error di log Laravel / warning Apache | 0 |

Ulang di lokal:
```bash
docker build -f docker/production/Dockerfile -t eduwgo-prod .
docker run --rm -p 8080:10000 -e PORT=10000 -e APP_KEY=base64:... -e APP_ENV=production -e APP_DEBUG=false \
  -e DB_CONNECTION=mysql -e DB_HOST=... -e DB_DATABASE=... -e DB_USERNAME=... -e DB_PASSWORD=... \
  -e SESSION_DRIVER=cookie -e CACHE_STORE=file -e LOG_CHANNEL=stderr eduwgo-prod
```

## 10. Pemisahan lokal vs demo

- Development tetap di Sail + MySQL lokal masing-masing. **DB Aiven hanya untuk demo publik**; jangan dipakai bertiga
  untuk development (migrasi beda branch bertabrakan, seeder/`migrate:fresh` menghapus data, kuota free habis).
- Kredensial Aiven, `APP_KEY` demo, `CRON_TOKEN`, token Xendit hanya di dashboard Render + password manager.
- Satu PIC deploy. Migrasi ke DB demo hanya migrasi baru (`migrate --force`), lewat `RUN_MIGRATIONS` (bagian 4) atau
  dari laptop PIC dengan env sementara di shell:
  ```bash
  DB_CONNECTION=mysql DB_HOST=... DB_PORT=... DB_DATABASE=... DB_USERNAME=... DB_PASSWORD=... \
  MYSQL_ATTR_SSL_CA=/path/ke/ca.pem php artisan migrate --force
  ```

## 11. Cek pasca-deploy

- [ ] Status service **Live**, health check `/up` hijau.
- [ ] Log: tanpa `SQLSTATE`, tanpa `PERINGATAN: file CA MySQL`, tanpa `No application encryption key`.
- [ ] Beranda menampilkan 10 motor dengan foto; katalog, detail + kalender, `/cabang`, `/tentang`.
- [ ] Migrasi `pending_registrations` sudah jalan dan `RUN_MIGRATIONS` kembali `false` (4.1).
- [ ] Login superadmin → `/admin/dashboard` → **ganti password** di Profil.
- [ ] Tombol Chat Admin membuka `wa.me/6283159110614`.
- [ ] Daftar akun baru → email kode OTP dari Brevo diterima (cek spam) → akun terbentuk setelah kode benar.
- [ ] Cookie sesi ber-flag `Secure` (DevTools → Application → Cookies) dan aset dimuat lewat `https://`.
- [ ] Checkout → halaman bayar Xendit test → simulasi bayar → booking jadi **Lunas** (webhook 200 di dashboard Xendit).
- [ ] cron-job.org: riwayat eksekusi 200 tiap menit; booking pending lewat waktu jadi **Expired**.
- [ ] `curl -X POST .../cron/run` tanpa token = 401.
- [ ] Halaman error tanpa stack trace (`APP_DEBUG=false`), dark mode & mobile normal.

## 12. Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| Request pertama lama / "Service waking up" | Instance tidur (7.1) | Tunggu; aktifkan cron tiap menit (bagian 6) |
| cron-job.org timeout sesekali | Cold start lebih lama dari timeout cron-job.org | Aman; eksekusi menit berikutnya jalan normal |
| Build gagal di `npm ci` / `composer install` | Lock file tidak sinkron | Perbaiki lock di lokal, PR baru |
| Deploy gagal health check | Container crash saat start | Tab Logs: biasanya env salah (`APP_KEY` kosong/format salah) |
| `SQLSTATE[HY000] [2002]` / timeout | Host/port Aiven salah atau service Aiven mati | Cek Connection information; nyalakan service Aiven |
| `Connections using insecure transport are prohibited` | CA tidak terpasang | Secret File `aiven-ca.pem` + `MYSQL_ATTR_SSL_CA` (3.C) |
| `certificate verify failed` / `did not match expected CN` | CA salah atau `DB_HOST` bukan hostname Aiven (mis. IP) | Unduh ulang CA dari service yang sama; pakai hostname persis |
| `Access denied for user` | User/password salah | Salin ulang dari Aiven |
| Daftar akun gagal / error `pending_registrations` doesn't exist | Migrasi PR #67 belum jalan | Bagian 4.1 |
| `419 Page Expired` saat login | Cookie sesi tidak tersimpan | `APP_URL` https yang benar, `SESSION_SECURE_COOKIE=true`, akses lewat https |
| Gambar motor rusak | Gambar hasil upload admin (8) | Pakai foto seeder; upload hilang saat restart |
| `/cron/run` 401 | Header bukan `Authorization: Bearer <CRON_TOKEN>` / token beda | Samakan token; perhatikan spasi |
| `/cron/run` 405 | Method GET | Pakai POST |
| `/cron/run` 429 | Lebih dari 30 panggilan/menit | Kurangi job |
| Webhook Xendit 403 | `XENDIT_CALLBACK_TOKEN` beda | Salin ulang verification token |
| Booking tidak jadi Lunas | URL webhook masih tunnel / event belum dicentang | Bagian 5 |
| Email tidak terkirim, checkout lambat | Port SMTP 25/465/587 diblokir (pakai Brevo 2525), sender belum terverifikasi, atau SMTP key salah | Bagian 7.4 |
| Perubahan env tidak berpengaruh | Config di-cache saat start | Simpan env di Render memicu deploy ulang; bila tidak, **Manual Deploy** → *Restart* |

## 13. Alternatif

- **Render berbayar (Starter):** tanpa sleep, Persistent Disk untuk `storage/app/public`, Background Worker, Cron Job.
  Image yang sama bisa dipakai.
- **Laravel Cloud:** platform resmi Laravel, ada worker, scheduler, object storage, DB terkelola tanpa Dockerfile.
  Cek biaya/kuota.
- **VPS (deskripsi awal EG-41):** Docker Compose / Nginx + PHP-FPM + MySQL + Supervisor (`queue:work`) + cron
  `schedule:run`. Semua batasan bagian 7 hilang, dengan biaya dan pengelolaan sendiri.
- **Vercel (rencana lama, dibatalkan):** runtime PHP komunitas, filesystem read-only, cron Hobby terbatas.
