@props(['items', 'title' => 'Syarat & ketentuan', 'intro' => null, 'heading' => 'h2'])
<div>
    <{{ $heading }} class="reveal text-2xl">{{ $title }}</{{ $heading }}>
    @if($intro)<p class="mt-3 text-sm">{{ $intro }}</p>@endif
    <ol class="mt-6 max-w-3xl list-decimal space-y-3 pl-5 text-sm leading-7">@foreach($items as $item)<li>{{ $item }}</li>@endforeach</ol>
    @isset($actions)<div class="mt-6 flex flex-wrap gap-3">{{ $actions }}</div>@endisset
</div>
