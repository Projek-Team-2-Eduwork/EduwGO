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
    <span {{ $attributes->merge(['class' => 'inline-flex items-center']) }}>
        <span class="text-2xl font-extrabold leading-none tracking-tight" style="color: var(--navy-900);">Eduw<span style="color: var(--orange-500);">Go</span></span>
    </span>
@endif
