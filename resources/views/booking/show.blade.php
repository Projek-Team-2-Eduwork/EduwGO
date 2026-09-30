<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-navy-900 py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Detail Pesanan</h2>
                        <x-badge-status :status="$booking->status" />
                    </div>

                    <div class="flex flex-col sm:flex-row gap-6 mb-8">
                        <div class="w-full sm:w-1/3">
                            @if($booking->vehicle?->image)
                                <img src="{{ asset($booking->vehicle->image) }}" class="w-full h-auto rounded-lg object-cover bg-gray-100 dark:bg-gray-900" alt="{{ $booking->vehicle->name }}">
                            @else
                                <div class="w-full aspect-square flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                            @endif
                        </div>
                        <div class="w-full sm:w-2/3 flex flex-col justify-center">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $booking->vehicle?->name ?? 'Kendaraan Dihapus' }}</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-2">{{ $booking->vehicle?->type?->name ?? '-' }}</p>
                            <p class="font-mono text-indigo-600 dark:text-indigo-400 mb-4 bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1 rounded-md inline-block">{{ $booking->code }}</p>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Waktu Mulai</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $booking->start_at->translatedFormat('D, d M Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Durasi</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $booking->duration_days }} Hari</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pembayaran</dt>
                                <dd class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="mt-8 flex justify-start">
                        <a href="{{ route('booking.index') }}" class="inline-flex justify-center items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-800 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 focus:bg-gray-300 dark:focus:bg-gray-600 active:bg-gray-400 dark:active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            &larr; Kembali ke Daftar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>