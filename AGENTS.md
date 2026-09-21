# EduwGo — Instruksi untuk Agent (Codex, Cursor, Copilot, dll.)

Sumber kebenaran: `CLAUDE.md` (aturan) dan `docs/SPEC.md` (spesifikasi). Baca keduanya sebelum mengubah kode. Ringkasan aturan yang tidak boleh dilanggar:

## Git

- **Dilarang** menjalankan `git commit`, `git push`, `git merge`, `git rebase`, `git reset`, `gh pr create`, atau perintah apa pun yang mengubah history/remote. Hanya ubah file. Manusia yang commit.
- Kerjakan hanya di branch issue (nama dari Linear: `<user>/eg-<nomor>-<slug>`). Jangan di `main`/`dev`.
- Di akhir task, tulis **saran** pesan commit:
  `<type>(<scope>): <deskripsi Indonesia> (EG-<nomor>)`
  type ∈ feat | fix | chore | refactor | docs | test | style | perf | ci. `(EG-<nomor>)` wajib.
- 1 issue = 1 branch = 1 PR ke `dev`.
- Jangan ubah `.env`, kredensial, lockfile, atau file bersama (`app/Enums/BookingStatus.php`, `app/Services/BookingService.php`, layout, `resources/views/components/`) tanpa persetujuan manusia.

## Stack & konvensi

- Laravel 13, PHP 8.3, Breeze Blade + Tailwind + Alpine, Sail, MySQL, Xendit Invoice API, spatie/laravel-permission, Chart.js CDN, dark mode class strategy.
- Logika bisnis di `app/Services/`. Status booking hanya lewat `BookingService::transition()`.
- Enum status: pending, paid, rented, returned, cancelled, expired.
- Ketersediaan per unit dengan buffer 60 menit; checkout pakai `DB::transaction` + `lockForUpdate()`.
- Config bisnis dari `setting()` (tabel settings), bukan hardcode.
- FormRequest untuk validasi, Policy untuk otorisasi, role hanya admin/user.
- Kode Inggris, teks UI Indonesia.
- Selesai = test hijau (`sail artisan test`), pint bersih, cek light/dark/mobile.

## Scope

Ikuti Figma. Jangan tambah fitur di luar `docs/SPEC.md` bagian 9 (keranjang, denda, deposit, refund sistem, perpanjangan, blacklist, ulasan, KTP, kasir, export, backup, Google login).
