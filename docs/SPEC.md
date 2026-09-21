# SPESIFIKASI EDUWGO — RENTAL MOTOR

- Sumber UI: Figma "ED.RENT (Eduwork)" (Website + Dashboard, desktop 1440 + mobile) — brand, warna, logo diganti ke EduwGo
- Referensi UX: tiket.com/sewa-mobil
- Repo: github.com/Projek-Team-2-Eduwork/EduwGO
- Payment Gateway: Xendit (Invoice API)
- Stack: Laravel 13 + Breeze (Blade + Tailwind + Alpine), Sail
- Backlog: Linear team EduwGO (EG-1..41)

## 1. Halaman Public & User (Penyewa)

### Home / Beranda [Figma: General > Home]
- Hero: banner motor di atas accent-gradient oranye + tagline serif ("Rental Motor Cepat & Aman, Mulai Rp75.000/hari"), tombol Pilih Kendaraan (navy, chamfered)
- "Mulai Perjalanan Anda dalam 3 Langkah": pilih motor → pilih durasi & bayar → ambil di lokasi (ikon bulat oranye)
- Katalog ringkas: card motor (foto, nama, tipe, plat, badge Tersedia/Disewa, harga/hari, tombol Booking) + "Lihat semua"
- Syarat & Ketentuan Rental Motor (6 poin sesuai desain: minimal 1×24 jam, konfirmasi via WhatsApp/datang, bawa 2 identitas, sertakan sosmed & WA aktif, cek kondisi saat serah terima, penyedia berhak batalkan/ganti unit)
- Footer: brand, deskripsi, About, Community, Socials, Kontak (Chat Admin), toggle dark mode
- Nama brand (default EduwGo), logo, warna diambil dari Pengaturan Toko

### Pilih Kendaraan / Daftar Kendaraan [Figma: User > Daftar Kendaraan]
- Filter bar: Tipe kendaraan (Matic / Cub — daftar tipe dikelola admin), Tanggal & jam mulai sewa, Durasi (hari), Status (Tersedia), Cari nama motor
- Ketersediaan dihitung per unit berdasarkan overlap tanggal-jam + buffer 1 jam: unit "Tersedia" hanya jika tidak ada booking aktif yang bentrok di rentang [mulai − 1 jam, mulai + durasi×24 jam + 1 jam]
- Filter tanggal+durasi tersimpan di session/query string, dibawa ke halaman detail & modal durasi
- Grid card, pagination, responsive (mobile: 1 kolom)

### Detail Kendaraan [Figma: User > Deskripsi Kendaraan]
- Foto utama, nama, tipe, spesifikasi: Brand, Tipe, Kapasitas Tangki, No. Plat (+ field custom opsional: tahun, transmisi, CC)
- Deskripsi, harga/hari, badge status, tombol Booking
- Rekomendasi Kendaraan: motor lain tipe sama yang tersedia di rentang tanggal yang dipilih
- Modal "Pilih Durasi Sewa": satuan 24 jam/hari, pilihan 1–5 hari (maksimal dari settings), tiap opsi tampilkan tanggal & jam selesai otomatis, total = harga/hari × hari, tombol Checkout
- Style input durasi bisa custom (catatan tugas): default list 1–5 hari ala desain; alternatif stepper/date-range boleh
- Jika unit tidak tersedia di rentang itu: opsi durasi yang bentrok di-disable + pesan

### Checkout (dari modal, tanpa keranjang: 1 booking = 1 motor)
- Wajib login & email terverifikasi
- Data penyewa: nama, nomor WhatsApp (dari profil, bisa edit), catatan opsional
- Validasi ulang ketersediaan di server dalam DB transaction + lockForUpdate pada unit motor (anti race condition)
- Snapshot harga/hari ke booking saat checkout
- Buat booking status Pending, kode booking format EG.000001, buat invoice Xendit (60 menit), redirect ke Menunggu Pembayaran
- Unit terkunci saat checkout, dilepas otomatis saat expired / batal
- Tanggal booking tidak bisa diubah setelah checkout; harus batalkan & booking ulang

