<x-admin-layout title="Dashboard">
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">
            {{ __('Dashboard Admin') }}
        </h2>
    </x-slot>

    @php
        $scope = array_filter(['branch' => request('branch')]);
        $statCards = [
            ['label' => 'Total Unit Aktif', 'value' => $stats['total_vehicles'], 'tone' => 'ink', 'href' => route('admin.kendaraan.index', $scope)],
            ['label' => 'Tersedia', 'value' => $stats['available_vehicles'], 'tone' => 'success', 'href' => route('admin.kendaraan.index', $scope + ['status' => 'tersedia'])],
            ['label' => 'Booking Aktif', 'value' => $stats['active_bookings'], 'tone' => 'accent', 'href' => route('admin.pesanan.index', $scope + ['status' => 'aktif'])],
            ['label' => 'Sedang Disewa', 'value' => $stats['rented_bookings'], 'tone' => 'accent', 'href' => route('admin.pesanan.index', $scope + ['status' => 'rented'])],
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-5 py-2 sm:space-y-8">
        @if(session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
        @if(session('error')) <x-alert type="error">{{ session('error') }}</x-alert> @endif

        @if($branches->isNotEmpty())
            {{-- Cakupan cabang superadmin berlaku untuk seluruh dashboard --}}
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-end sm:gap-3">
                @foreach(['dari' => $dari, 'sampai' => $sampai, 'tipe' => $tipe, 'metode' => $metode] as $key => $val)
                    @if($val) <input type="hidden" name="{{ $key }}" value="{{ $val }}"> @endif
                @endforeach
                <x-form.select name="branch" label="Cabang" :options="$branches" :value="request('branch')" placeholder="Semua cabang" onchange="this.form.submit()" class="sm:w-64" />
                <noscript><x-primary-button>Terapkan</x-primary-button></noscript>
            </form>
        @endif

        {{-- Kartu statistik: klik ke daftar terfilter --}}
        <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
            @foreach($statCards as $card)
                <a href="{{ $card['href'] }}" class="dash-stat card-surface flex min-w-0 flex-col justify-between p-4 transition hover:border-[var(--accent)] sm:p-5">
                    <span class="text-sm font-medium leading-snug">{{ $card['label'] }}</span>
                    <span class="mt-2 text-2xl font-bold leading-none text-[var(--{{ $card['tone'] }})] sm:text-3xl">{{ $card['value'] }}</span>
                </a>
            @endforeach
        </div>

        <div class="grid gap-5 sm:gap-6 lg:grid-cols-3">
            {{-- Jadwal hari ini --}}
            <section class="card-surface lg:col-span-2" aria-labelledby="jadwal-hari-ini">
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b border-[var(--line)] px-4 py-4 sm:px-5">
                    <h3 id="jadwal-hari-ini" class="font-bold">Jadwal hari ini</h3>
                    <span class="shrink-0 text-[11px] sm:text-xs">{{ now()->translatedFormat('l, d M Y') }}</span>
                </div>
                @if($schedule->isEmpty())
                    <p class="px-4 py-8 sm:px-5 text-center text-sm">Tidak ada unit diambil atau dikembalikan hari ini.</p>
                @else
                    <ul class="divide-y divide-[var(--line)]">
                        @foreach($schedule as $item)
                            @php($b = $item['booking'])
                            <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 sm:px-5">
                                <div class="w-14 shrink-0">
                                    <div class="font-mono text-base font-bold text-[var(--ink)]">{{ $item['at']->format('H:i') }}</div>
                                    <div class="text-xs font-semibold {{ $item['type'] === 'ambil' ? 'text-[var(--success)]' : 'text-[var(--warning)]' }}">{{ $item['type'] === 'ambil' ? 'Ambil' : 'Kembali' }}</div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.pesanan.show', $b->code) }}" class="font-semibold text-[var(--ink)] hover:underline">{{ $b->vehicle?->name ?? 'Kendaraan dihapus' }}</a>
                                    <div class="truncate text-xs"><span class="font-mono">{{ $b->code }}</span> · {{ $b->customer_name }}</div>
                                </div>
                                <div class="flex basis-full items-center justify-end gap-2 sm:basis-auto">
                                    @if($wa = $b->whatsappUrl())
                                        <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn-wa-edu inline-flex items-center px-3 text-xs">WA</a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.pesanan.update', $b->code) }}" onsubmit="return confirm('Ubah status {{ $b->code }}?')">
                                        @csrf
                                        <input type="hidden" name="to" value="{{ $item['type'] === 'ambil' ? 'rented' : 'returned' }}">
                                        <button class="btn-primary-edu inline-flex items-center px-3 text-xs">{{ $item['type'] === 'ambil' ? 'Serahkan unit' : 'Tandai kembali' }}</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Perlu tindakan: refund manual --}}
            <section class="card-surface" aria-labelledby="perlu-refund">
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b border-[var(--line)] px-4 py-4 sm:px-5">
                    <h3 id="perlu-refund" class="font-bold">Perlu refund</h3>
                    <a href="{{ route('admin.refund.index') }}" class="text-sm font-medium text-[var(--accent)] hover:underline">Lihat semua</a>
                </div>
                @if($refunds->isEmpty())
                    <p class="px-4 py-8 sm:px-5 text-center text-sm">Tidak ada refund tertunda.</p>
                @else
                    <ul class="divide-y divide-[var(--line)]">
                        @foreach($refunds->take(4) as $p)
                            <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.pesanan.show', $p->booking->code) }}" class="font-mono text-sm font-semibold text-[var(--ink)] hover:underline">{{ $p->booking->code }}</a>
                                    <div class="truncate text-xs">{{ $p->booking->customer_name }}</div>
                                </div>
                                <span class="shrink-0 text-sm font-bold text-[var(--warning)]">{{ rupiah($p->amount) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        {{-- Unit terlambat: daftar actionable --}}
        @if($overdue->isNotEmpty())
            <section class="card-surface border-[var(--danger)]" aria-labelledby="unit-terlambat">
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b border-[var(--line)] px-4 py-4 sm:px-5">
                    <h3 id="unit-terlambat" class="font-bold text-[var(--danger)]">{{ $stats['overdue_vehicles'] }} unit terlambat</h3>
                    <a href="{{ route('admin.pesanan.index', $scope + ['status' => 'terlambat']) }}" class="text-sm font-medium text-[var(--accent)] hover:underline">Lihat semua</a>
                </div>
                <ul class="divide-y divide-[var(--line)]">
                    @foreach($overdue as $b)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 sm:px-5">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.pesanan.show', $b->code) }}" class="font-semibold text-[var(--ink)] hover:underline">{{ $b->vehicle?->name ?? 'Kendaraan dihapus' }}</a>
                                <span class="ms-1 rounded bg-[var(--bg)] px-1.5 py-0.5 font-mono text-xs">{{ $b->vehicle?->plate_number ?? '-' }}</span>
                                <div class="truncate text-xs">{{ $b->customer_name }} · <span class="font-mono">{{ $b->code }}</span></div>
                            </div>
                            <span class="shrink-0 text-sm font-semibold text-[var(--danger)]">Terlambat {{ $b->end_at->diffForHumans(null, true) }}</span>
                            <div class="flex basis-full items-center justify-end gap-2 sm:basis-auto">
                                @if($wa = $b->whatsappUrl())
                                    <a href="{{ $wa }}?text={{ rawurlencode('Halo '.$b->customer_name.', masa sewa '.$b->code.' sudah lewat. Mohon segera kembalikan unit.') }}" target="_blank" rel="noopener" class="btn-wa-edu inline-flex items-center px-3 text-xs">Hubungi</a>
                                @endif
                                <form method="POST" action="{{ route('admin.pesanan.update', $b->code) }}" onsubmit="return confirm('Ubah status {{ $b->code }}?')">
                                    @csrf
                                    <input type="hidden" name="to" value="returned">
                                    <button class="btn-primary-edu inline-flex items-center px-3 text-xs">Tandai kembali</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="grid gap-5 sm:gap-6 lg:grid-cols-3">
            {{-- Pendapatan --}}
            <section class="card-surface p-4 sm:p-5 lg:col-span-2" aria-labelledby="pendapatan">
                <div class="mb-4 flex flex-wrap items-start justify-between gap-3 sm:gap-4">
                    <div class="min-w-0">
                        <h3 id="pendapatan" class="font-bold">Pendapatan</h3>
                        <div class="mt-1 text-sm">Total Pendapatan</div>
                        <div class="break-words text-2xl font-bold leading-tight text-[var(--accent)] sm:text-3xl">{{ rupiah($revenue) }}</div>
                    </div>
                    <a href="{{ route('admin.dashboard.export', ['dari' => $dari, 'sampai' => $sampai, 'tipe' => $tipe, 'metode' => $metode, 'branch' => request('branch')]) }}" class="btn-secondary-edu inline-flex w-full items-center justify-center px-4 text-sm sm:w-auto">
                        Export Excel
                    </a>
                </div>

                <x-responsive-filters name="admin-filters"><form method="GET" action="{{ route('admin.dashboard') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @if(request('branch')) <input type="hidden" name="branch" value="{{ request('branch') }}"> @endif
                    <x-form.datetime name="dari" label="Tanggal awal" :value="$dari" :time="false" :picker="true" />
                    <x-form.datetime name="sampai" label="Tanggal akhir" :value="$sampai" :time="false" :picker="true" />
                    <x-form.select name="tipe" label="Tipe kendaraan" :options="$vehicleTypes" :value="$tipe" placeholder="Semua tipe" class="text-sm" />
                    <x-form.select name="metode" label="Metode bayar" :options="['cash' => 'Tunai', 'xendit_invoice' => 'Online (Xendit)']" :value="$metode" placeholder="Semua metode" class="text-sm" />
                    <div class="flex justify-end sm:col-span-2 xl:col-span-4"><x-primary-button class="h-11 px-6">Terapkan</x-primary-button></div>
                </form></x-responsive-filters>

                @if($revenueByBranch->isNotEmpty())
                    @php($maxBranch = max($revenueByBranch->max('total'), 1))
                    <div class="mt-6 border-t border-[var(--line)] pt-4">
                        <h4 class="mb-3 text-sm font-bold">Per cabang</h4>
                        <ul class="space-y-3">
                            @foreach($revenueByBranch as $row)
                                <li>
                                    <div class="flex justify-between gap-3 text-sm">
                                        <a href="{{ route('admin.dashboard', ['branch' => $row['id'], 'dari' => $dari, 'sampai' => $sampai, 'tipe' => $tipe, 'metode' => $metode]) }}" class="font-medium text-[var(--ink)] hover:underline">{{ $row['name'] }}</a>
                                        <span class="font-semibold text-[var(--ink)]">{{ rupiah($row['total']) }}</span>
                                    </div>
                                    <div class="mt-1 h-2 rounded-full bg-[var(--bg)]" aria-hidden="true">
                                        <div class="h-2 rounded-full bg-[var(--accent)]" style="width: {{ round($row['total'] / $maxBranch * 100, 1) }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>

            {{-- Motor terlaris --}}
            <section class="card-surface p-4 sm:p-5" aria-labelledby="terlaris">
                <h3 id="terlaris" class="mb-4 font-bold">Motor terlaris</h3>
                <div class="relative flex h-60 w-full items-center justify-center sm:h-64">
                    @if($topVehicles->isEmpty())
                        <div class="text-sm">Belum ada data rental.</div>
                    @else
                        <canvas id="topVehiclesChart"></canvas>
                    @endif
                </div>
            </section>
        </div>

        {{-- Pesanan terakhir: 5 teratas, tanpa scroll internal --}}
        <section class="card-surface" aria-labelledby="pesanan-terakhir">
            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b border-[var(--line)] px-4 py-4 sm:px-5">
                <h3 id="pesanan-terakhir" class="font-bold">Pesanan Terakhir</h3>
                <a href="{{ route('admin.pesanan.index', $scope) }}" class="text-sm font-medium text-[var(--accent)] underline-offset-4 hover:underline">Lihat semua</a>
            </div>
            @if($recentBookings->isEmpty())
                <div class="p-6 text-center text-sm">Belum ada pesanan masuk.</div>
            @else
                <ul class="divide-y divide-[var(--line)]">
                    @foreach($recentBookings as $b)
                        <li>
                            <a href="{{ route('admin.pesanan.show', $b->code) }}" class="flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3 transition hover:bg-[var(--bg)] sm:gap-4 sm:px-5">
                                @if($b->vehicle_image)
                                    <x-picture :src="$b->vehicle_image" class="h-10 w-10 shrink-0 rounded-lg object-cover" alt="" />
                                @else
                                    <span class="h-10 w-10 shrink-0 rounded-lg bg-[var(--bg)]" aria-hidden="true"></span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-[var(--ink)]">{{ $b->customer_name }} <span class="font-normal">({{ $b->vehicle_name }})</span></p>
                                    <p class="mt-0.5 truncate text-xs"><span class="font-mono">{{ $b->code }}</span> · {{ \Carbon\Carbon::parse($b->start_at)->translatedFormat('d M Y H:i') }}</p>
                                </div>
                                <div class="flex w-full items-center justify-between gap-3 border-t border-[var(--line)] pt-2 sm:w-auto sm:shrink-0 sm:flex-col sm:items-end sm:border-0 sm:pt-0">
                                    <span class="text-sm font-bold text-[var(--ink)]">{{ rupiah($b->total_amount) }}</span>
                                    <x-badge-status :status="$b->status" />
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartData = @json($topVehicles);

            if(chartData.length > 0) {
                const root = document.documentElement;
                const token = (name) => getComputedStyle(root).getPropertyValue(name).trim();
                const palette = () => chartData.map(() => token('--accent'));
                const ctx = document.getElementById('topVehiclesChart').getContext('2d');
                const mobile = window.matchMedia('(max-width: 639px)');

                const chart = new Chart(ctx, {
                    type: 'bar',

                    data: {
                        labels: chartData.map(v => v.name),
                        datasets: [{
                            data: chartData.map(v => v.booking_count),
                            backgroundColor: palette().slice(0, chartData.length),
                            borderColor: token('--surface'),
                            borderWidth: 2,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        scales: { x: { beginAtZero: true, ticks: { precision: 0, color: token('--ink-muted') }, grid: { color: token('--line') } }, y: { ticks: { color: token('--ink-muted') }, grid: { display: false } } },
                        plugins: {
                            legend: {
                                display: false,
                                labels: {
                                    color: token('--ink-muted'),
                                    usePointStyle: true,
                                    padding: 16
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return ' ' + context.label + ': ' + context.raw + ' sewa';
                                    }
                                }
                            }
                        }
                    }
                });

                // Warna ikut token saat tema diganti (kelas "dark" pada <html>) atau lebar layar berubah.
                const sync = () => {
                    chart.data.datasets[0].backgroundColor = palette().slice(0, chartData.length);
                    chart.data.datasets[0].borderColor = token('--surface');
                    chart.options.plugins.legend.labels.color = token('--ink-muted');
                    chart.options.scales.x.ticks.color = token('--ink-muted');
                    chart.options.scales.y.ticks.color = token('--ink-muted');
                    chart.options.scales.x.grid.color = token('--line');
                    chart.update();
                };
                new MutationObserver(sync).observe(root, { attributes: true, attributeFilter: ['class'] });
                mobile.addEventListener('change', sync);
            }
        });
    </script>
    @endpush
</x-admin-layout>
