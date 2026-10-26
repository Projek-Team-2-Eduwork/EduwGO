<section id="pertanyaan-umum" class="landing-section">
    <div class="landing-inner">
        <h2>Pertanyaan umum</h2>
        <div class="home-faq">
            <details><summary>Apa saja syarat menyewa motor?<span aria-hidden="true">+</span></summary><p>KTP asli dan SIM C aktif. Ketentuan lain ada di <a href="{{ route('terms') }}" class="home-text-link">syarat &amp; ketentuan</a>.</p></details>
            <details><summary>Bisa bayar pakai apa?<span aria-hidden="true">+</span></summary><p>{{ collect(\App\Services\XenditService::CHANNELS)->groupBy('category')->map(fn ($channels, $category) => $category.' ('.$channels->pluck('label')->join(', ').')')->join('; ') }}. Bisa juga bayar tunai di cabang saat mengambil motor.</p></details>
            <details><summary>Bagaimana mengecek ketersediaan motor?<span aria-hidden="true">+</span></summary><p>Pilih cabang, tanggal dan jam mulai, serta durasi sewa. Tekan “Cari motor” untuk melihat motor sesuai jadwal Anda.</p></details>
            <details><summary>Di mana motor diambil?<span aria-hidden="true">+</span></summary><p>Ambil motor di cabang yang Anda pilih saat memesan. Periksa alamat dan jam layanan sebelum datang.</p></details>
            <details><summary>Apa yang dilakukan saat serah-terima?<span aria-hidden="true">+</span></summary><p>Cek kondisi motor bersama petugas sebelum berangkat.</p></details>
        </div>
    </div>
</section>
