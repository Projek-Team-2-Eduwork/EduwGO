@props(['name' => 'filters'])
<button type="button" x-data x-on:click="$dispatch('open-modal', @js($name))" class="btn-secondary-edu mb-4 inline-flex w-full items-center justify-center gap-2 lg:hidden sm:w-auto" aria-haspopup="dialog">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M4 6h16M7 12h10m-7 6h4" />
    </svg>
    Filter
</button>
<x-modal :name="$name" :inline-desktop="true" aria-label="Filter">
    <div class="p-5 lg:p-0">
        <div class="mb-5 flex items-center justify-between lg:hidden"><h2 class="text-lg">Filter</h2><button type="button" class="min-h-11 min-w-11" x-on:click="$dispatch('close')" aria-label="Tutup filter">&times;</button></div>
        {{ $slot }}
    </div>
</x-modal>
