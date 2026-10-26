<x-app-layout title="Sewa motor harian">
    @include('home.styles')
    <div class="home-landing">
        @include('home.hero')
        @include('home.catalog')
        <section class="landing-section home-information-band" aria-label="Fasilitas dan syarat sewa">
            <div class="landing-inner home-information-grid">
                @include('home.facts')
                @include('home.terms')
            </div>
        </section>
        @include('home.profil')
        @include('home.steps')
        @include('home.faq')
    </div>
</x-app-layout>
