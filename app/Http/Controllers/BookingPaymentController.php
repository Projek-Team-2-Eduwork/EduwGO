<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BookingPaymentController extends Controller
{
    public function waiting(Request $request, $code, XenditService $xenditService)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        // Authorization sederhana
        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        // Redirect jika status bukan pending
        if ($booking->status !== BookingStatus::Pending) {
            if (in_array($booking->status, [BookingStatus::Paid, BookingStatus::Rented, BookingStatus::Returned])) {
                return redirect()->route('booking.success', $booking->code);
            }

            return redirect()->route('booking.failed', $booking->code);
        }

        // Mode Payment Request (EG-52/53): arahkan ke halaman bayar sendiri.
        // Mode 'invoice' (default) tetap pakai halaman waiting lama — rollback aman.
        if (config('services.xendit.mode', 'invoice') === 'payment_request') {
            return redirect()->route('booking.bayar', $booking->code);
        }

        try {
            $payment = $xenditService->createInvoice($booking);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return view('booking.waiting', compact('booking', 'payment'));
    }

    /**
     * Halaman bayar sendiri (Xendit Payment Request): pilih metode lalu lihat
     * instruksi pembayaran (QR / nomor rekening) tanpa hosted checkout (EG-53).
     */
    public function bayar(Request $request, $code, XenditService $xenditService)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        if ($booking->status !== BookingStatus::Pending) {
            if (in_array($booking->status, [BookingStatus::Paid, BookingStatus::Rented, BookingStatus::Returned])) {
                return redirect()->route('booking.show', $booking->code)->with('bayar_berhasil', true);
            }

            return redirect()->route('booking.failed', $booking->code);
        }

        // Metode yang dipilih penyewa (default QRIS) — nilai tak dikenal jatuh balik ke QRIS
        $channel = strtoupper((string) $request->query('metode', 'QRIS'));

        if ($channel !== XenditService::COD && ! isset(XenditService::CHANNELS[$channel])) {
            $channel = match ($channel) {
                'BANK_TRANSFER' => 'BCA_VIRTUAL_ACCOUNT',
                'EWALLET' => 'SHOPEEPAY',
                default => 'QRIS',
            };
        }

        // Bayar di tempat (COD): tidak lewat gateway sama sekali.
        if ($channel === XenditService::COD) {
            return $this->bayarDiTempat($booking);
        }

        // Kartu kredit/debit tidak didukung Payments API v3 untuk negara ID
        // (wajib card_details / PCI-DSS), jadi dialihkan ke checkout Xendit.
        if ($channel === 'CREDIT_CARD') {
            try {
                $payment = $xenditService->createCardInvoice($booking);
            } catch (\Exception $e) {
                Log::channel('xendit')->error('Halaman bayar: gagal membuat invoice kartu.', [
                    'booking' => $booking->code,
                    'error' => $e->getMessage(),
                ]);

                return view('booking.bayar', [
                    'booking' => $booking,
                    'payment' => null,
                    'channel' => $channel,
                    'channelLabel' => XenditService::CHANNELS[$channel]['label'] ?? $channel,
                    'kategoriKanal' => XenditService::channelCatalog(),
                    'steps' => [],
                    'qrString' => null,
                    'paymentUrl' => null,
                    'error' => 'Gagal menghubungi penyedia pembayaran. Silakan coba lagi.',
                ]);
            }

            if ($payment->gateway_url) {
                return redirect()->away($payment->gateway_url);
            }
        }

        // Payment request dibuat lazily saat halaman dibuka (idempoten per channel)
        try {
            $payment = $xenditService->createPaymentRequest($booking, $channel);
        } catch (\Exception $e) {
            Log::channel('xendit')->error('Halaman bayar: gagal membuat payment request.', [
                'booking' => $booking->code,
                'error' => $e->getMessage(),
            ]);

            return view('booking.bayar', [
                'booking' => $booking,
                'payment' => null,
                'channel' => $channel,
                'channelLabel' => XenditService::CHANNELS[$channel]['label'] ?? $channel,
                'kategoriKanal' => XenditService::channelCatalog(),
                'steps' => [],
                'qrString' => null,
                'paymentUrl' => null,
                'error' => 'Gagal menghubungi penyedia pembayaran. Silakan coba lagi.',
            ]);
        }

        // Susun tampilan (langkah, QR, tautan tombol) dari payload gateway.
        // Instruksi dihitung ulang agar baris pembayaran lama ikut spesifik & rapi.
        $tampilan = $xenditService->paymentPresentation($payment);

        return view('booking.bayar', [
            'booking' => $booking,
            'payment' => $payment,
            'channel' => $channel,
            'channelLabel' => XenditService::CHANNELS[$channel]['label'] ?? $channel,
            'kategoriKanal' => XenditService::channelCatalog(),
            'steps' => $tampilan['steps'],
            'qrString' => $tampilan['qr_string'],
            'paymentUrl' => $tampilan['payment_url'],
            'error' => null,
        ]);
    }

    /**
     * Halaman instruksi bayar di tempat (COD). Tidak memanggil gateway: cukup
     * catatan pembayaran tunai pending yang nanti ditandai lunas admin lewat
     * "Tandai Sudah Dibayar (COD)" saat uang benar-benar diterima di cabang.
     */
    private function bayarDiTempat(Booking $booking): View
    {
        $booking->loadMissing(['vehicle.branch']);

        // Idempoten: pilihan COD yang sama tidak menumpuk baris pembayaran.
        $payment = Payment::where('booking_id', $booking->id)
            ->where('method', 'cash')
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if (! $payment) {
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'method' => 'cash',
                'status' => 'pending',
                'payment_channel' => XenditService::COD,
                'amount' => $booking->total_amount,
                // Batas bayar = waktu pengambilan, jadi bookings:expire ikut
                // menutup pesanan yang tidak pernah diambil.
                'expires_at' => $booking->start_at,
            ]);
        }

        return view('booking.bayar', [
            'booking' => $booking,
            'payment' => $payment,
            'channel' => XenditService::COD,
            'channelLabel' => 'Bayar di Tempat (COD)',
            'kategoriKanal' => XenditService::channelCatalog(),
            'steps' => $this->instruksiBayarDiTempat($booking),
            'qrString' => null,
            'paymentUrl' => null,
            'error' => null,
        ]);
    }

    /**
     * Langkah bayar di tempat, menyesuaikan cabang & jadwal pengambilan.
     *
     * @return array<int, string>
     */
    private function instruksiBayarDiTempat(Booking $booking): array
    {
        $cabang = $booking->vehicle?->branch?->name;
        $nominal = 'Rp'.number_format((float) $booking->total_amount, 0, ',', '.');

        return [
            'Pilih metode ini kalau kamu mau bayar tunai langsung di '.($cabang ?: 'cabang kami').'.',
            'Datang sesuai jadwal pengambilan: '.$booking->start_at->translatedFormat('D, d M Y H:i').'.',
            'Tunjukkan kode booking '.$booking->code.' kepada admin, lalu bayar '.$nominal.' secara tunai.',
            'Admin mencatat pembayaran, lalu status pesanan otomatis berubah jadi Lunas.',
            'Pesanan otomatis kedaluwarsa kalau belum dibayar sampai waktu pengambilan.',
        ];
    }

    public function success(Request $request, $code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        $lunas = [BookingStatus::Paid, BookingStatus::Rented, BookingStatus::Returned];

        if (in_array($booking->status, $lunas, true)) {
            return view('booking.success', ['booking' => $booking, 'isPaid' => true]);
        }

        if ($booking->status === BookingStatus::Pending) {
            return view('booking.success', ['booking' => $booking, 'isPaid' => false]);
        }

        return redirect()->route('booking.failed', $code);
    }

    public function failed(Request $request, $code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        $lunas = [BookingStatus::Paid, BookingStatus::Rented, BookingStatus::Returned];

        if (in_array($booking->status, $lunas, true)) {
            return redirect()->route('booking.success', $code);
        }

        return view('booking.failed', ['booking' => $booking]);
    }

    /**
     * Tombol demo (test mode only): menandai pembayaran berhasil lewat
     * simulate API Xendit — biar demo gak perlu curl manual.
     */
    public function demoBayar(Request $request, $code, XenditService $xenditService)
    {
        // Hanya aktif di mode test (key xnd_devel) — di production 404.
        if (! str_starts_with((string) config('services.xendit.secret_key'), 'xnd_devel')) {
            abort(404);
        }

        $booking = Booking::where('code', $code)->firstOrFail();

        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        $payment = $booking->payments()
            ->where('method', 'xendit_payment_request')
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if ($payment && $payment->gateway_reference) {
            $xenditService->simulatePaymentRequest($payment->gateway_reference, (int) $payment->amount);
        }

        return redirect()->route('booking.bayar', [
            'code' => $booking->code,
            'metode' => $payment?->payment_channel ?? 'QRIS',
        ]);
    }

    public function status(Request $request, $code, XenditService $xenditService)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        // Selaraskan dengan API Xendit bila masih pending (EG-56): polling "Saya
        // Sudah Bayar" jadi tetap ke-deteksi walau webhook telat/putus di mode test
        if ($booking->status === BookingStatus::Pending) {
            $xenditService->syncPaymentStatus($booking);
            $booking->refresh();
        }

        $lunas = [BookingStatus::Paid, BookingStatus::Rented, BookingStatus::Returned];
        $paid = in_array($booking->status, $lunas, true);

        // Notifikasi sukses "sekali baca" untuk halaman detail pesanan. Diset di
        // sini agar redirect JS halaman bayar (pembayaran baru terkonfirmasi)
        // langsung menampilkan notifikasi di /pesanan/{code}.
        if ($paid) {
            session()->flash('bayar_berhasil', true);
        }

        return response()->json([
            'status' => $booking->status->value,
            'paid' => $paid,
        ]);
    }

    /**
     * Menyimpan data booking lama ke session untuk form booking ulang (rebook)
     * dan mengarahkan pengguna ke halaman detail kendaraan dengan modal terbuka otomatis.
     */
    public function ulang(Request $request, $code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        // Pastikan hanya pemilik yang bisa booking ulang pesanannya sendiri
        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak diizinkan mengakses pesanan ini.');
        }

        $start = $booking->start_at;

        // Jika waktu sewa lama sudah berlalu, bulatkan ke 1 jam ke depan dari sekarang
        if ($start->isPast()) {
            $start = now()->startOfHour()->addHour();
        }

        // Simpan ke session untuk di-consume (pull) oleh modal di halaman detail
        session([
            'booking.start_at' => $start->format('Y-m-d\TH:i'),
            'booking.days' => $booking->duration_days,
        ]);

        return redirect()->route('kendaraan.detail', [
            'vehicle' => $booking->vehicle,
            'ulang' => 1,
        ]);
    }
}
