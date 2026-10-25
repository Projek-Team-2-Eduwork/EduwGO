<x-app-layout title="Tentang">
    <x-slot:title>Tentang Kami</x-slot:title>
    @section('meta_description', $description)

    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="reveal font-serif text-4xl sm:text-5xl">Tentang {{ $brand }}</h1>
        <p class="mt-4 text-sm leading-relaxed sm:text-base">{{ $description }}</p>

        @if ($branches->isEmpty())
            <dl class="section-plain mt-8 divide-y divide-[var(--line)]">
                <div class="p-5 sm:p-6">
                    <dt class="text-xs font-semibold uppercase tracking-wider" style="color: var(--accent);">Alamat</dt>
                    <dd class="mt-1 text-sm sm:text-base">{{ $address }}</dd>
                </div>
                <div class="p-5 sm:p-6">
                    <dt class="text-xs font-semibold uppercase tracking-wider" style="color: var(--accent);">Jam operasional</dt>
                    <dd class="mt-1 text-sm sm:text-base">{{ $hours }}</dd>
                </div>
            </dl>
        @else
            <div class="reveal-group mt-8 space-y-4">
                @foreach ($branches as $branch)
                    <section class="reveal section-plain p-5 sm:p-6" aria-labelledby="cabang-{{ $branch->slug }}">
                        <h2 id="cabang-{{ $branch->slug }}" class="text-lg">{{ $branch->name }}</h2>
                        <x-branch-info :branch="$branch" size="base" class="mt-4" />
                    </section>
                @endforeach
            </div>
        @endif

        <a href="{{ url('/') }}" class="btn-primary-edu mt-8 inline-flex items-center px-5 py-2.5 text-sm">Kembali ke beranda</a>
    </div>
</x-app-layout>
