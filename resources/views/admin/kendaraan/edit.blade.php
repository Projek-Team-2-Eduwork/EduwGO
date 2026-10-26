<x-admin-layout title="Ubah kendaraan:">
    <x-slot name="header">
        <h2 class="truncate text-xl font-semibold leading-tight">
            Ubah kendaraan: {{ $kendaraan->name }}
        </h2>
    </x-slot>

    <div class="py-2 sm:py-6">
        <div class="max-w-3xl mx-auto">
            <div class="section-plain p-6">
                <form action="{{ route('admin.kendaraan.update', $kendaraan->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="name" value="Nama Kendaraan *" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $kendaraan->name)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <div>
                            <x-input-label for="brand" value="Brand" />
                            <x-text-input id="brand" name="brand" type="text" class="mt-1 block w-full" :value="old('brand', $kendaraan->brand)" />
                            <x-input-error class="mt-2" :messages="$errors->get('brand')" />
                        </div>
                        <div>
                            <x-input-label for="vehicle_type_id" value="Tipe Kendaraan *" />
                            <select id="vehicle_type_id" name="vehicle_type_id" class="mt-1 block w-full rounded-lg shadow-sm" required>
                                <option value="">Pilih Tipe</option>
                                @foreach($types as $id => $name)
                                    <option value="{{ $id }}" {{ old('vehicle_type_id', $kendaraan->vehicle_type_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('vehicle_type_id')" />
                        </div>
                        @include('admin.kendaraan.partials.branch-field', ['current' => $kendaraan->branch_id])
                        <div>
                            <x-input-label for="tank_capacity" value="Kapasitas Tangki (Liter)" />
                            <x-text-input id="tank_capacity" name="tank_capacity" type="number" step="0.1" class="mt-1 block w-full" :value="old('tank_capacity', $kendaraan->tank_capacity)" />
                            <x-input-error class="mt-2" :messages="$errors->get('tank_capacity')" />
                        </div>
                        <div>
                            <x-input-label for="plate_number" value="Plat Nomor *" />
                            <x-text-input id="plate_number" name="plate_number" type="text" class="mt-1 block w-full uppercase" :value="old('plate_number', $kendaraan->plate_number)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('plate_number')" />
                        </div>
                        <div>
                            <x-input-label for="price_per_day" value="Harga Per Hari (Rp) *" />
                            <x-text-input id="price_per_day" name="price_per_day" type="number" class="mt-1 block w-full" :value="old('price_per_day', (int) $kendaraan->price_per_day)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('price_per_day')" />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="image" value="Foto Baru (Kosongkan jika tidak ingin mengubah)" />
                            <x-form.file name="image" label="" accept="image/*" />
                            <x-input-error class="mt-2" :messages="$errors->get('image')" />
                            @if($kendaraan->image)
                                <div class="mt-3">
                                    <p class="mb-1 text-sm">Foto saat ini:</p>
                                    <img src="{{ asset($kendaraan->image) }}" class="h-24 w-auto rounded-lg border border-[var(--line)] object-cover shadow-sm" alt="">
                                </div>
                            @endif
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="description" value="Deskripsi" />
                            <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-lg shadow-sm">{{ old('description', $kendaraan->description) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>
                        <div class="md:col-span-2 flex items-center">
                            <input type="hidden" name="is_active" value="0">
                            <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-[var(--line)] text-[var(--accent)] shadow-sm focus:ring-[var(--accent)]" {{ old('is_active', $kendaraan->is_active) ? 'checked' : '' }}>
                            <label for="is_active" class="ml-2 block text-sm text-[var(--ink)]">Status Aktif</label>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-4">
                        <a href="{{ route('admin.kendaraan.index') }}" class="text-sm underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent)]">Batal</a>
                        <x-primary-button>Ubah kendaraan</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
