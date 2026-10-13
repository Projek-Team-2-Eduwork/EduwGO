<x-app-layout>
    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            
            @if ($booking->isOverdue())
                <div class="mb-6 flex items-center rounded-r-lg border-l-4 border-red-500 bg-red-100 p-4 shadow-sm dark:bg-red-900/30">
                    <svg class="mr-3 h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span class="font-medium text-red-800 dark:text-red-300">Waktu sewa sudah lewat, segera kembalikan unit</span>
                </div>
            @endif

            {{-- Notifikasi sukses pembayaran (sekali baca, otomatis hilang) --}}
            @if (session('bayar_berhasil'))
                <div x-data="{ shown: true }" x-show="shown" x-init="setTimeout(() => { shown = false }, 8000)" x-transition.opacity
                     class="mb-6 flex items-start justify-between rounded-r-lg border-l-4 border-green-500 bg-green-100 p-4 shadow-sm dark:bg-green-900/30" role="status">
                    <div class="flex items-start">
                        <svg class="mr-3 h-6 w-6 shrink-0 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div>
                            <p class="font-bold text-green-800 dark:text-green-300">Pembayaran berhasil!</p>
                            <p class="text-sm text-green-700 dark:text-green-400">Pesanan {{ $booking->code }} sudah lunas. Simpan kode booking ini, lalu ikuti langkah selanjutnya di bawah.</p>
                        </div>
                    </div>
                    <button type="button" x-on:click="shown = false" class="ml-4 shrink-0 text-green-700 hover:opacity-70 dark:text-green-400" aria-label="Tutup notifikasi">&times;</button>
                </div>
            @endif

            <div class="card-surface overflow-hidden">
                <div class="p-6 md:p-8">
                    
                    <!-- Header: Info Kendaraan -->
                    <div class="flex flex-col md:flex-row gap-6 mb-8 pb-8 border-b border-[var(--border)]">
                        <div class="w-full md:w-1/3">
                            @if($booking->vehicle?->image)
                                <img src="{{ asset($booking->vehicle->image) }}" class="w-full h-48 object-cover rounded-lg bg-gray-100 dark:bg-gray-900 shadow-sm" alt="{{ $booking->vehicle->name }}" onerror="this.outerHTML='<div class=\'w-full h-48 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg shadow-sm\'><svg class=\'w-12 h-12 text-gray-400\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10\'></path></svg></div>'">
                            @else
                                <div class="w-full h-48 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg shadow-sm">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                            @endif
                        </div>
                        <div class="w-full md:w-2/3 flex flex-col justify-center">
                            <div class="flex justify-between items-start mb-2">
                                <h2 class="text-2xl font-bold">{{ $booking->vehicle?->name ?? 'Kendaraan Dihapus' }}</h2>
                                <x-badge-status :status="$booking->status" />
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 font-medium mb-1">{{ $booking->vehicle?->type?->name ?? '-' }}</p>
                            <p class="text-gray-700 dark:text-gray-300 font-mono bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded inline-block self-start">{{ $booking->vehicle?->plate_number ?? '-' }}</p>
                        </div>
                    </div>

                    <!-- Grid Detail -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 pb-8 border-b border-[var(--border)]">
                        <!-- Blok Detail Pemesanan -->
                        <div>
                            <h3 class="text-lg font-bold mb-4">Detail Pemesanan</h3>
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Nama Penyewa</dt>
                                    <dd class="font-medium text-[var(--navy-900)]">{{ $booking->user->name }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">WhatsApp</dt>
                                    <dd class="font-medium text-[var(--navy-900)]">{{ $booking->user->phone ?? '-' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">ID Booking</dt>
                                    <dd class="font-mono font-medium text-[var(--orange-500)]">{{ $booking->code }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Tanggal Booking</dt>
                                    <dd class="font-medium text-[var(--navy-900)]">{{ $booking->created_at->translatedFormat('D, d M Y H:i') }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Blok Rentang Sewa & Total -->
                        <div>
                            <h3 class="text-lg font-bold mb-4">Informasi Sewa</h3>
                            <dl class="space-y-3 text-sm mb-4">
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Mulai Sewa</dt>
                                    <dd class="font-medium text-[var(--navy-900)]">{{ $booking->start_at->translatedFormat('D, d M Y H:i') }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Selesai Sewa</dt>
                                    <dd class="font-medium text-[var(--navy-900)]">{{ $booking->end_at->translatedFormat('D, d M Y H:i') }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Durasi</dt>
                                    <dd class="font-medium text-[var(--navy-900)]">{{ $booking->duration_days }} Hari</dd>
                                </div>
                            </dl>
                            <div class="pt-3 border-t border-[var(--border)] flex justify-between items-center">
                                <span class="font-bold text-gray-700 dark:text-gray-300">Total Pembayaran</span>
                                <span class="text-xl font-black text-[var(--orange-500)]">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 pb-8 border-b border-[var(--border)]">
                        <!-- Timeline Status -->
                        <div>
                            <h3 class="text-lg font-bold mb-5">Riwayat Pesanan</h3>
                            <ol class="relative border-l border-gray-200 dark:border-gray-700 ml-2">
                                @foreach($booking->histories->sortBy('created_at') as $h)
                                    <li class="mb-5 ml-6">
                                        <span class="absolute flex items-center justify-center w-3 h-3 bg-[var(--orange-500)] rounded-full -left-1.5 ring-4 ring-[var(--surface)]"></span>
                                        <h4 class="flex items-center mb-1 text-sm font-bold">
                                            {{ App\Enums\BookingStatus::from($h->to_status)->label() }}
                                        </h4>
                                        <time class="block mb-1 text-xs font-normal text-gray-500 dark:text-gray-400">
                                            {{ $h->created_at->translatedFormat('D, d M Y H:i') }} &middot; 
                                            Oleh: 
                                            @if($h->changed_by === null)
                                                <span class="font-medium text-[var(--orange-500)]">Sistem</span>
                                            @elseif($h->changed_by === auth()->id())
                                                <span class="font-medium text-green-600 dark:text-green-400">Anda</span>
                                            @else
                                                <span class="font-medium text-blue-600 dark:text-blue-400">Admin</span>
                                            @endif
                                        </time>
                                        @if($h->note)
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-2 bg-gray-50 dark:bg-gray-900/50 p-2 rounded border border-[var(--border)]">{{ $h->note }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                        
                        <!-- Lokasi & Jam -->
                        <div>
                            <h3 class="text-lg font-bold mb-4">Lokasi Pengambilan</h3>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-[var(--border)]">
                                <div class="mb-4">
                                    <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Alamat</span>
                                    <p class="text-sm text-gray-800 dark:text-gray-200">{{ setting('contact.address', '-') }}</p>
                                </div>
                                <div>
                                    <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Jam Operasional</span>
                                    <p class="text-sm text-gray-800 dark:text-gray-200">{{ setting('contact.hours', '-') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Langkah selanjutnya setelah pembayaran (EG-53) -->
                    @if ($booking->status === App\Enums\BookingStatus::Paid)
                        <div class="mb-8 pb-8 border-b border-[var(--border)]">
                            <h3 class="text-lg font-bold mb-4">Langkah Selanjutnya</h3>
                            <ol class="space-y-4">
                                <li class="flex items-start gap-3 text-sm">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--orange-500)] text-xs font-bold text-white">1</span>
                                    <span class="text-gray-600 dark:text-gray-300">Datang ke lokasi pengambilan sesuai alamat dan jam operasional di atas, mulai sewa <strong class="text-[var(--navy-900)]">{{ $booking->start_at->translatedFormat('D, d M Y H:i') }}</strong>.</span>
                                </li>
                                <li class="flex items-start gap-3 text-sm">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--orange-500)] text-xs font-bold text-white">2</span>
                                    <span class="text-gray-600 dark:text-gray-300">Bawa KTP atau SIM yang masih berlaku untuk verifikasi saat serah terima.</span>
                                </li>
                                <li class="flex items-start gap-3 text-sm">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--orange-500)] text-xs font-bold text-white">3</span>
                                    <span class="text-gray-600 dark:text-gray-300">Konfirmasi kedatangan lewat tombol Chat Admin, dan pantau Riwayat Pesanan untuk catatan dari admin.</span>
                                </li>
                                <li class="flex items-start gap-3 text-sm">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--orange-500)] text-xs font-bold text-white">4</span>
                                    <span class="text-gray-600 dark:text-gray-300">Kembalikan unit sebelum <strong class="text-[var(--navy-900)]">{{ $booking->end_at->translatedFormat('D, d M Y H:i') }}</strong>, lalu minta admin menutup pesanan ini.</span>
                                </li>
                            </ol>
                        </div>
                    @endif

                    <!-- Footer & Aksi -->
                    <div class="flex flex-col-reverse md:flex-row justify-between items-center gap-4">
                        <a href="{{ route('booking.index') }}" class="btn-secondary-edu inline-flex w-full items-center justify-center px-4 py-2 text-sm md:w-auto">
                            &larr; Kembali ke Daftar
                        </a>
                        
                        <div class="w-full md:w-auto flex flex-col sm:flex-row gap-3">
                            <a href="https://wa.me/{{ setting('contact.whatsapp', '6281234567890') }}?text={{ urlencode('Halo, saya ingin bertanya soal pesanan '.$booking->code) }}" target="_blank" rel="noopener" class="btn-wa-edu inline-flex w-full items-center justify-center px-4 py-2 text-sm sm:w-auto">
                                Chat Admin
                            </a>

                            @if($booking->status === App\Enums\BookingStatus::Pending)
                                <x-danger-button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'batal-{{ $booking->code }}')" class="w-full sm:w-auto justify-center">
                                    Batalkan
                                </x-danger-button>
                                
                                <a href="{{ route('booking.waiting', $booking->code) }}" class="btn-primary-edu inline-flex w-full items-center justify-center px-4 py-2 text-sm sm:w-auto">
                                    Bayar
                                </a>

                                <!-- Modal Konfirmasi Batal -->
                                <x-modal name="batal-{{ $booking->code }}" maxWidth="sm">
                                    <div class="p-6">
                                        <h2 class="text-lg font-medium text-[var(--navy-900)] mb-2">Batalkan pesanan {{ $booking->code }}?</h2>
                                        <p class="mb-6 text-sm">Tindakan ini tidak dapat dibatalkan kembali. Anda yakin?</p>
                                        <div class="flex justify-end gap-3">
                                            <x-secondary-button x-on:click="$dispatch('close-modal', 'batal-{{ $booking->code }}')">
                                                Kembali
                                            </x-secondary-button>
                                            <form method="POST" action="{{ route('booking.cancel', $booking->code) }}">
                                                @csrf
                                                <x-danger-button type="submit">
                                                    Ya, Batalkan
                                                </x-danger-button>
                                            </form>
                                        </div>
                                    </div>
                                </x-modal>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
            
        </div>
    </div>
</x-app-layout>