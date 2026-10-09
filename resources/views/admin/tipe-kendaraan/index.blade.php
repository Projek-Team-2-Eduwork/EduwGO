<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Kelola Tipe Kendaraan</h2>
    </x-slot>

    <div class="py-12" x-data="{ editMode: null }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success')) <div class="p-4 bg-green-100 text-green-800 rounded-lg shadow-sm">{{ session('success') }}</div> @endif
            @if(session('error')) <div class="p-4 bg-red-100 text-red-800 rounded-lg shadow-sm">{{ session('error') }}</div> @endif
            @if($errors->any()) <div class="p-4 bg-red-100 text-red-800 rounded-lg shadow-sm">{{ $errors->first() }}</div> @endif

            <!-- Form Tambah -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                <form action="{{ route('admin.tipe-kendaraan.store') }}" method="POST" class="flex flex-col sm:flex-row gap-4 items-start sm:items-end">
                    @csrf
                    <div class="w-full sm:flex-1">
                        <x-input-label for="name" value="Nama Tipe Baru" />
                        <x-text-input id="name" name="name" type="text" class="w-full mt-1" required placeholder="Contoh: Matic, Sport" />
                    </div>
                    <x-primary-button class="py-3">Tambah Tipe</x-primary-button>
                </form>
            </div>

            <!-- List Tipe -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">Nama Tipe</th>
                                <th class="px-4 py-3">Slug / URL Param</th>
                                <th class="px-4 py-3">Jumlah Kendaraan</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($types as $type)
                                <tr x-show="editMode !== {{ $type->id }}" class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $type->name }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $type->slug }}</td>
                                    <td class="px-4 py-3">{{ $type->vehicles_count }} Unit</td>
                                    <td class="px-4 py-3 text-right flex justify-end gap-3">
                                        <button @click="editMode = {{ $type->id }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 font-medium">Edit</button>
                                        <form action="{{ route('admin.tipe-kendaraan.destroy', $type->id) }}" method="POST" onsubmit="return confirm('Hapus tipe ini?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 font-medium">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                <!-- Mode Edit -->
                                <tr x-show="editMode === {{ $type->id }}" x-cloak class="border-b bg-indigo-50/50 dark:bg-indigo-900/10 dark:border-gray-700">
                                    <td colspan="4" class="px-4 py-3">
                                        <form action="{{ route('admin.tipe-kendaraan.update', $type->id) }}" method="POST" class="flex gap-4 items-center">
                                            @csrf @method('PUT')
                                            <x-text-input name="name" value="{{ $type->name }}" class="w-full sm:w-1/2" required />
                                            <span class="text-xs text-gray-500 font-mono hidden sm:inline">Slug tetap: {{ $type->slug }}</span>
                                            <div class="flex ml-auto gap-2">
                                                <button type="button" @click="editMode = null" class="px-3 py-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded text-sm">Batal</button>
                                                <x-primary-button class="py-1.5 px-3">Simpan</x-primary-button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card Layout -->
                <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($types as $type)
                        <div class="p-4" x-show="editMode !== {{ $type->id }}">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-gray-100">{{ $type->name }}</div>
                                    <div class="text-xs font-mono text-gray-500 mt-1">{{ $type->slug }}</div>
                                </div>
                                <span class="bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 text-xs px-2 py-1 rounded">{{ $type->vehicles_count }} Unit</span>
                            </div>
                            <div class="flex justify-end gap-4 mt-3">
                                <button @click="editMode = {{ $type->id }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Edit</button>
                                <form action="{{ route('admin.tipe-kendaraan.destroy', $type->id) }}" method="POST" onsubmit="return confirm('Hapus tipe ini?');">
                                    @csrf @method('DELETE')
                                    <button class="text-sm font-medium text-red-600 dark:text-red-400">Hapus</button>
                                </form>
                            </div>
                        </div>
                        <div class="p-4 bg-indigo-50/50 dark:bg-indigo-900/10" x-show="editMode === {{ $type->id }}" x-cloak>
                            <form action="{{ route('admin.tipe-kendaraan.update', $type->id) }}" method="POST">
                                @csrf @method('PUT')
                                <x-text-input name="name" value="{{ $type->name }}" class="w-full text-sm" required />
                                <div class="flex justify-end gap-2 mt-3">
                                    <button type="button" @click="editMode = null" class="px-3 py-1.5 text-gray-600 dark:text-gray-400 rounded text-sm">Batal</button>
                                    <x-primary-button class="py-1.5 px-3">Simpan</x-primary-button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>