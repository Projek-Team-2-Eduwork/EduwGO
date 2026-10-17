# Rencana Deploy EduwGo ke Vercel

> **Status sekarang: belum deploy.** Demo masih lokal (Sail) + ngrok untuk webhook Xendit (lihat `docs/SPEC.md` bagian 3 dan 10).
> Dokumen ini hanya **rencana**. Belum ada akun/project Vercel, belum ada `vercel.json`, belum ada `api/index.php`, dan `.env` tidak diubah.
> Catatan: deskripsi EG-41 di Linear masih menyebut opsi VPS/Docker Compose + supervisor. Rencana ini memilih Vercel sebagai gantinya; opsi VPS tetap jalan kalau Vercel ternyata tidak cocok (lihat "Cadangan").

## 1. Opsi arsitektur

### A. Satu project Vercel (Laravel utuh, Blade SSR) — **direkomendasikan**
- Laravel dijalankan sebagai satu serverless function lewat runtime PHP komunitas `vercel-php` (Vercel tidak punya runtime PHP resmi).
- Entry point: `api/index.php` yang me-require `public/index.php`; `vercel.json` mengarahkan semua rute ke function itu, kecuali aset statis (`public/build`, `public/fonts`, dst).
- Cocok karena EduwGo adalah Blade SSR + Alpine (tanpa Inertia/Livewire/SPA, lihat `docs/KEPUTUSAN.md` bagian 2). Frontend dan backend satu codebase, satu domain, satu set cookie sesi, tanpa CORS.

### B. FE dan BE dipisah
- Frontend statis/SPA di Vercel, Laravel sebagai API di tempat lain.
- Tidak cocok: tidak ada SPA, semua halaman dirender Blade di server. Memisahkannya berarti menulis ulang view jadi SPA, menambah CORS, auth token/Sanctum, dan dua deploy. Biaya besar, tanpa manfaat untuk proyek ini.

**Rekomendasi: opsi A.**

## 2. Batasan Vercel serverless dan penanganannya

| Batasan | Dampak di EduwGo | Penanganan |
|---|---|---|
| Filesystem read-only (kecuali `/tmp`, tidak persisten) | `storage/logs`, cache view, session file, upload gagal | `LOG_CHANNEL=stderr`; `VIEW_COMPILED_PATH=/tmp/views`; `SESSION_DRIVER=cookie` atau `database`; `CACHE_STORE=array` atau `database`; `APP_STORAGE_PATH=/tmp/storage` bila perlu |
| Upload tidak persisten | Logo/favicon toko (Pengaturan) dan foto kendaraan dari admin hilang | Pakai disk eksternal (S3-compatible / Vercel Blob) lewat `FILESYSTEM_DISK`. Seeder memakai foto lokal di `public/`, itu aman karena ikut ter-deploy. **Perlu dicek di kode saat deploy**: view memakai `asset($vehicle->image)` |
| Tidak ada queue worker | Job/mail antrean tidak diproses | `QUEUE_CONNECTION=sync` (paling sederhana untuk skala demo), atau `database` + pemicu cron eksternal yang menjalankan `queue:work --stop-when-empty` |
| Tidak ada scheduler | `routes/console.php` (`bookings:expire` tiap 5 menit, `bookings:remind` harian, `bookings:flag-overdue` tiap jam) tidak jalan | Vercel Cron memanggil endpoint terproteksi (lihat bagian 4) yang menjalankan command-nya. Webhook Xendit EXPIRED tetap jadi jalur utama expire |
| MySQL tidak tersedia | Database harus di luar Vercel | Managed MySQL eksternal (mis. PlanetScale, Aiven, TiDB Serverless). Pakai SSL (`MYSQL_ATTR_SSL_CA`/opsi sesuai penyedia) dan batasi koneksi (serverless membuka koneksi per invocation) |
| Cold start + batas durasi function | Request pertama lambat; proses panjang terpotong | Hindari proses berat di request; cache config/route saat build |
| Build Vite | `public/build` di-ignore git, harus ada saat runtime | Jalankan `npm ci && npm run build` di build step Vercel (Build Command) |
| `APP_KEY` dan env | Tidak ada `.env` di server | Semua di Dashboard Vercel > Settings > Environment Variables (jangan commit) |
| HTTPS | — | Bawaan Vercel (domain `*.vercel.app` atau domain sendiri), set `APP_URL` https dan paksa skema (`URL::forceScheme` / trusted proxies) bila perlu |

## 3. Struktur file yang akan dibuat (belum dibuat)

```
api/index.php      # require __DIR__.'/../public/index.php';
vercel.json        # runtime vercel-php, routes, buildCommand, crons
```

Contoh `vercel.json` (sketsa, versi paket `vercel-php` harus dicek saat deploy):

