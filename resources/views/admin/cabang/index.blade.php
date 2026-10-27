<x-admin-layout title="Kelola Cabang">
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">Kelola Cabang</h2>
    </x-slot>

    <div class="py-2 sm:py-6" x-data="{ editMode: null, addOpen: {{ $errors->any() && ! old('_method') ? 'true' : 'false' }} }">
        <div class="mx-auto max-w-7xl space-y-6">
            @if(session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
            @if(session('error')) <x-alert type="error">{{ session('error') }}</x-alert> @endif
            @if($errors->any()) <x-alert type="error">{{ $errors->first() }}</x-alert> @endif

            <div class="section-plain p-4">
                <button type="button" @click="addOpen = ! addOpen" class="flex w-full items-center justify-between text-left text-sm font-semibold" :aria-expanded="addOpen.toString()">
                    <span>+ Tambah Cabang</span>
                    <svg class="h-4 w-4 transition" :class="addOpen ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="addOpen" x-cloak class="mt-4 border-t border-[var(--line)] pt-4">
                    @include('admin.cabang.partials.form', ['branch' => null, 'prefix' => 'new'])
                </div>
            </div>

            @if($cabang->isEmpty())
                <div class="section-plain">
                    <x-empty-state illustration="locations" title="Belum ada cabang" description="Tambahkan cabang pertama lewat form di atas." />
                </div>
            @else
                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach($cabang as $tempat)
                        <article class="section-plain p-4 sm:p-5">
                            <div x-show="editMode !== {{ $tempat->id }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="truncate text-base font-bold text-[var(--ink)]">{{ $tempat->name }}</h3>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $tempat->is_active ? 'bg-[var(--success-soft)] text-[var(--success)] dark:bg-[var(--success-soft)] dark:text-[var(--success)]' : 'bg-[var(--bg)] text-[var(--ink)] dark:bg-[var(--bg)] dark:text-[var(--ink-muted)]' }}">
                                        {{ $tempat->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>

                                <dl class="mt-3 space-y-1 text-sm">
                                    <div><dt class="sr-only">Alamat</dt><dd>{{ $tempat->address ?: '—' }}</dd></div>
                                    <div><dt class="sr-only">Kontak</dt><dd>{{ $tempat->phone ?: '—' }} &middot; {{ $tempat->operating_hours ?: '—' }}</dd></div>
                                    <div class="flex flex-wrap gap-x-3 text-xs">
                                        @forelse($tempat->socialLinks() as $name => $href)
                                            <a href="{{ $href }}" target="_blank" rel="noopener" class="text-[var(--accent)] underline-offset-4 hover:underline">{{ $name }}</a>
                                        @empty
                                            <span>Belum ada media sosial</span>
                                        @endforelse
                                    </div>
                                </dl>

                                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-[var(--line)] pt-3">
                                    <p class="text-xs">{{ $tempat->vehicles_count }} unit &middot; {{ $tempat->admins_count }} admin</p>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="editMode = {{ $tempat->id }}" class="btn-secondary-edu inline-flex items-center px-4 text-sm">Ubah</button>
                                        {{-- Hapus = aksi berbahaya: disembunyikan di menu, tetap minta konfirmasi --}}
                                        <details class="relative">
                                            <summary class="btn-secondary-edu inline-flex min-w-11 cursor-pointer list-none items-center justify-center px-3 text-sm" aria-label="Aksi lain {{ $tempat->name }}">&#8943;</summary>
                                            <div class="card-surface absolute end-0 z-20 mt-1 w-48 p-1">
                                                <form action="{{ route('admin.cabang.destroy', $tempat) }}" method="POST" onsubmit="return confirm('Hapus cabang {{ $tempat->name }}? Tindakan ini tidak bisa dibatalkan.');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="w-full rounded-md px-3 text-left text-sm font-medium text-[var(--danger)] hover:bg-[var(--danger-soft)]">Hapus cabang</button>
                                                </form>
                                            </div>
                                        </details>
                                    </div>
                                </div>
                            </div>

                            <div x-show="editMode === {{ $tempat->id }}" x-cloak>
                                @include('admin.cabang.partials.form', ['branch' => $tempat, 'prefix' => 'edit-'.$tempat->id, 'actions' => new \Illuminate\Support\HtmlString('<button type="button" @click="editMode = null" class="rounded px-3 py-1.5 text-sm underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent)]">Batal</button>')])
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
