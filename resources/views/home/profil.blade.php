<section id="tentang-penyewaan" class="landing-section">
    <div class="landing-inner">
        <h2>Lokasi cabang</h2>
        <div class="home-branches">
            @forelse($branches as $branch)
                <article>
                    <h3>{{ $branch->name }}</h3>
                    <div class="home-branch-details">
                        <p>{{ $branch->address ?: 'Alamat lokasi pengambilan akan diinformasikan oleh admin.' }}</p>
                        <p>{{ $branch->operating_hours ?: 'Konfirmasi jam layanan melalui WhatsApp.' }}</p>
                    </div>
                    <div class="home-branch-actions">
                        @if($branch->address)<a href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($branch->address) }}" target="_blank" rel="noopener" class="home-outline-link">Buka peta</a>@endif
                        @if($branch->whatsappUrl())<a href="{{ $branch->whatsappUrl() }}" target="_blank" rel="noopener" class="home-outline-link">WhatsApp</a>@endif
                    </div>
                </article>
            @empty
                <p class="home-branch-empty">Alamat lokasi pengambilan akan diinformasikan oleh admin.</p>
            @endforelse
        </div>
    </div>
</section>
