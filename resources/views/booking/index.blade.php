<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-navy-900 py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 px-4 sm:px-0">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Daftar Pesanan</h1>
                
                <form method="GET" class="mt-4 sm:mt-0">
                    <select name="status" onchange="this.form.submit()" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                        <option value="">Semua Status</option>
                        @foreach(App\Enums\BookingStatus::cases() as $case)
                            <option value="{{ $case->value }}" {{ request('status') === $case->value ? 'selected' : '' }}>
                                {{ $case->label() }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if(session('success'))
                <div class="px-4 sm:px-0 mb-6">
                    <x-alert type="success" dismissible>
                        {{ session('success') }}
                    </x-alert>
                </div>
            @endif

            @if($bookings->isEmpty())
                <div class="px-4 sm:px-0">
                    <x-empty-state 
                        title="Belum ada pesanan" 
                        description="Sewa motor favoritmu sekarang." 
                        actionLabel="Pilih Kendaraan" 
                        actionHref="{{ route('kendaraan.index') }}" 
                    />
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 px-4 sm:px-0 mb-8">
                    @foreach($bookings as $booking)
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
                            @if($booking->vehicle?->image)
                                <img src="{{ asset($booking->vehicle->image) }}" class="w-full h-48 object-cover bg-gray-100 dark:bg-gray-900" alt="{{ $booking->vehicle->name }}" onerror="this.outerHTML='<div class=\'w-full h-48 flex items-center justify-center bg-gray-100 dark:bg-gray-700\'><svg class=\'w-12 h-12 text-gray-400\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10\'></path></svg></div>'">
                            @else
                                <div class="w-full h-48 flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                            @endif
                            
                            <div class="p-5 flex flex-col flex-grow">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $booking->vehicle?->name ?? 'Kendaraan Dihapus' }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $booking->vehicle?->type?->name ?? '-' }}</p>
                                    </div>
                                    <x-badge-status :status="$booking->status" />
                                </div>
                                
                                <p class="text-sm font-mono text-gray-600 dark:text-gray-400 mb-3">{{ $booking->code }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">
                                    Mulai: {{ $booking->start_at->translatedFormat('D, d M H:i') }} &middot; {{ $booking->duration_days }} hari
                                </p>
                                
                                <div class="mt-auto">
                                    <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400 mb-4">
                                        Rp {{ number_format($booking->total_amount, 0, ',', '.') }}
                                    </p>
                                    
                                    <div class="flex flex-col gap-2">
                                        <a href="{{ route('booking.show', $booking->code) }}" class="inline-flex justify-center items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150 w-full">
                                            Detail
                                        </a>

                                        @if($booking->status === App\Enums\BookingStatus::Pending)
                                            <x-danger-button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'batal-{{ $booking->code }}')" class="w-full justify-center">
                                                Batalkan
                                            </x-danger-button>
                                            
                                            <!-- Modal Pembatalan -->
                                            <x-modal name="batal-{{ $booking->code }}" :show="false" maxWidth="sm">
                                                <div class="p-6">
                                                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                                        Batalkan pesanan {{ $booking->code }}?
                                                    </h2>
                                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                        Tindakan pembatalan ini tidak dapat dibatalkan kembali. Pastikan Anda benar-benar ingin membatalkan pesanan ini.
                                                    </p>
                                                    <div class="mt-6 flex justify-end gap-3">
                                                        <x-secondary-button x-on:click="$dispatch('close-modal', 'batal-{{ $booking->code }}')">
                                                            Kembali
                                                        </x-secondary-button>
                                                        <form method="POST" action="{{ route('booking.cancel', $booking->code) }}">
                                                            @csrf
                                                            <x-danger-button type="submit">
                                                                Ya, Batalkan
                                                            </x-danger-button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </x-modal>
                                        @elseif(in_array($booking->status, [App\Enums\BookingStatus::Paid, App\Enums\BookingStatus::Rented]))
                                            <p class="text-xs text-gray-500 dark:text-gray-400 text-center mt-2 mb-2">Ingin membatalkan? Hubungi admin via WhatsApp.</p>
                                            <a href="https://wa.me/{{ setting('contact.whatsapp', '6281234567890') }}?text={{ urlencode('Halo, saya ingin membatalkan pesanan '.$booking->code) }}" target="_blank" class="inline-flex justify-center items-center px-4 py-2 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600 focus:bg-green-600 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 w-full">
                                                Chat Admin
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="px-4 sm:px-0">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>