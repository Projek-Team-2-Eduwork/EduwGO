<x-app-layout>
    <x-slot:title>Tentang Kami</x-slot:title>
    @section('meta_description', $description)

    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="font-serif text-4xl sm:text-5xl">Tentang {{ $brand }}</h1>
        <p class="mt-4 text-sm leading-relaxed sm:text-base">{{ $description }}</p>

        @if ($branches->isEmpty())
            <dl class="card-surface mt-8 divide-y divide-[var(--border)]">
                <div class="p-5 sm:p-6">
                    <dt class="text-xs font-semibold uppercase tracking-wider" style="color: var(--orange-500);">Alamat</dt>
                    <dd class="mt-1 text-sm sm:text-base">{{ $address }}</dd>
                </div>
                <div class="p-5 sm:p-6">
                    <dt class="text-xs font-semibold uppercase tracking-wider" style="color: var(--orange-500);">Jam operasional</dt>
                    <dd class="mt-1 text-sm sm:text-base">{{ $hours }}</dd>
                </div>
            </dl>
        @else
            <div class="mt-8 space-y-4">
                @foreach ($branches as $branch)
                    <section class="card-surface p-5 sm:p-6" aria-labelledby="cabang-{{ $branch->slug }}">
                        <h2 id="cabang-{{ $branch->slug }}" class="text-lg">{{ $branch->name }}</h2>
                        <x-branch-info :branch="$branch" size="base" class="mt-4" />
                    </section>
                @endforeach
            </div>
        @endif

        <a href="{{ url('/') }}" class="btn-primary-edu mt-8 inline-flex items-center px-6 py-2.5 text-sm">Kembali ke Home</a>
    </div>
</x-app-layout>
