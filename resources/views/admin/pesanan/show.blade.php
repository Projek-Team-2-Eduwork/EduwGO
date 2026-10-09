<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Detail Pesanan: <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ $booking->code }}</span>
            </h2>
            <div class="flex gap-3">
                @if(Route::has('admin.pesanan.update') && in_array($booking->status->value, ['pending', 'paid', 'rented']))
                    <button type="button" x-data @click="$dispatch('open-modal', 'update-{{ $booking->code }}')" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase shadow-sm hover:bg-indigo-700 transition">Update Status</button>
                @endif
                @if($booking->status === App\Enums\BookingStatus::Pending)
                    <button type="button" x-data @click="$dispatch('open-modal', 'cash-{{ $booking->code }}')" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 active:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        Catat Bayar Tunai
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-lg shadow-sm">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="p-4 bg-red-100 text-red-800 rounded-lg shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Data Penyewa & Motor -->
                <div class="space-y-6">
                    <!-- Card Penyewa -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="font-bold text-gray-900 dark:text-gray-100">Data Penyewa</h3>
                        </div>
                        <div class="p-6">
                            <dl class="space-y-4 text-sm">
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">Nama</dt>
                                    <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $booking->customer_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">WhatsApp / Telepon</dt>
                                    <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $booking->customer_phone ?? '-' }}</dd>
                                </div>
                                
                                @if(isset($booking->user->social_account['instagram']) || isset($booking->user->social_account['facebook']))
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">Sosial Media Terhubung</dt>
                                    <dd class="font-semibold mt-1 flex gap-2">
                                        @if(isset($booking->user->social_account['instagram']))
                                            <a href="https://instagram.com/{{ $booking->user->social_account['instagram'] }}" target="_blank" class="text-pink-600 hover:underline">Instagram</a>
                                        @endif
                                        @if(isset($booking->user->social_account['facebook']))
                                            <a href="https://facebook.com/{{ $booking->user->social_account['facebook'] }}" target="_blank" class="text-blue-600 hover:underline">Facebook</a>
                                        @endif
                                    </dd>
                                </div>
                                @endif
                            </dl>
                            <div class="mt-6">
                                @php
                                    $wa = preg_replace('/[^0-9]/', '', $booking->customer_phone ?? '');
                                    if (str_starts_with($wa, '0')) {
                                        $wa = '62' . substr($wa, 1);
                                    }
                                @endphp
                                <a href="https://wa.me/{{ $wa }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600 focus:bg-green-600 shadow-sm w-full sm:w-auto justify-center">
                                    Chat Penyewa (WA)
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Card Kendaraan & Rentang -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="font-bold text-gray-900 dark:text-gray-100">Data Kendaraan</h3>
                            <x-badge-status :status="$booking->status" />
                        </div>
                        <div class="p-6">
                            <div class="flex items-start gap-4 mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                                @if($booking->vehicle?->image)
                                    <img src="{{ asset($booking->vehicle->image) }}" class="w-24 h-24 rounded-lg object-cover bg-gray-100 dark:bg-gray-700">
                                @else
                                    <div class="w-24 h-24 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    </div>
                                @endif
                                <div>
                                    <div class="font-bold text-lg text-gray-900 dark:text-gray-100">{{ $booking->vehicle?->name ?? 'Kendaraan Dihapus' }}</div>
                                    <div class="text-sm text-gray-500">{{ $booking->vehicle?->type?->name ?? '-' }}</div>
                                    <div class="text-sm font-mono mt-2 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded inline-block text-gray-700 dark:text-gray-300">{{ $booking->vehicle?->plate_number ?? '-' }}</div>
                                </div>
                            </div>
                            
                            <dl class="space-y-4 text-sm">
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">Rentang Sewa ({{ $booking->duration_days }} hari)</dt>
                                    <dd class="font-semibold text-gray-900 dark:text-gray-100 mt-1">
                                        {{ $booking->start_at->translatedFormat('d M Y H:i') }} &mdash; {{ $booking->end_at->translatedFormat('d M Y H:i') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">Total Harga</dt>
                                    <dd class="font-bold text-indigo-600 dark:text-indigo-400 text-lg">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Riwayat & Timeline -->
                <div class="space-y-6">
                    <!-- Card Payments -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="font-bold text-gray-900 dark:text-gray-100">Riwayat Pembayaran</h3>
                            @if($payment && $payment->status === 'pending')
                                <a href="{{ route('admin.pesanan.cek-status', $booking->code) }}" class="inline-flex items-center px-3 py-1 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 shadow-sm">
                                    Cek Status Xendit
                                </a>
                            @endif
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                                    <tr>
                                        <th class="px-4 py-3">Metode / Referensi</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Waktu Bayar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($booking->payments as $p)
                                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-gray-900 dark:text-gray-100 capitalize">{{ str_replace('_', ' ', $p->method) }}</div>
                                                <div class="text-xs font-mono mt-1">{{ $p->gateway_reference }}</div>
                                            </td>
                                            <td class="px-4 py-3 capitalize font-semibold {{ $p->status === 'paid' ? 'text-green-600 dark:text-green-400' : '' }}">{{ $p->status }}</td>
                                            <td class="px-4 py-3 text-xs">{{ $p->paid_at ? $p->paid_at->translatedFormat('d M Y H:i') : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-6 text-center">Belum ada riwayat pembayaran.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Timeline & Catatan -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden p-6">
                        <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-5">Timeline Status</h3>
                        <ol class="relative border-l border-gray-200 dark:border-gray-700 ml-2 mb-8">
                            @foreach($booking->histories->sortBy('created_at') as $h)
                                <li class="mb-5 ml-6">
                                    <span class="absolute flex items-center justify-center w-3 h-3 bg-indigo-500 rounded-full -left-1.5 ring-4 ring-white dark:ring-gray-800"></span>
                                    <h4 class="flex items-center mb-1 text-sm font-bold text-gray-900 dark:text-gray-100">
                                        {{ App\Enums\BookingStatus::from($h->to_status)->label() }}
                                    </h4>
                                    <time class="block mb-1 text-xs font-normal text-gray-500 dark:text-gray-400">
                                        {{ $h->created_at->translatedFormat('D, d M Y H:i') }} &middot; 
                                        Oleh: <span class="font-semibold">{{ $h->changer?->name ?? 'Sistem' }}</span>
                                    </time>
                                    @if($h->note)
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-2 bg-gray-50 dark:bg-gray-900/50 p-2 rounded border border-gray-100 dark:border-gray-700">{{ $h->note }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>

                        <form action="{{ route('admin.pesanan.notes', $booking->code) }}" method="POST" class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            @csrf
                            <label for="notes" class="block font-bold text-sm text-gray-900 dark:text-gray-100 mb-2">Catatan Internal Admin (Tidak terlihat oleh penyewa)</label>
                            <textarea id="notes" name="notes" rows="4" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Tuliskan catatan terkait pesanan ini...">{{ old('notes', $booking->notes) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                            <div class="mt-3 flex justify-end">
                                <x-primary-button>Simpan Catatan</x-primary-button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
    
    <!-- Render Partial Modal Update Status -->
    @include('admin.partials.update-status-modal', ['booking' => $booking])
    @include('admin.partials.cash-payment-modal', ['booking' => $booking])
</x-admin-layout>