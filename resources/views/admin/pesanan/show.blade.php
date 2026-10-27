<x-admin-layout title="Detail pesanan">
    @php
        // COD menyimpan satu baris tunai pending sampai admin mengonfirmasi
        // uangnya diterima. Dipakai untuk memberi aksi yang jelas di halaman ini.
        $codPending = $booking->payments->first(fn ($p) => $p->status === 'pending'
            && $p->method === 'cash'
            && $p->payment_channel === \App\Services\XenditService::COD);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
            <h2 class="truncate text-xl font-semibold leading-tight">
                <span class="hidden sm:inline">Pesanan</span> <span class="font-mono text-[var(--accent)]">{{ $booking->code }}</span>
            </h2>

        </div>
    </x-slot>

    <div class="py-2 sm:py-6">
        <div class="max-w-7xl mx-auto space-y-6">
            {{-- Toolbar aksi pesanan --}}
            <div class="card-surface flex flex-col items-stretch gap-2 p-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-3" role="toolbar" aria-label="Aksi pesanan">
                <x-badge-status :status="$booking->status" class="self-start sm:me-auto" />
                @if(in_array($booking->status->value, ['pending', 'paid', 'rented']))
                    <button type="button" x-data @click="$dispatch('open-modal', 'update-{{ $booking->code }}')" class="btn-primary-edu inline-flex w-full items-center justify-center px-4 text-sm sm:w-auto">Ubah status</button>
                @endif
                @if($booking->status === App\Enums\BookingStatus::Pending)
                    <button type="button" x-data @click="$dispatch('open-modal', 'cash-{{ $booking->code }}')" class="{{ $codPending ? 'btn-primary-edu' : 'btn-secondary-edu' }} inline-flex w-full items-center justify-center px-4 text-sm sm:w-auto">{{ $codPending ? 'Tandai Sudah Dibayar (COD)' : 'Catat bayar tunai' }}</button>
                @endif
                @if($payment && $payment->status === 'pending' && str_starts_with((string) $payment->method, 'xendit'))
                    <a href="{{ route('admin.pesanan.cek-status', $booking->code) }}" class="btn-secondary-edu inline-flex w-full items-center justify-center px-4 text-sm sm:w-auto">Sinkronkan status bayar</a>
                @endif
                @if($wa = $booking->whatsappUrl())
                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn-wa-edu inline-flex w-full items-center justify-center rounded-lg px-4 text-sm font-semibold sm:w-auto">Chat penyewa (WA)</a>
                @endif
            </div>

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
                $perluRefund = $booking->payments->where('needs_refund', true)->whereNull('refunded_at');
            @endphp
            @if($perluRefund->isNotEmpty())
                <x-alert type="warning">
                    Ada pembayaran Xendit yang masuk setelah pesanan ini tidak lagi menunggu pembayaran
                    (status {{ $booking->status->label() }}). Lakukan refund manual ke penyewa lalu tandai di <a href="{{ route('admin.refund.index') }}" class="font-semibold underline">Perlu refund</a>:
                    @foreach($perluRefund as $p)
                        <span class="font-mono">{{ $p->gateway_reference }}</span> ({{ rupiah($p->amount) }}){{ $loop->last ? '.' : ',' }}
                    @endforeach
                </x-alert>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Data Penyewa & Motor -->
                <div class="space-y-6">
                    <!-- Card Penyewa -->
                    <div class="section-plain overflow-hidden">
                        <div class="flex items-center justify-between border-b border-[var(--line)] px-4 py-4 sm:px-6">
                            <h3 class="font-bold">Data Penyewa</h3>
                        </div>
                        <div class="p-4 sm:p-6">
                            <dl class="receipt text-sm">
                                <div>
                                    <dt>Nama</dt>
                                    <dd class="font-semibold text-[var(--ink)]">{{ $booking->customer_name }}</dd>
                                </div>
                                <div>
                                    <dt>WhatsApp</dt>
                                    <dd class="font-semibold text-[var(--ink)]">{{ $booking->customer_phone ?? '-' }}</dd>
                                </div>

                                @php
                                    $socials = collect(['instagram' => 'https://instagram.com/', 'facebook' => 'https://facebook.com/'])
                                        ->filter(fn ($base, $key) => filled($booking->user?->social_account[$key] ?? null));
                                @endphp
                                @if($socials->isNotEmpty())
                                <div>
                                    <dt>Media sosial</dt>
                                    <dd class="flex flex-wrap justify-end gap-x-4 gap-y-1 font-semibold">
                                        @foreach($socials as $key => $base)
                                            <a href="{{ $base.ltrim($booking->user->social_account[$key], '@') }}" target="_blank" rel="noopener" class="text-[var(--accent)] underline-offset-4 hover:underline">{{ ucfirst($key) }}</a>
                                        @endforeach
                                    </dd>
                                </div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- Card Kendaraan & Rentang -->
                    <div class="section-plain overflow-hidden">
                        <div class="flex items-center justify-between gap-3 border-b border-[var(--line)] px-4 py-4 sm:px-6">
                            <h3 class="font-bold">Data Kendaraan</h3>
                            <x-badge-status :status="$booking->status" />
                        </div>
                        <div class="p-4 sm:p-6">
                            <div class="mb-6 flex items-start gap-4 border-b border-[var(--line)] pb-6">
                                @if($booking->vehicle?->image)
                                    <x-picture :src="$booking->vehicle->image" class="h-24 w-24 rounded-lg bg-[var(--bg)] object-cover" alt="" />
                                @else
                                    <div class="w-24 h-24 rounded-lg bg-[var(--bg)] flex items-center justify-center">
                                        <svg class="w-8 h-8 text-[var(--line)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="break-words text-lg font-bold text-[var(--ink)]">{{ $booking->vehicle?->name ?? 'Kendaraan Dihapus' }}</div>
                                    <div class="text-sm">{{ $booking->vehicle?->type?->name ?? '-' }}</div>
                                    <div class="mt-2 inline-block rounded-md bg-[var(--bg)] px-2 py-1 font-mono text-sm">{{ $booking->vehicle?->plate_number ?? '-' }}</div>
                                </div>
                            </div>

                            <dl class="receipt text-sm">
                                <div>
                                    <dt>Rentang Sewa ({{ $booking->duration_days }} hari)</dt>
                                    <dd class="mt-1 font-semibold text-[var(--ink)]">
                                        {{ $booking->start_at->translatedFormat('d M Y H:i') }} &mdash; {{ $booking->end_at->translatedFormat('d M Y H:i') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt>Total Harga</dt>
                                    <dd class="text-lg font-bold text-[var(--accent)]">{{ rupiah($booking->total_amount) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Riwayat & Timeline -->
                <div class="space-y-6">
                    <!-- Card Payments -->
                    <div class="section-plain overflow-hidden">
                        <div class="flex items-center justify-between border-b border-[var(--line)] px-4 py-4 sm:px-6">
                            <h3 class="font-bold">Riwayat Pembayaran</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm sm:min-w-[34rem]">
                                <thead class="bg-[var(--bg)] text-xs uppercase">
                                    <tr>
                                        <th class="px-3 py-3 sm:px-4">Metode / Referensi</th>
                                        <th class="px-3 py-3 sm:px-4">Status</th>
                                        <th class="px-3 py-3 sm:px-4">Waktu Bayar</th>
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

                                    @forelse($tampil as $p)
                                        <tr class="border-b border-[var(--line)] transition hover:bg-[var(--bg)]">
                                            <td class="px-3 py-3 sm:px-4">
                                                <div class="font-medium text-[var(--ink)]">{{ App\Services\XenditService::paymentLabel($p->payment_channel, $p->method) }}</div>
                                                <div class="mt-1 break-all font-mono text-xs">{{ $p->gateway_reference ?? '-' }}</div>
                                            </td>
                                            <td class="px-3 py-3 sm:px-4">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="font-semibold capitalize {{ $p->status === 'paid' ? 'text-[var(--success)]' : '' }}">{{ $p->status }}</span>
                                                    @if($p->needs_refund)
                                                        <span class="text-xs {{ $p->refunded_at ? '' : 'text-[var(--warning)]' }}">{{ $p->refunded_at ? 'Sudah direfund '.$p->refunded_at->translatedFormat('d M Y') : 'Perlu refund' }}</span>
                                                    @endif
                                                    @if($p->status === 'pending' && $p->method === 'cash' && $booking->status === App\Enums\BookingStatus::Pending)
                                                        <button type="button" x-data @click="$dispatch('open-modal', 'cash-{{ $booking->code }}')" class="btn-primary-edu inline-flex items-center px-3 text-xs">
                                                            Tandai Lunas
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-3 py-3 text-xs sm:px-4">{{ $p->paid_at ? $p->paid_at->translatedFormat('d M Y H:i') : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-3 py-6 text-center sm:px-4">Belum ada riwayat pembayaran.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Timeline & Catatan -->
                    <div class="section-plain overflow-hidden p-4 sm:p-6">
                        <h3 class="mb-5 font-bold">Timeline Status</h3>
                        <ol class="relative mb-8 ml-2 border-l border-[var(--line)]">
                            @foreach($booking->histories->sortBy('created_at') as $h)
                                <li class="mb-5 ml-6">
                                    <span class="absolute -left-1.5 flex h-3 w-3 items-center justify-center rounded-full bg-[var(--accent)] ring-4 ring-[var(--surface)]"></span>
                                    <h4 class="mb-1 flex items-center text-sm font-bold">
                                        {{ App\Enums\BookingStatus::from($h->to_status)->label() }}
                                    </h4>
                                    <time class="mb-1 block text-xs font-normal">
                                        {{ $h->created_at->translatedFormat('D, d M Y H:i') }} &middot;
                                        Oleh: <span class="font-semibold">{{ $h->changer?->name ?? 'Sistem' }}</span>
                                    </time>
                                    @if($h->note)
                                        <p class="mt-2 border-t border-[var(--line)] pt-2 text-sm">{{ $h->note }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>

                        <form action="{{ route('admin.pesanan.notes', $booking->code) }}" method="POST" class="border-t border-[var(--line)] pt-6">
                            @csrf
                            <label for="notes" class="mb-2 block text-sm font-semibold text-[var(--ink)]">Catatan Internal Admin (Tidak terlihat oleh penyewa)</label>
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

    <!-- Render Partial Modal Ubah status -->
    @include('admin.partials.update-status-modal', ['booking' => $booking])
    @include('admin.partials.cash-payment-modal', ['booking' => $booking, 'codPending' => $codPending])
</x-admin-layout>
