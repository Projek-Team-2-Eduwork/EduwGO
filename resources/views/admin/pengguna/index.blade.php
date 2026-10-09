<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Daftar Pengguna</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success')) <div class="p-4 bg-green-100 text-green-800 rounded-lg shadow-sm">{{ session('success') }}</div> @endif
            @if(session('error')) <div class="p-4 bg-red-100 text-red-800 rounded-lg shadow-sm">{{ session('error') }}</div> @endif

            <!-- Pencarian -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                <form method="GET" action="{{ route('admin.pengguna.index') }}" class="flex gap-2">
                    <x-text-input name="q" value="{{ request('q') }}" placeholder="Cari Nama / Email / Telepon" class="w-full md:w-1/3" />
                    <x-primary-button>Cari</x-primary-button>
                </form>
            </div>

            <!-- List Pengguna -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
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
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 {{ !$user->is_active ? 'opacity-60' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900 dark:text-gray-100">{{ $user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ $user->phone ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-xs">{{ $user->created_at->translatedFormat('d M Y') }}</td>
                                    <td class="px-4 py-3 text-center font-bold">{{ $user->bookings_count }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs font-bold rounded {{ $user->hasRole('admin') ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $user->hasRole('admin') ? 'Admin' : 'Penyewa' }}
                                        </span>
                                        @if(!$user->is_active)
                                            <span class="ml-1 px-2 py-1 text-xs font-bold rounded bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-3" x-data>
                                            <form action="{{ route('admin.pengguna.toggle', $user->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-xs font-medium {{ $user->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}">
                                                    {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.pengguna.role', $user->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="role" value="{{ $user->hasRole('admin') ? 'user' : 'admin' }}">
                                                <button type="submit" onclick="return confirm('Ubah role pengguna ini?')" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
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
                <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($users as $user)
                        <div class="p-4 {{ !$user->is_active ? 'opacity-60 bg-gray-50 dark:bg-gray-900' : '' }}">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-gray-100">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->email }} &bull; {{ $user->phone ?? '-' }}</div>
                                </div>
                                <span class="px-2 py-0.5 text-xs font-bold rounded {{ $user->hasRole('admin') ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $user->hasRole('admin') ? 'Admin' : 'Penyewa' }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center mt-4">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $user->bookings_count }} Booking</span>
                                <div class="flex gap-3">
                                    <form action="{{ route('admin.pengguna.toggle', $user->id) }}" method="POST">
                                        @csrf
                                        <button class="text-sm font-medium {{ $user->is_active ? 'text-red-600' : 'text-green-600' }}">
                                            {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.pengguna.role', $user->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="role" value="{{ $user->hasRole('admin') ? 'user' : 'admin' }}">
                                        <button class="text-sm font-medium text-indigo-600 dark:text-indigo-400" onclick="return confirm('Ubah role pengguna ini?')">
                                            Jadikan {{ $user->hasRole('admin') ? 'User' : 'Admin' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>