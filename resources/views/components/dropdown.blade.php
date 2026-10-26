@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1', 'mobileSheet' => false])

@php
$sheetName = 'dropdown-'.\Illuminate\Support\Str::uuid();
$alignmentClasses = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-top',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$width = match ($width) {
    '48' => 'w-48',
    default => $width,
};
@endphp

<div class="relative" x-data="{ open: false }" x-init="$watch('open', value => window.setPanelScrollLock($el, value && window.innerWidth &lt; 1024))" @keydown.tab="if (open) window.trapPanelFocus($event, $el)" @resize.window="if (window.innerWidth >= 1024) window.setPanelScrollLock($el, false)" @click.outside="open = false" @close.stop="open = false" @keydown.escape="if (open) { $event.preventDefault(); $event.stopPropagation(); open = false; }">
    <div @click="if (@js($mobileSheet) && window.innerWidth < 1024) $dispatch('open-modal', @js($sheetName)); else open = ! open">
        {{ $trigger }}
    </div>

    @if ($mobileSheet)
        <x-modal :name="$sheetName" aria-label="Pilih cabang">
            <div class="p-5">
                <div class="mb-3 flex items-center justify-between"><h2 class="text-xl text-[var(--ink)]">Pilih cabang</h2><button type="button" class="min-h-11 min-w-11 text-2xl" @click="$dispatch('close')" aria-label="Tutup pemilih cabang">&times;</button></div>
                {{ $content }}
            </div>
        </x-modal>
    @endif

    <div x-show="open"
            @include('components.panel-transition')
            class="dropdown-panel absolute z-50 mt-2 {{ $width }} rounded-lg shadow-lg {{ $alignmentClasses }}"
            style="display: none;"
            @click="open = false">
        <div class="rounded-lg border border-[var(--line)] bg-[var(--surface)] {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
