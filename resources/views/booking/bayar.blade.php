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

            @if ($payment && $payment->expires_at && $channel !== 'COD')
                <div x-data="hitungMundur({{ json_encode($payment->expires_at?->toIso8601String()) }}, {{ json_encode(route('booking.failed', $booking->code)) }})"
                     class="mt-4 rounded-xl p-4 text-center" style="background-color: var(--bg);">
                    <p class="mb-1 text-sm">Sisa Waktu Pembayaran</p>
                    <div class="font-mono text-3xl font-bold tracking-wider text-red-600 dark:text-red-500">
                        <span x-text="hours"></span>:<span x-text="minutes"></span>:<span x-text="seconds"></span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Pilih metode (gaya gateway: per kategori + logo kanal) --}}
        <div class="card-surface mb-6 p-6">
            <h2 class="mb-4 font-semibold text-[var(--navy-900)]">Pilih Metode Pembayaran</h2>

            <div class="space-y-5">
                @foreach ($kategoriKanal as $kategori)
                    <div>
                        <div class="mb-2 flex items-center gap-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $kategori['name'] }}</p>
                            <span class="flex-1 border-t border-[var(--border)]"></span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach ($kategori['channels'] as $kanal)
                                <a href="{{ route('booking.bayar', ['code' => $booking->code, 'metode' => $kanal['code']]) }}"
                                   @class([
                                        'flex flex-col items-center gap-2 rounded-xl border px-3 py-3 text-center text-sm font-medium transition',
                                        'border-[var(--orange-500)] bg-[var(--orange-500)]/10 ring-2 ring-[var(--orange-500)]' => $channel === $kanal['code'],
                                        'border-[var(--border)] hover:border-[var(--navy-900)]' => $channel !== $kanal['code'],
                                    ])>
                                    <span class="flex h-9 w-24 items-center justify-center gap-1 rounded-lg border border-[var(--border)] bg-white px-2 py-1">
                                        @forelse ($kanal['logo'] as $logo)
                                            <img src="{{ $logo }}" alt="" class="h-full min-w-0 flex-1 object-contain" loading="lazy">
                                        @empty
                                            {!! $kanal['icon'] !!}
                                        @endforelse
                                    </span>
                                    <span class="leading-tight">{{ $kanal['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
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
            <div class="card-surface p-6" x-data="bayarPembayaran({{ json_encode(route('booking.status', $booking->code)) }}, {{ json_encode(route('booking.show', $booking->code)) }})">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-semibold text-[var(--navy-900)]">Instruksi Pembayaran</h2>
                    @if (($channelLabel ?? null) !== null)
                        <span class="rounded-full border border-[var(--border)] px-3 py-1 text-xs font-semibold text-[var(--navy-900)]">{{ $channelLabel }}</span>
                    @endif
                </div>

                @if ($qrString)
                    <div class="mb-4 rounded-xl bg-white p-4 text-center">
                        <canvas id="qr-canvas" width="224" height="224" class="mx-auto h-56 w-56"></canvas>
                        <p class="mt-2 text-xs text-gray-500">Kalau QR-nya tidak muncul, pakai kode cadangan ini:</p>
                        <p class="break-all text-xs text-gray-500">{{ $qrString }}</p>
                    </div>
                @endif

                @if ($paymentUrl)
                    <a href="{{ $paymentUrl }}" target="_blank" rel="noopener"
                       class="btn-secondary-edu mb-4 flex w-full items-center justify-center py-3 text-sm">
                        Buka Pembayaran {{ $channelLabel ?? '' }}
                    </a>
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
                    @if ($channel === 'COD')
                        {{-- Bayar di tempat: tidak ada tombol cek status, admin yang mencatat --}}
                        <div class="rounded-xl border border-[var(--border)] p-4 text-sm" style="background-color: var(--bg);">
                            Simpan kode booking ini. Pembayaran tunai dicatat oleh admin saat kamu datang ke cabang, lalu status pesanan otomatis berubah jadi <strong>Lunas</strong>.
                        </div>
                        <a href="{{ route('booking.show', $booking->code) }}"
                           class="btn-primary-edu flex w-full items-center justify-center py-3 text-sm">
                            Kembali ke Detail Pesanan
                        </a>
                    @else
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
                        @if (str_starts_with((string) config('services.xendit.secret_key'), 'xnd_devel'))
                            <form method="POST" action="{{ route('booking.demo-bayar', $booking->code) }}">
                                @csrf
                                <button type="submit"
                                    class="block w-full rounded-lg border border-dashed border-yellow-500 py-2 text-center text-xs font-semibold text-yellow-500">
                                    🎲 Demo: Tandai pembayaran berhasil (test mode)
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        @endif
    </div>

@push('scripts')
    @if ($qrString)
        <script type="module">
            const canvas = document.getElementById('qr-canvas');

            if (canvas) {
                import('{{ asset('js/qrcode.esm.js') }}')
                    .then(({ default: QRCode }) => QRCode.toCanvas(canvas, @json($qrString), { width: 224, margin: 1 }))
                    .catch((error) => console.error('Gagal memuat QRIS', error));
            }
        </script>
    @endif

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

                    // 1. Ambil status dulu (bo bisa lambat, biar nge-triggger polling)
                    let dataPaid = false;
                    try {
                        const res = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
                        const data = await res.json();
                        if (data.paid) dataPaid = true;
                    } catch (e) {
                        // gagal koneksi — biarin aja, tetap lanjut proses bawah
                    }

                    // 2. Kalau udah dibayar → tampil pop up sukses + redirect
                    if (dataPaid) {
                        if (confirm('Pembayaran Berhasil ✓\n\nHalaman akan dialihkan ke detail pesanan lunas.')) {
                            window.location.href = successUrl;
                        }
                        return;
                    }

                    // 3. Kalau belum dibayar → pesan + mulai polling 5 detik
                    this.message = 'Pembayaran sedang diverifikasi (max 5 detik). Silakan tunggu notifikasi.';
                    if (!this.pollTimer) {
                        this.pollTimer = setInterval(() => this.sudahBayar(), 5000);
                    }
                    this.checking = false;
                }
            }))
        });
    </script>
@endpush
</x-app-layout>