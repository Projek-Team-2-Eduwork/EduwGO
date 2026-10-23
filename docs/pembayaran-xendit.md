# Pembayaran Xendit — Panduan Operasional

Panduan lengkap alur pembayaran online EduwGo: cara kerja, cara tes **gratis & tanpa
ribet** sampai production, plus troubleshooting. Diperbarui untuk EG-52…EG-57.

---

## 1. Ringkasan alur

```
Penyewa pilih motor → checkout → booking (pending)
   → /pembayaran/{kode}  (halaman bayar sendiri, pilih metode)
   → payment_request dibuat ke API Xendit v3 (per metode)
   → penyewa bayar (scan QR / VA / e-wallet / kode retail)
   → deteksi bayar:
        1. webhook Xendit        → /webhooks/xendit (utama, real-time)
        2. polling otomatis      → "Saya Sudah Bayar" nyek-ke API Xendit
        3. admin manual          → Bayar Tunai di panel admin
   → booking jadi paid → redirect /pesanan/{kode} + notif sukses + "Langkah Selanjutnya"
```

- Halaman bayar memakai **Payments API v3** (`POST /v3/payment_requests`,
  header `api-version: 2024-11-11`), bukan hosted checkout — tampilan sendiri.
- Metode aktif (divalidasi ke akun demo): **QRIS, BCA Virtual Account, ShopeePay,
  DANA, OVO, LinkAja, AstraPay, Alfamart, Indomaret**. Metode lain tampil
  "Segera" dan bisa ditambahkan lewat konstanta `XenditService::CHANNELS`.
- Webhook diproses idempotent (`PaymentService::applyInvoiceStatus`) — kirim ulang
  aman, tidak dobel bayar.

## 2. Mode kerja (env)

| Variabel | Nilai | Keterangan |
|---|---|---|
| `XENDIT_MODE` | `payment_request` | Halaman bayar sendiri (aktif sekarang) |
| `XENDIT_MODE` | `invoice` | **Rollback**: hosted invoice Xendit (alur lama EG-48…51) |
| `XENDIT_SECRET_KEY` | `xnd_development...` | Key **demo** — semua API call diarahkan ke akun demo |
| `XENDIT_CALLBACK_TOKEN` | (rahasia) | Token verifikasi header `x-callback-token` |

Ganti `XENDIT_MODE` = cukup ubah env + `php artisan config:clear` (tanpa deploy ulang kode).

## 3. Tes lokal — GRATIS & tanpa ribet (cuma 3 langkah)

Webhook Xendit butuh URL publik HTTPS; `localhost` tidak bisa diakses server Xendit.
Solusinya pakai **Cloudflare quick tunnel** — gratis, tanpa daftar akun, tanpa kartu kredit.

### Langkah 1 — Nyalakan tunnel (sekali jalan, ±10 detik)

Di mesin developer (Windows contohnya):

```powershell
# Download sekali saja (52 MB) — resmi dari GitHub Cloudflare:
curl.exe -L -o cloudflared.exe https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe

# Nyalakan tunnel (biarkan terminal terbuka):
.\cloudflared.exe tunnel --url http://localhost:80
```

Cari baris di output yang berisi URL, contoh:

```
https://lafayette-cams-bra-endif.trycloudflare.com
```

macOS: `brew install cloudflared` lalu perintah tunnel sama.
Linux: ganti `cloudflared.exe` jadi `cloudflared`.

> **Gratis?** Ya — quick tunnel gratis selamanya, tanpa akun. URL-nya sementara:
> **berubah setiap kali tunnel di-restart**, jadi wajib update di dashboard (Langkah 2).

### Langkah 2 — Daftarkan URL di dashboard Xendit (sekali per URL)

1. Login ke **dashboard.xendit.co** (pakai akun demo/proyek lo).
2. Buka **Settings → Developers → Callbacks** (halaman "URL Webhook").
3. Scroll ke section **PAYMENTS** (bukan FVA/Disbursement — itu produk lama).
4. Isi dua baris dengan URL tunnel + path webhook:

   ```
   https://URL-LO.trycloudflare.com/webhooks/xendit
   ```

   - `payment.capture` (pembayaran berhasil)
   - `payment.failure` (pembayaran gagal)

5. Klik **"Tes dan simpan"**. Tes ping bakal diterima (endpoint membalas 200 untuk
   event terotentikasi yang referensinya tak dikenal — memang didesain begitu).

Section **INVOICES** (`invoice.status`) boleh diisi juga dengan URL yang sama —
opsional, hanya untuk mode rollback invoice.

### Langkah 3 — Verifikasi

