<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Kelola Kendaraan') }}
            </h2>
            <a href="{{ route('admin.kendaraan.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                + Tambah Kendaraan
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-lg shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                <form method="GET" action="{{ route('admin.kendaraan.index') }}" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <x-text-input name="q" value="{{ request('q') }}" placeholder="Cari nama atau plat..." class="w-full" />
                    </div>
                    <div>
                        <select name="tipe" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full">
                            <option value="">Semua Tipe</option>
                            @foreach($types as $id => $name)
                                <option value="{{ $id }}" {{ request('tipe') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select name="status" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full">
                            <option value="">Semua Status</option>
                            <option value="tersedia" {{ request('status') === 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                            <option value="disewa" {{ request('status') === 'disewa' ? 'selected' : '' }}>Disewa</option>
                            <option value="nonaktif" {{ request('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div>
                        <x-primary-button type="submit">Filter</x-primary-button>
                        @if(request()->hasAny(['q', 'tipe', 'status']))
                            <a href="{{ route('admin.kendaraan.index') }}" class="ml-2 text-sm text-gray-500 hover:underline">Reset</a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Grid Card -->
            @if($vehicles->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-lg p-12 text-center text-gray-500 dark:text-gray-400">
                    Tidak ada kendaraan yang ditemukan.
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach($vehicles as $vehicle)
                        @php
                            if (!$vehicle->is_active) {
                                $badge = ['text' => 'Nonaktif', 'class' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'];
                            } elseif ($vehicle->is_rented) {
                                $badge = ['text' => 'Disewa', 'class' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'];
                            } else {
                                $badge = ['text' => 'Tersedia', 'class' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'];
                            }
                        @endphp

                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col">
                            <div class="relative h-48 bg-gray-100 dark:bg-gray-900">
                                @if($vehicle->image)
                                    <img src="{{ asset($vehicle->image) }}" class="w-full h-full object-cover" alt="{{ $vehicle->name }}">
                                @endif
                                <span class="absolute top-2 right-2 px-2 py-1 text-xs font-bold rounded {{ $badge['class'] }}">
                                    {{ $badge['text'] }}
                                </span>
                            </div>
                            <div class="p-4 flex flex-col flex-grow">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $vehicle->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ $vehicle->type?->name ?? '-' }}</p>
                                
                                <div class="flex justify-between items-center mt-auto pt-4">
                                    <div class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-sm text-gray-700 dark:text-gray-300">
                                        {{ $vehicle->plate_number }}
                                    </div>
                                    <div class="font-bold text-indigo-600 dark:text-indigo-400">
                                        Rp {{ number_format($vehicle->price_per_day, 0, ',', '.') }}<span class="text-xs text-gray-500 font-normal">/hari</span>
                                    </div>
                                </div>
                                
                                <div class="mt-4 flex gap-2">
                                    <a href="{{ route('admin.kendaraan.show', $vehicle->id) }}" class="flex-1 text-center px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-800 dark:text-gray-200 uppercase hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                        Detail
                                    </a>
                                    <a href="{{ route('admin.kendaraan.edit', $vehicle->id) }}" class="flex-1 text-center px-4 py-2 bg-indigo-50 dark:bg-indigo-900/50 border border-transparent rounded-md font-semibold text-xs text-indigo-700 dark:text-indigo-300 uppercase hover:bg-indigo-100 dark:hover:bg-indigo-900 transition">
                                        Update
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-6">
                    {{ $vehicles->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>