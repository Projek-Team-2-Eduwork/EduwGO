{{-- Ubah role pengguna; admin cabang wajib memilih cabang. --}}
@php($current = $user->hasRole('superadmin') ? 'superadmin' : ($user->hasRole('admin') ? 'admin' : 'user'))
<form action="{{ route('admin.pengguna.role', $user->id) }}" method="POST" x-data="{ role: '{{ $current }}' }" class="flex flex-wrap items-center gap-2" onsubmit="return confirm('Ubah role pengguna ini?')">
    @csrf
    <select name="role" x-model="role" aria-label="Role {{ $user->name }}" class="rounded-md border border-[var(--border)] bg-[var(--surface)] py-1 text-xs">
        <option value="user">Penyewa</option>
        <option value="admin">Admin cabang</option>
        <option value="superadmin">Superadmin</option>
    </select>
    <select name="branch_id" x-show="role === 'admin'" x-bind:disabled="role !== 'admin'" aria-label="Cabang {{ $user->name }}" class="rounded-md border border-[var(--border)] bg-[var(--surface)] py-1 text-xs">
        <option value="">Pilih cabang</option>
        @foreach ($branches as $id => $name)
            <option value="{{ $id }}" @selected($user->branch_id === $id)>{{ $name }}</option>
        @endforeach
    </select>
    <button type="submit" class="text-xs font-medium text-[var(--orange-500)] underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Simpan</button>
</form>
