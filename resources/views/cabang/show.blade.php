@php
    // Filter waktu ikut terbawa ke detail motor (dibaca modal durasi lewat ?start=).
    $carry = array_filter([
        'start' => $filter['start_at'],
        'days' => $filter['days'],
    ], fn ($value) => filled($value));

    $activeCount = collect([$filter['type'], $filter['start_at'], $filter['available'] ?: null, $filter['q']])->filter(fn ($v) => filled($v))->count();
@endphp

<x-app-layout :title="$cabang->name">
    <div class="mx-auto max-w-[1320px] space-y-6 px-4 pt-8 sm:px-6 lg:pt-12 xl:px-0" x-data="{ loading: false }" x-on:pageshow.window="loading = false">
        <div>
            <h1 class="reveal text-2xl">{{ $cabang->name }}</h1>
            <p class="mt-2 text-sm">
                @if ($cabang->address) {{ $cabang->address }} @endif
                @if ($cabang->operating_hours)
                    @if ($cabang->address) <span aria-hidden="true">&middot;</span> @endif
                    {{ $cabang->operating_hours }}
                @endif
            </p>
            <a href="{{ route('kendaraan.index', ['cabang' => $cabang->slug]) }}" class="btn-secondary-edu mt-3 inline-flex items-center px-4 py-2 text-sm">Jadikan cabang pilihan</a>
        </div>

        <x-responsive-filters name="catalog-filters">
            <x-filter-bar
                :action="route('cabang.show', $cabang->slug)"
                class="rounded-b-none lg:rounded-b-[inherit]"
                x-on:change="if (window.matchMedia('(min-width: 1024px)').matches) $el.requestSubmit()"
                x-on:submit="loading = true; $dispatch('close-modal', 'catalog-filters')"
            >
                <x-form.chips name="type" label="Tipe kendaraan" placeholder="Semua" :options="$types" :value="$filter['type']" class="sm:col-span-2 lg:col-span-4" />

                <x-form.datetime name="start_at" label="Tanggal & jam sewa" :value="$filter['start_at']" :min="$minStart->format('Y-m-d\TH:i')" step="3600" :picker="true" />

                <x-form.select name="days" label="Durasi sewa" placeholder="Pilih durasi" :options="collect(range(1, $maxDays))->mapWithKeys(fn ($d) => [$d => $d.' hari'])->all()" :value="$filter['days']" />

                <x-form.input name="q" label="Cari kendaraan" placeholder="Nama motor" :value="$filter['q']" maxlength="100" />

                <x-form.select name="harga" label="Urutkan harga" placeholder="Default (nama)"
                    :options="['termurah' => 'Harga termurah', 'termahal' => 'Harga termahal', 'populer' => 'Paling populer']" :value="$filter['harga']" />

                <x-form.input name="harga_min" type="number" label="Harga minimal" placeholder="Rp0" :value="$filter['harga_min']" min="0" />

                <x-form.input name="harga_max" type="number" label="Harga maksimal" placeholder="Semua" :value="$filter['harga_max']" min="0" />

                @if ($filter['available'])
                    <input type="hidden" name="available" value="1">
                @endif

                <x-slot name="actions" class="lg:hidden">
                    <x-primary-button class="flex-1 justify-center">Terapkan</x-primary-button>
                    <a href="{{ route('cabang.reset', $cabang->slug) }}" class="btn-secondary-edu inline-flex flex-1 items-center justify-center px-5 py-2.5 text-sm">Reset</a>
                    <button type="button" class="btn-secondary-edu px-3 py-1.5 text-xs" x-on:click="$dispatch('close-modal', 'catalog-filters')">Tutup</button>
                </x-slot>
            </x-filter-bar>

            @if ($activeCount > 0)
                <a href="{{ route('cabang.reset', $cabang->slug) }}" class="mt-2 hidden text-sm font-medium text-[var(--ink)] underline-offset-4 hover:underline lg:inline-block">Reset filter</a>
            @endif
        </x-responsive-filters>

        @if ($filter['start_at'] && $filter['days'])
            <p class="text-sm" role="status">
                Menampilkan motor yang tersedia mulai
                <strong class="text-[var(--ink)]">{{ \Illuminate\Support\Carbon::parse($filter['start_at'])->translatedFormat('d M Y, H:i') }}</strong>
                selama <strong class="text-[var(--ink)]">{{ $filter['days'] }} hari</strong>.
            </p>
        @endif

        <x-vehicle-grid-skeleton x-show="loading" x-cloak style="display: none" />

        <div class="space-y-6" x-show="!loading">
        @if ($vehicles->isEmpty())
            @if ($timeFiltered)
                <x-empty-state title="Tidak ada motor tersedia di waktu tersebut" description="Coba ganti tanggal, jam, tipe, atau durasi sewa." action-label="Reset filter" :action-href="route('cabang.reset', $cabang->slug)" />
            @else
                <x-empty-state title="Belum ada motor di cabang ini" description="Motor tidak ditemukan sesuai filter Anda." action-label="Reset filter" :action-href="route('cabang.reset', $cabang->slug)" />
            @endif
        @else
            <div class="reveal-group grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($vehicles as $vehicle)
                    <x-vehicle-card
                        :vehicle="$vehicle"
                        :available="! $vehicle->is_rented"
                        :scheduled="$timeFiltered"
                        :days="$filter['days']"
                        :href="route('kendaraan.detail', ['vehicle' => $vehicle] + $carry)"
                    />
                @endforeach
            </div>

            {{ $vehicles->links('components.pagination') }}
        @endif
        </div>
    </div>

    <p class="mx-auto mt-8 max-w-[1320px] px-4 text-sm"><a href="{{ route('terms') }}" class="inline-flex min-h-11 items-center underline">Syarat &amp; ketentuan sewa</a></p>
</x-app-layout>
