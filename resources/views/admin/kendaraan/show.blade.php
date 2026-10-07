<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Detail Kendaraan: {{ $kendaraan->name }}
            </h2>
            <div class="flex gap-3">
                <a href="{{ route('admin.kendaraan.edit', $kendaraan->id) }}" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">Update</a>
                <form action="{{ route('admin.kendaraan.destroy', $kendaraan->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kendaraan ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 shadow-sm">Hapus</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col md:flex-row">
                <div class="md:w-1/3 bg-gray-100 dark:bg-gray-900 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 flex items-center justify-center p-6">
                    @if($kendaraan->image)
                        <img src="{{ asset($kendaraan->image) }}" class="w-full object-contain rounded-lg shadow-sm" alt="{{ $kendaraan->name }}">
                    @else
                        <span class="text-gray-400">Tidak ada foto</span>
                    @endif
                </div>
                <div class="p-6 md:w-2/3">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $kendaraan->name }}</h3>
                            <p class="text-gray-600 dark:text-gray-400">{{ $kendaraan->type?->name ?? 'Tipe Tidak Ditemukan' }} &middot; {{ $kendaraan->brand ?? 'Tanpa Brand' }}</p>
                        </div>
                        <span class="px-3 py-1 text-sm font-bold rounded {{ $kendaraan->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $kendaraan->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <p class="text-sm text-gray-500">Plat Nomor</p>
                            <p class="font-mono font-bold text-gray-800 dark:text-gray-200">{{ $kendaraan->plate_number }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Harga per Hari</p>
                            <p class="font-bold text-indigo-600 dark:text-indigo-400">Rp {{ number_format($kendaraan->price_per_day, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Kapasitas Tangki</p>
                            <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $kendaraan->tank_capacity ? $kendaraan->tank_capacity . ' L' : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Tanggal Didaftarkan</p>
                            <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $kendaraan->created_at->format('d M Y') }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 mb-1">Deskripsi</p>
                        <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $kendaraan->description ?: 'Tidak ada deskripsi.' }}</p>
                    </div>
                </div>
            </div>

            <!-- Riwayat Pesanan Kendaraan -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">Jadwal Pesanan Unit</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-900 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3 rounded-l-lg">Kode Booking</th>
                                <th class="px-4 py-3">Penyewa</th>
                                <th class="px-4 py-3">Jadwal Sewa</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 rounded-r-lg text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kendaraan->bookings as $booking)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-3 font-mono text-gray-900 dark:text-white">{{ $booking->code }}</td>
                                    <td class="px-4 py-3">{{ $booking->customer_name }}</td>
                                    <td class="px-4 py-3">{{ $booking->start_at->format('d M Y H:i') }} - {{ $booking->end_at->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3 capitalize">{{ $booking->status->value }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('admin.pesanan.show', $booking->code) }}" class="text-indigo-600 hover:underline">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada riwayat pesanan untuk unit ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>