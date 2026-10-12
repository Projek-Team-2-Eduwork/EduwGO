<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">
            {{ __('Daftar Pesanan') }}
        </h2>
    </x-slot>

    <div class="py-12">
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
            <div class="card-surface p-4">
                <form method="GET" action="{{ route('admin.pesanan.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="md:col-span-1">
                        <x-input-label for="q" value="Cari Pesanan" />
                        <x-text-input id="q" name="q" value="{{ request('q') }}" placeholder="Nama / Motor / Kode" class="w-full mt-1" />
                    </div>
                    <div class="md:col-span-1">
                        <x-input-label for="status" value="Status" />
                        <select name="status" id="status" class="w-full rounded-md text-sm shadow-sm sm:w-auto" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                                @foreach(App\Enums\BookingStatus::cases() as $s)
                            <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                            @endforeach
                            <option value="terlambat" {{ request('status') === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                        </select>
                    </div>
                    <div class="md:col-span-1">
                        <x-input-label for="tipe" value="Tipe Kendaraan" />
                        <select id="tipe" name="tipe" class="mt-1 w-full rounded-md shadow-sm">
                            <option value="">Semua Tipe</option>
                            @foreach($types as $id => $name)
                                <option value="{{ $id }}" {{ request('tipe') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-1">
                        <x-input-label for="mulai" value="Mulai" />
                        <x-text-input id="mulai" type="datetime-local" name="mulai" value="{{ request('mulai') }}" class="w-full mt-1" />
                    </div>
                    <div class="md:col-span-1">
                        <x-input-label for="selesai" value="Selesai" />
                        <div class="flex gap-2 items-start mt-1">
                            <x-text-input id="selesai" type="datetime-local" name="selesai" value="{{ request('selesai') }}" class="w-full" />
                            <x-primary-button type="submit" class="px-4 py-3">Cari</x-primary-button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Content Container -->
            <div class="card-surface overflow-hidden">
                
                @if($bookings->isEmpty())
                    <x-empty-state title="Tidak ada pesanan" description="Tidak ada pesanan yang sesuai dengan filter pencarian." />
                @else
                    <!-- Tabel Desktop -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-3">Kendaraan</th>
                                    <th class="px-4 py-3">Kode Booking</th>
                                    <th class="px-4 py-3">Jadwal Sewa</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Total</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bookings as $b)
                                    <tr class="border-b dark:border-gray-700 {{ $b->isOverdue() ? 'bg-red-50 dark:bg-red-900/20' : '' }} hover:bg-gray-50 dark:hover:bg-gray-700 transition cursor-pointer" onclick="window.location='{{ route('admin.pesanan.show', $b->code) }}'">
                                        <td class="px-4 py-3 flex items-center gap-3">
                                            @if($b->vehicle?->image)
                                                <img src="{{ asset($b->vehicle->image) }}" class="w-12 h-12 rounded object-cover">
                                            @else
                                                <div class="w-12 h-12 rounded bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="font-bold text-gray-900 dark:text-gray-100">{{ $b->vehicle?->name ?? 'Kendaraan Dihapus' }}</div>
                                                <div class="text-xs text-gray-500">{{ $b->customer_name }}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 font-mono font-medium">{{ $b->code }}</td>
                                        <td class="px-4 py-3 text-xs">
                                            <div class="font-medium text-gray-800 dark:text-gray-200">{{ $b->start_at->translatedFormat('d M Y H:i') }}</div>
                                            <div class="text-gray-500">{{ $b->duration_days }} hari</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-badge-status :status="$b->status" />
                                            @if($b->isOverdue())
                                                <span class="ml-2 px-2 py-0.5 text-[10px] font-bold rounded bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200">Terlambat {{ $b->end_at->diffForHumans() }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">
                                            Rp {{ number_format($b->total_amount, 0, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if(Route::has('admin.pesanan.update') && in_array($b->status->value, ['pending', 'paid', 'rented']))
                                                <button type="button" x-data @click.stop="$dispatch('open-modal', 'update-{{ $b->code }}')" class="font-medium text-[var(--orange-500)] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Update</button>
                                            @else
                                                <button disabled class="cursor-not-allowed font-medium opacity-50">Update</button>
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
                            <a href="{{ route('admin.pesanan.show', $b->code) }}" class="block border-b border-gray-200 dark:border-gray-700 p-4 {{ $b->isOverdue() ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="font-mono font-bold text-sm text-gray-800 dark:text-gray-200">{{ $b->code }}</div>
                                    <div class="flex flex-col items-end gap-1">
                                        <x-badge-status :status="$b->status" />
                                        @if($b->isOverdue())
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200">Terlambat {{ $b->end_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex gap-3 mb-3">
                                    @if($b->vehicle?->image)
                                        <img src="{{ asset($b->vehicle->image) }}" class="w-16 h-16 rounded object-cover">
                                    @else
                                        <div class="w-16 h-16 rounded bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-gray-100">{{ $b->vehicle?->name ?? 'Dihapus' }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $b->customer_name }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ $b->start_at->translatedFormat('d M Y H:i') }} ({{ $b->duration_days }} hr)</div>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <div class="font-bold text-gray-900 dark:text-gray-100">Rp {{ number_format($b->total_amount, 0, ',', '.') }}</div>
                                    @if(Route::has('admin.pesanan.update') && in_array($b->status->value, ['pending', 'paid', 'rented']))
                                        <span role="button" tabindex="0" x-data @click.prevent.stop="$dispatch('open-modal', 'update-{{ $b->code }}')" @keydown.enter.prevent.stop="$dispatch('open-modal', 'update-{{ $b->code }}')" class="cursor-pointer text-sm font-medium text-[var(--orange-500)] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Update</span>
                                    @else
                                        <span class="text-sm font-medium opacity-50">Update</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
                
                <div class="border-t border-[var(--border)] p-4">
                    {{ $bookings->links() }}
                </div>
            </div>

            <!-- Modals (Diletakkan di luar container tabel/list utama) -->
            @foreach($bookings as $booking)
                @include('admin.partials.update-status-modal', ['booking' => $booking])
            @endforeach

        </div>
    </div>
</x-admin-layout>