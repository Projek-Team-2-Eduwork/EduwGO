<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">Kelola Tipe Kendaraan</h2>
    </x-slot>

    <div class="py-12" x-data="{ editMode: null }">
        <div class="max-w-7xl mx-auto space-y-6">
            @if(session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
            @if(session('error')) <x-alert type="error">{{ session('error') }}</x-alert> @endif
            @if($errors->any()) <x-alert type="error">{{ $errors->first() }}</x-alert> @endif

            <!-- Form Tambah -->
            <div class="card-surface p-4">
                <form action="{{ route('admin.tipe-kendaraan.store') }}" method="POST" class="flex flex-col sm:flex-row gap-4 items-start sm:items-end">
                    @csrf
                    <div class="w-full sm:flex-1">
                        <x-input-label for="name" value="Nama Tipe Baru" />
                        <x-text-input id="name" name="name" type="text" class="w-full mt-1" required placeholder="Contoh: Matic, Sport" />
                    </div>
                    <x-primary-button>Tambah Tipe</x-primary-button>
                </form>
            </div>

            <!-- List Tipe -->
            <div class="card-surface overflow-hidden">
                @if($types->isEmpty())
                    <x-empty-state title="Belum ada tipe kendaraan" description="Tambahkan tipe pertama lewat form di atas." />
                @else
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-[var(--bg)] text-xs uppercase">
                            <tr>
                                <th>Nama Tipe</th>
                                <th>Slug / URL Param</th>
                                <th>Jumlah Kendaraan</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($types as $type)
                                <tr x-show="editMode !== {{ $type->id }}" class="border-b border-[var(--border)] transition hover:bg-[var(--bg)]">
                                    <td class="px-4 py-3 font-medium text-[var(--navy-900)]">{{ $type->name }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $type->slug }}</td>
                                    <td>{{ $type->vehicles_count }} Unit</td>
                                    <td class="px-4 py-3 text-right flex justify-end gap-3">
                                        <button @click="editMode = {{ $type->id }}" class="font-medium text-[var(--orange-500)] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Edit</button>
                                        <form action="{{ route('admin.tipe-kendaraan.destroy', $type->id) }}" method="POST" onsubmit="return confirm('Hapus tipe ini?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="font-medium text-red-600 dark:text-red-400 underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                <!-- Mode Edit -->
                                <tr x-show="editMode === {{ $type->id }}" x-cloak class="border-b border-[var(--border)] bg-[var(--bg)]">
                                    <td colspan="4">
                                        <form action="{{ route('admin.tipe-kendaraan.update', $type->id) }}" method="POST" class="flex gap-4 items-center">
                                            @csrf @method('PUT')
                                            <x-text-input name="name" value="{{ $type->name }}" aria-label="Nama tipe" class="w-full sm:w-1/2" required />
                                            <span class="hidden font-mono text-xs sm:inline">Slug tetap: {{ $type->slug }}</span>
                                            <div class="flex ml-auto gap-2">
                                                <button type="button" @click="editMode = null" class="rounded px-3 py-1.5 text-sm underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Batal</button>
                                                <x-primary-button class="px-3 py-1.5">Simpan</x-primary-button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card Layout -->
                <div class="divide-y divide-[var(--border)] md:hidden">
                    @foreach($types as $type)
                        <div class="p-4" x-show="editMode !== {{ $type->id }}">
                            <div class="mb-2 flex items-start justify-between">
                                <div>
                                    <div class="font-bold text-[var(--navy-900)]">{{ $type->name }}</div>
                                    <div class="mt-1 font-mono text-xs">{{ $type->slug }}</div>
                                </div>
                                <span class="rounded-md bg-[var(--bg)] px-2 py-1 text-xs">{{ $type->vehicles_count }} Unit</span>
                            </div>
                            <div class="mt-3 flex justify-end gap-4">
                                <button @click="editMode = {{ $type->id }}" class="text-sm font-medium text-[var(--orange-500)] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Edit</button>
                                <form action="{{ route('admin.tipe-kendaraan.destroy', $type->id) }}" method="POST" onsubmit="return confirm('Hapus tipe ini?');">
                                    @csrf @method('DELETE')
                                    <button class="text-sm font-medium text-red-600 dark:text-red-400 underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">Hapus</button>
                                </form>
                            </div>
                        </div>
                        <div class="p-4 bg-[var(--bg)]" x-show="editMode === {{ $type->id }}" x-cloak>
                            <form action="{{ route('admin.tipe-kendaraan.update', $type->id) }}" method="POST">
                                @csrf @method('PUT')
                                <x-text-input name="name" value="{{ $type->name }}" aria-label="Nama tipe" class="w-full text-sm" required />
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