### Menunggu Pembayaran [Figma: User > Menunggu Pembayaran]
- Countdown 60 menit (jam:menit:detik) sinkron dengan expires_at invoice Xendit
- Tombol Bayar Sekarang → invoice_url Xendit (VA, QRIS, e-wallet, retail)
- Tombol Chat Admin → wa.me/{nomor dari settings}?text=Halo, booking EG.000001
- Countdown habis → auto redirect ke Pembayaran Gagal

### Pembayaran Berhasil [Figma: User > Pembayaran Berhasil]
- Ilustrasi sukses, pesan "cek status booking & konfirmasi pengambilan via Chat Admin", tombol Chat Admin & Lihat Pesanan

### Pembayaran Gagal / Waktu Habis [Figma: User > Pembayaran Gagal]
- "Ups, Waktunya Habis!", tombol Booking Ulang (booking baru, invoice baru, external_id baru) & Chat Admin

### Daftar Pesanan [Figma: User > Daftar Pesanan, state kosong & terisi]
- Card per booking: foto motor, nama, kode booking, tanggal & jam sewa, status badge, harga, tombol Detail
- Filter status, state kosong dengan ilustrasi + CTA Pilih Kendaraan
- Tombol Batalkan hanya saat Pending. Setelah Lunas: tidak ada tombol batal, tampil teks "Hubungi admin via WhatsApp untuk pembatalan" + tombol Chat Admin

### Detail Pemesanan [Figma: User > Detail Pemesanan]
- Foto motor, nama, plat; Nama penyewa, WhatsApp, ID booking, tanggal booking, Mulai sewa & Selesai sewa (tanggal + jam), durasi, total, status
- Timeline status (log perubahan: kapan, oleh siapa)
- Tombol kontekstual: Bayar (pending), Chat Admin (semua), Batalkan (pending saja)
- Peringatan jika lewat waktu selesai sewa dan belum dikembalikan (overdue) — tanpa denda otomatis

### Profil [Figma: Profil > Home]
- Edit nama, email, nomor WhatsApp, akun sosmed (sesuai S&K); ubah kata sandi; preferensi tema (terang/gelap/sistem)
- Ringkasan: jumlah booking, booking aktif

## 2. Halaman Admin (Pengelola Rental)

Layout: sidebar (Dashboard, Daftar Pesanan, Kendaraan, Tipe Kendaraan, Pengaturan, Log Out), navbar atas sama dengan user

### Dashboard Admin [Figma: Admin > Dashboard Admin]
- Widget "5 Top Orderan Rental": donut chart 5 motor paling sering dibooking + total order (Chart.js, warna ikut tema)
- Widget "Jumlah Kendaraan": total unit, Tersedia, Jumlah bookingan (aktif), Sedang disewa
- Widget "Pendapatan": filter Tipe Kendaraan + Tanggal Awal + Tanggal Akhir → Total Pendapatan (booking Lunas/Sedang disewa/Sudah kembali)
- Widget "Pesanan Terakhir": 8 booking terbaru + "Lihat semua" → Daftar Pesanan
- Iterasi: badge jumlah booking overdue hari ini

### Daftar Pesanan Admin [Figma: Admin > Daftar Pesanan]
- Filter: Status (Semua/Pending/Lunas/Sedang disewa/Sudah kembali/Batal/Expired), Tipe kendaraan, Tanggal & jam sewa, Cari kendaraan / kode booking
- Baris: foto, nama motor, kode booking, tanggal & jam sewa, status badge, harga, tombol Update
- Update status (modal): Lunas → Sedang disewa (serah terima), Sedang disewa → Sudah kembali (unit balik), Pending/Lunas → Batal (alasan wajib; untuk Lunas: catat kesepakatan refund dari chat WA di kolom catatan)
- Detail booking: data penyewa (nama, WA, sosmed), riwayat payment Xendit, log status, catatan admin, tombol Chat Penyewa (wa.me nomor penyewa)
- Iterasi: tandai merah booking overdue (lewat selesai sewa, belum Sudah kembali)

