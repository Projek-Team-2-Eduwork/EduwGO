@props(['align' => 'right'])

@php
    $ctx = app(\App\Services\BranchContext::class);
    $branches = $ctx->active();
    $current = $ctx->current();
@endphp

@if ($current)
    <x-dropdown :align="$align" width="w-64">
        <x-slot name="trigger">
            <button type="button" {{ $attributes->class(['inline-flex max-w-full items-center gap-2 rounded-lg border border-[var(--line)] px-3 py-2 text-sm font-medium text-[var(--ink)] transition hover:bg-[var(--bg)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent)]']) }} style="background-color: var(--surface);" aria-haspopup="listbox" aria-label="Pilih cabang, saat ini {{ $current->name }}">
                <svg class="h-4 w-4 shrink-0 text-[var(--accent)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10zm0-8a2 2 0 100-4 2 2 0 000 4z"/></svg>
                <span class="truncate">{{ $current->name }}</span>
                @if ($branches->count() > 1)
                    <svg class="h-4 w-4 shrink-0 fill-current" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                @endif
            </button>
        </x-slot>

        <x-slot name="content">
            <p class="border-b border-[var(--line)] px-4 py-2 text-xs font-semibold uppercase tracking-wider text-[var(--ink-muted)]">Pilih cabang</p>
            @foreach ($branches as $branch)
                <x-dropdown-link :href="request()->fullUrlWithQuery(['cabang' => $branch->slug])" role="option" :aria-selected="$branch->is($current) ? 'true' : 'false'" class="{{ $branch->is($current) ? 'font-semibold !text-[var(--ink)]' : '' }}">
                    <span class="block">{{ $branch->name }}</span>
                    @if ($branch->address)
                        <span class="block truncate text-xs font-normal">{{ $branch->address }}</span>
                    @endif
                </x-dropdown-link>
            @endforeach
        </x-slot>
    </x-dropdown>
@endif
