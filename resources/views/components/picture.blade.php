@props(['src'])
{{-- <img> dengan sumber AVIF bila tersedia (lihat avif_url); browser tanpa AVIF memakai src asli. --}}
<picture class="contents">
    @if ($avif = avif_url($src))
        <source type="image/avif" srcset="{{ $avif }}">
    @endif
    <img src="{{ asset($src) }}" {{ $attributes }}>
</picture>
