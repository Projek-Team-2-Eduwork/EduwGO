<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center px-4 sm:px-0 mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Riwayat Transaksi</h1>
                <form method="GET" action="{{ route('booking.index') }}" class="flex">
                    <select name="status" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        @foreach(App\Enums\BookingStatus::cases() as $statusOpt)
                            <option value="{{ $statusOpt->value }}" {{ request('status') === $statusOpt->value ? 'selected' : '' }}>
                                {{ $statusOpt->label() }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-4 sm:px-0 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Jumlah Transaksi</div>
                    <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ $totalTransaksi }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Dibayar</div>
                    <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Menunggu Bayar</div>
                    <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ $menungguBayar }}</div>
                </div>
            </div>

            @if(session('success'))
                <div class="px-4 sm:px-0 mb-6">
                    <x-alert type="success" dismissible>
                        {{ session('success') }}
                    </x-alert>
                </div>
            @endif

            @if($bookings->isEmpty())
                <x-empty-state title="Belum ada pesanan" description="Sewa motor favoritmu sekarang." actionLabel="Pilih Kendaraan" actionHref="{{ route('kendaraan.index') }}" />
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 px-4 sm:px-0">
                    @foreach($bookings as $booking)
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col overflow-hidden">
                            <img src="{{ $booking->vehicle->image ? asset($booking->vehicle->image) : '' }}" alt="{{ $booking->vehicle->name }}" class="w-full h-48 object-cover" onerror="this.outerHTML='<div class=\'w-full h-48 bg-gray-200 dark:bg-gray-700 flex items-center justify-center\'><svg class=\'w-12 h-12 text-gray-400\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L28 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'></path></svg></div>'">
                            <div class="p-5 flex flex-col flex-1">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $booking->vehicle->name }}</h3>
                                    <x-badge-status :status="$booking->status" />
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ $booking->vehicle->type->name }}</p>
                                <p class="text-sm font-mono text-gray-600 dark:text-gray-300 mb-4">{{ $booking->code }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Mulai: {{ $booking->start_at->translatedFormat('D, d M H:i') }} &middot; {{ $booking->duration_days }} hari</p>
                                
                                <div class="mb-4">
                                    @if($booking->latestPayment)
                                        @php
                                            $methodLabel = ucwords(str_replace('_', ' ', $booking->latestPayment->method));
                                            $paymentStatus = $booking->latestPayment->status;
                                            $badgeClass = match($paymentStatus) {
                                                'paid' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
                                                'pending' => 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200',
                                                'expired', 'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-200',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                            {{ $methodLabel }} - {{ ucfirst($paymentStatus) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-100">Belum bayar</span>
                                    @endif
                                </div>

                                <div class="mt-auto">
                                    <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400 mb-4">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</p>
                                    
                                    <div class="flex gap-2">
                                        <a href="{{ route('booking.show', $booking->code) }}" class="inline-flex justify-center items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150 flex-1">
                                            Detail
                                        </a>
                                        
                                        @if($booking->status === App\Enums\BookingStatus::Pending)
                                            <x-danger-button x-data @click="$dispatch('open-modal', 'cancel-booking-{{ $booking->code }}')" class="flex-1 justify-center">
                                                Batalkan
                                            </x-danger-button>
                                            
                                            <x-modal name="cancel-booking-{{ $booking->code }}" focusable>
                                                <form method="POST" action="{{ route('booking.cancel', $booking->code) }}" class="p-6">
                                                    @csrf
                                                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Batalkan Pesanan</h2>
                                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Apakah Anda yakin ingin membatalkan pesanan ini?</p>
                                                    <div class="mt-6 flex justify-end">
                                                        <x-secondary-button x-on:click="$dispatch('close')">Tutup</x-secondary-button>
                                                        <x-danger-button class="ml-3">Ya, Batalkan</x-danger-button>
                                                    </div>
                                                </form>
                                            </x-modal>
                                        @elseif(in_array($booking->status, [App\Enums\BookingStatus::Paid, App\Enums\BookingStatus::Rented]))
                                            <a href="https://wa.me/{{ setting('contact.whatsapp', '6281234567890') }}" target="_blank" class="inline-flex justify-center items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-500 active:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 flex-1">
                                                WhatsApp
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-6 px-4 sm:px-0">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>