@props(['name' => 'filters'])
<button type="button" x-data x-on:click="$dispatch('open-modal', @js($name))" class="btn-secondary-edu mb-4 inline-flex items-center lg:hidden" aria-haspopup="dialog">Filter</button>
<x-modal :name="$name" :inline-desktop="true" aria-label="Filter">
    <div class="p-5 lg:p-0">
        <div class="mb-5 flex items-center justify-between lg:hidden"><h2 class="text-lg">Filter</h2><button type="button" class="min-h-11 min-w-11" x-on:click="$dispatch('close')" aria-label="Tutup filter">&times;</button></div>
        {{ $slot }}
    </div>
</x-modal>
