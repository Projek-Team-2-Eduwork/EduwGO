<x-admin-layout title="Kelola kendaraan">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="min-w-0 truncate text-xl font-semibold leading-tight">Kelola Kendaraan</h2>
            <a href="{{ route('admin.kendaraan.create') }}" class="btn-primary-edu inline-flex shrink-0 items-center px-4 text-sm">
                + Tambah<span class="hidden sm:inline">&nbsp;Kendaraan</span>
            </a>
        </div>
    </x-slot>

    <div class="py-2 sm:py-6">
        <div class="max-w-7xl mx-auto space-y-6">

            @if(session('success'))
                <x-alert type="success">
                    {{ session('success') }}
                </x-alert>
            @endif

            <!-- Filters -->
            <div class="section-plain p-4">
                <x-responsive-filters name="admin-filters"><form method="GET" action="{{ route('admin.kendaraan.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                    <x-form.input name="q" label="Cari" :value="request('q')" placeholder="Nama atau plat" />
                    @include('admin.partials.branch-filter')
                    <x-form.select name="tipe" label="Tipe kendaraan" :options="$types" :value="request('tipe')" placeholder="Semua tipe" />
                    <x-form.select name="status" label="Status" :options="['tersedia' => 'Tersedia', 'disewa' => 'Sedang disewa', 'nonaktif' => 'Nonaktif']" :value="request('status')" placeholder="Semua status" />
                    <div class="flex items-center gap-3">
                        <x-primary-button class="h-11 px-6">Terapkan</x-primary-button>
                        @if(request()->hasAny(['q', 'tipe', 'status', 'branch']))
                            <a href="{{ route('admin.kendaraan.index') }}" class="text-sm underline-offset-4 hover:underline">Reset</a>
                        @endif
                    </div>
                </form></x-responsive-filters>
            </div>

            @if($vehicles->isEmpty())
                <x-empty-state title="Tidak ada kendaraan" description="Tidak ada kendaraan yang ditemukan." />
            @else
                @php
                    $badgeFor = function ($vehicle) {
                        if (! $vehicle->is_active) {
                            return ['Nonaktif', 'bg-[var(--bg)] text-[var(--ink-muted)]'];
                        }

                        return $vehicle->is_rented
                            ? ['Sedang disewa', 'bg-[var(--warning-soft)] text-[var(--warning)]']
                            : ['Tersedia', 'bg-[var(--success-soft)] text-[var(--success)]'];
                    };
                @endphp
                <div class="section-plain overflow-hidden">
                    {{-- Tabel padat (desktop) --}}
                    <div class="hidden overflow-x-auto md:block">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-[var(--bg)] text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-3">Kendaraan</th>
                                    <th class="px-4 py-3">Plat</th>
                                    @if($branches->isNotEmpty()) <th class="px-4 py-3">Cabang</th> @endif
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Harga/hari</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vehicles as $vehicle)
                                    @php([$label, $tone] = $badgeFor($vehicle))
                                    <tr>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center gap-3">
                                                @if($vehicle->image)
                                                    <x-picture :src="$vehicle->image" class="h-12 w-12 shrink-0 rounded-lg object-cover" alt="" loading="lazy" />
                                                @else
                                                    <span class="h-12 w-12 shrink-0 rounded-lg bg-[var(--bg)]" aria-hidden="true"></span>
                                                @endif
                                                <div class="min-w-0">
                                                    <div class="truncate font-semibold text-[var(--ink)]">{{ $vehicle->name }}</div>
                                                    <div class="text-xs">{{ $vehicle->type?->name ?? '-' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 font-mono text-xs">{{ $vehicle->plate_number }}</td>
                                        @if($branches->isNotEmpty()) <td class="px-4 py-2 text-xs">{{ $vehicle->branch?->name ?? '-' }}</td> @endif
                                        <td class="px-4 py-2"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $tone }}">{{ $label }}</span></td>
                                        <td class="px-4 py-2 text-right font-semibold text-[var(--ink)]">{{ rupiah($vehicle->price_per_day) }}</td>
                                        <td class="px-4 py-2">
                                            <div class="flex justify-end gap-4 text-sm font-medium">
                                                <a href="{{ route('admin.kendaraan.show', $vehicle->id) }}" class="text-[var(--accent)] hover:underline">Detail</a>
                                                <a href="{{ route('admin.kendaraan.edit', $vehicle->id) }}" class="text-[var(--accent)] hover:underline">Ubah</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Daftar ringkas (mobile) --}}
                    <ul class="divide-y divide-[var(--line)] md:hidden">
                        @foreach($vehicles as $vehicle)
                            @php([$label, $tone] = $badgeFor($vehicle))
                            <li class="flex items-center gap-3 p-3">
                                @if($vehicle->image)
                                    <x-picture :src="$vehicle->image" class="h-12 w-12 shrink-0 rounded-lg object-cover" alt="" loading="lazy" />
                                @else
                                    <span class="h-12 w-12 shrink-0 rounded-lg bg-[var(--bg)]" aria-hidden="true"></span>
                                @endif
                                <a href="{{ route('admin.kendaraan.show', $vehicle->id) }}" class="block min-w-0 flex-1">
                                    <span class="block truncate font-semibold text-[var(--ink)]">{{ $vehicle->name }}</span>
                                    <span class="block truncate text-xs"><span class="font-mono">{{ $vehicle->plate_number }}</span> · {{ rupiah($vehicle->price_per_day) }}/hari</span>
                                    <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $tone }}">{{ $label }}</span>
                                </a>
                                <a href="{{ route('admin.kendaraan.edit', $vehicle->id) }}" class="shrink-0 px-2 text-sm font-medium text-[var(--accent)]">Ubah</a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="border-t border-[var(--line)] p-4">
                        {{ $vehicles->links('components.pagination') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
