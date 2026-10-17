{{-- Alamat, jam operasional, kontak & media sosial satu cabang (halaman Tentang, footer, profil beranda). --}}
@props(['branch', 'size' => 'sm'])

@php
    $wa = $branch->whatsappUrl('Halo, saya butuh bantuan.');
    $socials = $branch->socialLinks();
    $text = $size === 'base' ? 'text-sm sm:text-base' : 'text-xs';
    $label = 'text-xs font-semibold uppercase tracking-wider';
    $pill = $size === 'base' ? 'px-4 py-2 text-sm' : 'px-3 py-1.5 text-xs';
@endphp

<dl {{ $attributes->class(['space-y-3']) }}>
    <div>
        <dt class="{{ $label }}" style="color: var(--orange-500);">Alamat</dt>
        <dd class="mt-1 {{ $text }} leading-relaxed">{{ $branch->address ?: 'Alamat akan diinformasikan oleh admin.' }}</dd>
    </div>
    <div>
        <dt class="{{ $label }}" style="color: var(--orange-500);">Jam operasional</dt>
        <dd class="mt-1 {{ $text }} leading-relaxed">{{ $branch->operating_hours ?: 'Hubungi admin untuk jam operasional.' }}</dd>
    </div>
    @if ($wa || $socials)
        <div>
            <dt class="{{ $label }}" style="color: var(--orange-500);">Kontak &amp; Media Sosial</dt>
            <dd class="mt-2 flex flex-wrap gap-2">
                @if ($wa)
                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn-secondary-edu inline-flex items-center {{ $pill }}">WhatsApp</a>
                @endif
                @foreach ($socials as $name => $href)
                    <a href="{{ $href }}" target="_blank" rel="noopener" class="btn-secondary-edu inline-flex items-center {{ $pill }}">{{ $name }}</a>
                @endforeach
            </dd>
        </div>
    @endif
</dl>
