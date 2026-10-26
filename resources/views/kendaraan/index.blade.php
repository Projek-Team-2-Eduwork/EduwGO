@php
    use Illuminate\Support\Arr;
    use Illuminate\Support\Carbon;

    // Filter waktu ikut terbawa ke detail motor (dibaca modal durasi lewat ?start=).
    $carry = array_filter([
        'start' => $filter['start_at'],
        'days' => $filter['days'],
    ], fn ($value) => filled($value));

    // Query lengkap saat ini (bentuk canonical: urut lewat `harga`); dasar tautan hapus-per-chip.
    $params = array_filter([
        'type' => $filter['type'],
        'start_at' => $filter['start_at'],
        'days' => $filter['days'],
        'q' => $filter['q'],
        'harga' => $filter['harga'],
        'harga_min' => $filter['harga_min'],
        'harga_max' => $filter['harga_max'],
        'available' => $filter['available'] ? 1 : null,
    ], fn ($value) => filled($value));

    // Hapus satu kelompok; bila tak ada sisa query pakai route reset supaya session tidak memulihkannya lagi.
    $without = function (array $keys) use ($params) {
        $rest = Arr::except($params, $keys);

        return $rest ? route('kendaraan.index', $rest) : route('kendaraan.reset');
    };

    $start = $filter['start_at'] ? Carbon::parse($filter['start_at']) : null;
    $min = $filter['harga_min'];
    $max = $filter['harga_max'];
    $priceLabel = match (true) {
        $min !== null && $max !== null => rupiah((float) $min).'–'.rupiah((float) $max).'/hari',
        $min !== null => 'Mulai '.rupiah((float) $min).'/hari',
        $max !== null => 'Maks. '.rupiah((float) $max).'/hari',
        default => null,
    };

    // [label, parameter yang dibuang]. Urutan tidak dihitung sebagai filter.
    $chips = array_filter([
        $filter['q'] ? ['“'.$filter['q'].'”', ['q']] : null,
        $filter['type'] ? [$types[$filter['type']] ?? 'Tipe', ['type']] : null,
        $timeFiltered ? [$start->translatedFormat('d M, H:i').' · '.$filter['days'].' hari', ['start_at', 'days']] : null,
        $priceLabel ? [$priceLabel, ['harga_min', 'harga_max']] : null,
        $filter['available'] ? ['Tersedia sekarang', ['available']] : null,
    ]);
    $activeCount = count($chips);
    $moreCount = ($priceLabel ? 1 : 0) + ($filter['available'] ? 1 : 0);
    $branchLabel = $currentBranch?->name ?? 'cabang ini';
    $desktop = "window.matchMedia('(min-width: 1024px)').matches";
@endphp

