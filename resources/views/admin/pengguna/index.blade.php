<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">Daftar Pengguna</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6">
            @if(session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
            @if(session('error')) <x-alert type="error">{{ session('error') }}</x-alert> @endif

            <!-- Buat admin cabang baru -->
            <div class="card-surface p-4" x-data="{ open: {{ $errors->any() && old('_form') === 'admin' ? 'true' : 'false' }} }">
                <button type="button" @click="open = ! open" class="flex w-full items-center justify-between text-left text-sm font-semibold" :aria-expanded="open.toString()">
                    <span>+ Buat Admin Cabang</span>
                    <svg class="h-4 w-4 transition" :class="open ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <form x-show="open" x-cloak action="{{ route('admin.pengguna.store') }}" method="POST" class="mt-4 grid gap-4 border-t border-[var(--border)] pt-4 sm:grid-cols-2">
                    @csrf
                    <input type="hidden" name="_form" value="admin">
                    <div>
                        <x-input-label for="new-name" value="Nama *" />
                        <x-text-input id="new-name" name="name" type="text" class="mt-1 w-full" required :value="old('name')" />
                        <x-input-error class="mt-1" :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <x-input-label for="new-email" value="Email *" />
                        <x-text-input id="new-email" name="email" type="email" class="mt-1 w-full" required :value="old('email')" />
                        <x-input-error class="mt-1" :messages="$errors->get('email')" />
                    </div>
                    <div>
                        <x-input-label for="new-phone" value="Nomor WhatsApp" />
                        <x-text-input id="new-phone" name="phone" type="text" inputmode="tel" class="mt-1 w-full" :value="old('phone')" />
                        <x-input-error class="mt-1" :messages="$errors->get('phone')" />
                    </div>
                    <div>
                        <x-input-label for="new-branch" value="Cabang *" />
                        <select id="new-branch" name="branch_id" required class="mt-1 block w-full rounded-lg border border-[var(--border)] bg-[var(--surface)] shadow-sm">
                            <option value="">Pilih cabang</option>
                            @foreach($branches as $id => $name)
                                <option value="{{ $id }}" @selected(old('branch_id') == $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-1" :messages="$errors->get('branch_id')" />
                    </div>
                    <div>
                        <x-input-label for="new-password" value="Password * (min. 8 karakter)" />
                        <x-text-input id="new-password" name="password" type="password" autocomplete="new-password" class="mt-1 w-full" required />
                        <x-input-error class="mt-1" :messages="$errors->get('password')" />
                    </div>
                    <div>
                        <x-input-label for="new-password-confirmation" value="Konfirmasi Password *" />
                        <x-text-input id="new-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-1 w-full" required />
                    </div>
                    <div class="sm:col-span-2">
                        <x-primary-button class="px-5 py-2.5">Buat Admin</x-primary-button>
                    </div>
                </form>
            </div>

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
                                        @include('admin.pengguna.partials.role-badge', ['user' => $user])
                                        @if(!$user->is_active)
                                            <span class="ml-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-200">Nonaktif</span>
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
                                            @include('admin.pengguna.partials.role-form', ['user' => $user, 'branches' => $branches])
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
                                @include('admin.pengguna.partials.role-badge', ['user' => $user])
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
                                    @include('admin.pengguna.partials.role-form', ['user' => $user, 'branches' => $branches])
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