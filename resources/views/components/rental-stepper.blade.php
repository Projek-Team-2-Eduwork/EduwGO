@props(['step' => 2])
<ol aria-label="Tahapan sewa" class="mb-8 flex flex-wrap gap-x-3 gap-y-2 border-b border-[var(--line)] pb-4 text-sm">
    @foreach(['Pilih jadwal', 'Konfirmasi', 'Bayar'] as $label)
        <li @if($loop->iteration === $step) aria-current="step" @endif class="inline-flex items-center gap-3 {{ $loop->iteration === $step ? 'font-bold text-[var(--ink)]' : 'text-[var(--ink-muted)]' }}">
            @unless($loop->first)<span aria-hidden="true">·</span>@endunless {{ $label }}
        </li>
    @endforeach
</ol>
