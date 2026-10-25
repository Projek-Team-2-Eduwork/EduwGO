@php($statusBadge = fn ($user) => $user->is_active
    ? '<span class="rounded-full bg-[var(--success-soft)] px-2.5 py-0.5 text-xs font-semibold text-[var(--success)]">Aktif</span>'
    : '<span class="rounded-full bg-[var(--danger-soft)] px-2.5 py-0.5 text-xs font-semibold text-[var(--danger)]">Nonaktif</span>')

<div class="hidden overflow-x-auto md:block">
    <table class="w-full text-left text-sm">
        <thead class="bg-[var(--bg)] text-xs uppercase">
            <tr>
                <th class="px-4 py-3">Nama</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Role</th>
                <th class="px-4 py-3">Cabang</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    <td class="px-4 py-2"><a href="{{ route('admin.pengguna.show', $user) }}" class="font-semibold text-[var(--ink)] hover:underline">{{ $user->name }}</a></td>
                    <td class="px-4 py-2 text-xs">{{ $user->email }}</td>
                    <td class="px-4 py-2">@include('admin.pengguna.partials.role-badge', ['user' => $user, 'showBranch' => false])</td>
                    <td class="px-4 py-2 text-xs">{{ $user->isSuperadmin() ? 'Semua cabang' : ($user->branch?->name ?? 'Belum ada cabang') }}</td>
                    <td class="px-4 py-2">{!! $statusBadge($user) !!}</td>
                    <td class="px-4 py-2 text-right">
                        @if($user->is(auth()->user()))
                            <span class="text-xs">Akun Anda</span>
                        @else
                            <button type="button" x-data @click="$dispatch('open-modal', 'staf-{{ $user->id }}')" class="text-sm font-medium text-[var(--accent)] hover:underline">Ubah</button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<ul class="divide-y divide-[var(--line)] md:hidden">
    @foreach($users as $user)
        <li class="flex items-center gap-3 p-4">
            <a href="{{ route('admin.pengguna.show', $user) }}" class="block min-w-0 flex-1">
                <span class="block truncate font-semibold text-[var(--ink)]">{{ $user->name }}</span>
                <span class="block truncate text-xs">{{ $user->email }}</span>
                <span class="mt-1 flex flex-wrap items-center gap-1">@include('admin.pengguna.partials.role-badge', ['user' => $user]) {!! $statusBadge($user) !!}</span>
            </a>
            @unless($user->is(auth()->user()))
                <button type="button" x-data @click="$dispatch('open-modal', 'staf-{{ $user->id }}')" class="shrink-0 px-2 text-sm font-medium text-[var(--accent)]">Ubah</button>
            @endunless
        </li>
    @endforeach
</ul>

{{-- Panel Ubah staf: role, cabang, status aktif --}}
@foreach($users as $user)
    @continue($user->is(auth()->user()))
    <x-modal name="staf-{{ $user->id }}" maxWidth="md" aria-label="Ubah staf {{ $user->name }}">
        <div class="space-y-6 p-6">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-semibold">{{ $user->name }}</h2>
                    <p class="truncate text-xs">{{ $user->email }}</p>
                </div>
                <button type="button" x-on:click="$dispatch('close')" class="min-h-11 min-w-11 text-xl" aria-label="Tutup">&times;</button>
            </div>

            @include('admin.pengguna.partials.role-form', ['user' => $user, 'branches' => $branches])

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[var(--line)] pt-4">
                <form action="{{ route('admin.pengguna.reset-password', $user) }}" method="POST">
                    @csrf
                    <button class="btn-secondary-edu inline-flex items-center px-4 text-sm">Kirim link reset password</button>
                </form>
                <form action="{{ route('admin.pengguna.toggle', $user->id) }}" method="POST">
                    @csrf
                    <button class="inline-flex items-center px-2 text-sm font-medium {{ $user->is_active ? 'text-[var(--danger)]' : 'text-[var(--success)]' }} hover:underline">{{ $user->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun' }}</button>
                </form>
            </div>
        </div>
    </x-modal>
@endforeach
