# EduwGo — Panduan Agent

Website rental motor. Tugas Bootcamp Eduwork, tim 3 orang. UI wajib ikut Figma "ED.RENT (Eduwork)"; brand/warna/logo diganti ke EduwGo.

- Spesifikasi lengkap: `docs/SPEC.md` (baca sebelum mengerjakan fitur apa pun)
- Keputusan bisnis: `docs/SPEC.md` bagian 10
- Backlog & detail tiap task: Linear team **EduwGO** (prefix `EG-`), project EduwGo
- Repo: github.com/Projek-Team-2-Eduwork/EduwGO
- Figma: figma.com/community/file/1577539134528638346
- Referensi UX: tiket.com/sewa-mobil

## Stack

- Laravel 13, PHP 8.3, Breeze (Blade + Tailwind + Alpine). Tanpa Inertia/Livewire/Vue/React.
- Laravel Sail (mysql + mailpit). Semua perintah lewat `./vendor/bin/sail ...`.
- Xendit Invoice API (`xendit/xendit-php`), spatie/laravel-permission, Chart.js via CDN.
- Timezone `Asia/Jakarta`. Database MySQL.
- Dark mode: Tailwind `darkMode: 'class'`, token warna via CSS variable (lihat `docs/SPEC.md` bagian 8).

## Aturan Git — WAJIB

1. **Agent TIDAK BOLEH commit, push, merge, rebase, reset, atau membuat PR.** Hanya ubah file di working tree. Manusia yang review diff lalu commit sendiri.
2. Sebelum mengerjakan task, pastikan sedang di branch yang benar. Nama branch diambil dari tombol "Copy git branch name" di issue Linear (format `<user>/eg-<nomor>-<slug>`). Jangan bekerja di `main` atau `dev`.
3. Setiap task selesai, agent **menyarankan** pesan commit (tidak menjalankannya) dengan format:
   ```
   <type>(<scope>): <deskripsi singkat, bahasa Indonesia> (EG-<nomor>)
   ```
   - `type`: `feat`, `fix`, `chore`, `refactor`, `docs`, `test`, `style`, `perf`, `ci`
   - `scope`: area kode, contoh `booking`, `xendit`, `admin`, `auth`, `vehicle`, `ui`, `db`, `settings`
   - `(EG-<nomor>)` wajib di akhir supaya Linear otomatis menautkan commit & PR ke issue
   - Contoh: `feat(booking): cek ketersediaan per unit dengan buffer 1 jam (EG-11)`
   - Contoh: `chore(db): migration vehicles, vehicle_types, settings (EG-3)`
4. Satu issue Linear = satu branch = satu PR ke `dev`. Jangan campur dua issue dalam satu branch.
5. Jangan sentuh file di luar scope issue. Butuh perubahan di file bersama (`app/Enums/BookingStatus.php`, `app/Services/BookingService.php`, layout, komponen di `resources/views/components/`) → beri tahu manusia, jangan ubah diam-diam.
6. Jangan ubah `.env`, kredensial, atau `composer.lock`/`package-lock.json` kecuali issue memang tentang itu.

## Pembagian alur (label Linear)

- **Alur A: Booking–Bayar** (Nastyo): AvailabilityService, modal durasi, checkout, Xendit, webhook, countdown, expire, pesanan user
- **Alur B: Admin** (Faisal): dashboard, daftar pesanan admin, update status, kendaraan CRUD, tipe kendaraan, pengaturan toko
- **Alur C: Public & Fondasi** (Mario): init project, auth, home, katalog, detail motor, design token, dark mode, layout, komponen, responsive, CI

Kalau task menyentuh alur orang lain, hentikan dan tanya.

## Konvensi kode

- Bahasa: kode & identifier Inggris; label UI, pesan validasi, komentar penjelasan bahasa Indonesia.
- Controller tipis. Logika bisnis di `app/Services/` (`BookingService`, `AvailabilityService`, `XenditService`, `PenaltyService` tidak ada — di luar scope).
- Semua perubahan status booking **hanya** lewat `BookingService::transition()`. Jangan `$booking->update(['status' => ...])` langsung.
- Enum `App\Enums\BookingStatus`: `pending, paid, rented, returned, cancelled, expired`. Label UI: Pending, Lunas, Sedang disewa, Sudah kembali, Batal, Expired.
- Status motor "Tersedia/Disewa" = turunan booking aktif, bukan kolom. Kolom manual hanya `is_active`.
- Validasi pakai FormRequest. Otorisasi pakai Policy. Role hanya `admin` dan `user`.
- Harga: snapshot `price_per_day` ke `bookings` saat checkout.
- Ketersediaan: booking status IN (pending, paid, rented) dan `(start_at − buffer) < end_baru AND (end_at + buffer) > start_baru`, buffer dari `setting('booking.buffer_minutes', 60)`.
- Checkout: `DB::transaction` + `lockForUpdate()` pada vehicle.
- Webhook Xendit: verifikasi `x-callback-token`, idempotent, simpan raw payload.
- Nilai konfigurasi bisnis (WA admin, durasi maks, durasi invoice, buffer, brand, S&K) dari tabel `settings` via helper `setting()`, jangan hardcode.
- View: layout `layouts.app` (user) & `layouts.admin`. Komponen Blade di `resources/views/components/`. Cek light + dark + mobile (375px) sebelum selesai.
- Query: hindari N+1 (eager load). Cek dengan Debugbar.
- Test: tiap issue minimal 1 feature test happy path + 1 kasus gagal. Jalankan `sail artisan test` dan `sail bin pint --test` sebelum menyatakan selesai.

## Di luar scope (jangan dibuat meski terlihat "berguna")

Keranjang, multi motor per booking, denda, deposit, DP, refund via sistem, perpanjangan, blacklist, ulasan/rating, upload KTP, role kasir, export PDF/Excel, backup DB, pembatalan mandiri user setelah Lunas, Google login.

## Definition of Done (per issue)

- Migration + model + relasi sesuai `docs/SPEC.md` bagian 7
- FormRequest + Policy
- Responsive (mobile Figma) + dark mode dicek
- Feature test happy path + 1 gagal, hijau
- Seeder tersedia jika ada data baru
- Tidak ada N+1
- Saran pesan commit dengan `(EG-<nomor>)`
