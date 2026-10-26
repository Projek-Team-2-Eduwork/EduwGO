<section id="cara-sewa" class="landing-section home-steps-band">
    <div class="landing-inner">
        <h2>Cara sewa</h2>
        <ol class="home-steps">
            @foreach(['Pilih motor & jadwal' => 'Tentukan cabang, tanggal, dan durasi sewa.', 'Bayar' => 'Bayar online atau tunai di cabang saat ambil motor.', 'Ambil di cabang' => 'Bawa KTP asli dan SIM C sesuai jadwal.'] as $title => $text)
                <li><span aria-hidden="true">{{ $loop->iteration }}</span><div><h3>{{ $title }}</h3><p>{{ $text }}</p></div></li>
            @endforeach
        </ol>
        <a href="#hero-search" class="home-steps-cta">Cari motor</a>
    </div>
</section>
