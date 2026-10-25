@use('App\Http\Controllers\Admin\JadwalArmadaController', 'Jadwal')
<x-admin-layout title="Jadwal armada">
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">Jadwal armada</h2>
    </x-slot>

    @php
        $tones = [
            'info' => 'bg-[var(--info)] text-[var(--on-accent)]',
            'success' => 'bg-[var(--success)] text-[var(--surface)]',
            'warning' => 'bg-[var(--warning)] text-[var(--surface)]',
            'danger' => 'bg-[var(--danger)] text-[var(--surface)]',
        ];
        $nav = fn (int $shift) => request()->fullUrlWithQuery(['mulai' => $from->copy()->addDays($shift)->toDateString(), 'page' => null]);
    @endphp

    <div class="mx-auto max-w-7xl space-y-4 py-2">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <form method="GET" action="{{ route('admin.jadwal-armada') }}" class="flex flex-wrap items-end gap-3">
                @include('admin.partials.branch-filter')
                <x-form.datetime name="mulai" label="Mulai" :value="$from" :time="false" :picker="true" />
                <x-primary-button class="h-11">Tampilkan</x-primary-button>
            </form>
            <div class="flex gap-2">
                <a href="{{ $nav(-Jadwal::DAYS) }}" class="btn-secondary-edu inline-flex items-center px-3 text-sm">&larr; 14 hari</a>
                <a href="{{ request()->fullUrlWithQuery(['mulai' => null, 'page' => null]) }}" class="btn-secondary-edu inline-flex items-center px-3 text-sm">Hari ini</a>
                <a href="{{ $nav(Jadwal::DAYS) }}" class="btn-secondary-edu inline-flex items-center px-3 text-sm">14 hari &rarr;</a>
            </div>
        </div>

        <ul class="flex flex-wrap gap-x-4 gap-y-1 text-xs" aria-label="Keterangan warna">
            @foreach(['info' => 'Menunggu bayar', 'success' => 'Lunas', 'warning' => 'Sedang disewa', 'danger' => 'Terlambat'] as $tone => $label)
                <li class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm {{ $tones[$tone] }}"></span>{{ $label }}</li>
            @endforeach
            <li class="inline-flex items-center gap-1.5"><span class="fleet-buffer h-3 w-3 rounded-sm"></span>Buffer {{ $buffer }} menit</li>
        </ul>

        @if($vehicles->isEmpty())
            <x-empty-state title="Tidak ada unit aktif" description="Belum ada unit aktif di cakupan cabang ini." />
        @else
            <div class="card-surface overflow-x-auto">
                <table class="fleet-grid w-full border-collapse text-xs">
                    <thead>
                        <tr>
                            <th scope="col" class="sticky left-0 z-10 min-w-40 bg-[var(--surface)] px-3 py-2 text-left">Unit</th>
                            @foreach($days as $day)
                                <th scope="col" class="min-w-12 px-1 py-2 text-center font-medium {{ $day->isToday() ? 'text-[var(--accent)]' : '' }}">
                                    <div>{{ $day->translatedFormat('D') }}</div>
                                    <div class="text-sm font-bold">{{ $day->format('d') }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vehicles as $vehicle)
                            <tr>
                                <th scope="row" class="sticky left-0 z-10 bg-[var(--surface)] px-3 py-2 text-left font-normal">
                                    <div class="truncate font-semibold text-[var(--ink)]">{{ $vehicle->name }}</div>
                                    <div class="font-mono">{{ $vehicle->plate_number }}</div>
                                </th>
                                @foreach($days as $day)
                                    @php($cell = Jadwal::cell($vehicle, $day, $buffer))
                                    <td class="h-12 p-0.5">
                                        @if($cell)
                                            @php($b = $cell['booking'])
                                            <a href="{{ route('admin.pesanan.show', $b->code) }}"
                                               title="{{ $b->code }} · {{ $b->customer_name }} · {{ $b->start_at->format('d/m H:i') }}–{{ $b->end_at->format('d/m H:i') }}{{ $cell['buffer'] ? ' (buffer)' : '' }}"
                                               class="flex h-full w-full items-center justify-center rounded {{ $cell['buffer'] ? 'fleet-buffer' : $tones[Jadwal::tone($b)] }}">
                                                <span class="sr-only">{{ $b->code }} {{ $cell['buffer'] ? 'buffer' : $b->status->label() }}</span>
                                            </a>
                                        @else
                                            <span class="block h-full w-full rounded bg-[var(--bg)]"></span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $vehicles->links('components.pagination') }}
        @endif
    </div>
</x-admin-layout>
