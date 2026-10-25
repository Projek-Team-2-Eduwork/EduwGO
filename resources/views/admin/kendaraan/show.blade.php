<x-admin-layout title="Detail kendaraan">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="truncate text-xl font-semibold leading-tight">
                Detail Kendaraan
            </h2>

        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="mb-6 flex flex-wrap gap-3" aria-label="Aksi halaman">
                <a href="{{ route('admin.kendaraan.edit', $kendaraan->id) }}" class="btn-secondary-edu inline-flex items-center px-5 py-2.5 text-sm">Ubah</a>
                <form action="{{ route('admin.kendaraan.destroy', $kendaraan->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kendaraan ini?');">
                    @csrf
                    @method('DELETE')
                    <x-danger-button type="submit">Hapus</x-danger-button>
                </form>
            </div>
            <div class="section-plain overflow-hidden">
                <div class="flex flex-col md:flex-row">
                    <div class="flex items-center justify-center border-b bg-[var(--bg)] p-6 md:w-1/3 md:border-b-0 md:border-e border-[var(--line)]">
                        @if($kendaraan->image)
                            <x-picture :src="$kendaraan->image" class="w-full rounded-lg object-contain shadow-sm" alt="{{ $kendaraan->name }}" />
                        @else
                            <span>Tidak ada foto</span>
                        @endif
                    </div>
                    <div class="p-6 md:w-2/3">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-2xl font-bold text-[var(--ink)]">{{ $kendaraan->name }}</h3>
                                <p class="mt-1">{{ $kendaraan->type?->name ?? 'Tipe Tidak Ditemukan' }} &middot; {{ $kendaraan->brand ?? 'Tanpa Brand' }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $kendaraan->is_active ? 'bg-[var(--success-soft)] text-[var(--success)] dark:bg-[var(--success-soft)] dark:text-[var(--success)]' : 'bg-[var(--bg)] text-[var(--ink)] dark:bg-[var(--bg)] dark:text-[var(--ink-muted)]' }}">
                                {{ $kendaraan->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <div class="mb-6 grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm">Plat Nomor</p>
                                <p class="font-mono font-bold text-[var(--ink)]">{{ $kendaraan->plate_number }}</p>
                            </div>
                            <div>
                                <p class="text-sm">Harga per Hari</p>
                                <p class="font-bold text-[var(--accent)]">{{ rupiah($kendaraan->price_per_day) }}</p>
                            </div>
                            <div>
                                <p class="text-sm">Kapasitas Tangki</p>
                                <p class="font-semibold text-[var(--ink)]">{{ $kendaraan->tank_capacity ? $kendaraan->tank_capacity . ' L' : '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm">Tanggal Didaftarkan</p>
                                <p class="font-semibold text-[var(--ink)]">{{ $kendaraan->created_at->translatedFormat('d M Y') }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="mb-1 text-sm">Deskripsi</p>
                            <p class="whitespace-pre-line text-sm">{{ $kendaraan->description ?: 'Tidak ada deskripsi.' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Jadwal Booking --}}
            <div class="section-plain overflow-hidden p-6">
                <h3 class="mb-4 font-bold">Jadwal Booking</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-[var(--bg)] text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3">Kode Booking</th>
                                <th class="px-4 py-3">Penyewa</th>
                                <th class="px-4 py-3">Jadwal Sewa</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // Helper untuk memformat string buffer
                                $h = floor($bufferMenit / 60);
                                $m = $bufferMenit % 60;
                                $bufferText = '';
                                if ($h > 0) $bufferText .= $h . ' jam ';
                                if ($m > 0) $bufferText .= $m . ' menit';
                                $bufferText = trim($bufferText);
                            @endphp

                             @forelse($jadwal as $range)
                                @php $booking = $range['booking']; @endphp
                                <tr class="border-b border-[var(--line)] transition hover:bg-[var(--bg)]">
                                    <td class="px-4 py-3 font-mono text-xs font-semibold text-[var(--accent)]">{{ $booking->code }}</td>
                                    <td class="px-4 py-3 font-medium text-[var(--ink)]">{{ $booking->customer_name }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-[var(--ink)]">
                                            {{ $booking->start_at->translatedFormat('d M Y H:i') }} - {{ $booking->end_at->translatedFormat('d M Y H:i') }}
                                        </div>
                                        <div class="mt-1 text-xs">
                                            {{ $range['mulai']->translatedFormat('d M Y H:i') }} - {{ $range['selesai']->translatedFormat('d M Y H:i') }} (+{{ $bufferText }} buffer)
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-badge-status :status="$booking->status" />
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('admin.pesanan.show', $booking->code) }}" class="font-medium text-[var(--accent)] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent)]">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center">Belum ada booking aktif dalam 30 hari ke depan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