```json
{
  "buildCommand": "npm ci && npm run build",
  "functions": {
    "api/index.php": { "runtime": "vercel-php@<versi>" }
  },
  "routes": [
    { "src": "/(build|fonts|images)/(.*)", "dest": "/public/$1/$2" },
    { "src": "/(.*)", "dest": "/api/index.php" }
  ],
  "crons": [
    { "path": "/internal/cron/jadwal", "schedule": "*/5 * * * *" }
  ],
  "env": {
    "APP_ENV": "production",
    "APP_DEBUG": "false",
    "LOG_CHANNEL": "stderr",
    "SESSION_DRIVER": "cookie",
    "CACHE_STORE": "array",
    "QUEUE_CONNECTION": "sync",
    "VIEW_COMPILED_PATH": "/tmp/views"
  }
}
```

Rahasia (`APP_KEY`, `DB_*`, `XENDIT_*`, `CRON_SECRET`) **tidak** ditaruh di `vercel.json`, hanya di dashboard.

## 4. Pengganti scheduler: Vercel Cron

- Buat satu rute terproteksi, mis. `GET /internal/cron/jadwal`, yang memeriksa header `Authorization: Bearer $CRON_SECRET` (Vercel mengirim header ini otomatis bila env `CRON_SECRET` diset) lalu memanggil `Artisan::call('bookings:expire')`, dan `bookings:flag-overdue` (tiap jam) serta `bookings:remind` (harian) bila waktunya, atau cukup `schedule:run` (Laravel yang memilah command yang jatuh tempo).
- Tolak (401) tanpa token yang benar; jangan dimasukkan ke grup `auth` karena pemanggilnya Vercel, bukan user.
- Perhatian: cron Vercel pada paket Hobby dibatasi (sekitar sekali per hari), jadi interval 5 menit butuh paket berbayar. Untuk demo, expire tetap berjalan lewat webhook Xendit EXPIRED dan tombol "Cek Status Xendit" di admin; alternatifnya pemicu cron eksternal (mis. cron-job.org) ke endpoint yang sama. Cek batas terbaru di dokumentasi Vercel.

## 5. Webhook Xendit

- Ganti URL webhook invoice di Dashboard Xendit (mode test) dari URL ngrok menjadi `https://<domain-publik>/webhooks/xendit`.
- Isi `XENDIT_CALLBACK_TOKEN` di env Vercel sama persis dengan token di dashboard Xendit; controller memverifikasi header `x-callback-token` (`app/Http/Controllers/XenditWebhookController.php`).
- `success_redirect`/`failure_redirect` invoice dibangun dari `APP_URL`, jadi `APP_URL` harus domain publik.
- Rute webhook sudah dikecualikan dari CSRF (`bootstrap/app.php`).
- Produksi sungguhan butuh verifikasi bisnis Xendit dan `XENDIT_SECRET_KEY` live (di luar rencana demo ini).

## 6. Checklist deploy (nanti)

Persiapan
- [ ] Putuskan penyedia MySQL terkelola, buat database, catat host/user/password/SSL.
- [ ] Putuskan penyimpanan upload (S3-compatible/Vercel Blob) dan sesuaikan `config/filesystems.php`.
- [ ] Buat akun Vercel dan hubungkan repo GitHub (dilakukan manual oleh tim, bukan agent).

Kode (PR terpisah)
- [ ] Tambah `api/index.php` dan `vercel.json` (bagian 3), cek versi `vercel-php`.
- [ ] Tambah rute cron terproteksi + test (bagian 4).
- [ ] Cek tidak ada penulisan ke `storage/` saat runtime selain yang diarahkan ke `/tmp`.
- [ ] Pastikan trusted proxies/HTTPS (`APP_URL` https) benar.

Environment di dashboard Vercel
- [ ] `APP_KEY` (`php artisan key:generate --show` di lokal), `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`.
- [ ] `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (+ SSL).
- [ ] `LOG_CHANNEL=stderr`, `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION`, `FILESYSTEM_DISK` sesuai tabel bagian 2.
- [ ] `MAIL_*` ke SMTP sungguhan (Mailpit hanya lokal).
- [ ] `XENDIT_SECRET_KEY` (test `xnd_development_*`), `XENDIT_CALLBACK_TOKEN`, `CRON_SECRET`.

Rilis
- [ ] Deploy preview, jalankan migrasi dan seeder demo sekali dari lokal ke DB terkelola (`php artisan migrate --force`; seeder bila perlu), bukan dari function.
- [ ] Uji: beranda, katalog, detail + kalender, login, checkout, redirect ke Xendit.
- [ ] Ubah URL webhook di Xendit, bayar lewat simulasi dashboard Xendit, pastikan booking jadi Lunas.
- [ ] Uji endpoint cron (token benar = 200, tanpa token = 401) dan expire booking pending.
- [ ] Cek dark mode, mobile, dan log di Vercel (stderr).

## 7. Cadangan

Bila batasan serverless terlalu mengganggu (upload, queue, scheduler), opsi VPS Ubuntu + Docker Compose (nginx, php-fpm, mysql, supervisor untuk queue, cron `schedule:run`) atau Laravel Forge, seperti di deskripsi EG-41, menyelesaikan semuanya tanpa workaround, dengan biaya server dan pengelolaan sendiri.
