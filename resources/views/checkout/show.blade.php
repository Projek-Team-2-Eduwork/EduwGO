<x-app-layout>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Konfirmasi Checkout</h2>

                @if(session('error'))
                    <div class="mb-4 bg-red-100 text-red-700 p-3 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">{{ $vehicle->name }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Harga per hari: Rp {{ number_format($vehicle->price_per_day, 0, ',', '.') }}</p>

                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs text-gray-500">Mulai</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $start->translatedFormat('D, d M Y H:i') }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-500">Selesai</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $end->translatedFormat('D, d M Y H:i') }}</span>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-between items-center border-t border-gray-200 dark:border-gray-700 pt-4">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Durasi: {{ $days }} hari</span>
                        <span class="text-lg font-bold text-blue-600 dark:text-blue-400">Total: Rp {{ number_format($vehicle->price_per_day * $days, 0, ',', '.') }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('checkout.store', $vehicle->id) }}">
                    @csrf
                    <input type="hidden" name="start" value="{{ $start->toIso8601String() }}">
                    <input type="hidden" name="days" value="{{ $days }}">

                    <div class="mb-4">
                        <x-input-label for="customer_name" value="Nama Lengkap" />
                        <x-text-input id="customer_name" class="block mt-1 w-full" type="text" name="customer_name" value="{{ old('customer_name', auth()->user()->name) }}" required autofocus />
                        <x-input-error :messages="$errors->get('customer_name')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="customer_phone" value="Nomor WhatsApp" />
                        <x-text-input id="customer_phone" class="block mt-1 w-full" type="text" name="customer_phone" value="{{ old('customer_phone', auth()->user()->phone) }}" required />
                        <x-input-error :messages="$errors->get('customer_phone')" class="mt-2" />
                    </div>

                    <div class="mb-6">
                        <x-input-label for="notes" value="Catatan Tambahan (Opsional)" />
                        <textarea id="notes" name="notes" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full" rows="3">{{ old('notes') }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        <x-input-error :messages="$errors->get('start')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button>
                            Konfirmasi & Bayar
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>