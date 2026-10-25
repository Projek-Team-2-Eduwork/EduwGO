@props([
    'title',
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
    'illustration' => 'vehicles',
])

<div {{ $attributes->merge(['class' => 'section-plain flex flex-col items-center px-6 py-12 text-center']) }}>
    <x-illustration :name="$illustration" />

    <h3 class="mt-5  text-2xl">{{ $title }}</h3>

    @if ($description)
        <p class="mt-2 max-w-md text-sm">{{ $description }}</p>
    @endif

    @if ($actionLabel && $actionHref)
        <a href="{{ $actionHref }}" class="btn-primary-edu mt-6 inline-flex items-center px-5 py-2.5 text-sm">{{ $actionLabel }}</a>
    @endif

    {{ $slot }}
</div>
