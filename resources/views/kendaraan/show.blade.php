<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 flex flex-col md:flex-row gap-6">
                    <div class="md:w-1/2">
                        <!-- Foto Utama Placeholder -->
                        <div class="w-full h-64 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                            <span class="text-gray-500 dark:text-gray-400 text-lg">Foto Kendaraan</span>
                        </div>
                    </div>
                    <div class="md:w-1/2 flex flex-col justify-between">
                        <div>
                            <h1 class="text-2xl font-bold mb-2">{{ $vehicle->name }}</h1>
                            <div class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ $vehicle->type->name ?? 'Tipe Motor' }}</span>
                                <span class="ml-2 font-mono">{{ $vehicle->plate_number ?? 'B 1234 ABC' }}</span>
                            </div>
                            <p class="mb-6">{{ $vehicle->description ?? 'Deskripsi kendaraan tidak tersedia.' }}</p>
                            <div class="text-xl font-semibold mb-6">
                                Rp {{ number_format($vehicle->price_per_day, 0, ',', '.') }} <span class="text-sm font-normal text-gray-500">/ hari</span>
                            </div>
                        </div>
                        <div>
                            <x-booking-duration-modal :vehicle="$vehicle" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>