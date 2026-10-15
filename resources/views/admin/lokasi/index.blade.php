<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">Kelola Lokasi</h2>
    </x-slot>

    <div class="py-12" x-data="{ editMode: null }">
        <div class="max-w-7xl mx-auto space-y-6">
            @if(session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
            @if(session('error')) <x-alert type="error">{{ session('error') }}</x-alert> @endif
            @if($errors->any()) <x-alert type="error">{{ $errors->first() }}</x-alert> @endif

            <!-- Form Tambah -->
            <div class="card-surface p-4">
                <form action="{{ route('admin.lokasi.store') }}" method="POST" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    <div>
                        <x-input-label for="name" value="Nama Lokasi *" />
                        <x-text-input id="name" name="name" type="text" class="w-full mt-1" required placeholder="Contoh: Cabang Malang Kota" />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <x-input-label for="address" value="Alamat" />
                        <x-text-input id="address" name="address" type="text" class="w-full mt-1" placeholder="Jl. ... No. ..., Kota" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Telepon" />
                        <x-text-input id="phone" name="phone" type="text" class="w-full mt-1" placeholder="08xxxxxxxxxx" />
                    </div>
                    <div>
                        <x-input-label for="operating_hours" value="Jam Operasional" />
                        <x-text-input id="operating_hours" name="operating_hours" type="text" class="w-full mt-1" placeholder="Setiap hari, 08.00 – 20.00 WIB" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-primary-button class="py-3">Tambah Lokasi</x-primary-button>
                    </div>
                </form>
            </div>

            <!-- List Lokasi -->
            <div class="card-surface overflow-hidden">
                @if($lokasi->isEmpty())
                    <x-empty-state title="Belum ada lokasi" description="Tambahkan lokasi pertama lewat form di atas." />
                @else
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-[var(--bg)] text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3">Nama Lokasi</th>
                                <th class="px-4 py-3">Slug</th>
                                <th class="px-4 py-3">Alamat</th>
                                <th class="px-4 py-3">Telepon</th>
                                <th class="px-4 py-3">Jam Operasional</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Unit</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lokasi as $tempat)
                                <tr x-show="editMode !== {{ $tempat->id }}" class="border-b border-[var(--border)] transition hover:bg-[var(--bg)]">
                                    <td class="px-4 py-3 font-medium text-[var(--navy-900)]">{{ $tempat->name }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $tempat->slug }}</td>
                                    <td class="px-4 py-3">{{ $tempat->address ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $tempat->phone ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $tempat->operating_hours ?: '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded px-2 py-1 text-xs font-bold {{ $tempat->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $tempat->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">{{ $tempat->vehicles_count }} Unit</td>
                                    <td class="px-4 py-3 text-right flex justify-end gap-3">
                                        <button @click="editMode = {{ $tempat->id }}" class="font-medium text-[var(--orange-500)] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Edit</button>
                                        <form action="{{ route('admin.lokasi.destroy', $tempat->id) }}" method="POST" onsubmit="return confirm('Hapus lokasi ini?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="font-medium text-red-600 dark:text-red-400 underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                <!-- Mode Edit -->
                                <tr x-show="editMode === {{ $tempat->id }}" x-cloak class="border-b border-[var(--border)] bg-[var(--bg)]">
                                    <td colspan="8" class="px-4 py-3">
                                        <form action="{{ route('admin.lokasi.update', $tempat->id) }}" method="POST" class="grid gap-3 sm:grid-cols-5 sm:items-end">
                                            @csrf @method('PUT')
                                            <div>
                                                <x-input-label for="name-{{ $tempat->id }}" value="Nama" />
                                                <x-text-input id="name-{{ $tempat->id }}" name="name" value="{{ $tempat->name }}" class="w-full mt-1" required />
                                            </div>
                                            <div>
                                                <x-input-label for="address-{{ $tempat->id }}" value="Alamat" />
                                                <x-text-input id="address-{{ $tempat->id }}" name="address" value="{{ $tempat->address }}" class="w-full mt-1" />
                                            </div>
                                            <div>
                                                <x-input-label for="phone-{{ $tempat->id }}" value="Telepon" />
                                                <x-text-input id="phone-{{ $tempat->id }}" name="phone" value="{{ $tempat->phone }}" class="w-full mt-1" />
                                            </div>
                                            <div>
                                                <x-input-label for="hours-{{ $tempat->id }}" value="Jam Operasional" />
                                                <x-text-input id="hours-{{ $tempat->id }}" name="operating_hours" value="{{ $tempat->operating_hours }}" class="w-full mt-1" />
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <label class="inline-flex items-center gap-2 text-sm">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <input type="checkbox" name="is_active" value="1" {{ $tempat->is_active ? 'checked' : '' }} class="rounded border-[var(--border)] text-[var(--orange-500)] shadow-sm focus:ring-[var(--orange-500)]">
                                                    Aktif
                                                </label>
                                                <div class="flex ml-auto gap-2">
                                                    <button type="button" @click="editMode = null" class="rounded px-3 py-1.5 text-sm underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Batal</button>
                                                    <x-primary-button class="px-3 py-1.5">Simpan</x-primary-button>
                                                </div>
                                            </div>
                                            <span class="text-xs sm:col-span-5">Slug tetap: <span class="font-mono">{{ $tempat->slug }}</span></span>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card Layout -->
                <div class="divide-y divide-[var(--border)] md:hidden">
                    @foreach($lokasi as $tempat)
                        <div class="p-4" x-show="editMode !== {{ $tempat->id }}">
                            <div class="mb-2 flex items-start justify-between">
                                <div>
                                    <div class="font-bold text-[var(--navy-900)]">{{ $tempat->name }}</div>
                                    <div class="mt-1 font-mono text-xs">{{ $tempat->slug }}</div>
                                    <div class="mt-1 text-sm">{{ $tempat->address ?: '—' }}</div>
                                    <div class="mt-1 text-sm">{{ $tempat->phone ?: '—' }} · {{ $tempat->operating_hours ?: '—' }}</div>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="rounded bg-[var(--bg)] px-2 py-1 text-xs">{{ $tempat->vehicles_count }} Unit</span>
                                    <span class="rounded px-2 py-1 text-xs font-bold {{ $tempat->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $tempat->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3 flex justify-end gap-4">
                                <button @click="editMode = {{ $tempat->id }}" class="text-sm font-medium text-[var(--orange-500)] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Edit</button>
                                <form action="{{ route('admin.lokasi.destroy', $tempat->id) }}" method="POST" onsubmit="return confirm('Hapus lokasi ini?');">
                                    @csrf @method('DELETE')
                                    <button class="text-sm font-medium text-red-600 dark:text-red-400 underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">Hapus</button>
                                </form>
                            </div>
                        </div>
                        <div class="p-4 bg-[var(--bg)]" x-show="editMode === {{ $tempat->id }}" x-cloak>
                            <form action="{{ route('admin.lokasi.update', $tempat->id) }}" method="POST" class="space-y-3">
                                @csrf @method('PUT')
                                <x-input-label for="m-name-{{ $tempat->id }}" value="Nama" />
                                <x-text-input id="m-name-{{ $tempat->id }}" name="name" value="{{ $tempat->name }}" class="w-full text-sm" required />
                                <x-input-label for="m-address-{{ $tempat->id }}" value="Alamat" />
                                <x-text-input id="m-address-{{ $tempat->id }}" name="address" value="{{ $tempat->address }}" class="w-full text-sm" />
                                <x-input-label for="m-phone-{{ $tempat->id }}" value="Telepon" />
                                <x-text-input id="m-phone-{{ $tempat->id }}" name="phone" value="{{ $tempat->phone }}" class="w-full text-sm" />
                                <x-input-label for="m-hours-{{ $tempat->id }}" value="Jam Operasional" />
                                <x-text-input id="m-hours-{{ $tempat->id }}" name="operating_hours" value="{{ $tempat->operating_hours }}" class="w-full text-sm" />
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ $tempat->is_active ? 'checked' : '' }} class="rounded border-[var(--border)] text-[var(--orange-500)] shadow-sm focus:ring-[var(--orange-500)]">
                                    Aktif
                                </label>
                                <div class="mt-3 flex justify-end gap-2">
                                    <button type="button" @click="editMode = null" class="rounded px-3 py-1.5 text-sm underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Batal</button>
                                    <x-primary-button class="px-3 py-1.5">Simpan</x-primary-button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>