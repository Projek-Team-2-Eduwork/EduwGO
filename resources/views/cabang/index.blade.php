<x-app-layout title="Cabang Kami">
    <div class="mx-auto max-w-[1320px] space-y-6 px-4 pt-8 sm:px-6 lg:pt-12 xl:px-0">
        <div>
            <h1 class="reveal text-2xl">Cabang Kami</h1>
            <p class="mt-2 text-sm">Pilih cabang untuk melihat motor yang tersedia di sana.</p>
        </div>

        @if ($cabang->isEmpty())
            <x-empty-state illustration="locations" title="Belum ada cabang" description="Cabang penyewaan akan muncul di sini setelah diaktifkan." />
        @else
            <div class="reveal-group grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($cabang as $tempat)
                    <a href="{{ route('cabang.show', $tempat->slug) }}" class="reveal section-plain group flex flex-col gap-2 p-5 transition duration-150  ">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="text-base font-bold group-hover:underline">{{ $tempat->name }}</h2>
                            <span class="shrink-0 rounded-full bg-[var(--bg)] px-2.5 py-1 text-xs font-semibold">{{ $tempat->units_count }} unit aktif</span>
                        </div>
                        @if ($tempat->address)
                            <p class="text-sm">{{ $tempat->address }}</p>
                        @endif
                        <p class="mt-auto flex flex-wrap items-center gap-2 text-xs">
                            @if ($tempat->phone)
                                <span>{{ $tempat->phone }}</span>
                            @endif
                            @if ($tempat->operating_hours)
                                @if ($tempat->phone) <span aria-hidden="true">&middot;</span> @endif
                                <span>{{ $tempat->operating_hours }}</span>
                            @endif
                        </p>
                        @if ($currentBranch && $currentBranch->is($tempat))
                            <span class="text-xs font-semibold" style="color: var(--accent);">Cabang pilihan Anda</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
