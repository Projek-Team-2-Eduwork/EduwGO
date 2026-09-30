@php
    // Filter waktu ikut terbawa ke detail motor (dibaca modal durasi lewat ?start=).
    $carry = array_filter([
        'start' => $filter['start_at'],
        'days' => $filter['days'],
    ], fn ($value) => filled($value));

    $activeCount = collect([$filter['type'], $filter['start_at'], $filter['available'] ?: null, $filter['q']])->filter(fn ($v) => filled($v))->count();
@endphp

<x-app-layout title="Pilih Kendaraan">
    <div class="mx-auto max-w-[1320px] space-y-6 px-4 py-8 sm:px-6 lg:py-12 xl:px-0" x-data="{ filterOpen: false }" x-on:keydown.escape.window="filterOpen = false">
        <div>
            <h1 class="text-2xl">Daftar Kendaraan</h1>
            <p class="mt-2 text-sm">Cari motor sesuai tipe dan waktu sewa. Hanya unit yang benar-benar tersedia yang ditampilkan saat waktu diisi.</p>
        </div>

        {{-- Mobile: tombol Filter membuka bottom sheet; desktop: bar inline --}}
        <button type="button" class="btn-secondary-edu inline-flex w-full items-center justify-center gap-2 px-4 py-2.5 text-sm lg:hidden" x-on:click="filterOpen = true" aria-haspopup="dialog">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 5h18M6 12h12M10 19h4"/></svg>
            Filter
            @if ($activeCount > 0)
                <span class="rounded-full bg-[var(--orange-500)] px-2 text-xs font-semibold text-white">{{ $activeCount }}</span>
            @endif
        </button>

        <div x-show="filterOpen" x-transition.opacity class="fixed inset-0 z-40 bg-black/50 lg:hidden" x-on:click="filterOpen = false" aria-hidden="true"></div>

        <div
            id="filter-panel"
            class="fixed inset-x-0 bottom-0 z-50 hidden max-h-[85vh] overflow-y-auto rounded-t-2xl shadow-2xl lg:static lg:z-auto lg:block lg:max-h-none lg:overflow-visible lg:rounded-none lg:shadow-none"
            x-bind:class="{ '!block': filterOpen }"
            x-bind:role="filterOpen ? 'dialog' : null"
            x-bind:aria-modal="filterOpen ? 'true' : null"
            aria-label="Filter kendaraan"
        >
            {{-- Desktop: submit otomatis saat nilai berubah (Figma tanpa tombol); mobile: lewat tombol Terapkan --}}
            <x-filter-bar
                :action="route('kendaraan.index')"
                class="rounded-b-none lg:rounded-b-[inherit]"
                x-on:change="if (window.matchMedia('(min-width: 1024px)').matches) $el.requestSubmit()"
            >
                <x-form.select name="type" label="Tipe kendaraan" placeholder="Semua" :options="$types" :value="$filter['type']" />

                <x-form.datetime name="start_at" label="Tanggal & jam sewa" :value="$filter['start_at']" :min="$minStart->format('Y-m-d\TH:i')" step="3600" />

                <x-form.select name="days" label="Durasi sewa" placeholder="Pilih durasi" :options="collect(range(1, $maxDays))->mapWithKeys(fn ($d) => [$d => $d.' hari'])->all()" :value="$filter['days']" />

                <x-form.input name="q" label="Cari kendaraan" placeholder="Nama motor" :value="$filter['q']" maxlength="100" />

                {{-- Filter "hanya tersedia" tetap didukung lewat query ?available=1, tapi tidak ada di desain --}}
                @if ($filter['available'])
                    <input type="hidden" name="available" value="1">
                @endif

                <x-slot name="actions" class="lg:hidden">
                    <x-primary-button class="flex-1 justify-center">Terapkan</x-primary-button>
                    <a href="{{ route('kendaraan.reset') }}" class="btn-secondary-edu inline-flex flex-1 items-center justify-center px-4 py-2 text-sm">Reset</a>
                    <button type="button" class="px-2 py-2 text-sm underline" x-on:click="filterOpen = false">Tutup</button>
                </x-slot>
            </x-filter-bar>

            @if ($activeCount > 0)
                <a href="{{ route('kendaraan.reset') }}" class="mt-2 hidden text-sm font-medium text-[var(--navy-900)] underline-offset-4 hover:underline lg:inline-block">Reset filter</a>
            @endif
        </div>

        @if ($filter['start_at'] && $filter['days'])
            <p class="text-sm" role="status">
                Menampilkan motor yang tersedia mulai
                <strong class="text-[var(--navy-900)]">{{ \Illuminate\Support\Carbon::parse($filter['start_at'])->translatedFormat('d M Y, H:i') }}</strong>
                selama <strong class="text-[var(--navy-900)]">{{ $filter['days'] }} hari</strong>.
            </p>
        @endif

        @if ($vehicles->isEmpty())
            @if ($timeFiltered)
                <x-empty-state title="Tidak ada motor tersedia di waktu tersebut" description="Coba ganti tanggal, jam, tipe, atau durasi sewa." action-label="Reset filter" :action-href="route('kendaraan.reset')" />
            @else
                <x-empty-state title="Motor tidak ditemukan" description="Tidak ada motor yang cocok dengan filter kamu." action-label="Reset filter" :action-href="route('kendaraan.reset')" />
            @endif
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($vehicles as $vehicle)
                    <x-vehicle-card
                        :vehicle="$vehicle"
                        :available="$timeFiltered ? true : ! $vehicle->is_rented"
                        :href="route('kendaraan.detail', ['vehicle' => $vehicle] + $carry)"
                    />
                @endforeach
            </div>

            {{ $vehicles->links('components.pagination') }}
        @endif
    </div>

    @include('home.terms')
</x-app-layout>
