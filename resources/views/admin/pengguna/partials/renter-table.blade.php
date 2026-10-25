@php($statusBadge = fn ($user) => $user->is_active
    ? '<span class="rounded-full bg-[var(--success-soft)] px-2.5 py-0.5 text-xs font-semibold text-[var(--success)]">Aktif</span>'
    : '<span class="rounded-full bg-[var(--danger-soft)] px-2.5 py-0.5 text-xs font-semibold text-[var(--danger)]">Nonaktif</span>')

{{-- Penyewa: tanpa kontrol role, hanya aktif/nonaktif + detail --}}
<div class="hidden overflow-x-auto md:block">
    <table class="w-full text-left text-sm">
        <thead class="bg-[var(--bg)] text-xs uppercase">
            <tr>
                <th class="px-4 py-3">Nama</th>
                <th class="px-4 py-3">Kontak</th>
                <th class="px-4 py-3">Role</th>
                <th class="px-4 py-3">Terdaftar</th>
                <th class="px-4 py-3 text-center">Booking</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    <td class="px-4 py-2"><a href="{{ route('admin.pengguna.show', $user) }}" class="font-semibold text-[var(--ink)] hover:underline">{{ $user->name }}</a></td>
                    <td class="px-4 py-2 text-xs">{{ $user->email }}<br>{{ $user->phone ?? '-' }}</td>
                    <td class="px-4 py-2">@include('admin.pengguna.partials.role-badge', ['user' => $user])</td>
                    <td class="px-4 py-2 text-xs">{{ $user->created_at->translatedFormat('d M Y') }}</td>
                    <td class="px-4 py-2 text-center font-bold">{{ $user->bookings_count }}</td>
                    <td class="px-4 py-2">{!! $statusBadge($user) !!}</td>
                    <td class="px-4 py-2">
                        <div class="flex items-center justify-end gap-4 text-sm font-medium">
                            <a href="{{ route('admin.pengguna.show', $user) }}" class="text-[var(--accent)] hover:underline">Detail</a>
                            <form action="{{ route('admin.pengguna.toggle', $user->id) }}" method="POST">
                                @csrf
                                <button class="{{ $user->is_active ? 'text-[var(--danger)]' : 'text-[var(--success)]' }} hover:underline">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        </div>
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
                <span class="block truncate text-xs">{{ $user->phone ?? $user->email }} · {{ $user->bookings_count }} booking</span>
                <span class="mt-1 inline-flex flex-wrap items-center gap-1">@include('admin.pengguna.partials.role-badge', ['user' => $user]) {!! $statusBadge($user) !!}</span>
            </a>
            <form action="{{ route('admin.pengguna.toggle', $user->id) }}" method="POST" class="shrink-0">
                @csrf
                <button class="px-2 text-sm font-medium {{ $user->is_active ? 'text-[var(--danger)]' : 'text-[var(--success)]' }}">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </form>
        </li>
    @endforeach
</ul>
