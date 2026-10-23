## Issue Linear

<!-- Contoh: EG-12. Satu PR = satu issue. Target branch: dev -->
EG-

## Perubahan

<!-- Ringkas apa yang diubah dan kenapa -->
-

## Cara test

<!-- Langkah manual + perintah test yang dijalankan -->
1.
2.

## Screenshot

Wajib untuk perubahan UI. Hapus bagian yang tidak relevan.

| Light | Dark | Mobile (375px) |
| ----- | ---- | -------------- |
|       |      |                |

## Definition of Done

- [ ] Migration + model + relasi sesuai `docs/SPEC.md` bagian 7
- [ ] FormRequest + Policy
- [ ] Responsive (mobile Figma) + dark mode sudah dicek
- [ ] Feature test happy path + 1 kasus gagal, hijau
- [ ] Seeder tersedia jika ada data baru
- [ ] Tidak ada N+1
- [ ] `sail artisan test` hijau dan `sail bin pint --test` bersih
- [ ] Pesan commit memakai format `<type>(<scope>): <deskripsi> (EG-<nomor>)`
- [ ] Tidak mengubah file bersama tanpa persetujuan tim (lihat CODEOWNERS)
