@props([
    'items',                // list<string> poin S&K
    'title',
    'intro' => null,
    'heading' => 'h2',      // h1 di halaman penuh, h2 sebagai seksi beranda
    'headingId' => 'judul-syarat-ketentuan',
])

{{--
    Kartu navy yang sama dengan hero: beranda dibuka dan ditutup dengan bentuk yang sama.
    Desktop: judul di kiri atas, aksi di kiri bawah sejajar akhir daftar. Mobile: judul, daftar, lalu aksi.
--}}
<div {{ $attributes->merge(['class' => 'hero-kartu grid gap-8 px-5 py-9 sm:px-10 sm:py-12 lg:grid-cols-12 lg:grid-rows-[auto_1fr] lg:gap-x-12 lg:gap-y-10 lg:px-14 lg:py-14']) }}>
    <div class="lg:col-span-5">
        <p class="text-sm font-semibold text-[#C6F659]">Sebelum menyewa</p>
        <{{ $heading }} id="{{ $headingId }}" @class([
            'mt-3 leading-tight text-white [text-wrap:balance]',
            'text-3xl lg:text-[40px] lg:leading-[1.15]' => $heading !== 'h1',
            'text-4xl sm:text-5xl lg:text-[56px] lg:leading-[1.1]' => $heading === 'h1',
        ])>{{ $title }}</{{ $heading }}>
        @if ($intro)
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/70 lg:text-base">{{ $intro }}</p>
        @endif
    </div>

    <ol role="list" class="divide-y divide-white/10 lg:col-span-7 lg:col-start-6 lg:row-span-2 lg:row-start-1">
        @foreach ($items as $item)
            <li data-sk-poin class="grid grid-cols-[2.25rem_1fr] gap-3 py-4 first:pt-0 last:pb-0 sm:grid-cols-[3rem_1fr] sm:py-5">
                <span class="text-sm font-semibold leading-[1.625rem] tabular-nums text-[#C6F659]" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                <p class="text-[15px] leading-[1.625rem] text-white/85 lg:text-base">{{ $item }}</p>
            </li>
        @endforeach
    </ol>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-3 lg:col-span-5 lg:row-start-2 lg:self-end">
            {{ $actions }}
        </div>
    @endisset
</div>
