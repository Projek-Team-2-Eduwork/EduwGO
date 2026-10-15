<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
            <h2 class="text-xl font-semibold leading-tight">
                {{ __('Kelola Kendaraan') }}
            </h2>
            <a href="{{ route('admin.kendaraan.create') }}" class="btn-primary-edu inline-flex items-center px-4 py-2 text-sm">
                + Tambah Kendaraan
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6">
            
            @if(session('success'))
                <x-alert type="success">
                    {{ session('success') }}
                </x-alert>
            @endif

            <!-- Filters -->
            <div class="card-surface p-4">
                <form method="GET" action="{{ route('admin.kendaraan.index') }}" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <x-text-input name="q" value="{{ request('q') }}" placeholder="Cari nama atau plat..." aria-label="Cari nama atau plat" class="w-full" />
                    </div>
                    <div>
                        <select name="tipe" aria-label="Filter tipe kendaraan" class="w-full rounded-md shadow-sm">
                            <option value="">Semua Tipe</option>
                            @foreach($types as $id => $name)
                                <option value="{{ $id }}" {{ request('tipe') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select name="status" aria-label="Filter status kendaraan" class="w-full rounded-md shadow-sm">
                            <option value="">Semua Status</option>
                            <option value="tersedia" {{ request('status') === 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                            <option value="disewa" {{ request('status') === 'disewa' ? 'selected' : '' }}>Disewa</option>
                            <option value="nonaktif" {{ request('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div>
                        <x-primary-button type="submit">Filter</x-primary-button>
                        @if(request()->hasAny(['q', 'tipe', 'status']))
                            <a href="{{ route('admin.kendaraan.index') }}" class="ml-2 text-sm underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Reset</a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Grid Card -->
            @if($vehicles->isEmpty())
                <x-empty-state title="Tidak ada kendaraan" description="Tidak ada kendaraan yang ditemukan." />
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($vehicles as $vehicle)
                        @php
                            if (!$vehicle->is_active) {
                                $badge = ['text' => 'Nonaktif', 'class' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'];
                            } elseif ($vehicle->is_rented) {
                                $badge = ['text' => 'Disewa', 'class' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'];
                            } else {
                                $badge = ['text' => 'Tersedia', 'class' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'];
                            }
                        @endphp

                        <div class="card-surface group flex flex-col overflow-hidden transition hover:-translate-y-0.5">
                            <div class="relative h-48 bg-[var(--bg)]">
                                @if($vehicle->image)
                                    <img src="{{ asset($vehicle->image) }}" class="w-full h-full object-cover" alt="{{ $vehicle->name }}" loading="lazy">
                                @endif
                                <span class="absolute top-2 right-2 px-2 py-1 text-xs font-bold rounded {{ $badge['class'] }}">
                                    {{ $badge['text'] }}
                                </span>
                            </div>
                            <div class="p-4 flex flex-col flex-grow">
                                <h3 class="text-lg font-bold">{{ $vehicle->name }}</h3>
                                <p class="mb-2 text-sm">{{ $vehicle->type?->name ?? '-' }}</p>
                                
                                <div class="mt-auto flex items-center justify-between pt-4">
                                    <div class="rounded bg-[var(--bg)] px-2 py-1 font-mono text-sm">
                                        {{ $vehicle->plate_number }}
                                    </div>
                                    <div class="font-bold text-[var(--orange-500)]">
                                        Rp {{ number_format($vehicle->price_per_day, 0, ',', '.') }}<span class="text-xs font-normal">/hari</span>
                                    </div>
                                </div>
                                
                                <div class="mt-4 flex gap-2">
                                    <a href="{{ route('admin.kendaraan.show', $vehicle->id) }}" class="btn-secondary-edu flex-1 px-4 py-2 text-center text-xs">
                                        Detail
                                    </a>
                                    <a href="{{ route('admin.kendaraan.edit', $vehicle->id) }}" class="btn-secondary-edu flex-1 px-4 py-2 text-center text-xs">
                                        Update
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-6">
                    {{ $vehicles->links('components.pagination') }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>