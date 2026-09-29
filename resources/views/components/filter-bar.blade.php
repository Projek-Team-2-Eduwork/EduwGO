@props(['action' => null, 'method' => 'GET'])

{{-- Bar filter: isi kolom lewat slot (form/input, form/select, dst). Tombol aksi lewat slot "actions". --}}
<form
    @if ($action) action="{{ $action }}" @endif
    method="{{ $method }}"
    {{ $attributes->merge(['class' => 'card-surface p-4']) }}
>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
        {{ $slot }}

        @isset($actions)
            <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-1">
                {{ $actions }}
            </div>
        @endisset
    </div>
</form>
