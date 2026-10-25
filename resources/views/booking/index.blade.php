<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl">Riwayat Transaksi</h1>
                <form method="GET" action="{{ route('booking.index') }}" class="flex">
                    <select name="status" aria-label="Filter status pesanan" class="rounded-lg text-sm shadow-sm" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        @foreach(App\Enums\BookingStatus::cases() as $statusOpt)
                            <option value="{{ $statusOpt->value }}" {{ request('status') === $statusOpt->value ? 'selected' : '' }}>
                                {{ $statusOpt->label() }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="card-surface p-5">
                    <div class="text-sm text-[var(--ink-muted)] mb-1">Jumlah Transaksi</div>
                    <div class="text-lg font-bold text-[var(--navy-900)]">{{ $totalTransaksi }}</div>
                </div>
                <div class="card-surface p-5">
                    <div class="text-sm text-[var(--ink-muted)] mb-1">Total Dibayar</div>
                    <div class="text-lg font-bold text-[var(--navy-900)]">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
                </div>
                <div class="card-surface p-5">
                    <div class="text-sm text-[var(--ink-muted)] mb-1">Menunggu Bayar</div>
                    <div class="text-lg font-bold text-[var(--navy-900)]">{{ $menungguBayar }}</div>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6">
                    <x-alert type="success" dismissible>
                        {{ session('success') }}
                    </x-alert>
                </div>
            @endif

            @if($bookings->isEmpty())
                <x-empty-state title="Belum ada pesanan" description="Sewa motor favoritmu sekarang." action-label="Pilih Kendaraan" :action-href="route('kendaraan.index')" />
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($bookings as $booking)
                        <div class="card-surface group flex flex-col overflow-hidden transition hover:-translate-y-0.5">
                            <img src="{{ $booking->vehicle?->image ? asset($booking->vehicle->image) : '' }}" alt="{{ $booking->vehicle?->name ?? 'Kendaraan dihapus' }}" class="w-full h-48 object-cover" loading="lazy" onerror="this.outerHTML='<div class=\'w-full h-48 bg-[var(--bg)] flex items-center justify-center\'><svg class=\'w-12 h-12 text-[var(--border)]\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L28 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'></path></svg></div>'">
                            <div class="p-5 flex flex-col flex-1">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="truncate text-base font-bold">{{ $booking->vehicle?->name ?? 'Kendaraan Dihapus' }}</h3>
                                    <x-badge-status :status="$booking->status" />
                                </div>
                                <p class="text-sm mb-2">{{ $booking->vehicle?->type?->name ?? '-' }}</p>
                                <p class="font-mono text-sm mb-4">{{ $booking->code }}</p>
                                <p class="text-sm mb-2">Mulai: {{ $booking->start_at->translatedFormat('D, d M H:i') }} &middot; {{ $booking->duration_days }} hari</p>
                                
                                <div class="mb-4">
                                    @if($booking->latestPayment)
                                        @php
                                            $methodLabel = ucwords(str_replace('_', ' ', $booking->latestPayment->method));
                                            $paymentStatus = $booking->latestPayment->status;
                                            $badgeClass = match($paymentStatus) {
                                                'paid' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
                                                'pending' => 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200',
                                                'expired', 'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                                                default => 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-100',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                            {{ $methodLabel }} - {{ ucfirst($paymentStatus) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-100">Belum bayar</span>
                                    @endif
                                </div>

                                <div class="mt-auto">
                                    <p class="mb-4 text-lg font-bold text-[var(--navy-900)]">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</p>
                                    
                                    <div class="flex gap-2">
                                        <a href="{{ route('booking.show', $booking->code) }}" class="btn-secondary-edu inline-flex flex-1 items-center justify-center px-5 py-2.5 text-sm">
                                            Detail
                                        </a>
                                        
                                        @if($booking->status === App\Enums\BookingStatus::Pending)
                                            <x-danger-button x-data @click="$dispatch('open-modal', 'cancel-booking-{{ $booking->code }}')" class="flex-1 justify-center">
                                                Batalkan
                                            </x-danger-button>
                                            
                                            <x-modal name="cancel-booking-{{ $booking->code }}" focusable>
                                                <form method="POST" action="{{ route('booking.cancel', $booking->code) }}" class="p-6">
                                                    @csrf
                                                    <h2 class="text-lg font-semibold text-[var(--navy-900)]">Batalkan Pesanan</h2>
                                                    <p class="mt-1 text-sm">Apakah Anda yakin ingin membatalkan pesanan ini?</p>
                                                    <div class="mt-6 flex justify-end">
                                                        <x-secondary-button x-on:click="$dispatch('close')">Tutup</x-secondary-button>
                                                        <x-danger-button class="ml-3">Ya, Batalkan</x-danger-button>
                                                    </div>
                                                </form>
                                            </x-modal>
                                        @elseif(in_array($booking->status, [App\Enums\BookingStatus::Paid, App\Enums\BookingStatus::Rented]))
                                            <a href="https://wa.me/{{ setting('contact.whatsapp', '6281234567890') }}" target="_blank" rel="noopener" class="btn-wa-edu inline-flex flex-1 items-center justify-center gap-2 px-5 py-2.5 text-sm">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                                WhatsApp
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-6">
                    {{ $bookings->links('components.pagination') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>