### Kendaraan Admin [Figma: Admin > Kendaraan Admin]
- Index: card grid (foto, nama, tipe, plat, badge status, harga/hari, tombol Detail/Update), filter Status (Semua/Tersedia/Disewa/Nonaktif), Tipe, Cari, tombol "+ Tambah"
- Create/Edit: nama, brand, tipe (pilih dari master tipe), kapasitas tangki, no. plat (unik), harga/hari, foto utama, deskripsi, status aktif/nonaktif
- Siswa input sendiri list motor (catatan tugas) → seeder minimal 10 motor dengan foto lokal
- Soft delete jika sudah punya booking; hapus permanen hanya jika belum pernah dibooking
- Detail unit: jadwal booking unit ini (list rentang tanggal terpakai, termasuk buffer)

### Kelola Tipe Kendaraan
- CRUD tipe: Matic, Cub, Sport, dst. Dipakai filter user & admin

### Pengaturan Toko
- Brand: nama (default EduwGo), logo terang & logo gelap, tagline hero, favicon
- Kontak: nomor WhatsApp admin, alamat lokasi pengambilan, jam operasional, link sosial media footer
- Aturan: durasi maksimal sewa (default 5 hari), durasi invoice Xendit (default 60 menit), buffer antar sewa (default 60 menit), teks Syarat & Ketentuan
- Warna primer & aksen bisa di-override dari sini (opsional, default palet EduwGo)

### Kelola User (minimal)
- Daftar penyewa: nama, WA, jumlah booking; buat akun admin tambahan
- Role hanya 2: admin & user (tidak ada kasir)

## 3. Integrasi Payment Gateway (Xendit)

- Produk: Xendit Invoice API (penyewa pilih metode bayar di halaman Xendit)
- Package: xendit/xendit-php
- createInvoice saat checkout: external_id = kode booking (EG.000001), amount = total, invoice_duration = 3600 detik (dari settings), success_redirect → Pembayaran Berhasil, failure_redirect → Pembayaran Gagal
- Endpoint Webhook: POST /webhooks/xendit (dikecualikan dari CSRF)
  - Verifikasi header x-callback-token
  - Idempotent: webhook yang sama bisa dikirim lebih dari sekali, tidak boleh double-update
  - PAID / SETTLED → payment paid, booking Pending → Lunas
  - EXPIRED → payment expired, booking Pending → Expired, unit dilepas
  - Simpan raw payload ke payments.gateway_payload
- Tabel payments (terpisah dari bookings):
  - booking_id, method (xendit_invoice | cash), gateway_reference, gateway_url, gateway_payload (json)
  - amount, status (pending | paid | expired | failed), paid_at, expires_at
  - method cash: admin catat bayar tunai saat penyewa datang langsung (iterasi)
- Fallback: scheduler tiap 5 menit cek booking Pending yang expires_at lewat → Expired; tombol "Cek Status Xendit" di admin (GET /v2/invoices/{id})
- Refund: tidak lewat sistem. Pembatalan setelah Lunas hanya oleh admin setelah konfirmasi via WA; refund manual, dicatat di catatan booking
- Test mode: secret key xnd_development_*, simulasi bayar dari dashboard Xendit, webhook lokal via ngrok
- Produksi: perlu verifikasi bisnis Xendit (KTP + dokumen usaha)

## 4. Notifikasi & Kontak

- Desain hanya pakai "Chat Admin" (WhatsApp) — tidak ada sistem notifikasi otomatis
- Wajib: tombol Chat Admin di navbar ("Butuh bantuan?"), Menunggu Pembayaran, Berhasil, Gagal, Detail Pemesanan, Daftar Pesanan (untuk batal setelah lunas); link wa.me dengan pesan terisi otomatis (kode booking)
- Iterasi (opsional, email via Mailpit/SMTP): booking dibuat (link bayar), pembayaran berhasil, expired, pengingat H-1 pengambilan, pengingat waktu selesai sewa
- Admin: tombol Chat Penyewa di detail booking

