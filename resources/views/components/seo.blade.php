@props([
    'title' => null,
    'description' => null,
    'image' => null,
])

{{--
    Meta SEO dasar: <title>, description, Open Graph, Twitter card, favicon.
    Dipakai di layouts/partials/head.blade.php. Nilai bisa diberikan lewat props,
    atau dari view halaman lewat @section('meta_title' | 'meta_description' | 'meta_image').
    Bila kosong, dipakai default dari Pengaturan Toko / config.
--}}
@php
    $clean = fn ($value) => trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES));

    $brand = setting('brand.name', config('app.name', 'EduwGo'));
    $pageTitle = $clean($title) ?: $clean($__env->yieldContent('meta_title'));
    $fullTitle = $pageTitle ? $pageTitle.' — '.$brand : $brand;

    $metaDescription = $clean($description)
        ?: $clean($__env->yieldContent('meta_description'))
        ?: $clean(setting('brand.description', $brand.' menyediakan rental motor cepat, aman, dan terjangkau. Pilih motor, tentukan durasi, bayar online, lalu ambil di lokasi.'));

    $metaImage = $clean($image) ?: $clean($__env->yieldContent('meta_image')) ?: $clean(setting('brand.og_image') ?: setting('brand.logo_light'));
    if ($metaImage && ! preg_match('#^https?://#i', $metaImage)) {
        $metaImage = url($metaImage);
    }

    $favicon = setting('brand.favicon');
@endphp

<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $brand }}">
<meta property="og:locale" content="id_ID">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ url()->current() }}">
@if ($metaImage)
    <meta property="og:image" content="{{ $metaImage }}">
@endif

<meta name="twitter:card" content="{{ $metaImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
@if ($metaImage)
    <meta name="twitter:image" content="{{ $metaImage }}">
@endif

@if ($favicon)
    <link rel="icon" href="{{ $favicon }}">
@endif