<x-app-layout title="Pilih motor">
    <style>
        /* Filter katalog: satu panel, grid 12 kolom di desktop, tumpuk di mobile. */
        .catalog-filter {
            display: grid;
            gap: .75rem;
            padding: 1rem;
            border: 1px solid var(--line);
            border-radius: .75rem;
            background: var(--surface);
        }

        .catalog-filter .cf-fields { padding: 1.25rem; }
        .catalog-filter .cf-fields > * + * { margin-top: 1.25rem; }
        .catalog-filter .cf-mobile-actions { display: flex; gap: .5rem; }

        @media (min-width: 1024px) {
            .catalog-filter {
                grid-template-columns: repeat(12, minmax(0, 1fr));
                align-items: end;
                gap: 1rem;
            }

            .catalog-filter .cf-branch { grid-column: 1 / span 3; grid-row: 1; }
            .catalog-filter .cf-start { grid-column: 4 / span 5; grid-row: 1; }
            .catalog-filter .cf-days { grid-column: 9 / span 2; grid-row: 1; }
            .catalog-filter .cf-submit { grid-column: 11 / span 2; grid-row: 1; width: 100%; }
            .catalog-filter .cf-q { grid-column: 1 / span 3; grid-row: 2; }
            .catalog-filter .cf-types { grid-column: 4 / span 6; grid-row: 2; }
            .catalog-filter .cf-more { grid-column: 10 / span 3; grid-row: 2; display: flex; justify-content: flex-end; }
            .catalog-filter .cf-error { grid-column: 1 / span 12; grid-row: 3; }

            .catalog-filter .cf-fields { display: contents; padding: 0; }
            .catalog-filter .cf-fields > * + * { margin-top: 0; }
            .catalog-filter .cf-more > button { width: auto; min-width: 10.5rem; }
            .catalog-filter .cf-mobile-actions { display: none; }
        }

        @media (max-width: 639px) {
            .catalog-filter { padding: .75rem; }
        }
    </style>

    <div class="mx-auto max-w-[1320px] space-y-5 px-4 pt-6 sm:px-6 lg:pt-10 xl:px-0" x-data="{ loading: false, pairError: null }" x-on:pageshow.window="loading = false">
        <div>
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm">
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center hover:underline">Beranda</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page" class="text-[var(--ink)]">Pilih motor</span>
            </nav>
            <h1 class="reveal text-2xl">Pilih motor di {{ $currentBranch?->name ?? 'cabang pilihan Anda' }}</h1>
        </div>

        {{-- Satu form GET. Desktop: bar perjalanan + baris filter (grid). Mobile: jadwal & filter di bottom sheet. --}}
        <form id="catalog-form" method="GET" action="{{ route('kendaraan.index') }}"
            class="catalog-filter"
            x-on:submit="
                const s = $el.elements.start_at.value, d = $el.elements.days.value;
                if (!s !== !d) {
                    $event.preventDefault();
                    pairError = s ? 'Pilih durasi sewa juga.' : 'Isi tanggal & jam mulai juga.';
                    if (!{{ $desktop }}) $dispatch('open-modal', 'catalog-filters');
                    return;
                }
                pairError = null; loading = true; $dispatch('close-modal', 'catalog-filters');
            ">
            <div class="cf-branch min-w-0">
                <span class="block text-sm font-medium text-[var(--ink)]">Ambil di</span>
                <x-branch-picker align="left" class="mt-1 min-h-11 w-full" />
            </div>

            {{-- Mobile: ringkasan jadwal selalu terlihat, membuka sheet --}}
            <button type="button" class="flex min-h-11 w-full items-center justify-between gap-3 rounded-lg border border-[var(--line)] px-3 py-2 text-left text-sm lg:hidden"
                x-on:click="$dispatch('open-modal', 'catalog-filters')" aria-haspopup="dialog">
                <span class="min-w-0">
                    <span class="block text-xs">Jadwal sewa</span>
                    <span class="block truncate font-semibold text-[var(--ink)]">{{ $timeFiltered ? $start->translatedFormat('d M, H:i').' · '.$filter['days'].' hari' : 'Atur jadwal' }}</span>
                </span>
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>

            <x-modal name="catalog-filters" inline-desktop="contents" aria-label="Jadwal dan filter" :show="$errors->hasAny(['start_at', 'days'])">
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-[var(--line)] bg-[var(--surface)] px-5 py-2 lg:hidden">
                    <h2 class="text-lg">Jadwal &amp; filter</h2>
                    <button type="button" class="min-h-11 min-w-11 text-2xl" x-on:click="$dispatch('close')" aria-label="Tutup filter">&times;</button>
                </div>

                <div class="cf-fields">
                    <div class="cf-start">
                        <x-form.datetime name="start_at" label="Mulai sewa" :value="$filter['start_at']" :min="$minStart->format('Y-m-d\TH:i')" step="3600" :picker="true" />
                    </div>

                    <div class="cf-days">
                        <x-form.select name="days" label="Durasi" placeholder="Pilih durasi" :options="collect(range(1, $maxDays))->mapWithKeys(fn ($d) => [$d => $d.' hari'])->all()" :value="$filter['days']" />
                    </div>

                    <p x-show="pairError" x-text="pairError" x-cloak role="alert" class="cf-error text-sm text-[var(--danger)]"></p>

                    <div class="cf-types">
                        <x-form.chips name="type" label="Tipe" placeholder="Semua" :options="$types" :value="$filter['type']"
                            x-on:change="if ({{ $desktop }}) $el.form.requestSubmit()" />
                    </div>

                    {{-- Filter lainnya: popover di desktop, langsung terlihat di sheet mobile --}}
                    <div class="cf-more relative"
                        x-data="{ more: false, desktop: {{ $desktop }} }"
                        x-init="window.matchMedia('(min-width: 1024px)').addEventListener('change', e => desktop = e.matches)"
                        x-on:click.outside="more = false"
                        x-on:keydown.escape="if (more) { more = false; $event.preventDefault(); }">
                        <button type="button" class="btn-secondary-edu hidden w-full items-center justify-center gap-2 lg:inline-flex" x-on:click="more = !more" :aria-expanded="more" aria-controls="filter-lainnya">
                            Filter lainnya
                            @if ($moreCount)
                                <span class="rounded-full bg-[var(--accent)] px-1.5 text-xs text-[var(--on-accent)]">{{ $moreCount }}</span>
                            @endif
                        </button>

                        <div id="filter-lainnya" x-show="more || !desktop" x-cloak
                            class="space-y-4 lg:absolute lg:right-0 lg:top-full lg:z-30 lg:mt-2 lg:w-80 lg:rounded-lg lg:border lg:border-[var(--line)] lg:bg-[var(--surface)] lg:p-4 lg:shadow-lg">
                            <fieldset>
                                <legend class="text-sm font-medium text-[var(--ink)]">Harga per hari</legend>
                                <div class="grid grid-cols-2 gap-3">
                                    <x-form.input name="harga_min" type="number" label="Minimal" placeholder="Rp0" :value="$filter['harga_min']" min="0" step="1000" />
                                    <x-form.input name="harga_max" type="number" label="Maksimal" placeholder="Semua" :value="$filter['harga_max']" min="0" step="1000" />
                                </div>
                            </fieldset>

                            @if ($timeFiltered)
                                <p class="text-xs">Hasil sudah disaring sesuai jadwal Anda.</p>
                            @else
                                <label class="flex min-h-11 items-center gap-3 text-sm text-[var(--ink)]">
                                    <input type="checkbox" name="available" value="1" class="h-5 w-5 rounded" @checked($filter['available'])>
                                    Hanya yang tersedia sekarang
                                </label>
                            @endif

                            <button type="submit" class="btn-primary-edu hidden w-full justify-center lg:inline-flex">Terapkan</button>
                        </div>
                    </div>
                </div>

                <div class="sticky bottom-0 flex gap-2 border-t border-[var(--line)] bg-[var(--surface)] px-5 pt-4 lg:hidden" style="padding-bottom: max(1rem, env(safe-area-inset-bottom));">
                    <a href="{{ route('kendaraan.reset') }}" class="btn-secondary-edu inline-flex flex-1 items-center justify-center">Reset</a>
                    <button type="submit" class="btn-primary-edu inline-flex flex-1 items-center justify-center">Terapkan</button>
                </div>
            </x-modal>

            <button type="submit" class="cf-submit btn-primary-edu hidden min-h-11 items-center justify-center lg:inline-flex">Cari motor</button>

            <div class="cf-q">
                <x-form.input name="q" type="search" label="Cari nama motor" placeholder="Nama motor" :value="$filter['q']" maxlength="100" />
            </div>

            <div class="cf-mobile-actions lg:hidden">
                <button type="button" class="btn-secondary-edu inline-flex flex-1 items-center justify-center" x-on:click="$dispatch('open-modal', 'catalog-filters')" aria-haspopup="dialog">
                    Filter{{ $activeCount ? ' ('.$activeCount.')' : '' }}
                </button>
                <button type="submit" class="btn-primary-edu inline-flex flex-1 items-center justify-center">Cari</button>
            </div>
        </form>

        @if ($chips)
            <div class="flex flex-wrap items-center gap-2" aria-label="Filter aktif">
                @foreach ($chips as [$label, $keys])
                    <a href="{{ $without($keys) }}" class="inline-flex min-h-11 items-center gap-2 rounded-full border border-[var(--line)] bg-[var(--surface)] px-4 text-sm font-medium text-[var(--ink)] transition hover:border-[var(--accent)]" aria-label="Hapus filter {{ $label }}">
                        {{ $label }} <span aria-hidden="true">&times;</span>
                    </a>
                @endforeach
                <a href="{{ route('kendaraan.reset') }}" class="inline-flex min-h-11 items-center px-2 text-sm font-semibold text-[var(--ink)] underline-offset-4 hover:underline">Reset filter</a>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm" role="status">
                <strong class="text-[var(--ink)]">{{ $vehicles->total() }} motor</strong>
                @if ($timeFiltered)
                    tersedia mulai <strong class="text-[var(--ink)]">{{ $start->translatedFormat('d M Y, H:i') }}</strong> selama <strong class="text-[var(--ink)]">{{ $filter['days'] }} hari</strong>
                @else
                    · tarif per hari
                @endif
            </p>
            <label class="flex items-center gap-2 text-sm">
                <span>Urutkan</span>
                <select name="harga" form="catalog-form" class="min-h-11 rounded-lg py-2 text-sm" x-on:change="$el.form.requestSubmit()">
                    @foreach (['' => 'Nama A–Z', 'termurah' => 'Harga termurah', 'termahal' => 'Harga termahal', 'populer' => 'Paling populer'] as $value => $label)
                        <option value="{{ $value }}" @selected((string) $filter['harga'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        {{-- Skeleton tampil saat filter dikirim (halaman sedang memuat ulang) --}}
        <x-vehicle-grid-skeleton x-show="loading" x-cloak style="display: none" />

        <div class="space-y-6" x-show="!loading">
        @if ($vehicles->isEmpty())
            @php($changeSchedule = $desktop." ? document.getElementById('start_at')?._flatpickr?.open() : \$dispatch('open-modal', 'catalog-filters')")
            @if ($filter['q'])
                <x-empty-state :title="'Tidak ada motor cocok untuk “'.$filter['q'].'”'" :description="'Di '.$branchLabel.'. Coba kata kunci lain.'" action-label="Hapus kata kunci" :action-href="$without(['q'])">
                    <div class="mt-3 flex flex-wrap justify-center gap-x-5">
                        @if ($timeFiltered)
                            <button type="button" class="inline-flex min-h-11 items-center text-sm font-semibold underline" x-on:click="{{ $changeSchedule }}">Ubah jadwal</button>
                        @endif
                        <a href="{{ route('kendaraan.reset') }}" class="inline-flex min-h-11 items-center text-sm underline">Reset semua filter</a>
                    </div>
                </x-empty-state>
            @elseif ($timeFiltered)
                <x-empty-state title="Tidak ada motor tersedia di waktu tersebut" :description="'Semua unit di '.$branchLabel.' terpakai pada jadwal itu. Coba ganti tanggal, jam, atau durasi.'">
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
                        <button type="button" class="btn-primary-edu inline-flex items-center" x-on:click="{{ $changeSchedule }}">Ubah jadwal</button>
                        <a href="{{ route('kendaraan.reset') }}" class="inline-flex min-h-11 items-center text-sm underline">Reset semua filter</a>
                    </div>
                </x-empty-state>
            @elseif ($activeCount)
                <x-empty-state title="Motor tidak ditemukan" :description="'Tidak ada motor yang cocok dengan filter Anda di '.$branchLabel.'.'" action-label="Reset filter" :action-href="route('kendaraan.reset')" />
            @else
                <x-empty-state :title="'Belum ada motor di '.$branchLabel" description="Pilih cabang lain lewat pilihan “Ambil di” di atas." />
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

    <p class="mx-auto mt-8 max-w-[1320px] px-4 text-sm sm:px-6 xl:px-0"><a href="{{ route('terms') }}" class="inline-flex min-h-11 items-center underline">Syarat &amp; ketentuan sewa</a></p>
</x-app-layout>
