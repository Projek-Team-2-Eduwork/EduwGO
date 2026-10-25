<x-admin-layout>
    @php
        // COD menyimpan satu baris tunai pending sampai admin mengonfirmasi
        // uangnya diterima. Dipakai untuk memberi aksi yang jelas di halaman ini.
        $codPending = $booking->payments->first(fn ($p) => $p->status === 'pending'
            && $p->method === 'cash'
            && $p->payment_channel === \App\Services\XenditService::COD);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
            <h2 class="text-xl font-semibold leading-tight">
                Detail Pesanan: <span class="font-mono text-[var(--orange-500)]">{{ $booking->code }}</span>
            </h2>
            <div class="flex gap-3">
                @if(Route::has('admin.pesanan.update') && in_array($booking->status->value, ['pending', 'paid', 'rented']))
                    <button type="button" x-data @click="$dispatch('open-modal', 'update-{{ $booking->code }}')" class="btn-primary-edu inline-flex items-center px-5 py-2.5 text-sm">Update Status</button>
                @endif
                @if($booking->status === App\Enums\BookingStatus::Pending)
                    <button type="button" x-data @click="$dispatch('open-modal', 'cash-{{ $booking->code }}')" class="{{ $codPending ? 'btn-primary-edu' : 'btn-secondary-edu' }} inline-flex items-center px-5 py-2.5 text-sm">
                        {{ $codPending ? 'Tandai Sudah Dibayar (COD)' : 'Catat Bayar Tunai' }}
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6">
            @if(session('success'))
                <x-alert type="success">
                    {{ session('success') }}
                </x-alert>
            @endif
            
            @if(session('error'))
                <x-alert type="error">
                    {{ session('error') }}
                </x-alert>
            @endif

            @php
                $perluRefund = $booking->payments->where('needs_refund', true);
            @endphp
            @if($perluRefund->isNotEmpty())
                <x-alert type="warning">
                    Ada pembayaran Xendit yang masuk setelah pesanan ini tidak lagi menunggu pembayaran
                    (status {{ $booking->status->label() }}). Lakukan refund manual ke penyewa:
                    @foreach($perluRefund as $p)
                        <span class="font-mono">{{ $p->gateway_reference }}</span> (Rp {{ number_format($p->amount, 0, ',', '.') }}){{ $loop->last ? '.' : ',' }}
                    @endforeach
                </x-alert>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Data Penyewa & Motor -->
                <div class="space-y-6">
                    <!-- Card Penyewa -->
                    <div class="card-surface overflow-hidden">
                        <div class="flex items-center justify-between border-b border-[var(--border)] px-6 py-4">
                            <h3 class="font-bold">Data Penyewa</h3>
                        </div>
                        <div class="p-6">
                            <dl class="space-y-4 text-sm">
                                <div>
                                    <dt>Nama</dt>
                                    <dd class="font-semibold text-[var(--navy-900)]">{{ $booking->customer_name }}</dd>
                                </div>
                                <div>
                                    <dt>WhatsApp / Telepon</dt>
                                    <dd class="font-semibold text-[var(--navy-900)]">{{ $booking->customer_phone ?? '-' }}</dd>
                                </div>
                                
                                @if(isset($booking->user->social_account['instagram']) || isset($booking->user->social_account['facebook']))
                                <div>
                                    <dt>Sosial Media Terhubung</dt>
                                    <dd class="mt-1 flex gap-2 font-semibold">
                                        @if(isset($booking->user->social_account['instagram']))
                                            <a href="https://instagram.com/{{ $booking->user->social_account['instagram'] }}" target="_blank" rel="noopener" class="text-pink-600 underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Instagram</a>
                                        @endif
                                        @if(isset($booking->user->social_account['facebook']))
                                            <a href="https://facebook.com/{{ $booking->user->social_account['facebook'] }}" target="_blank" rel="noopener" class="text-[var(--orange-500)] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Facebook</a>
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
                                <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="btn-wa-edu inline-flex w-full items-center justify-center px-5 py-2.5 text-sm sm:w-auto">
                                    Chat Penyewa (WA)
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Card Kendaraan & Rentang -->
                    <div class="card-surface overflow-hidden">
                        <div class="flex items-center justify-between border-b border-[var(--border)] px-6 py-4">
                            <h3 class="font-bold">Data Kendaraan</h3>
                            <x-badge-status :status="$booking->status" />
                        </div>
                        <div class="p-6">
                            <div class="mb-6 flex items-start gap-4 border-b border-[var(--border)] pb-6">
                                @if($booking->vehicle?->image)
                                    <img src="{{ asset($booking->vehicle->image) }}" class="h-24 w-24 rounded-lg bg-[var(--bg)] object-cover" alt="">
                                @else
                                    <div class="w-24 h-24 rounded-lg bg-[var(--bg)] flex items-center justify-center">
                                        <svg class="w-8 h-8 text-[var(--border)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    </div>
                                @endif
                                <div>
                                    <div class="text-lg font-bold text-[var(--navy-900)]">{{ $booking->vehicle?->name ?? 'Kendaraan Dihapus' }}</div>
                                    <div class="text-sm">{{ $booking->vehicle?->type?->name ?? '-' }}</div>
                                    <div class="mt-2 inline-block rounded-md bg-[var(--bg)] px-2 py-1 font-mono text-sm">{{ $booking->vehicle?->plate_number ?? '-' }}</div>
                                </div>
                            </div>
                            
                            <dl class="space-y-4 text-sm">
                                <div>
                                    <dt>Rentang Sewa ({{ $booking->duration_days }} hari)</dt>
                                    <dd class="mt-1 font-semibold text-[var(--navy-900)]">
                                        {{ $booking->start_at->translatedFormat('d M Y H:i') }} &mdash; {{ $booking->end_at->translatedFormat('d M Y H:i') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt>Total Harga</dt>
                                    <dd class="text-lg font-bold text-[var(--orange-500)]">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Riwayat & Timeline -->
                <div class="space-y-6">
                    <!-- Card Payments -->
                    <div class="card-surface overflow-hidden">
                        <div class="flex items-center justify-between border-b border-[var(--border)] px-6 py-4">
                            <h3 class="font-bold">Riwayat Pembayaran</h3>
                            @if($payment && $payment->status === 'pending' && str_starts_with((string) $payment->method, 'xendit'))
                                <a href="{{ route('admin.pesanan.cek-status', $booking->code) }}" class="btn-secondary-edu inline-flex items-center px-3 py-1.5 text-xs">
                                    Cek Status Xendit
                                </a>
                            @endif
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-[var(--bg)] text-xs uppercase">
                                    <tr>
                                        <th class="px-4 py-3">Metode / Referensi</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Waktu Bayar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        // Tampilkan pembayaran yang lunas (termasuk yang perlu refund);
                                        // bila belum ada, cukup percobaan terakhir. Percobaan online yang
                                        // batal/kedaluwarsa tidak lagi menumpuk di sini.
                                        $riwayat = $booking->payments->sortByDesc('id');
                                        $tampil = $riwayat->where('status', 'paid');
                                        $tampil = $tampil->isNotEmpty() ? $tampil : $riwayat->take(1);
                                    @endphp

                                    @forelse($tampil as $terakhir)
                                        <tr class="border-b border-[var(--border)] transition hover:bg-[var(--bg)]">
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-[var(--navy-900)]">{{ App\Services\XenditService::paymentLabel($terakhir->payment_channel, $terakhir->method) }}</div>
                                                <div class="mt-1 font-mono text-xs">{{ $terakhir->gateway_reference ?? '-' }}</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="font-semibold capitalize {{ $terakhir->status === 'paid' ? 'text-emerald-700 dark:text-emerald-400' : '' }}">{{ $terakhir->status }}</span>
                                                    @if($terakhir->needs_refund)
                                                        <span class="text-xs text-amber-700 dark:text-amber-400">Perlu refund</span>
                                                    @endif
                                                    @if($terakhir->status === 'pending' && $terakhir->method === 'cash' && $booking->status === App\Enums\BookingStatus::Pending)
                                                        <button type="button" x-data @click="$dispatch('open-modal', 'cash-{{ $booking->code }}')" class="btn-primary-edu inline-flex items-center px-2.5 py-1 text-xs">
                                                            Tandai Lunas
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-xs">{{ $terakhir->paid_at ? $terakhir->paid_at->translatedFormat('d M Y H:i') : '-' }}</td>
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
                    <div class="card-surface overflow-hidden p-6">
                        <h3 class="mb-5 font-bold">Timeline Status</h3>
                        <ol class="relative mb-8 ml-2 border-l border-[var(--border)]">
                            @foreach($booking->histories->sortBy('created_at') as $h)
                                <li class="mb-5 ml-6">
                                    <span class="absolute -left-1.5 flex h-3 w-3 items-center justify-center rounded-full bg-[var(--orange-500)] ring-4 ring-[var(--surface)]"></span>
                                    <h4 class="mb-1 flex items-center text-sm font-bold">
                                        {{ App\Enums\BookingStatus::from($h->to_status)->label() }}
                                    </h4>
                                    <time class="mb-1 block text-xs font-normal">
                                        {{ $h->created_at->translatedFormat('D, d M Y H:i') }} &middot; 
                                        Oleh: <span class="font-semibold">{{ $h->changer?->name ?? 'Sistem' }}</span>
                                    </time>
                                    @if($h->note)
                                        <p class="mt-2 rounded border border-[var(--border)] bg-[var(--bg)] p-2 text-sm">{{ $h->note }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>

                        <form action="{{ route('admin.pesanan.notes', $booking->code) }}" method="POST" class="border-t border-[var(--border)] pt-6">
                            @csrf
                            <label for="notes" class="mb-2 block text-sm font-semibold text-[var(--navy-900)]">Catatan Internal Admin (Tidak terlihat oleh penyewa)</label>
                            <textarea id="notes" name="notes" rows="4" class="w-full rounded-lg shadow-sm" placeholder="Tuliskan catatan terkait pesanan ini...">{{ old('notes', $booking->notes) }}</textarea>
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
    @include('admin.partials.cash-payment-modal', ['booking' => $booking, 'codPending' => $codPending])
</x-admin-layout>