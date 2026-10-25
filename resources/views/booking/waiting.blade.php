<x-app-layout>
    <div class="flex items-center justify-center px-4 py-12">
        <div class="card-surface w-full max-w-md p-8">
            <!-- Ikon Menunggu -->
            <div class="flex justify-center mb-6">
                <svg class="w-16 h-16 text-yellow-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <h2 class="mb-2 text-center text-2xl font-bold text-[var(--navy-900)]">Menunggu Pembayaran</h2>
            <p class="mb-6 text-center text-sm">
                Selesaikan pembayaran sebelum waktu habis agar pesanan <strong>{{ $booking->code }}</strong> tidak dibatalkan.
            </p>

            <!-- Alpine.js Countdown (Berdasarkan waktu server, tahan reload) -->
            <div x-data="countdown('{{ $payment->expires_at->toIso8601String() }}', '{{ route('booking.failed', $booking->code) }}')" 
                 class="mb-6 rounded-lg p-4 text-center" style="background-color: var(--bg);">
                <p class="mb-1 text-sm">Sisa Waktu</p>
                <div class="text-3xl font-bold text-red-600 dark:text-red-500 font-mono tracking-wider">
                    <span x-text="hours"></span>:<span x-text="minutes"></span>:<span x-text="seconds"></span>
                </div>
            </div>

            <!-- Ringkasan -->
            <div class="mb-6 flex justify-between items-center border-t border-b border-[var(--border)] py-4">
                <span class="font-medium">Total Pembayaran</span>
                <span class="text-xl font-bold text-[var(--navy-900)]">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</span>
            </div>

            <!-- Tombol Aksi -->
            <div class="space-y-3">
                <a href="{{ $payment->gateway_url }}" target="_blank" rel="noopener"
                   class="btn-primary-edu flex w-full items-center justify-center py-3 text-sm">
                    Bayar Sekarang
                </a>
                
                @php
                    $waNumber = setting('contact.whatsapp', '6281234567890');
                    $waText = urlencode("Halo, saya ingin konfirmasi kendala pembayaran untuk pesanan {$booking->code}");
                @endphp
                <a href="https://wa.me/{{ $waNumber }}?text={{ $waText }}" target="_blank" rel="noopener"
                   class="btn-wa-edu flex w-full items-center justify-center py-3 text-sm">
                    Chat Admin
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('countdown', (expiryIso, failUrl) => ({
                expiryTime: new Date(expiryIso).getTime(),
                remaining: 0,
                hours: '00',
                minutes: '00',
                seconds: '00',
                timer: null,

                init() {
                    this.updateTime();
                    this.timer = setInterval(() => this.updateTime(), 1000);
                },

                updateTime() {
                    const now = new Date().getTime();
                    this.remaining = this.expiryTime - now;

                    if (this.remaining <= 0) {
                        clearInterval(this.timer);
                        window.location.href = failUrl;
                        return;
                    }

                    const h = Math.floor((this.remaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const m = Math.floor((this.remaining % (1000 * 60 * 60)) / (1000 * 60));
                    const s = Math.floor((this.remaining % (1000 * 60)) / 1000);

                    this.hours = String(h).padStart(2, '0');
                    this.minutes = String(m).padStart(2, '0');
                    this.seconds = String(s).padStart(2, '0');
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>