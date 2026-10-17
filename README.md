# EduwGo

Website rental motor. Tugas Bootcamp Programming Eduwork, tim 3 orang. UI mengikuti Figma "ED.RENT (Eduwork)" dengan brand/warna/logo diganti ke EduwGo.

- Spesifikasi lengkap: [`docs/SPEC.md`](docs/SPEC.md)
- Keputusan tim, git flow, konvensi, DoD: [`docs/KEPUTUSAN.md`](docs/KEPUTUSAN.md)
- Struktur database: [`docs/ERD.md`](docs/ERD.md)
- Backlog & detail tiap task: Linear team **EduwGO** (prefix `EG-`)

## Stack

Laravel 13, PHP 8.3, Breeze (Blade + Tailwind + Alpine), Laravel Sail (Docker), MySQL 8.4, phpMyAdmin, Xendit Invoice API.

## Setup (5 langkah)

Butuh Docker Desktop terpasang & berjalan. Tidak perlu install PHP/Composer/MySQL lokal — semua jalan di container.

```bash
git clone https://github.com/Projek-Team-2-Eduwork/EduwGO.git
cd EduwGO
cp .env.example .env          # isi XENDIT_SECRET_KEY & XENDIT_CALLBACK_TOKEN kalau sudah ada (lihat docs/SPEC.md #3)
./vendor/bin/sail up -d       # build image (sekali) & jalankan semua service
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
```

Kalau `vendor/` belum ada (clone baru, belum pernah composer install), jalankan dulu:

```bash
docker run --rm -v "$(pwd)":/app -w /app composer:2 install --ignore-platform-reqs
```

lalu lanjut dari langkah `cp .env.example .env` di atas.

## Service & Port

| Service | URL | Kegunaan |
|---|---|---|
| App | http://localhost | Aplikasi Laravel |
| phpMyAdmin | http://localhost:8080 | Lihat/edit database (server: `mysql`, user: `sail`, password: `password`) |
| MySQL | `localhost:3306` | Untuk klien DB eksternal (TablePlus, dll) |

Ubah port di `.env` (`APP_PORT`, `FORWARD_DB_PORT`, `FORWARD_PHPMYADMIN_PORT`) kalau bentrok dengan service lain di laptop.

## Perintah sehari-hari

```bash
./vendor/bin/sail up -d        # nyalakan semua container (background)
./vendor/bin/sail down         # matikan semua container
./vendor/bin/sail artisan ...  # ganti "php artisan"
./vendor/bin/sail composer ... # ganti "composer"
./vendor/bin/sail npm ...      # ganti "npm"
./vendor/bin/sail test         # jalankan test
./vendor/bin/sail bin pint     # format kode
```

## Webhook Xendit di lokal

Xendit perlu URL publik untuk mengirim webhook. Pakai [ngrok](https://ngrok.com):

```bash
ngrok http 80
```

Salin URL `https://xxxx.ngrok-free.app`, daftarkan `https://xxxx.ngrok-free.app/webhooks/xendit` di dashboard Xendit → **Settings → Developers → Webhooks** (Invoices). Detail lengkap: `docs/SPEC.md` bagian 3.

## Scheduler auto-expire (EG-26)

Booking Pending yang invoice Xendit-nya lewat waktu di-expire otomatis tiap 5 menit (`bookings:expire`) — fallback kalau webhook Xendit tidak masuk. Scheduler harus dijalankan terpisah:

```bash
# lokal — biarkan jalan di terminal terpisah (Ctrl+C untuk berhenti)
./vendor/bin/sail artisan schedule:work
```

Untuk produksi, daftarkan cron ini:

```cron
* * * * * cd /path/ke/EduwGO && php artisan schedule:run >> /dev/null 2>&1
```

Admin juga bisa memaksa sinkronisasi status invoice lewat tombol "Cek Status Xendit" di halaman `/admin/pesanan/{kode}`.

## Email lewat Gmail SMTP

Default lokal: email ditulis ke log (`storage/logs/laravel.log`), jadi kode verifikasi bisa dibaca di sana. Untuk kirim email sungguhan lewat Gmail:

1. Pakai akun Gmail khusus proyek (jangan akun pribadi).
2. Aktifkan **Verifikasi 2 langkah** di akun Google, lalu buat **App Password** di https://myaccount.google.com/apppasswords (16 karakter, tanpa spasi).
3. Isi `.env` (contoh lengkap ada di `.env.example`):

   ```env
   MAIL_MAILER=smtp
   MAIL_SCHEME=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=akun-proyek@gmail.com
   MAIL_PASSWORD=app-password-16-karakter
   MAIL_FROM_ADDRESS="akun-proyek@gmail.com"
   MAIL_FROM_NAME="${APP_NAME}"
   ```

4. Bersihkan cache config: `./vendor/bin/sail artisan config:clear`.
5. Email status booking dikirim lewat queue (`QUEUE_CONNECTION=database`), jadi worker harus jalan: `./vendor/bin/sail artisan queue:work`. Setelah ubah `.env`/kode, jalankan `./vendor/bin/sail artisan queue:restart`. Di hosting tanpa worker (mis. Vercel) set `QUEUE_CONNECTION=sync`. Email verifikasi dikirim setelah halaman merespons dan reset password dikirim langsung, keduanya tidak butuh worker.

Catatan:
- Batas Gmail sekitar 500 email/hari; cukup untuk demo.
- Email bisa masuk folder **spam** — cek di sana saat uji coba.
- Alamat pengirim (`MAIL_FROM_ADDRESS`) harus sama dengan `MAIL_USERNAME`, kalau tidak Gmail menimpanya.
- **Jangan commit `.env`** atau App Password. Kalau bocor, cabut di halaman App Password dan buat baru.

## Image Docker kustom

Container `laravel.test` **tidak** memakai image bawaan Laravel Sail (berat: mongodb, postgres, playwright, swoole, dsb — tidak dipakai project ini). Dockerfile-nya ada di `docker/8.3/`, hanya berisi ekstensi yang dipakai: `pdo_mysql`, `mbstring`, `zip`, `exif`, `pcntl`, `bcmath`, `gd`, `redis`. Perintah `sail` tetap sama seperti Sail resmi.

Kalau butuh ekstensi PHP tambahan, edit `docker/8.3/Dockerfile` lalu `./vendor/bin/sail build laravel.test`.

## Catatan

- Timezone aplikasi: `Asia/Jakarta` (lihat `config/app.php`)
- Locale: `id`
- Database default: `laravel`, user `sail`, password `password` (lihat `.env`)
