@props([
    'title',
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

<div {{ $attributes->merge(['class' => 'card-surface flex flex-col items-center px-6 py-12 text-center']) }}>
    <span class="inline-flex h-16 w-16 items-center justify-center rounded-full text-white" style="background-image: var(--accent-gradient);" aria-hidden="true">
        @if (isset($icon))
            {{ $icon }}
        @else
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13V7a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4m16 0h-4l-1 2H9l-1-2H4"/></svg>
        @endif
    </span>

    <h3 class="mt-5 font-serif text-2xl">{{ $title }}</h3>

    @if ($description)
        <p class="mt-2 max-w-md text-sm">{{ $description }}</p>
    @endif

    @if ($actionLabel && $actionHref)
        <a href="{{ $actionHref }}" class="btn-primary-edu mt-6 inline-flex items-center px-6 py-2.5 text-sm">{{ $actionLabel }}</a>
    @endif

    {{ $slot }}
</div>
