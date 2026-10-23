@php
    $steps = [
        ['title' => 'Pilih Motor', 'text' => 'Telusuri katalog dan pilih motor yang sesuai kebutuhan perjalanan Anda.', 'icon' => 'M5 17a2 2 0 104 0 2 2 0 00-4 0zm10 0a2 2 0 104 0 2 2 0 00-4 0zM7 17h8M3 17h2m14 0h2M6 9h6l3 5M12 9V6h3'],
        ['title' => 'Pilih Durasi & Bayar', 'text' => 'Tentukan lama sewa lalu bayar online lewat VA, QRIS, e-wallet, atau retail.', 'icon' => 'M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['title' => 'Ambil di Lokasi', 'text' => 'Konfirmasi via WhatsApp, bawa identitas, cek kondisi motor, lalu berangkat.', 'icon' => 'M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
    ];
@endphp

<section id="cara-sewa" class="mx-auto max-w-[1320px] px-4 pt-12 sm:px-6 lg:pt-20 xl:px-0">
    <h2 class="text-2xl">Mulai Perjalanan Anda dalam 3 Langkah</h2>
    <p class="mt-2 text-sm">Tidak perlu repot! Proses penyewaan motor tercepat, paling aman, dan transparan.</p>

    <ol class="mt-6 grid gap-4 md:grid-cols-3">
        @foreach ($steps as $i => $step)
            <li class="card-surface flex items-start gap-4 p-5">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-white" style="background-color: var(--orange-500);" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $step['icon'] }}"/></svg>
                </span>
                <div>
                    <h3 class="text-sm">{{ $i + 1 }}. {{ $step['title'] }}</h3>
                    <p class="mt-1 text-xs leading-relaxed">{{ $step['text'] }}</p>
                </div>
            </li>
        @endforeach
    </ol>
</section>
