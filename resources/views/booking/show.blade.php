<x-app-layout title="Detail pesanan">
    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

            @if ($booking->isOverdue())
                <div class="mb-6 flex items-center border-y border-[var(--line)] py-4">
                    <svg class="mr-3 h-6 w-6 text-[var(--danger)] dark:text-[var(--danger)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span class="font-medium text-[var(--danger)] dark:text-[var(--danger)]">Waktu sewa sudah lewat, segera kembalikan unit</span>
                </div>
            @endif

            {{-- Panel status utama: Lunas / Menunggu / Expired / Batal langsung terlihat --}}
            <div class="mb-6">
                <x-booking-status-panel :booking="$booking" :payment="$payment" />
            </div>

            <div class="section-plain overflow-hidden">
                <div class="p-6 md:p-8">

                    <!-- Header: Info Kendaraan -->
                    <div class="flex flex-col md:flex-row gap-6 mb-8 pb-8 border-b border-[var(--line)]">
                        <div class="w-full md:w-1/3">
                            @if($booking->vehicle?->image)
                                <div class="relative flex h-48 w-full items-center justify-center overflow-hidden"><x-illustration name="vehicles" /><x-picture :src="$booking->vehicle->image" class="absolute inset-0 w-full h-48 object-cover rounded-lg bg-[var(--bg)] shadow-sm" alt="{{ $booking->vehicle->name }}" onerror="this.remove()" /></div>
                            @else
                                <div class="w-full h-48 flex items-center justify-center bg-[var(--bg)] rounded-lg shadow-sm">
                                    <x-illustration name="vehicles" />
                                </div>
                            @endif
                        </div>
                        <div class="w-full md:w-2/3 flex flex-col justify-center">
                            <div class="flex justify-between items-start mb-2 gap-3">
                                <h2 class="text-2xl font-bold">{{ $booking->vehicle?->name ?? 'Kendaraan Dihapus' }}</h2>
                            </div>
                            <p class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)] font-medium mb-1">{{ $booking->vehicle?->type?->name ?? '-' }}</p>
                            <p class="text-[var(--ink)] font-mono bg-[var(--bg)] px-3 py-1 rounded-md inline-block self-start">{{ $booking->vehicle?->plate_number ?? '-' }}</p>
                        </div>
                    </div>

                    <!-- Grid Detail -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 pb-8 border-b border-[var(--line)]">
                        <!-- Blok Detail Pemesanan -->
                        <div>
                            <h3 class="text-lg font-bold mb-4">Detail Pemesanan</h3>
                            <dl class="receipt text-sm">
                                <div>
                                    <dt class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">Nama Penyewa</dt>
                                    <dd class="font-medium text-[var(--ink)]">{{ $booking->user->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">WhatsApp</dt>
                                    <dd class="font-medium text-[var(--ink)]">{{ $booking->user->phone ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">ID Booking</dt>
                                    <dd class="font-mono font-medium text-[var(--accent)]">{{ $booking->code }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">Tanggal Booking</dt>
                                    <dd class="font-medium text-[var(--ink)]">{{ $booking->created_at->translatedFormat('D, d M Y H:i') }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Blok Rentang Sewa & Total -->
                        <div>
                            <h3 class="text-lg font-bold mb-4">Informasi Sewa</h3>
                            <dl class="receipt text-sm mb-4">
                                <div>
                                    <dt class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">Mulai Sewa</dt>
                                    <dd class="font-medium text-[var(--ink)]">{{ $booking->start_at->translatedFormat('D, d M Y H:i') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">Selesai Sewa</dt>
                                    <dd class="font-medium text-[var(--ink)]">{{ $booking->end_at->translatedFormat('D, d M Y H:i') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">Durasi</dt>
                                    <dd class="font-medium text-[var(--ink)]">{{ $booking->duration_days }} Hari</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Ringkasan Pembayaran (gaya invoice) -->
                    <div class="mb-8 pb-8 border-b border-[var(--line)]">
                        <x-payment-summary :booking="$booking" :payment="$payment" />
                    </div>

                    <!-- Riwayat Pesanan & Lokasi -->
                    <div class="grid grid-cols-1 gap-8 mb-8 pb-8 border-b border-[var(--line)] md:grid-cols-5">
                        <!-- Timeline Status -->
                        <div class="md:col-span-3">
                            <div class="mb-5 flex flex-wrap items-center justify-between gap-2">
                                <h3 class="text-lg font-bold">Riwayat Pesanan</h3>
                                <span class="text-xs text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">{{ $riwayat->count() }} perubahan status</span>
                            </div>

                            <ol class="relative space-y-6 border-l border-[var(--line)] pl-8">
                                @forelse($riwayat as $h)
                                    @php
                                        $to = App\Enums\BookingStatus::tryFrom($h->to_status);
                                        $from = $h->from_status ? App\Enums\BookingStatus::tryFrom($h->from_status) : null;
                                        $sebelumnya = $loop->index > 0 ? $riwayat[$loop->index - 1] : null;

                                        $dot = match ($to?->color()) {
                                            'success' => 'bg-[var(--success)] dark:bg-[var(--success)]',
                                            'warning' => 'bg-[var(--warning)] dark:bg-[var(--warning)]',
                                            'danger' => 'bg-[var(--danger)] dark:bg-[var(--danger)]',
                                            'info' => 'bg-[var(--info)] dark:bg-[var(--info)]',
                                            default => 'bg-[var(--bg)] dark:bg-[var(--bg)]',
                                        };
                                    @endphp

                                    @continue($to === null)

                                    <li class="relative">
                                        <span class="absolute -left-[2.9rem] flex h-8 w-8 items-center justify-center rounded-full text-[var(--on-accent)] ring-4 ring-[var(--surface)] {{ $dot }}">
                                            <x-status-icon :status="$to" class="h-4 w-4" />
                                        </span>

                                        <div class="flex flex-wrap items-center gap-2">
                                            @if(!$from)<span class="font-semibold text-[var(--ink)]">Pesanan dibuat</span>@else<x-badge-status :status="$to" />@endif
                                            @if ($loop->last)
                                                <span class="rounded-full border border-[var(--line)] px-2 py-0.5 text-[11px] font-semibold text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">Status terbaru</span>
                                            @endif
                                            <span class="text-xs text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">{{ $h->created_at->diffForHumans() }}</span>
                                        </div>

                                        <p class="mt-1.5 text-xs text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">
                                            {{ $h->created_at->translatedFormat('D, d M Y H:i') }} &middot; Oleh:
                                            @if($h->changed_by === null)
                                                <span class="font-semibold text-[var(--accent)]">Sistem</span>
                                            @elseif($h->changed_by === auth()->id())
                                                <span class="font-semibold text-[var(--success)] dark:text-[var(--success)]">Anda</span>
                                            @else
                                                <span class="font-semibold text-[var(--info)] dark:text-[var(--info)]">Admin</span>@if($h->changer?->name) <span class="font-semibold">{{ $h->changer->name }}</span>@endif
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
                                            <p class="mt-2 flex items-start gap-2 border-t border-[var(--line)] pt-3 text-sm text-[var(--ink-muted)]">
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

                        <div class="md:col-span-2"><x-pickup-location :vehicle="$booking->vehicle" /></div>
                    </div>

                    <!-- Langkah selanjutnya setelah pembayaran (EG-53) -->
                    @if ($booking->status === App\Enums\BookingStatus::Paid)
                        <div class="mb-8 pb-8 border-b border-[var(--line)]">
                            <h3 class="text-lg font-bold mb-4">Langkah Selanjutnya</h3>
                            <ol class="space-y-4">
                                <li class="flex items-start gap-3 text-sm">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--accent)] text-xs font-bold text-[var(--on-accent)]">1</span>
                                    <span class="text-[var(--ink-muted)]">Datang ke lokasi pengambilan sesuai alamat dan jam operasional di atas, mulai sewa <strong class="text-[var(--ink)]">{{ $booking->start_at->translatedFormat('D, d M Y H:i') }}</strong>.</span>
                                </li>
                                <li class="flex items-start gap-3 text-sm">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--accent)] text-xs font-bold text-[var(--on-accent)]">2</span>
                                    <span class="text-[var(--ink-muted)]">Bawa KTP atau SIM yang masih berlaku untuk verifikasi saat serah terima.</span>
                                </li>
                                <li class="flex items-start gap-3 text-sm">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--accent)] text-xs font-bold text-[var(--on-accent)]">3</span>
                                    <span class="text-[var(--ink-muted)]">Konfirmasi kedatangan lewat tombol Hubungi admin, dan pantau Riwayat Pesanan untuk catatan dari admin.</span>
                                </li>
                                <li class="flex items-start gap-3 text-sm">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--accent)] text-xs font-bold text-[var(--on-accent)]">4</span>
                                    <span class="text-[var(--ink-muted)]">Kembalikan unit sebelum <strong class="text-[var(--ink)]">{{ $booking->end_at->translatedFormat('D, d M Y H:i') }}</strong>, lalu minta admin menutup pesanan ini.</span>
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
                            <a href="{{ $booking->vehicle?->branch?->whatsappUrl('Halo, saya ingin bertanya soal pesanan '.$booking->code) ?: 'https://wa.me/'.preg_replace('/\D/', '', (string) setting('contact.whatsapp')) }}" target="_blank" rel="noopener" class="btn-wa-edu inline-flex w-full items-center justify-center px-5 py-2.5 text-sm sm:w-auto">
                                Hubungi admin
                            </a>

                            @if($booking->status === App\Enums\BookingStatus::Pending)
                                <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'batal-{{ $booking->code }}')" class="inline-flex min-h-11 items-center justify-center text-sm text-danger underline">
                                    Batalkan
                                </button>

                                <!-- Modal Konfirmasi Batal -->
                                <x-modal name="batal-{{ $booking->code }}" maxWidth="sm">
                                    <div class="p-6">
                                        <h2 class="text-lg font-medium text-[var(--ink)] mb-2">Batalkan pesanan {{ $booking->code }}?</h2>
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
                            @endif
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
