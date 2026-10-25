@php
    $steps = [
        ['title' => 'Pilih motor dan jadwal', 'text' => 'Cari motor di cabang pilihan Anda sesuai waktu dan durasi sewa.'],
        ['title' => 'Konfirmasi dan bayar', 'text' => 'Periksa pesanan Anda, lalu selesaikan pembayaran online.'],
        ['title' => 'Ambil di cabang', 'text' => 'Datang sesuai jadwal, bawa identitas, dan cek kondisi motor bersama petugas.'],
    ];
@endphp
<section id="cara-sewa" class="landing-section">
    <div class="landing-inner">
        <h2 class="reveal text-2xl">Cara sewa</h2>
        <ol class="reveal-group mt-6 grid gap-8 md:grid-cols-3">
            @foreach($steps as $step)
                <li class="reveal flex items-start gap-4">
                    <span class="text-3xl font-semibold leading-none text-[var(--accent)]" aria-hidden="true">{{ $loop->iteration }}</span>
                    <div><h3 class="text-base">{{ $step['title'] }}</h3><p class="mt-2 text-sm leading-6">{{ $step['text'] }}</p></div>
                </li>
            @endforeach
        </ol>
    </div>
</section>
