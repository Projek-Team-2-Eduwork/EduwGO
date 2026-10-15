<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">Daftar Pengguna</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6">
            @if(session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
            @if(session('error')) <x-alert type="error">{{ session('error') }}</x-alert> @endif

            <!-- Pencarian -->
            <div class="card-surface p-4">
                <form method="GET" action="{{ route('admin.pengguna.index') }}" class="flex gap-2">
                    <x-text-input name="q" value="{{ request('q') }}" placeholder="Cari Nama / Email / Telepon" aria-label="Cari pengguna" class="w-full md:w-1/3" />
                    <x-primary-button>Cari</x-primary-button>
                </form>
            </div>

            <!-- List Pengguna -->
            <div class="card-surface overflow-hidden">
                @if($users->isEmpty())
                    <x-empty-state title="Tidak ada pengguna" description="Belum ada pengguna yang terdaftar." />
                @else
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-[var(--bg)] text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3">Nama & Kontak</th>
                                <th class="px-4 py-3">Terdaftar</th>
                                <th class="px-4 py-3 text-center">Booking</th>
                                <th class="px-4 py-3">Role & Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr class="border-b border-[var(--border)] transition hover:bg-[var(--bg)] {{ !$user->is_active ? 'opacity-60' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-[var(--navy-900)]">{{ $user->name }}</div>
                                        <div class="text-xs">{{ $user->email }}</div>
                                        <div class="mt-0.5 text-xs">{{ $user->phone ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-xs">{{ $user->created_at->translatedFormat('d M Y') }}</td>
                                    <td class="px-4 py-3 text-center font-bold">{{ $user->bookings_count }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded px-2 py-1 text-xs font-bold {{ $user->hasRole('admin') ? 'bg-[var(--coral-300)] text-[var(--navy-900)]' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $user->hasRole('admin') ? 'Admin' : 'Penyewa' }}
                                        </span>
                                        @if(!$user->is_active)
                                            <span class="ml-1 rounded bg-red-100 px-2 py-1 text-xs font-bold text-red-800 dark:bg-red-900/50 dark:text-red-300">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-3" x-data>
                                            <form action="{{ route('admin.pengguna.toggle', $user->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-xs font-medium underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)] {{ $user->is_active ? 'text-red-600 dark:text-red-400' : 'text-emerald-700 dark:text-emerald-400' }}">
                                                    {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.pengguna.role', $user->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="role" value="{{ $user->hasRole('admin') ? 'user' : 'admin' }}">
                                                <button type="submit" onclick="return confirm('Ubah role pengguna ini?')" class="text-xs font-medium text-[var(--orange-500)] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">
                                                    Jadikan {{ $user->hasRole('admin') ? 'User' : 'Admin' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card Layout -->
                <div class="divide-y divide-[var(--border)] md:hidden">
                    @foreach($users as $user)
                        <div class="p-4 {{ !$user->is_active ? 'bg-[var(--bg)] opacity-60' : '' }}">
                            <div class="mb-2 flex items-start justify-between">
                                <div>
                                    <div class="font-bold text-[var(--navy-900)]">{{ $user->name }}</div>
                                    <div class="text-xs">{{ $user->email }} &bull; {{ $user->phone ?? '-' }}</div>
                                </div>
                                <span class="rounded px-2 py-0.5 text-xs font-bold {{ $user->hasRole('admin') ? 'bg-[var(--coral-300)] text-[var(--navy-900)]' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $user->hasRole('admin') ? 'Admin' : 'Penyewa' }}
                                </span>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-sm font-semibold">{{ $user->bookings_count }} Booking</span>
                                <div class="flex gap-3">
                                    <form action="{{ route('admin.pengguna.toggle', $user->id) }}" method="POST">
                                        @csrf
                                        <button class="text-sm font-medium underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)] {{ $user->is_active ? 'text-red-600 dark:text-red-400' : 'text-emerald-700 dark:text-emerald-400' }}">
                                            {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.pengguna.role', $user->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="role" value="{{ $user->hasRole('admin') ? 'user' : 'admin' }}">
                                        <button class="text-sm font-medium text-[var(--orange-500)] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]" onclick="return confirm('Ubah role pengguna ini?')">
                                            Jadikan {{ $user->hasRole('admin') ? 'User' : 'Admin' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
                
                <div class="border-t border-[var(--border)] p-4">
                    {{ $users->links('components.pagination') }}
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>