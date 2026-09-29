<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Detail Pesanan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 text-green-700 p-4 rounded shadow-sm">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="mb-4 bg-red-100 text-red-700 p-4 rounded shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-bold mb-4 border-b pb-2">Informasi Booking</h3>
                            <ul class="space-y-2">
                                <li><span class="text-gray-500 text-sm">Kode:</span> <br> <span class="font-medium">{{ $booking->code }}</span></li>
                                <li><span class="text-gray-500 text-sm">Status:</span> <br> <span class="font-medium capitalize">{{ $booking->status->value }}</span></li>
                                <li><span class="text-gray-500 text-sm">Customer:</span> <br> <span class="font-medium">{{ $booking->customer_name }} ({{ $booking->customer_phone }})</span></li>
                                <li><span class="text-gray-500 text-sm">Kendaraan:</span> <br> <span class="font-medium">{{ $booking->vehicle->name ?? $booking->vehicle_id }}</span></li>
                                <li><span class="text-gray-500 text-sm">Rentang Sewa:</span> <br> <span class="font-medium">{{ $booking->start_at->format('d M Y H:i') }} - {{ $booking->end_at->format('d M Y H:i') }}</span></li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-lg font-bold mb-4 border-b pb-2">Informasi Pembayaran</h3>
                            @if($payment)
                                <ul class="space-y-2 mb-6">
                                    <li><span class="text-gray-500 text-sm">Status:</span> <br> <span class="font-medium capitalize">{{ $payment->status }}</span></li>
                                    <li><span class="text-gray-500 text-sm">Gateway Ref:</span> <br> <span class="font-medium">{{ $payment->gateway_reference }}</span></li>
                                    <li><span class="text-gray-500 text-sm">Expires At:</span> <br> <span class="font-medium">{{ $payment->expires_at ? $payment->expires_at->format('d M Y H:i:s') : '-' }}</span></li>
                                </ul>

                                @if($payment->status === 'pending')
                                    <a href="{{ route('admin.pesanan.cek-status', $booking->code) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                                        Cek Status Xendit
                                    </a>
                                @endif
                            @else
                                <p class="text-gray-500 italic">Tidak ada data pembayaran.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>