Bukti webhook nyampe, salah satu:

- **Dashboard**: halaman **Log Webhook** → status event berubah sukses (bukan
  "URL Not Set"/gagal). Bisa juga pilih event lalu **"Kirim Ulang Webhook"**.
- **Server**: `tail -f storage/logs/xendit.log` → muncul log
  `Status invoice Xendit diterapkan` waktu webhook masuk.
- **E2E**: bayar test mode dari halaman bayar → tunggu ±beberapa detik → klik
  **"Saya Sudah Bayar"** → harusnya redirect ke `/pesanan/{kode}` + notif hijau.

### Alternatif — tes tanpa mendaftarkan webhook (tunnel lagi males nyala?)

Simulasikan webhook langsung dari server (sama efeknya dengan webhook asli):

```bash
docker exec -i eduwgo-laravel.test-1 php artisan tinker
```

```php
// Ganti KODE dengan kode booking pending, mis. 'EG.000005'
$b = Booking::where('code', 'EG.000005')->first();
$p = Payment::where('booking_id', $b->id)
    ->where('method', 'xendit_payment_request')
    ->where('status', 'pending')->latest('id')->first();
app(\App\Services\XenditService::class)->handleWebhook([
    'event' => 'payment.capture',
    'data' => [
        'payment_request_id' => $p->gateway_reference,
        'status' => 'SUCCEEDED',
        'captures' => [['capture_timestamp' => now()->toIso8601String()]],
    ],
]);
$b->refresh()->status; // harus 'paid'
```

## 4. Production (saat online beneran)

1. Deploy ke server/hosting dengan domain tetap + HTTPS (mis. `eduwgo.my.id`).
2. Daftarkan **satu kali** di dashboard Xendit:
   `https://eduwgo.my.id/webhooks/xendit` (event `payment.capture` + `payment.failure`).
3. Pastikan env production: `XENDIT_MODE=payment_request`, secret key production
   (bukan `xnd_development...`), `XENDIT_CALLBACK_TOKEN` sama dengan yang
   terdaftar di dashboard.
4. Selesai — setelah ini pengguna tidak perlu klik apa-apa: bayar → otomatis
   ke-deteksi → notif sukses.

Catatan:
- URL tunnel (`trycloudflare.com`) **hanya untuk tes lokal** — jangan dipakai di produksi.
- Jika webhook production terlambat/putus, tombol "Saya Sudah Bayar" tetap menyelesaikan
  via pengecekan API (lihat §5), jadi pengguna tidak nyangkut.

## 5. Bagaimana pembayaran terdeteksi (3 lapis pengaman)

| Lapis | Kapan jalan | Sumber kebenaran |
|---|---|---|
| 1. Webhook `/webhooks/xendit` | Real-time saat bayar | Xendit → server kita |
| 2. Polling "Saya Sudah Bayar" + `syncPaymentStatus()` | Tiap klik / tiap 5 detik | Server kita → API Xendit (`GET /v3/payment_requests/{id}`) |
| 3. Admin "Bayar Tunai" | Manual | Admin panel |

Jadi walau webhook belum ke-daftar atau telat, pengguna tetap tidak nyangkut —
polling otomatis menyelaraskan status dari API Xendit.

## 6. Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| Webhook log: **"URL Not Set"** | URL belum terdaftar di dashboard | Ikuti §3 Langkah 2 |
| Buka `/webhooks/xendit` di browser → **405** | Route khusus POST | Normal — jangan dibuka via browser |
| "Tes dan simpan" gagal | Tunnel mati / URL salah | Nyalakan ulang tunnel, salin URL baru |
| "Pembayaran belum terdeteksi" terus | Bayar test mode tanpa webhook & API masih `REQUIRES_ACTION` | Wajar di test mode; pakai simulasi tinker (§3) atau admin Bayar Tunai |
| QR gak kegambar di halaman bayar | — | Library QR sudah vendor lokal (`public/js/qrcode.esm.js`), tidak bergantung CDN |
| Mau rollback ke alur lama | — | Set `XENDIT_MODE=invoice` |

## 7. Test otomatis

- `tests/Feature/PembayaranTest.php` — halaman bayar, katalog metode, polling,
  sync API, webhook (termasuk event tak dikenal di-acknowledge & token wajib).
- `tests/Feature/BookingPaymentTest.php` — mode invoice (dikunci `phpunit.xml`
  via `XENDIT_MODE=invoice`).
- Jalankan: `docker exec eduwgo-laravel.test-1 sh -c './vendor/bin/pint --test && ./vendor/bin/phpunit'`
