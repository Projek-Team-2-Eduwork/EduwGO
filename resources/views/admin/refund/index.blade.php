<x-admin-layout title="Perlu refund">
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">Perlu refund</h2>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5 py-2 sm:space-y-6">
        @if(session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
        @if(session('error')) <x-alert type="error">{{ session('error') }}</x-alert> @endif

        <p class="text-sm">Pembayaran online yang masuk setelah pesanan tidak lagi menunggu pembayaran. Kirim refund manual ke penyewa (di luar sistem), lalu tandai di sini.</p>

        @foreach(['awaiting' => 'Menunggu refund', 'done' => 'Sudah direfund (20 terakhir)'] as $key => $heading)
            @php($rows = $$key)
            <section class="card-surface" aria-labelledby="refund-{{ $key }}">
                <h3 id="refund-{{ $key }}" class="border-b border-[var(--line)] px-4 py-4 font-bold sm:px-5">{{ $heading }} <span class="text-sm font-normal">({{ $rows->count() }})</span></h3>
                @if($rows->isEmpty())
                    <p class="px-4 py-8 text-center text-sm sm:px-5">{{ $key === 'awaiting' ? 'Tidak ada refund tertunda.' : 'Belum ada.' }}</p>
                @else
                    <ul class="divide-y divide-[var(--line)]">
                        @foreach($rows as $p)
                            <li class="grid gap-3 px-4 py-4 sm:flex sm:flex-wrap sm:items-center sm:gap-x-6 sm:gap-y-2 sm:px-5 sm:py-3">
                                <div class="min-w-0 sm:flex-1">
                                    <a href="{{ route('admin.pesanan.show', $p->booking->code) }}" class="font-mono text-sm font-semibold text-[var(--ink)] hover:underline">{{ $p->booking->code }}</a>
                                    <div class="truncate text-xs">{{ $p->booking->customer_name }} · {{ $p->booking->customer_phone }}</div>
                                </div>
                                <div class="flex items-center justify-between gap-4 sm:contents">
                                    <div class="min-w-0 text-xs">
                                        <div class="font-medium text-[var(--ink)]">{{ $p->payment_channel ?: str_replace('_', ' ', $p->method) }}</div>
                                        <div>{{ $key === 'awaiting' ? 'Dibayar '.($p->paid_at?->translatedFormat('d M Y H:i') ?? '-') : 'Direfund '.$p->refunded_at->translatedFormat('d M Y H:i') }}</div>
                                    </div>
                                    <span class="shrink-0 text-sm font-bold {{ $key === 'awaiting' ? 'text-[var(--warning)]' : 'text-[var(--ink)]' }} sm:w-28 sm:text-right">{{ rupiah($p->amount) }}</span>
                                </div>
                                @if($key === 'awaiting')
                                    <form method="POST" action="{{ route('admin.refund.update', $p) }}" onsubmit="return confirm('Refund {{ $p->booking->code }} sudah dikirim ke penyewa?')" class="sm:shrink-0">
                                        @csrf
                                        <button class="btn-primary-edu inline-flex w-full items-center justify-center px-4 text-sm sm:w-auto">Tandai sudah direfund</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>
</x-admin-layout>
