<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        if (! isset(XenditService::CHANNELS[$channel])) {
            $channel = match ($channel) {
                'BANK_TRANSFER' => 'BCA_VIRTUAL_ACCOUNT',
                'EWALLET' => 'SHOPEEPAY',
                default => 'QRIS',
            };
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
                'kategoriKanal' => XenditService::channelCatalog(),
                'steps' => [],
                'qrString' => null,
                'error' => 'Gagal menghubungi penyedia pembayaran. Silakan coba lagi.',
            ]);
        }

        // Pisahkan string QR dari langkah instruksi. Sumber utama: aksi QR_STRING
        // pada gateway_payload (mode test Xendit tidak memakai prefix EMV 000201);
        // fallback lama dipertahankan untuk baris payment format lawas.
        $steps = is_array($payment->instructions ?? null) ? $payment->instructions : [];
        $payload = $payment->gateway_payload ?? null;

        // Baris lama bisa tersimpan double-encoded (string JSON dalam kolom json)
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        $payload = is_array($payload) ? $payload : [];
        $qrString = null;

        foreach (is_array($payload['actions'] ?? null) ? $payload['actions'] : [] as $action) {
            if (($action['descriptor'] ?? null) === 'QR_STRING' && ! empty($action['value'])) {
                $qrString = (string) $action['value'];

                break;
            }
        }

        foreach ($steps as $index => $step) {
            if (is_string($step) && ($step === $qrString || str_starts_with($step, '000201'))) {
                $qrString ??= $step;
                unset($steps[$index]);
            }
        }

        return view('booking.bayar', [
            'booking' => $booking,
            'payment' => $payment,
            'channel' => $channel,
            'kategoriKanal' => XenditService::channelCatalog(),
            'steps' => array_values($steps),
            'qrString' => $qrString,
            'error' => null,
        ]);
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
