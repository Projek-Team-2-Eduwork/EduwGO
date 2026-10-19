@props(['branches'])

@php
    // Angka di bawah harus sejalan dengan .cabang-marquee-kartu di resources/css/app.css
    $lebarKartu = 288; // 18rem
    $jarakKartu = 16;  // 1rem

    $jumlah = max(1, $branches->count());

    // Daftar cabang diulang per paruh supaya paruh pertama selalu lebih lebar dari
    // wadahnya (maks 1320px), jadi animasi menggeser tidak pernah menyisakan ruang kosong
    // walau cabang aktifnya cuma satu.
    $ulangan = max(1, (int) ceil(1320 / ($jumlah * ($lebarKartu + $jarakKartu))));

    // Kecepatan dibuat tetap (~45 piksel/detik) berapa pun jumlah cabangnya.
    $durasi = max(24, (int) round($jumlah * $ulangan * ($lebarKartu + $jarakKartu) / 45));
@endphp

{{-- Kartu cabang berjalan: dua paruh identik, animasi CSS menggeser tepat setengah trek. --}}
<div {{ $attributes->class('cabang-marquee') }} style="--cabang-marquee-durasi: {{ $durasi }}s">
    <div class="cabang-marquee-track">
        @foreach ([false, true] as $duplikat)
            @foreach (range(1, $ulangan) as $ulang)
                @foreach ($branches as $branch)
                    <div class="cabang-marquee-kartu" @if ($duplikat) aria-hidden="true" inert @endif>
                        <p class="text-sm font-semibold" style="color: var(--navy-900);">{{ $branch->name }}</p>
                        <x-branch-info :branch="$branch" class="mt-3" />
                    </div>
                @endforeach
            @endforeach
        @endforeach
    </div>
</div>
