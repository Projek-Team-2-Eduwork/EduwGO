# Keputusan Tim EduwGo

Disepakati 21 September 2026. Kalau mau mengubah, diskusikan bertiga dulu, lalu update file ini dan `docs/SPEC.md`.

## 1. Keputusan bisnis

| Hal | Keputusan |
|---|---|
| Satuan sewa | 24 jam per hari, mulai dari tanggal + jam yang dipilih penyewa |
| Durasi | 1–5 hari (maksimal diatur admin di Pengaturan) |
| Pembayaran | Lunas di awal via Xendit Invoice, tanpa DP |
| Batas bayar | 60 menit setelah checkout; lewat → status Expired, unit dilepas |
| Buffer antar sewa | 1 jam (unit tidak bisa dibooking 1 jam sebelum/sesudah booking lain) |
| Pembatalan oleh penyewa | Hanya saat status Pending |
| Pembatalan setelah Lunas | Hanya admin, setelah konfirmasi via WhatsApp; refund manual, dicatat di catatan booking |
| Keterlambatan | Ditandai merah di admin (overdue), **tanpa denda** |
| 1 booking | 1 motor. Tidak ada keranjang |
| Identitas penyewa | Dicek fisik saat pengambilan (sesuai S&K), tidak ada upload KTP |
| Notifikasi | Hanya tombol Chat Admin (WhatsApp). Email opsional di Fase 3 |
| Role | `admin` dan `user` saja, tidak ada kasir |
| Kode booking | `EG.000001` (6 digit, urut) |

Di luar scope (tidak dibuat): keranjang, denda, deposit, DP, refund via sistem, perpanjangan, blacklist, ulasan/rating, upload KTP, kasir, export PDF/Excel, backup DB, pembatalan mandiri setelah Lunas, Google login.

## 2. Keputusan teknis

| Hal | Keputusan |
|---|---|
| Framework | Laravel 13, PHP 8.3 |
| Frontend | Breeze: Blade + Tailwind + Alpine. Tanpa Inertia/Livewire/Vue/React |
| Lokal | Laravel Sail (Docker): app (image kustom di `docker/8.3/`), MySQL 8.4, Mailpit, phpMyAdmin |
| Database | MySQL |
| Timezone | `Asia/Jakarta` |
| Locale | `id` |
| Payment | Xendit Invoice API (`xendit/xendit-php`), sandbox dulu |
| Role | `spatie/laravel-permission` |
| Chart | Chart.js via CDN |
| Dark mode | Tailwind `darkMode: 'class'`, warna via CSS variable |
| Brand | Nama default **EduwGo**, bisa diubah dari Pengaturan. Palet & tipografi di `docs/SPEC.md` bagian 8 |
| Deploy | Belakangan. Sementara demo lokal + ngrok untuk webhook |

## 3. Pembagian kerja

Dibagi per alur (label di Linear), bukan per layer:

| Alur | Siapa | Cakupan |
|---|---|---|
| **A: Booking–Bayar** | Nastyo | AvailabilityService, modal durasi, checkout, Xendit invoice & webhook, countdown, auto-expire, Daftar/Detail Pesanan user, profil |
| **B: Admin** | Faisal | Dashboard, Daftar Pesanan admin, update status, CRUD kendaraan, tipe kendaraan, pengaturan toko, overdue |
| **C: Public & Fondasi** | Mario | Init project, auth, home, katalog, detail motor, design token, dark mode, layout, komponen, responsive, CI |

Review silang: A review B, B review C, C review A.

File bersama yang perubahannya harus disetujui ketiganya:
- `app/Enums/BookingStatus.php`
- `app/Services/BookingService.php`
- `resources/views/layouts/*`
- `resources/views/components/*`
- `docs/ERD.md`, `docs/SPEC.md`, file ini

## 4. Alur Git

```
main             ← release per fase (tag v0.1, v0.2), protected
dev              ← integrasi, protected, wajib 1 approval
<nama>/eg-<nomor> ← branch issue, dibuat dari dev, 1 issue = 1 branch = 1 PR
```

- Format nama branch: `<nama>/eg-<nomor>`, huruf kecil. Contoh: `mario/eg-2`, `nastyo/eg-11`, `faisal/eg-18`.
- Linear otomatis menautkan branch & PR ke issue selama ada `eg-<nomor>` di nama branch. Atur sekali di Linear: Settings → Workspace → Git → Branch format = `{username}/{issueIdentifier}` supaya tombol **Copy git branch name** langsung memberi format ini.
- PR selalu ke `dev`. Jangan push langsung ke `dev`/`main`.
- Jangan campur dua issue dalam satu branch.
- Format pesan commit:

  ```
  <type>(<scope>): <deskripsi singkat, bahasa Indonesia> (EG-<nomor>)
  ```

  - `type`: `feat`, `fix`, `chore`, `refactor`, `docs`, `test`, `style`, `perf`, `ci`
  - `scope`: `booking`, `xendit`, `admin`, `auth`, `vehicle`, `ui`, `db`, `settings`, `setup`
  - `(EG-<nomor>)` wajib di akhir supaya Linear menautkan commit & PR ke issue
  - Contoh: `feat(booking): cek ketersediaan per unit dengan buffer 1 jam (EG-11)`
  - Tanpa trailer atau atribusi tambahan apa pun di pesan commit maupun deskripsi PR.

## 5. Konvensi kode

- Kode & identifier bahasa Inggris; label UI, pesan validasi, komentar bahasa Indonesia.
- Controller tipis, logika bisnis di `app/Services/`.
- Status booking **hanya** diubah lewat `BookingService::transition()`.
- Validasi pakai FormRequest, otorisasi pakai Policy.
- Nilai bisnis (WA admin, durasi, buffer, brand, S&K) dari `setting()`, bukan hardcode.
- Hindari N+1 (eager load), cek dengan Debugbar.
- Setiap halaman dicek di 375px / 768px / 1280px, light & dark.

## 6. Definition of Done (per issue)

- [ ] Migration + model + relasi sesuai `docs/ERD.md`
- [ ] FormRequest + Policy
- [ ] Responsive (mobile sesuai Figma) + dark mode dicek
- [ ] Feature test: 1 happy path + 1 kasus gagal, hijau (`sail artisan test`)
- [ ] `sail bin pint --test` bersih
- [ ] Seeder tersedia jika ada data baru
- [ ] Tidak ada N+1
- [ ] PR direview & di-approve 1 orang lain
- [ ] Fitur bayar/status: webhook & scheduler diuji lokal
