<?php

namespace App\Support;

class RentalTerms
{
    private const DEFAULT = [
        'Sewa minimal 1x24 jam.',
        'Konfirmasi pemesanan via WhatsApp atau datang langsung ke lokasi.',
        'Wajib membawa 2 identitas asli (KTP dan SIM C) saat pengambilan.',
        'Sertakan akun sosial media dan nomor WhatsApp yang aktif.',
        'Cek kondisi motor bersama petugas saat serah terima.',
        'Penyedia berhak membatalkan pesanan atau mengganti unit motor.',
    ];

    /**
     * Poin S&K untuk blok di Home dan Daftar Kendaraan: array JSON atau teks per baris
     * dari setting `content.terms`; jatuh ke default bila kosong.
     *
     * @return list<string>
     */
    public static function list(): array
    {
        $value = setting('content.terms');
        $items = is_string($value) ? (json_decode($value, true) ?? preg_split('/\R/', $value)) : $value;

        $items = is_array($items)
            ? array_values(array_filter(array_map(fn ($item) => is_string($item) ? trim($item) : '', $items)))
            : [];

        return $items ?: self::DEFAULT;
    }
}
