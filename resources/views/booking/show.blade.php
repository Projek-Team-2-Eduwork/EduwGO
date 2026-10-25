<x-app-layout>
    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

            @if ($booking->isOverdue())
                <div class="mb-6 flex items-center rounded-r-lg border-l-4 border-red-500 bg-red-100 p-4 shadow-sm dark:bg-red-900/30">
                    <svg class="mr-3 h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span class="font-medium text-red-800 dark:text-red-300">Waktu sewa sudah lewat, segera kembalikan unit</span>
                </div>
            @endif

            {{-- Notifikasi sukses pembayaran (sekali baca, otomatis hilang) --}}
            @if (session('bayar_berhasil'))
                <div x-data="{ shown: true }" x-show="shown" x-init="setTimeout(() => { shown = false }, 8000)" x-transition.opacity
                     class="mb-6 flex items-start justify-between rounded-r-lg border-l-4 border-green-500 bg-green-100 p-4 shadow-sm dark:bg-green-900/30" role="status">
                    <div class="flex items-start">
                        <svg class="mr-3 h-6 w-6 shrink-0 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div>
                            <p class="font-bold text-green-800 dark:text-green-300">Pembayaran berhasil!</p>
                            <p class="text-sm text-green-700 dark:text-green-400">Pesanan {{ $booking->code }} sudah lunas. Simpan kode booking ini, lalu ikuti langkah selanjutnya di bawah.</p>
                        </div>
                    </div>
                    <button type="button" x-on:click="shown = false" class="ml-4 shrink-0 text-green-700 hover:opacity-70 dark:text-green-400" aria-label="Tutup notifikasi">&times;</button>
                </div>
            @endif

            {{-- Panel status utama: Lunas / Menunggu / Expired / Batal langsung terlihat --}}
            <div class="mb-6">
                <x-booking-status-panel :booking="$booking" :payment="$payment" />
            </div>

            <div class="card-surface overflow-hidden">
                <div class="p-6 md:p-8">

                    <!-- Header: Info Kendaraan -->
                    <div class="flex flex-col md:flex-row gap-6 mb-8 pb-8 border-b border-[var(--border)]">
                        <div class="w-full md:w-1/3">
                            @if($booking->vehicle?->image)
                                <img src="{{ asset($booking->vehicle->image) }}" class="w-full h-48 object-cover rounded-lg bg-[var(--bg)] shadow-sm" alt="{{ $booking->vehicle->name }}" onerror="this.outerHTML='<div class=\'w-full h-48 flex items-center justify-center bg-[var(--bg)] rounded-lg shadow-sm\'><svg class=\'w-12 h-12 text-[var(--ink-muted)]\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10\'></path></svg></div>'">
                            @else
                                <div class="w-full h-48 flex items-center justify-center bg-[var(--bg)] rounded-lg shadow-sm">
                                    <svg class="w-12 h-12 text-[var(--ink-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                            @endif
                        </div>
                        <div class="w-full md:w-2/3 flex flex-col justify-center">
                            <div class="flex justify-between items-start mb-2 gap-3">
                                <h2 class="text-2xl font-bold">{{ $booking->vehicle?->name ?? 'Kendaraan Dihapus' }}</h2>
                                <x-badge-status :status="$booking->status" />
                            </div>
                            <p class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)] font-medium mb-1">{{ $booking->vehicle?->type?->name ?? '-' }}</p>
                            <p class="text-[var(--navy-900)] font-mono bg-[var(--bg)] px-3 py-1 rounded-md inline-block self-start">{{ $booking->vehicle?->plate_number ?? '-' }}</p>
                        </div>
                    </div>

                    <!-- Grid Detail -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 pb-8 border-b border-[var(--border)]">
                        <!-- Blok Detail Pemesanan -->
                        <div>
                            <h3 class="text-lg font-bold mb-4">Detail Pemesanan</h3>
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">Nama Penyewa</dt>
                                    <dd class="font-medium text-[var(--navy-900)]">{{ $booking->user->name }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">WhatsApp</dt>
                                    <dd class="font-medium text-[var(--navy-900)]">{{ $booking->user->phone ?? '-' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">ID Booking</dt>
                                    <dd class="font-mono font-medium text-[var(--orange-500)]">{{ $booking->code }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">Tanggal Booking</dt>
                                    <dd class="font-medium text-[var(--navy-900)]">{{ $booking->created_at->translatedFormat('D, d M Y H:i') }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Blok Rentang Sewa & Total -->
                        <div>
                            <h3 class="text-lg font-bold mb-4">Informasi Sewa</h3>
                            <dl class="space-y-3 text-sm mb-4">
                                <div class="flex justify-between">
                                    <dt class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">Mulai Sewa</dt>
                                    <dd class="font-medium text-[var(--navy-900)]">{{ $booking->start_at->translatedFormat('D, d M Y H:i') }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">Selesai Sewa</dt>
                                    <dd class="font-medium text-[var(--navy-900)]">{{ $booking->end_at->translatedFormat('D, d M Y H:i') }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">Durasi</dt>
                                    <dd class="font-medium text-[var(--navy-900)]">{{ $booking->duration_days }} Hari</dd>
                                </div>
                            </dl>
                            <div class="pt-3 border-t border-[var(--border)] flex justify-between items-center">
                                <span class="font-bold text-[var(--navy-900)]">Total Pembayaran</span>
                                <span class="text-xl font-black text-[var(--orange-500)]">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Ringkasan Pembayaran (gaya invoice) -->
                    <div class="mb-8 pb-8 border-b border-[var(--border)]">
                        <x-payment-summary :booking="$booking" :payment="$payment" />
                    </div>

                    <!-- Riwayat Pesanan & Lokasi -->
                    <div class="grid grid-cols-1 gap-8 mb-8 pb-8 border-b border-[var(--border)] md:grid-cols-5">
                        <!-- Timeline Status -->
                        <div class="md:col-span-3">
                            <div class="mb-5 flex flex-wrap items-center justify-between gap-2">
                                <h3 class="text-lg font-bold">Riwayat Pesanan</h3>
                                <span class="text-xs text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">{{ $riwayat->count() }} perubahan status</span>
                            </div>

                            <ol class="relative space-y-6 border-l border-[var(--border)] pl-8">
                                @forelse($riwayat as $h)
                                    @php
                                        $to = App\Enums\BookingStatus::tryFrom($h->to_status);
                                        $from = $h->from_status ? App\Enums\BookingStatus::tryFrom($h->from_status) : null;
                                        $sebelumnya = $loop->index > 0 ? $riwayat[$loop->index - 1] : null;

                                        $dot = match ($to?->color()) {
                                            'success' => 'bg-emerald-600 dark:bg-emerald-500',
                                            'warning' => 'bg-amber-500 dark:bg-amber-400',
                                            'danger' => 'bg-red-600 dark:bg-red-500',
                                            'info' => 'bg-sky-600 dark:bg-sky-500',
                                            default => 'bg-slate-500 dark:bg-slate-400',
                                        };
                                    @endphp

                                    @continue($to === null)

                                    <li class="relative">
                                        <span class="absolute -left-[2.9rem] flex h-8 w-8 items-center justify-center rounded-full text-white ring-4 ring-[var(--surface)] {{ $dot }}">
                                            <x-status-icon :status="$to" class="h-4 w-4" />
                                        </span>

                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-badge-status :status="$to" />
                                            @if ($loop->last)
                                                <span class="rounded-full border border-[var(--border)] px-2 py-0.5 text-[11px] font-semibold text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">Status terbaru</span>
                                            @endif
                                            <span class="text-xs text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">{{ $h->created_at->diffForHumans() }}</span>
                                        </div>

                                        <p class="mt-1.5 text-xs text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">
                                            {{ $h->created_at->translatedFormat('D, d M Y H:i') }} &middot; Oleh:
                                            @if($h->changed_by === null)
                                                <span class="font-semibold text-[var(--orange-500)]">Sistem</span>
                                            @elseif($h->changed_by === auth()->id())
                                                <span class="font-semibold text-green-700 dark:text-green-400">Anda</span>
                                            @else
                                                <span class="font-semibold text-blue-600 dark:text-blue-400">Admin</span>@if($h->changer?->name) <span class="font-semibold">{{ $h->changer->name }}</span>@endif
                                            @endif
                                        </p>

                                        @if($from)
                                            <p class="mt-1 text-xs text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">
                                                Perubahan status: {{ $from->label() }} <span aria-hidden="true">&rarr;</span> {{ $to->label() }}
                                                @if($sebelumnya)
                                                    &middot; {{ $sebelumnya->created_at->diffForHumans($h->created_at, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 1]) }} setelah langkah sebelumnya
                                                @endif
                                            </p>
                                        @endif

                                        @if($h->note)
                                            <p class="mt-2 flex items-start gap-2 rounded-lg border border-[var(--border)] bg-[var(--bg)] p-3 text-sm text-[var(--navy-700)]">
                                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-[var(--ink-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m-6 8l-4-4V6a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H7z" />
                                                </svg>
                                                <span>{{ $h->note }}</span>
                                            </p>
                                        @endif
                                    </li>
                                @empty
                                    <li class="text-sm text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">Belum ada riwayat perubahan status untuk pesanan ini.</li>
                                @endforelse
                            </ol>
                        </div>

                        <!-- Lokasi & Jam -->
                        <div class="md:col-span-2">
                            <h3 class="text-lg font-bold mb-4">Lokasi Pengambilan</h3>
                            <div class="bg-[var(--bg)] rounded-lg p-4 border border-[var(--border)]">
                                <div class="mb-4">
                                    <span class="block text-xs font-semibold text-[var(--ink-muted)] dark:text-[var(--ink-muted)] uppercase tracking-wider mb-1">Alamat</span>
                                    <p class="text-sm text-[var(--navy-900)]">{{ setting('contact.address', '-') }}</p>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-[var(--ink-muted)] dark:text-[var(--ink-muted)] uppercase tracking-wider mb-1">Jam Operasional</span>
                                    <p class="text-sm text-[var(--navy-900)]">{{ setting('contact.hours', '-') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Langkah selanjutnya setelah pembayaran (EG-53) -->
                    @if ($booking->status === App\Enums\BookingStatus::Paid)
                        <div class="mb-8 pb-8 border-b border-[var(--border)]">
                            <h3 class="text-lg font-bold mb-4">Langkah Selanjutnya</h3>
                            <ol class="space-y-4">
                                <li class="flex items-start gap-3 text-sm">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--orange-500)] text-xs font-bold text-white">1</span>
                                    <span class="text-gray-600 dark:text-gray-300">Datang ke lokasi pengambilan sesuai alamat dan jam operasional di atas, mulai sewa <strong class="text-[var(--navy-900)]">{{ $booking->start_at->translatedFormat('D, d M Y H:i') }}</strong>.</span>
                                </li>
                                <li class="flex items-start gap-3 text-sm">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--orange-500)] text-xs font-bold text-white">2</span>
                                    <span class="text-gray-600 dark:text-gray-300">Bawa KTP atau SIM yang masih berlaku untuk verifikasi saat serah terima.</span>
                                </li>
                                <li class="flex items-start gap-3 text-sm">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--orange-500)] text-xs font-bold text-white">3</span>
                                    <span class="text-gray-600 dark:text-gray-300">Konfirmasi kedatangan lewat tombol Chat Admin, dan pantau Riwayat Pesanan untuk catatan dari admin.</span>
                                </li>
                                <li class="flex items-start gap-3 text-sm">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--orange-500)] text-xs font-bold text-white">4</span>
                                    <span class="text-gray-600 dark:text-gray-300">Kembalikan unit sebelum <strong class="text-[var(--navy-900)]">{{ $booking->end_at->translatedFormat('D, d M Y H:i') }}</strong>, lalu minta admin menutup pesanan ini.</span>
                                </li>
                            </ol>
                        </div>
                    @endif

                    <!-- Footer & Aksi -->
                    <div class="flex flex-col-reverse md:flex-row justify-between items-center gap-4">
                        <a href="{{ route('booking.index') }}" class="btn-secondary-edu inline-flex w-full items-center justify-center px-5 py-2.5 text-sm md:w-auto">
                            &larr; Kembali ke Daftar
                        </a>

                        <div class="w-full md:w-auto flex flex-col sm:flex-row gap-3">
                            <a href="https://wa.me/{{ setting('contact.whatsapp', '6281234567890') }}?text={{ urlencode('Halo, saya ingin bertanya soal pesanan '.$booking->code) }}" target="_blank" rel="noopener" class="btn-wa-edu inline-flex w-full items-center justify-center px-5 py-2.5 text-sm sm:w-auto">
                                Chat Admin
                            </a>

                            @if($booking->status === App\Enums\BookingStatus::Pending)
                                <x-danger-button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'batal-{{ $booking->code }}')" class="w-full sm:w-auto justify-center">
                                    Batalkan
                                </x-danger-button>

                                <a href="{{ route('booking.waiting', $booking->code) }}" class="btn-primary-edu inline-flex w-full items-center justify-center px-5 py-2.5 text-sm sm:w-auto">
                                    Bayar
                                </a>

                                <!-- Modal Konfirmasi Batal -->
                                <x-modal name="batal-{{ $booking->code }}" maxWidth="sm">
                                    <div class="p-6">
                                        <h2 class="text-lg font-medium text-[var(--navy-900)] mb-2">Batalkan pesanan {{ $booking->code }}?</h2>
                                        <p class="mb-6 text-sm">Tindakan ini tidak dapat dibatalkan kembali. Anda yakin?</p>
                                        <div class="flex justify-end gap-3">
                                            <x-secondary-button x-on:click="$dispatch('close-modal', 'batal-{{ $booking->code }}')">
                                                Kembali
                                            </x-secondary-button>
                                            <form method="POST" action="{{ route('booking.cancel', $booking->code) }}">
                                                @csrf
                                                <x-danger-button type="submit">
                                                    Ya, Batalkan
                                                </x-danger-button>
                                            </form>
                                        </div>
                                    </div>
                                </x-modal>
                            @elseif(in_array($booking->status, [App\Enums\BookingStatus::Cancelled, App\Enums\BookingStatus::Expired], true))
                                <a href="{{ route('booking.rebook', $booking->code) }}" class="btn-primary-edu inline-flex w-full items-center justify-center px-4 py-2 text-sm sm:w-auto">
                                    Pesan Ulang
                                </a>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
