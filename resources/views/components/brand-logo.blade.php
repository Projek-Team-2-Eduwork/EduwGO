@php
    $name = setting('brand.name', 'EduwGo');
    $logoLight = setting('brand.logo_light');
    $logoDark = setting('brand.logo_dark') ?: $logoLight;
    $imgClass = $attributes->get('class', 'h-9 w-auto');
@endphp

{{-- Logo dari Pengaturan Toko (terang/gelap); fallback ke wordmark bila belum diatur. --}}
@if ($logoLight)
    <img src="{{ $logoLight }}" alt="{{ $name }}" class="{{ $imgClass }} dark:hidden">
    <img src="{{ $logoDark }}" alt="{{ $name }}" class="{{ $imgClass }} hidden dark:block">
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
        <span class="inline-flex h-8 w-8 items-center justify-center text-white" style="background-image: var(--accent-gradient); clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);" aria-hidden="true">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </span>
        <span class="font-serif text-2xl leading-none" style="color: var(--navy-900);">{{ $name }}</span>
    </span>
@endif
