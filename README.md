# EduwGo

Website rental motor. Tugas Bootcamp Programming Eduwork, tim 3 orang. UI mengikuti Figma "ED.RENT (Eduwork)" dengan brand/warna/logo diganti ke EduwGo.

- Spesifikasi lengkap: [`docs/SPEC.md`](docs/SPEC.md)
- Keputusan tim, git flow, konvensi, DoD: [`docs/KEPUTUSAN.md`](docs/KEPUTUSAN.md)
- Struktur database: [`docs/ERD.md`](docs/ERD.md)
- Backlog & detail tiap task: Linear team **EduwGO** (prefix `EG-`)

## Stack

Laravel 13, PHP 8.3, Breeze (Blade + Tailwind + Alpine), Laravel Sail (Docker), MySQL 8.4, Mailpit, phpMyAdmin, Xendit Invoice API.

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
| Mailpit | http://localhost:8025 | Lihat email yang terkirim (verifikasi, dll) |
| phpMyAdmin | http://localhost:8080 | Lihat/edit database (server: `mysql`, user: `sail`, password: `password`) |
| MySQL | `localhost:3306` | Untuk klien DB eksternal (TablePlus, dll) |

Ubah port di `.env` (`APP_PORT`, `FORWARD_DB_PORT`, `FORWARD_MAILPIT_PORT`, `FORWARD_MAILPIT_DASHBOARD_PORT`, `FORWARD_PHPMYADMIN_PORT`) kalau bentrok dengan service lain di laptop.

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

## Image Docker kustom

Container `laravel.test` **tidak** memakai image bawaan Laravel Sail (berat: mongodb, postgres, playwright, swoole, dsb — tidak dipakai project ini). Dockerfile-nya ada di `docker/8.3/`, hanya berisi ekstensi yang dipakai: `pdo_mysql`, `mbstring`, `zip`, `exif`, `pcntl`, `bcmath`, `gd`, `redis`. Perintah `sail` tetap sama seperti Sail resmi.

Kalau butuh ekstensi PHP tambahan, edit `docker/8.3/Dockerfile` lalu `./vendor/bin/sail build laravel.test`.

## Catatan

- Timezone aplikasi: `Asia/Jakarta` (lihat `config/app.php`)
- Locale: `id`
- Database default: `laravel`, user `sail`, password `password` (lihat `.env`)
