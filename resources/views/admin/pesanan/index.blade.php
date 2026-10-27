<x-admin-layout title="Pesanan">
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">
            Kelola pesanan
        </h2>
    </x-slot>

    <div class="py-2 sm:py-6">
        <div class="max-w-7xl mx-auto space-y-6">

            @if(session('success'))
                <x-alert type="success">
                    {{ session('success') }}
                </x-alert>
            @endif

            @if(session('error'))
                <x-alert type="error">
                    {{ session('error') }}
                </x-alert>
            @endif

            <!-- Filter -->
            @php
                $statusOptions = collect(App\Enums\BookingStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all() + ['aktif' => 'Aktif (belum selesai)', 'terlambat' => 'Terlambat'];
            @endphp
            <div class="section-plain p-4">
                <x-responsive-filters name="admin-filters"><form method="GET" action="{{ route('admin.pesanan.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @include('admin.partials.branch-filter')
                    <x-form.input name="q" label="Cari pesanan" :value="request('q')" placeholder="Nama / motor / kode" />
                    <x-form.select name="status" label="Status" :options="$statusOptions" :value="request('status')" placeholder="Semua status" />
                    <x-form.select name="tipe" label="Tipe kendaraan" :options="$types" :value="request('tipe')" placeholder="Semua tipe" />
                    <x-form.select name="urut" label="Urutkan" :options="['kode_asc' => 'Kode terendah → tertinggi', 'kode_desc' => 'Kode tertinggi → terendah']" :value="request('urut')" placeholder="Jadwal terbaru" />
                    <x-form.datetime name="mulai" label="Jadwal dari" :value="request('mulai')" :picker="true" />
                    <x-form.datetime name="selesai" label="Jadwal sampai" :value="request('selesai')" :picker="true" />
                    <div class="flex items-center justify-end gap-3 sm:col-span-2 lg:col-span-3">
                        @if(request()->hasAny(['q', 'status', 'tipe', 'urut', 'mulai', 'selesai', 'branch']))
                            <a href="{{ route('admin.pesanan.index') }}" class="text-sm underline-offset-4 hover:underline">Reset</a>
                        @endif
                        <x-primary-button class="h-11 px-6">Terapkan</x-primary-button>
                    </div>
                </form></x-responsive-filters>
            </div>

            <!-- Kartu ringkasan status (EG-51): klik untuk filter, klik lagi untuk lepas -->
            @php
                $statusHref = fn (string $key) => request('status') === $key
                    ? request()->fullUrlWithQuery(['status' => null])
                    : request()->fullUrlWithQuery(['status' => $key]);
            @endphp
            <div class="grid grid-cols-2 gap-x-4 gap-y-4 border-y border-[var(--line)] py-4 sm:grid-cols-3 sm:gap-x-6 sm:py-5 xl:grid-cols-6">
                @foreach(App\Enums\BookingStatus::cases() as $status)
                    <a href="{{ $statusHref($status->value) }}" class="inline-flex min-h-11 flex-col justify-center {{ request('status') === $status->value ? 'font-bold text-[var(--accent)]' : '' }}" @if(request('status') === $status->value) aria-current="true" @endif>
                        <div class="text-xs font-medium leading-snug sm:text-sm">{{ $status->label() }}</div>
                        <div class="mt-1.5 text-2xl font-bold leading-none text-[var(--ink)] sm:mt-2 sm:text-3xl">{{ $summary[$status->value] }}</div>
                    </a>
                @endforeach
            </div>

            <!-- Content Container -->
            <div class="section-plain overflow-hidden">

                @if($bookings->isEmpty())
                    <x-empty-state illustration="bookings" title="Tidak ada pesanan" description="Tidak ada pesanan yang sesuai dengan filter pencarian." />
                @else
                    <!-- Tabel Desktop -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-sm text-left text-[var(--ink-muted)] dark:text-[var(--ink-muted)]">
                            <thead class="text-xs uppercase bg-[var(--bg)] text-[var(--ink-muted)]">
                                <tr>
                                    <th>Kendaraan</th>
                                    <th>Kode Booking</th>
                                    <th>Jadwal Sewa</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bookings as $b)
                                    <tr class="border-b border-[var(--line)] {{ $b->isOverdue() ? 'bg-[var(--danger-soft)] dark:bg-[var(--danger-soft)]' : '' }} hover:bg-[var(--bg)] transition cursor-pointer" onclick="window.location='{{ route('admin.pesanan.show', $b->code) }}'">
                                        <td class="px-4 py-3 flex items-center gap-3">
                                            @if($b->vehicle?->image)
                                                <x-picture :src="$b->vehicle->image" class="w-12 h-12 rounded-lg object-cover" />
                                            @else
                                                <div class="w-12 h-12 rounded-lg bg-[var(--bg)] flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-[var(--ink-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="font-bold text-[var(--ink)]">{{ $b->vehicle?->name ?? 'Kendaraan Dihapus' }}</div>
                                                <div class="text-xs text-[var(--ink-muted)]">{{ $b->customer_name }}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 font-mono font-medium">{{ $b->code }}</td>
                                        <td class="px-4 py-3 text-xs">
                                            <div class="font-medium text-[var(--ink)]">{{ $b->start_at->translatedFormat('d M Y H:i') }}</div>
                                            <div class="text-[var(--ink-muted)]">{{ $b->duration_days }} hari</div>
                                        </td>
                                        <td>
                                            <x-badge-status :status="$b->status" />
                                            @if($b->isOverdue())
                                                <span class="ml-2 px-2 py-0.5 text-[10px] font-bold rounded bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200">Terlambat {{ $b->end_at->diffForHumans() }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-[var(--ink)]">
                                            {{ rupiah($b->total_amount) }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if(Route::has('admin.pesanan.update') && in_array($b->status->value, ['pending', 'paid', 'rented']))
                                                <button type="button" x-data @click.stop="$dispatch('open-modal', 'update-{{ $b->code }}')" class="font-medium text-[var(--accent)] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent)]">Ubah</button>
                                            @else
                                                <span class="text-xs" title="Tidak ada perubahan status yang tersedia">Selesai</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Card Mobile -->
                    <div class="md:hidden">
                        @foreach($bookings as $b)
                            <a href="{{ route('admin.pesanan.show', $b->code) }}" class="block border-b border-[var(--line)] p-4 {{ $b->isOverdue() ? 'bg-[var(--danger-soft)] dark:bg-[var(--danger-soft)]' : '' }}">
                                <div class="mb-2 flex items-start justify-between gap-2">
                                    <div class="min-w-0 font-mono text-sm font-bold text-[var(--ink)]">{{ $b->code }}</div>
                                    <div class="flex shrink-0 flex-col items-end gap-1">
                                        <x-badge-status :status="$b->status" />
                                        @if($b->isOverdue())
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200">Terlambat {{ $b->end_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-3 flex gap-3">
                                    @if($b->vehicle?->image)
                                        <x-picture :src="$b->vehicle->image" class="h-16 w-16 shrink-0 rounded-lg object-cover" />
                                    @else
                                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-[var(--bg)]">
                                            <svg class="h-8 w-8 text-[var(--ink-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="break-words font-bold text-[var(--ink)]">{{ $b->vehicle?->name ?? 'Dihapus' }}</div>
                                        <div class="break-words text-sm text-[var(--ink-muted)]">{{ $b->customer_name }}</div>
                                        <div class="mt-1 text-xs text-[var(--ink-muted)]">{{ $b->start_at->translatedFormat('d M Y H:i') }} ({{ $b->duration_days }} hr)</div>
                                    </div>
                                </div>
                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <div class="font-bold text-[var(--ink)]">{{ rupiah($b->total_amount) }}</div>
                                    @if(Route::has('admin.pesanan.update') && in_array($b->status->value, ['pending', 'paid', 'rented']))
                                        <span role="button" tabindex="0" x-data @click.prevent.stop="$dispatch('open-modal', 'update-{{ $b->code }}')" @keydown.enter.prevent.stop="$dispatch('open-modal', 'update-{{ $b->code }}')" @keydown.space.prevent.stop="$dispatch('open-modal', 'update-{{ $b->code }}')" class="inline-flex min-h-11 items-center cursor-pointer text-sm font-medium text-[var(--accent)] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent)]">Ubah</span>
                                    @else
                                        <span class="text-xs" title="Tidak ada perubahan status yang tersedia">Selesai</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="border-t border-[var(--line)] p-4">
                    {{ $bookings->links('components.pagination') }}
                </div>
            </div>

            <!-- Modals (Diletakkan di luar container tabel/list utama) -->
            @foreach($bookings as $booking)
                @include('admin.partials.update-status-modal', ['booking' => $booking])
            @endforeach

        </div>
    </div>
</x-admin-layout>