## 5. Halaman Autentikasi [Figma: General > Buat Akun, Masuk]

- Masuk: split layout (banner motor kiri dengan accent-gradient, form kanan), email + password, "Lupa kata sandi", link ke Buat Akun
- Buat Akun: nama, email, nomor WhatsApp, password + konfirmasi, checkbox setuju S&K
- Verifikasi Email: wajib sebelum checkout (cegah akun spam kunci unit)
- Lupa & Reset Password (Breeze)
- Redirect setelah login: admin → /admin/dashboard, user → halaman sebelumnya / home
- Google login: tidak ada di desain, skip

## 6. Alur Status Booking (State Machine)

```
pending ──bayar (webhook)──> paid ──admin serah terima──> rented ──admin terima unit──> returned
   │                          │                             │
   ├─expire (webhook/cron)──> expired                       └─lewat selesai sewa (cron)──> flag overdue (status tetap rented, tanda merah)
   ├─batal (user)──> cancelled
   └─batal (admin)──> cancelled
                              └─batal (ADMIN SAJA, setelah konfirmasi WA, alasan wajib)──> cancelled
```

- Label UI (sesuai Figma): pending = Pending, paid = Lunas, rented = Sedang disewa, returned = Sudah kembali, cancelled = Batal, expired = Expired (waktu habis)
- Status "Tersedia/Disewa" pada motor = turunan dari booking aktif (paid/rented) di waktu sekarang, bukan kolom manual; kolom manual hanya aktif/nonaktif
- Semua transisi lewat `BookingService::transition()` dan tercatat di `booking_status_histories` (siapa, kapan, catatan)

## 7. Struktur Data (Ringkas)

- users: name, email, phone (WA), social_account, password, email_verified_at, theme_preference (light|dark|system); role via spatie (admin|user)
- vehicle_types: name, slug (Matic, Cub, ...)
- vehicles: vehicle_type_id, name, slug, brand, plate_number (unique), tank_capacity, price_per_day, image, description, is_active, deleted_at
- bookings: code (EG.000001), user_id, vehicle_id, start_at (datetime), end_at (datetime), duration_days, price_per_day (snapshot), total_amount, status, customer_name, customer_phone, notes, cancel_reason
- booking_status_histories: booking_id, from_status, to_status, changed_by (null = sistem), note
- payments: booking_id, method, gateway_reference, gateway_url, gateway_payload, amount, status, paid_at, expires_at
- settings: key, value (json) — brand, logo, warna, WA, alamat, S&K, durasi maks, durasi invoice, buffer
- Availability: unit tersedia jika tidak ada booking status IN (pending, paid, rented) dengan (start_at − buffer) < end_baru AND (end_at + buffer) > start_baru; buffer = 60 menit

## 8. Desain & Frontend

- Layout & struktur halaman ikuti Figma; warna, tipografi, bentuk tombol diganti ke identitas EduwGo di bawah
- Palet EduwGo (light), dipetakan ke Tailwind via CSS variable:

```css
:root {
  --bg:              #E4E7EC;
  --bg-gradient:     linear-gradient(180deg, #F2F4F7 0%, #D9DEE6 100%);
  --surface:         #FFFFFF;
  --navy-900:        #0F1A2E;   /* headline serif, tombol utama */
  --navy-700:        #1C2942;   /* hover, frame */
  --ink-muted:       #4B5563;   /* body text */
  --orange-500:      #F26A1B;   /* ikon bulat, tombol kecil, badge */
  --accent-gradient: linear-gradient(135deg, #FF8A3D 0%, #F2571C 100%); /* hero image bg */
  --coral-300:       #FFB27A;   /* highlight lembut */
  --border:          #C9D0DA;
  --gold-tan:        #C78A5A;   /* aksen interior/box */
}
```

