<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">
            {{ __('Dashboard Admin') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Widget: Stats (Jumlah Kendaraan) -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="card-surface p-6">
                        <div class="text-sm font-medium">Total Unit Aktif</div>
                        <div class="mt-2 text-3xl font-bold text-[var(--navy-900)]">{{ $stats['total_vehicles'] }}</div>
                    </div>
                    <div class="card-surface p-6">
                        <div class="text-sm font-medium">Tersedia</div>
                        <div class="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['available_vehicles'] }}</div>
                    </div>
                    <div class="card-surface p-6">
                        <div class="text-sm font-medium">Booking Aktif</div>
                        <div class="mt-2 text-3xl font-bold text-[var(--orange-500)]">{{ $stats['active_bookings'] }}</div>
                    </div>
                    <div class="card-surface p-6">
                        <div class="text-sm font-medium">Sedang Disewa</div>
                        <div class="mt-2 text-3xl font-bold text-[var(--orange-500)]">{{ $stats['rented_bookings'] }}</div>
                    </div>
                    @if($stats['overdue_vehicles'] > 0)
                        <a href="{{ route('admin.pesanan.index', ['status' => 'terlambat']) }}" class="btn-danger-edu col-span-2 flex items-center justify-center p-4 text-center text-base">
                            {{ $stats['overdue_vehicles'] }} unit terlambat
                        </a>
                    @endif
                </div>

                <!-- Widget: Top 5 Orderan Rental -->
                <div class="card-surface p-6">
                    <h3 class="mb-4 font-bold">5 Top Orderan Rental</h3>
                    <div class="flex items-center justify-center relative w-full h-64">
                        @if($topVehicles->isEmpty())
                            <div class="text-sm">Belum ada data rental.</div>
                        @else
                            <canvas id="topVehiclesChart"></canvas>
                        @endif
                    </div>
                </div>

                <!-- Widget: Pendapatan -->
                <div class="card-surface col-span-1 p-6 lg:col-span-2">
                    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="font-bold">Pendapatan</h3>
                        <a href="{{ route('admin.dashboard.export', ['dari' => $dari, 'sampai' => $sampai, 'tipe' => $tipe, 'metode' => $metode, 'branch' => request('branch')]) }}" class="btn-primary-edu inline-flex items-center px-5 py-2.5 text-sm">
                            Export Excel
                        </a>
                    </div>
                    
                    <form method="GET" action="{{ route('admin.dashboard') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                        @include('admin.partials.branch-filter')
                        <div>
                            <x-input-label for="dari" value="Tanggal Awal" />
                            <x-text-input id="dari" type="date" name="dari" value="{{ $dari }}" class="w-full mt-1 text-sm" />
                        </div>
                        <div>
                            <x-input-label for="sampai" value="Tanggal Akhir" />
                            <x-text-input id="sampai" type="date" name="sampai" value="{{ $sampai }}" class="w-full mt-1 text-sm" />
                        </div>
                        <div>
                            <x-input-label for="tipe" value="Tipe Kendaraan" />
                            <select id="tipe" name="tipe" class="w-full mt-1 rounded-lg shadow-sm text-sm">
                                <option value="">Semua Tipe</option>
                                @foreach($vehicleTypes as $id => $name)
                                    <option value="{{ $id }}" {{ $tipe == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="metode" value="Metode Bayar" />
                            <div class="flex gap-2 items-start mt-1">
                                <select id="metode" name="metode" class="w-full rounded-lg shadow-sm text-sm">
                                    <option value="">Semua Metode</option>
                                    <option value="cash" {{ $metode === 'cash' ? 'selected' : '' }}>Tunai (Cash)</option>
                                    <option value="xendit_invoice" {{ $metode === 'xendit_invoice' ? 'selected' : '' }}>Xendit</option>
                                </select>
                                <x-primary-button type="submit" class="px-3 py-2 text-xs h-[38px]">Filter</x-primary-button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="mb-6 rounded-lg border border-[var(--border)] bg-[var(--bg)] p-4 text-center">
                        <div class="text-sm">Total Pendapatan</div>
                        <div class="mt-2 text-3xl font-bold text-[var(--orange-500)] sm:text-4xl">
                            Rp {{ number_format($revenue, 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <!-- Widget: Pesanan Terakhir -->
                <div class="card-surface col-span-1 flex flex-col overflow-hidden lg:col-span-2">
                    <div class="flex items-center justify-between border-b border-[var(--border)] px-6 py-4">
                        <h3 class="font-bold">Pesanan Terakhir</h3>
                        <a href="{{ route('admin.pesanan.index') }}" class="text-sm font-medium text-[var(--orange-500)] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Lihat semua</a>
                    </div>
                    <div class="overflow-y-auto flex-1 max-h-[400px]">
                        @if($recentBookings->isEmpty())
                            <div class="p-6 text-center text-sm">Belum ada pesanan masuk.</div>
                        @else
                            <ul class="divide-y divide-[var(--border)]">
                                @foreach($recentBookings as $b)
                                    <li class="p-4 transition hover:bg-[var(--bg)]">
                                        <div class="flex items-center gap-4">
                                            @if($b->vehicle_image)
                                                <img src="{{ asset($b->vehicle_image) }}" class="w-12 h-12 rounded-lg object-cover flex-shrink-0" alt="">
                                            @else
                                                <div class="w-12 h-12 rounded-lg bg-[var(--bg)] flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-6 h-6 text-[var(--border)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                                </div>
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                <p class="truncate text-sm font-bold text-[var(--navy-900)]">
                                                    {{ $b->customer_name }} <span class="font-normal">({{ $b->vehicle_name }})</span>
                                                </p>
                                                <p class="truncate mt-0.5 text-xs">
                                                    {{ $b->vehicle_type_name }} &bull; {{ \Carbon\Carbon::parse($b->start_at)->translatedFormat('d M Y H:i') }}
                                                </p>
                                            </div>
                                            <div class="text-right flex-shrink-0 flex flex-col items-end gap-1">
                                                <span class="text-sm font-bold text-[var(--navy-900)]">Rp {{ number_format($b->total_amount, 0, ',', '.') }}</span>
                                                <x-badge-status :status="$b->status" />
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartData = @json($topVehicles);
            
            if(chartData.length > 0) {
                const root = document.documentElement;
                const token = (name) => getComputedStyle(root).getPropertyValue(name).trim();
                const palette = () => ['--navy-900', '--navy-700', '--orange-500', '--coral-300', '--gold-tan'].map(token);
                const ctx = document.getElementById('topVehiclesChart').getContext('2d');
                const mobile = window.matchMedia('(max-width: 639px)');

                // Teks tengah digambar di canvas supaya selalu pas di tengah donat (legend bisa di kanan/bawah).
                const centerText = {
                    id: 'centerText',
                    afterDraw(c) {
                        const { left, right, top, bottom } = c.chartArea;
                        const x = (left + right) / 2;
                        const y = (top + bottom) / 2;
                        c.ctx.save();
                        c.ctx.textAlign = 'center';
                        c.ctx.textBaseline = 'middle';
                        c.ctx.fillStyle = token('--ink-muted');
                        c.ctx.font = '12px "Plus Jakarta Sans", sans-serif';
                        c.ctx.fillText('Total Sewa', x, y - 14);
                        c.ctx.fillStyle = token('--navy-900');
                        c.ctx.font = '700 24px "Plus Jakarta Sans", sans-serif';
                        c.ctx.fillText(@json($topVehicles->sum('booking_count')), x, y + 10);
                        c.ctx.restore();
                    }
                };

                const chart = new Chart(ctx, {
                    type: 'doughnut',
                    plugins: [centerText],
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
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: mobile.matches ? 'bottom' : 'right',
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
                    chart.options.plugins.legend.position = mobile.matches ? 'bottom' : 'right';
                    chart.update();
                };
                new MutationObserver(sync).observe(root, { attributes: true, attributeFilter: ['class'] });
                mobile.addEventListener('change', sync);
            }
        });
    </script>
    @endpush
</x-admin-layout>