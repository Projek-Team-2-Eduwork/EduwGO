<x-app-layout>
    <div class="mx-auto w-full max-w-lg px-4 py-8">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-[var(--navy-900)]">Pembayaran</h1>
            <p class="mt-1 text-sm">Selesaikan pembayaran untuk pesanan <strong>{{ $booking->code }}</strong>.</p>
        </div>

        {{-- Ringkasan & sisa waktu --}}
        <div class="card-surface mb-6 p-6">
            <div class="flex items-center justify-between border-b border-[var(--border)] pb-4">
                <span class="font-medium">Total Pembayaran</span>
                <span class="text-xl font-bold text-[var(--navy-900)]">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</span>
            </div>

            @if ($payment)
                <div x-data="hitungMundur({{ json_encode($payment->expires_at?->toIso8601String()) }}, {{ json_encode(route('booking.failed', $booking->code)) }})"
                     class="mt-4 rounded-xl p-4 text-center" style="background-color: var(--bg);">
                    <p class="mb-1 text-sm">Sisa Waktu Pembayaran</p>
                    <div class="font-mono text-3xl font-bold tracking-wider text-red-600 dark:text-red-500">
                        <span x-text="hours"></span>:<span x-text="minutes"></span>:<span x-text="seconds"></span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Pilih metode --}}
        <div class="mb-6 grid grid-cols-3 gap-2">
            @foreach (['QRIS' => 'QRIS', 'BANK_TRANSFER' => 'Transfer Bank', 'EWALLET' => 'E-Wallet'] as $value => $label)
                <a href="{{ route('booking.bayar', ['code' => $booking->code, 'metode' => $value]) }}"
                   @class([
                       'block rounded-xl border px-3 py-3 text-center text-sm font-medium transition',
                       'border-[var(--orange-500)] bg-[var(--orange-500)]/10 ring-2 ring-[var(--orange-500)]' => $channel === $value,
                       'border-[var(--border)] hover:border-[var(--navy-900)]' => $channel !== $value,
                   ])>
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Isi halaman --}}
        @if ($error)
            {{-- Error state: API gagal --}}
            <div class="card-surface p-6">
                <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                    {{ $error }}
                </div>
                <a href="{{ url()->current() }}" class="btn-primary-edu mt-4 flex w-full items-center justify-center py-3 text-sm">
                    Coba Lagi
                </a>
            </div>
        @elseif (! $payment || ($steps === [] && ! $qrString))
            {{-- Empty state: metode kosong --}}
            <div class="card-surface p-8 text-center">
                <svg class="mx-auto h-14 w-14 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                <h3 class="mt-4 font-semibold text-[var(--navy-900)]">Metode pembayaran belum tersedia</h3>
                <p class="mt-1 text-sm">Muat ulang halaman atau pilih metode lain.</p>
                <a href="{{ url()->current() }}" class="btn-primary-edu mt-4 inline-flex px-6 py-2.5 text-sm">Coba Lagi</a>
            </div>
        @else
            {{-- Instruksi pembayaran --}}
            <div class="card-surface p-6" x-data="bayarPembayaran({{ json_encode(route('booking.status', $booking->code)) }}, {{ json_encode(route('booking.success', $booking->code)) }})">
                <h2 class="mb-4 font-semibold text-[var(--navy-900)]">Instruksi Pembayaran</h2>

                @if ($qrString)
                    <div class="mb-4 rounded-xl bg-white p-4 text-center">
                        <canvas id="qr-canvas" width="224" height="224" class="mx-auto h-56 w-56"></canvas>
                        <p class="mt-2 break-all text-xs text-gray-500">{{ $qrString }}</p>
                    </div>
                @endif

                <ol class="mb-6 space-y-2 text-sm">
                    @foreach ($steps as $index => $step)
                        @if (is_string($step))
                            <li class="flex gap-2">
                                <span class="font-bold text-[var(--orange-500)]">{{ $index + 1 }}.</span>
                                <span>{{ $step }}</span>
                            </li>
                        @endif
                    @endforeach
                </ol>

                <div class="space-y-3">
                    <button type="button" @click="sudahBayar()" :disabled="checking"
                        class="btn-primary-edu flex w-full items-center justify-center py-3 text-sm disabled:opacity-60">
                        <span x-show="!checking">Saya Sudah Bayar</span>
                        <span x-show="checking">Memeriksa status...</span>
                    </button>
                    <p x-show="message" x-text="message" class="text-center text-sm text-gray-500"></p>
                    <a href="{{ route('booking.show', $booking->code) }}"
                       class="block w-full py-2 text-center text-sm font-medium text-[var(--navy-900)] underline">
                        Kembali ke Detail Pesanan
                    </a>
                </div>
            </div>
        @endif
    </div>

    @if ($qrString)
        <script type="module">
            import QRCode from '{{ asset('js/qrcode.esm.js') }}';

            QRCode.toCanvas(document.getElementById('qr-canvas'), @json($qrString), { width: 224, margin: 1 });
        </script>
    @endif

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('hitungMundur', (expiryIso, failUrl) => ({
                expiryTime: expiryIso ? new Date(expiryIso).getTime() : 0,
                hours: '00',
                minutes: '00',
                seconds: '00',
                timer: null,

                init() {
                    if (!this.expiryTime) {
                        return;
                    }

                    this.updateTime();
                    this.timer = setInterval(() => this.updateTime(), 1000);
                },

                updateTime() {
                    const remaining = this.expiryTime - new Date().getTime();

                    if (remaining <= 0) {
                        clearInterval(this.timer);
                        window.location.href = failUrl;
                        return;
                    }

                    const h = Math.floor((remaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const m = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
                    const s = Math.floor((remaining % (1000 * 60)) / 1000);

                    this.hours = String(h).padStart(2, '0');
                    this.minutes = String(m).padStart(2, '0');
                    this.seconds = String(s).padStart(2, '0');
                }
            }));

            Alpine.data('bayarPembayaran', (statusUrl, successUrl) => ({
                checking: false,
                message: '',
                pollTimer: null,

                async sudahBayar() {
                    if (this.checking) {
                        return;
                    }

                    this.checking = true;
                    this.message = '';

                    try {
                        const res = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
                        const data = await res.json();

                        if (data.paid) {
                            window.location.href = successUrl;
                            return;
                        }

                        this.message = 'Pembayaran belum terdeteksi. Status dicek otomatis tiap 5 detik.';

                        if (!this.pollTimer) {
                            this.pollTimer = setInterval(() => this.sudahBayar(), 5000);
                        }
                    } catch (e) {
                        this.message = 'Gagal memeriksa status pembayaran. Silakan coba lagi.';
                    }

                    this.checking = false;
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>