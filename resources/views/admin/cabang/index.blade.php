<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">Kelola Cabang</h2>
    </x-slot>

    <div class="py-6 sm:py-12" x-data="{ editMode: null, addOpen: {{ $errors->any() && ! old('_method') ? 'true' : 'false' }} }">
        <div class="mx-auto max-w-7xl space-y-6">
            @if(session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
            @if(session('error')) <x-alert type="error">{{ session('error') }}</x-alert> @endif
            @if($errors->any()) <x-alert type="error">{{ $errors->first() }}</x-alert> @endif

            <div class="card-surface p-4">
                <button type="button" @click="addOpen = ! addOpen" class="flex w-full items-center justify-between text-left text-sm font-semibold" :aria-expanded="addOpen.toString()">
                    <span>+ Tambah Cabang</span>
                    <svg class="h-4 w-4 transition" :class="addOpen ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="addOpen" x-cloak class="mt-4 border-t border-[var(--border)] pt-4">
                    @include('admin.cabang.partials.form', ['branch' => null, 'prefix' => 'new'])
                </div>
            </div>

            @if($cabang->isEmpty())
                <div class="card-surface">
                    <x-empty-state title="Belum ada cabang" description="Tambahkan cabang pertama lewat form di atas." />
                </div>
            @else
                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach($cabang as $tempat)
                        <article class="card-surface p-4 sm:p-5">
                            <div x-show="editMode !== {{ $tempat->id }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="truncate text-base font-bold text-[var(--navy-900)]">{{ $tempat->name }}</h3>
                                        <p class="mt-0.5 font-mono text-xs">{{ $tempat->slug }}</p>
                                    </div>
                                    <span class="shrink-0 rounded px-2 py-1 text-xs font-bold {{ $tempat->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $tempat->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>

                                <dl class="mt-3 space-y-1 text-sm">
                                    <div><dt class="sr-only">Alamat</dt><dd>{{ $tempat->address ?: '—' }}</dd></div>
                                    <div><dt class="sr-only">Kontak</dt><dd>{{ $tempat->phone ?: '—' }} &middot; {{ $tempat->operating_hours ?: '—' }}</dd></div>
                                    <div class="flex flex-wrap gap-x-3 text-xs">
                                        @forelse($tempat->socialLinks() as $name => $href)
                                            <a href="{{ $href }}" target="_blank" rel="noopener" class="underline-offset-4 hover:underline">{{ $name }}</a>
                                        @empty
                                            <span>Belum ada media sosial</span>
                                        @endforelse
                                    </div>
                                </dl>

                                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-[var(--border)] pt-3">
                                    <p class="text-xs">{{ $tempat->vehicles_count }} unit &middot; {{ $tempat->admins_count }} admin</p>
                                    <div class="flex gap-4">
                                        <button type="button" @click="editMode = {{ $tempat->id }}" class="text-sm font-medium text-[var(--orange-500)] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Edit</button>
                                        <form action="{{ route('admin.cabang.destroy', $tempat) }}" method="POST" onsubmit="return confirm('Hapus cabang ini?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-red-600 underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 dark:text-red-400">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div x-show="editMode === {{ $tempat->id }}" x-cloak>
                                @include('admin.cabang.partials.form', ['branch' => $tempat, 'prefix' => 'edit-'.$tempat->id, 'actions' => new \Illuminate\Support\HtmlString('<button type="button" @click="editMode = null" class="rounded px-3 py-1.5 text-sm underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Batal</button>')])
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
