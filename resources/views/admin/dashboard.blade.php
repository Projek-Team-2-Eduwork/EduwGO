<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard Admin') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Widget: Stats (Jumlah Kendaraan) -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">Total Unit Aktif</div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">{{ $stats['total_vehicles'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">Tersedia</div>
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">{{ $stats['available_vehicles'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">Booking Aktif</div>
                        <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mt-2">{{ $stats['active_bookings'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">Sedang Disewa</div>
                        <div class="text-3xl font-bold text-orange-500 dark:text-orange-400 mt-2">{{ $stats['rented_bookings'] }}</div>
                    </div>
                </div>

                <!-- Widget: Top 5 Orderan Rental -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-4">5 Top Orderan Rental</h3>
                    <div class="flex items-center justify-center relative w-full h-64">
                        @if($topVehicles->isEmpty())
                            <div class="text-gray-500">Belum ada data rental.</div>
                        @else
                            <canvas id="topVehiclesChart"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Total Sewa</span>
                                <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $topVehicles->sum('booking_count') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Widget: Pendapatan -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-4">Pendapatan</h3>
                    
                    <form method="GET" action="{{ route('admin.dashboard') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
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
                            <div class="flex gap-2 items-start mt-1">
                                <select id="tipe" name="tipe" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                    <option value="">Semua Tipe</option>
                                    @foreach($vehicleTypes as $id => $name)
                                        <option value="{{ $id }}" {{ $tipe == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                <x-primary-button type="submit" class="px-3 py-2 text-xs">Filter</x-primary-button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700 text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Pendapatan</div>
                        <div class="text-3xl sm:text-4xl font-bold text-indigo-600 dark:text-indigo-400 mt-2">
                            Rp {{ number_format($revenue, 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <!-- Widget: Pesanan Terakhir -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="font-bold text-gray-900 dark:text-gray-100">Pesanan Terakhir</h3>
                        <a href="{{ route('admin.pesanan.index') }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Lihat semua</a>
                    </div>
                    <div class="overflow-y-auto flex-1 max-h-[400px]">
                        @if($recentBookings->isEmpty())
                            <div class="p-6 text-center text-gray-500">Belum ada pesanan masuk.</div>
                        @else
                            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($recentBookings as $b)
                                    <li class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                        <div class="flex items-center gap-4">
                                            @if($b->vehicle_image)
                                                <img src="{{ asset($b->vehicle_image) }}" class="w-12 h-12 rounded object-cover flex-shrink-0">
                                            @else
                                                <div class="w-12 h-12 rounded bg-gray-200 dark:bg-gray-600 flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                                </div>
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate">
                                                    {{ $b->customer_name }} <span class="text-gray-500 font-normal">({{ $b->vehicle_name }})</span>
                                                </p>
                                                <p class="text-xs text-gray-500 truncate mt-0.5">
                                                    {{ $b->vehicle_type_name }} &bull; {{ \Carbon\Carbon::parse($b->start_at)->translatedFormat('d M Y H:i') }}
                                                </p>
                                            </div>
                                            <div class="text-right flex-shrink-0 flex flex-col items-end gap-1">
                                                <span class="text-sm font-bold text-gray-900 dark:text-gray-100">Rp {{ number_format($b->total_amount, 0, ',', '.') }}</span>
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
                const root = getComputedStyle(document.documentElement);
                const colors = [
                    root.getPropertyValue('--navy-900').trim() || '#1e3a8a',
                    root.getPropertyValue('--navy-700').trim() || '#1d4ed8',
                    root.getPropertyValue('--orange-500').trim() || '#f97316',
                    root.getPropertyValue('--coral-300').trim() || '#fca5a5',
                    root.getPropertyValue('--gold-tan').trim() || '#fbbf24'
                ];

                const ctx = document.getElementById('topVehiclesChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: chartData.map(v => v.name),
                        datasets: [{
                            data: chartData.map(v => v.booking_count),
                            backgroundColor: colors.slice(0, chartData.length),
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    color: root.getPropertyValue('--tw-text-opacity') ? undefined : '#6b7280', 
                                    usePointStyle: true,
                                    padding: 20
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
            }
        });
    </script>
    @endpush
</x-admin-layout>