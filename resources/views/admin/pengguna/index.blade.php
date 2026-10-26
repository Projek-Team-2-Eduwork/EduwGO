<x-admin-layout title="Daftar Pengguna">
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">Daftar Pengguna</h2>
    </x-slot>

    <div class="py-2 sm:py-6">
        <div class="max-w-7xl mx-auto space-y-6">
            @if(session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
            @if(session('error')) <x-alert type="error">{{ session('error') }}</x-alert> @endif

            {{-- Tab: Staf dan Penyewa dikelola terpisah --}}
            <nav class="flex gap-1 border-b border-[var(--line)]" aria-label="Jenis pengguna">
                @foreach(['staf' => 'Staf', 'penyewa' => 'Penyewa'] as $key => $label)
                    <a href="{{ route('admin.pengguna.index', ['tab' => $key]) }}" @if($tab === $key) aria-current="page" @endif
                       class="-mb-px inline-flex items-center gap-2 border-b-2 px-4 text-sm font-semibold {{ $tab === $key ? 'border-[var(--accent)] text-[var(--ink)]' : 'border-transparent hover:text-[var(--ink)]' }}">
                        {{ $label }} <span class="rounded-full bg-[var(--bg)] px-2 text-xs">{{ $counts[$key] }}</span>
                    </a>
                @endforeach
            </nav>

            @if($tab === 'staf')
                @include('admin.pengguna.partials.create-admin')
            @endif

            <form method="GET" action="{{ route('admin.pengguna.index') }}" class="flex gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <x-text-input name="q" value="{{ request('q') }}" placeholder="Cari nama / email / telepon {{ $tab }}" aria-label="Cari {{ $tab }}" class="w-full md:w-1/3" />
                <x-primary-button class="h-11">Cari</x-primary-button>
            </form>

            <div class="section-plain overflow-hidden">
                @if($users->isEmpty())
                    <x-empty-state illustration="users" title="Tidak ada {{ $tab }}" description="Tidak ada {{ $tab }} yang cocok." />
                @elseif($tab === 'staf')
                    @include('admin.pengguna.partials.staff-table')
                @else
                    @include('admin.pengguna.partials.renter-table')
                @endif

                <div class="border-t border-[var(--line)] p-4">
                    {{ $users->links('components.pagination') }}
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
