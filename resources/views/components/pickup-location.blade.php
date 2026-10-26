@props(['vehicle'])
@php($branch = $vehicle?->branch)
<section {{ $attributes->class(['space-y-3 text-left']) }} aria-label="Lokasi pengambilan">
    <h3 class="text-lg font-bold">Lokasi pengambilan</h3>
    @if($branch)
        <p class="font-semibold text-[var(--ink)]">{{ $branch->name }}</p>
        <dl class="pickup-facts text-sm">
            <div><dt>Alamat</dt><dd>{{ $branch->address ?: 'Hubungi cabang untuk alamat pengambilan.' }}</dd></div>
            <div><dt>Jam operasional</dt><dd>{{ $branch->operating_hours ?: 'Konfirmasikan jam pengambilan dengan cabang.' }}</dd></div>
            <div><dt>WhatsApp</dt><dd>{{ $branch->phone ?: 'Belum tersedia' }}</dd></div>
        </dl>
        <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
            @if($branch->address)
                <a href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($branch->name.' '.$branch->address) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center font-semibold underline">Buka Google Maps</a>
            @endif
            @if($branch->whatsappUrl())
                <a href="{{ $branch->whatsappUrl('Halo, saya ingin konfirmasi pengambilan motor.') }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center font-semibold underline">Hubungi cabang</a>
            @endif
        </div>
    @else
        <p class="text-sm">Hubungi admin untuk mengonfirmasi lokasi pengambilan.</p>
    @endif
</section>

@once
    <style>
        .pickup-facts > div { display: grid; grid-template-columns: 7.5rem minmax(0, 1fr); gap: 1rem; padding: .8rem 0; border-bottom: 1px solid var(--line); }
        .pickup-facts dt { color: var(--ink-muted); }
        .pickup-facts dd { color: var(--ink); overflow-wrap: anywhere; }
        @media (max-width: 479px) {
            .pickup-facts > div { grid-template-columns: 1fr; gap: .15rem; }
        }
    </style>
@endonce
