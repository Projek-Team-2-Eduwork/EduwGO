<x-app-layout :title="$isPaid ? 'Pembayaran diterima' : 'Memproses pembayaran'">
    <div class="py-12">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="section-plain overflow-hidden p-6 text-center md:p-10"
                 @if(!$isPaid) x-data="paymentPoller('{{ route('booking.status', $booking->code) }}')" @endif>

                @if($isPaid)
                    <!-- State Lunas -->
                    <div class="mb-6 flex justify-center">
                        <x-illustration name="success" />
                    </div>
                    <h2 class="mb-4 text-2xl font-bold text-[var(--ink)]">Pembayaran diterima</h2>
                    <p class="mb-2">Simpan kode pesanan Anda dan konfirmasikan jadwal pengambilan dengan cabang.</p>
                    <p class="mb-8 inline-block rounded-md bg-[var(--bg)] px-4 py-2 text-lg font-mono font-semibold text-[var(--ink)]">{{ $booking->code }}</p>
                @else
                    <!-- State Memproses -->
                    <div class="mb-6 flex justify-center">
                        <x-illustration name="waiting" />
                    </div>
                    <h2 class="mb-4 text-2xl font-bold text-[var(--ink)]">Memproses pembayaran…</h2>
                    <p class="mb-2">Pembayaran Anda sedang diverifikasi. Halaman ini memperbarui otomatis.</p>
                    <p class="mb-4 inline-block rounded-md bg-[var(--bg)] px-4 py-2 text-lg font-mono font-semibold text-[var(--ink)]">{{ $booking->code }}</p>

                    <div x-show="showTimeoutMessage" style="display: none;" class="mb-4 text-sm font-medium text-[var(--danger)] dark:text-[var(--danger)]">
                        Masih diproses? Hubungi admin.
                    </div>
                @endif

                <div class="my-8 text-left">
                    <h3 class="text-lg">{{ $booking->vehicle?->name }}</h3>
                    <p class="mt-2 text-sm">{{ $booking->start_at->translatedFormat('d M Y, H:i') }} — {{ $booking->end_at->translatedFormat('d M Y, H:i') }}</p>
                    <x-pickup-location :vehicle="$booking->vehicle" class="mt-6" />
                </div>
                <div class="mt-4 flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="{{ route('booking.show', $booking->code) }}" class="btn-primary-edu px-6 py-3 text-center text-sm">
                        Lihat detail pesanan
                    </a>
                    <a href="https://wa.me/{{ setting('contact.whatsapp', '6281234567890') }}?text={{ urlencode('Halo, saya ingin konfirmasi pembayaran pesanan ' . $booking->code) }}" target="_blank" rel="noopener" class="btn-wa-edu flex items-center justify-center gap-2 px-6 py-3 text-center text-sm">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.015c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Hubungi admin
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(!$isPaid)
        @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('paymentPoller', (url) => ({
                    attempts: 0,
                    maxAttempts: 40,
                    showTimeoutMessage: false,
                    init() {
                        const interval = setInterval(() => {
                            this.attempts++;
                            if (this.attempts > this.maxAttempts) {
                                clearInterval(interval);
                                this.showTimeoutMessage = true;
                                return;
                            }
                            fetch(url)
                                .then(res => res.json())
                                .then(data => {
                                    if (data.status !== 'pending') {
                                        clearInterval(interval);
                                        window.location.reload();
                                    }
                                })
                                .catch(err => console.error(err));
                        }, 3000);
                    }
                }));
            });
        </script>
        @endpush
    @endif
</x-app-layout>
