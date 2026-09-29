<x-app-layout>
    <x-slot:title>Tentang Kami</x-slot:title>
    @section('meta_description', $description)

    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="font-serif text-4xl sm:text-5xl">Tentang {{ $brand }}</h1>
        <p class="mt-4 text-sm leading-relaxed sm:text-base">{{ $description }}</p>

        <dl class="card-surface mt-8 divide-y divide-[var(--border)]">
            <div class="p-5 sm:p-6">
                <dt class="text-xs font-semibold uppercase tracking-wider" style="color: var(--orange-500);">Alamat</dt>
                <dd class="mt-1 text-sm sm:text-base">{{ $address }}</dd>
            </div>
            <div class="p-5 sm:p-6">
                <dt class="text-xs font-semibold uppercase tracking-wider" style="color: var(--orange-500);">Jam operasional</dt>
                <dd class="mt-1 text-sm sm:text-base">{{ $hours }}</dd>
            </div>
            @if ($socials->isNotEmpty() || $waUrl)
                <div class="p-5 sm:p-6">
                    <dt class="text-xs font-semibold uppercase tracking-wider" style="color: var(--orange-500);">Media sosial</dt>
                    <dd class="mt-2 flex flex-wrap gap-3">
                        @foreach ($socials as $label => $href)
                            <a href="{{ $href }}" target="_blank" rel="noopener" class="btn-secondary-edu inline-flex items-center px-4 py-2 text-sm">{{ $label }}</a>
                        @endforeach
                        @if ($waUrl)
                            <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn-secondary-edu inline-flex items-center px-4 py-2 text-sm">WhatsApp</a>
                        @endif
                    </dd>
                </div>
            @endif
        </dl>

        <a href="{{ url('/') }}" class="btn-primary-edu mt-8 inline-flex items-center px-6 py-2.5 text-sm">Kembali ke Home</a>
    </div>
</x-app-layout>
