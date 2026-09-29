<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    private const DEFAULT_TERMS = [
        'Sewa minimal 1x24 jam, dihitung sejak tanggal dan jam pengambilan yang dipilih.',
        'Pesanan dikonfirmasi lewat WhatsApp atau dengan datang langsung ke lokasi rental.',
        'Penyewa wajib membawa 2 identitas asli (misalnya KTP dan SIM C) saat pengambilan motor.',
        'Sertakan akun media sosial dan nomor WhatsApp aktif yang dapat dihubungi.',
        'Periksa kondisi motor bersama petugas saat serah terima, sebelum dan sesudah pemakaian.',
        'Penyedia berhak membatalkan pesanan atau mengganti unit dengan tipe yang setara bila diperlukan.',
    ];

    public function terms()
    {
        return view('pages.terms', ['points' => $this->termsPoints()]);
    }

    public function about()
    {
        $socials = collect([
            'Instagram' => setting('social.instagram'),
            'Facebook' => setting('social.facebook'),
            'TikTok' => setting('social.tiktok'),
            'YouTube' => setting('social.youtube'),
        ])->filter(fn ($href) => is_string($href) && $href !== '' && $href !== '#');

        $waNumber = preg_replace('/\D+/', '', (string) setting('contact.whatsapp', ''));

        return view('pages.about', [
            'brand' => setting('brand.name', 'EduwGo'),
            'description' => setting('brand.description', 'EduwGo menyediakan rental motor cepat, aman, dan terjangkau. Pilih motor, tentukan durasi, bayar online, lalu ambil di lokasi.'),
            'address' => setting('contact.address', 'Alamat lokasi pengambilan akan diinformasikan oleh admin.'),
            'hours' => setting('contact.hours', 'Setiap hari, 08.00 – 20.00 WIB'),
            'socials' => $socials,
            'waUrl' => $waNumber !== '' ? 'https://wa.me/'.$waNumber : null,
        ]);
    }

    /**
     * Poin S&K dari setting('content.terms') (teks, satu poin per baris, atau array/JSON);
     * default 6 poin bila kosong.
     *
     * @return array<int, string>
     */
    private function termsPoints(): array
    {
        $raw = setting('content.terms');

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_string($decoded))) {
                $raw = $decoded;
            }
        }

        $lines = is_array($raw) ? $raw : preg_split('/\R+/', (string) $raw);

        $points = collect($lines)
            ->filter(fn ($line) => is_string($line))
            ->map(fn ($line) => trim(preg_replace('/^\s*(\d+[.)]|[-*•])\s*/u', '', $line)))
            ->filter()
            ->values()
            ->all();

        return $points ?: self::DEFAULT_TERMS;
    }
}
