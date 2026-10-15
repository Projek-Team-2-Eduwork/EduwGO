@props(['count' => 8])

{{-- Placeholder grid kendaraan saat filter diterapkan; bentuk meniru <x-vehicle-card> --}}
<div {{ $attributes->merge(['class' => 'grid gap-4 sm:grid-cols-2 lg:grid-cols-4']) }} role="status">
    <span class="sr-only">Memuat kendaraan...</span>

    @for ($i = 0; $i < $count; $i++)
        <div class="card-surface flex flex-col p-4" aria-hidden="true">
            <div class="skeleton h-4 w-3/4"></div>
            <div class="skeleton mt-2 h-3 w-1/3"></div>
            <div class="skeleton my-3 aspect-[4/3] w-full"></div>
            <div class="flex items-center justify-between gap-2">
                <div class="skeleton h-3 w-1/3"></div>
                <div class="skeleton h-3 w-1/4"></div>
            </div>
            <div class="mt-3 flex items-center justify-between gap-3">
                <div class="skeleton h-5 w-1/3"></div>
                <div class="skeleton h-8 w-20"></div>
            </div>
        </div>
    @endfor
</div>
