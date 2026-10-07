<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Tambah Kendaraan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('admin.kendaraan.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="name" value="Nama Kendaraan *" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <div>
                            <x-input-label for="brand" value="Brand" />
                            <x-text-input id="brand" name="brand" type="text" class="mt-1 block w-full" :value="old('brand')" />
                            <x-input-error class="mt-2" :messages="$errors->get('brand')" />
                        </div>
                        <div>
                            <x-input-label for="vehicle_type_id" value="Tipe Kendaraan *" />
                            <select id="vehicle_type_id" name="vehicle_type_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Pilih Tipe</option>
                                @foreach($types as $id => $name)
                                    <option value="{{ $id }}" {{ old('vehicle_type_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('vehicle_type_id')" />
                        </div>
                        <div>
                            <x-input-label for="tank_capacity" value="Kapasitas Tangki (Liter)" />
                            <x-text-input id="tank_capacity" name="tank_capacity" type="number" step="0.1" class="mt-1 block w-full" :value="old('tank_capacity')" />
                            <x-input-error class="mt-2" :messages="$errors->get('tank_capacity')" />
                        </div>
                        <div>
                            <x-input-label for="plate_number" value="Plat Nomor *" />
                            <x-text-input id="plate_number" name="plate_number" type="text" class="mt-1 block w-full uppercase" :value="old('plate_number')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('plate_number')" />
                        </div>
                        <div>
                            <x-input-label for="price_per_day" value="Harga Per Hari (Rp) *" />
                            <x-text-input id="price_per_day" name="price_per_day" type="number" class="mt-1 block w-full" :value="old('price_per_day')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('price_per_day')" />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="image" value="Foto Kendaraan *" />
                            <input type="file" id="image" name="image" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" required>
                            <x-input-error class="mt-2" :messages="$errors->get('image')" />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="description" value="Deskripsi" />
                            <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>
                        <div class="md:col-span-2 flex items-center">
                            <input type="hidden" name="is_active" value="0">
                            <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">Status Aktif (Ditampilkan ke pengguna)</label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-4 gap-4">
                        <a href="{{ route('admin.kendaraan.index') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">Batal</a>
                        <x-primary-button>Simpan Kendaraan</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>