- Dark mode (class strategy `dark`, token dibalik, aksen oranye tetap):

```css
.dark {
  --bg:              #0B1220;
  --bg-gradient:     linear-gradient(180deg, #111A2B 0%, #0B1220 100%);
  --surface:         #141E31;
  --navy-900:        #F2F4F7;   /* headline & tombol utama jadi terang */
  --navy-700:        #D9DEE6;
  --ink-muted:       #A3ADBD;
  --orange-500:      #FF8A3D;   /* sedikit lebih terang supaya kontras */
  --accent-gradient: linear-gradient(135deg, #FF8A3D 0%, #F2571C 100%);
  --coral-300:       #FFB27A;
  --border:          #263248;
  --gold-tan:        #D9A377;
}
```

  - Toggle di navbar & footer (ikon matahari/bulan), pilihan: terang / gelap / ikut sistem
  - Urutan prioritas: preferensi user login (users.theme_preference) > localStorage > prefers-color-scheme
  - Script anti-flash di `<head>` (set class dark sebelum render)
  - Logo terang & gelap dari settings; foto motor pakai background surface, bukan putih hardcode
  - Chart.js: warna grid/label ikut token; badge status: kontras dicek di kedua tema (WCAG AA)
- Tipografi: heading serif (Instrument Serif, fallback Playfair Display), body sans (Inter atau Manrope) — Google Fonts, self-host di public/fonts untuk offline
- Tombol utama: navy, sudut miring (chamfered) via clip-path polygon, bukan rounded; tombol sekunder oranye kecil; hover navy-700
- Aksen oranye hanya untuk ikon, tombol sekunder, badge, satu hero image; sisanya netral
- Background halaman: --bg-gradient, bukan flat
- Komponen Blade: navbar (user/admin), sidebar admin, card motor, badge status, filter bar, modal durasi, countdown (Alpine), footer, empty state, pagination, theme-toggle
- Semua halaman ada versi mobile di Figma → responsive wajib (dinilai)
- Grafik dashboard: Chart.js via CDN
- Iterasi UI yang boleh: style input durasi, filter tambahan, kalender ketersediaan per unit (opsional)

## 9. Di Luar Scope (sengaja tidak dibuat, tidak ada di desain)

- Keranjang / multi motor per booking
- Denda, deposit, DP, refund via sistem, perpanjangan, blacklist, ulasan/rating
- Upload KTP/SIM (identitas dicek fisik saat pengambilan, sesuai S&K)
- Role kasir, laporan export PDF/Excel, backup DB (boleh ditambah kalau waktu sisa)
- Pembatalan mandiri oleh user setelah Lunas
- Google login

## 10. Keputusan (disepakati 2026-09-21)

- Framework: Laravel 13, PHP 8.3, Breeze Blade + Tailwind + Alpine
- Lokal: Laravel Sail (mysql + mailpit); Database: MySQL
- Timezone: Asia/Jakarta di config/app.php
- Satuan sewa: 24 jam per hari, mulai dari tanggal+jam yang dipilih
- Durasi: 1–5 hari (maks dari settings)
- Pembayaran: lunas di awal via Xendit, tanpa DP
- Batas bayar: 60 menit (invoice_duration 3600)
- Pembatalan: user hanya saat Pending; setelah Lunas hanya admin, setelah konfirmasi via chat/WA, refund manual
- Buffer antar sewa: 1 jam
- Nama brand: EduwGo (default di settings, bisa diubah)
- Warna & gaya: palet di bagian 8 + dark mode
- Nama repo: EduwGO → github.com/Projek-Team-2-Eduwork/EduwGO
- Deploy: belakangan; sementara demo lokal + ngrok untuk webhook
- Pembagian kerja (per alur): A Booking–Bayar = Nastyo / B Admin = Faisal / C Public & Fondasi = Mario
- Git flow: `main` ← `dev` ← branch issue dari Linear; 1 issue = 1 branch = 1 PR; review silang A→B, B→C, C→A; agent tidak commit
