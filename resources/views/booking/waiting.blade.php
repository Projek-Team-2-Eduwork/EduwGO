<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-navy-900 py-12 flex items-center justify-center px-4">
        <div class="card-surface max-w-md w-full p-8 shadow-lg border border-gray-100 dark:border-gray-800" style="border-radius: 16px;">
            <!-- Ikon Menunggu -->
            <div class="flex justify-center mb-6">
                <svg class="w-16 h-16 text-yellow-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-2">Menunggu Pembayaran</h2>
            <p class="text-center text-gray-600 dark:text-gray-400 mb-6 text-sm">
                Selesaikan pembayaran sebelum waktu habis agar pesanan <strong>{{ $booking->code }}</strong> tidak dibatalkan.
            </p>

            <!-- Alpine.js Countdown (Berdasarkan waktu server, tahan reload) -->
            <div x-data="countdown('{{ $payment->expires_at->toIso8601String() }}', '{{ route('booking.failed', $booking->code) }}')" 
                 class="bg-gray-100 dark:bg-gray-800 rounded-xl p-4 text-center mb-6">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Sisa Waktu</p>
                <div class="text-3xl font-bold text-red-600 dark:text-red-500 font-mono tracking-wider">
                    <span x-text="hours"></span>:<span x-text="minutes"></span>:<span x-text="seconds"></span>
                </div>
            </div>

            <!-- Ringkasan -->
            <div class="flex justify-between items-center border-t border-b border-gray-200 dark:border-gray-700 py-4 mb-6">
                <span class="text-gray-600 dark:text-gray-300 font-medium">Total Pembayaran</span>
                <span class="text-xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</span>
            </div>

            <!-- Tombol Aksi -->
            <div class="space-y-3">
                <a href="{{ $payment->gateway_url }}" target="_blank" 
                   class="btn-primary-edu w-full flex justify-center items-center py-3 rounded-lg text-white font-semibold shadow-md transition duration-200">
                    Bayar Sekarang
                </a>
                
                @php
                    $waNumber = setting('contact.whatsapp', '6281234567890');
                    $waText = urlencode("Halo, saya ingin konfirmasi kendala pembayaran untuk pesanan {$booking->code}");
                @endphp
                <a href="https://wa.me/{{ $waNumber }}?text={{ $waText }}" target="_blank"
                   class="btn-secondary-edu w-full flex justify-center items-center py-3 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-semibold transition duration-200">
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