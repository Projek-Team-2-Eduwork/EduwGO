@use('Database\Seeders\RoleSeeder')
@php($current = $user->hasRole(RoleSeeder::SUPERADMIN) ? RoleSeeder::SUPERADMIN : RoleSeeder::ADMIN)
{{-- Hanya untuk staf: penyewa tidak punya kontrol role (ditolak juga di UserPolicy::setRole) --}}
<form action="{{ route('admin.pengguna.role', $user->id) }}" method="POST" x-data="{ role: '{{ $current }}' }" class="space-y-4" onsubmit="return confirm('Simpan perubahan role {{ $user->name }}?')">
    @csrf
    <div>
        <x-input-label for="role-{{ $user->id }}" value="Role" />
        <select id="role-{{ $user->id }}" name="role" x-model="role" class="mt-1 block w-full rounded-lg shadow-sm">
            <option value="{{ RoleSeeder::ADMIN }}">Admin cabang</option>
            <option value="{{ RoleSeeder::SUPERADMIN }}">Superadmin</option>
            <option value="{{ RoleSeeder::USER }}">Cabut akses staf (jadi penyewa)</option>
        </select>
    </div>
    <div x-show="role === '{{ RoleSeeder::ADMIN }}'">
        <x-input-label for="branch-{{ $user->id }}" value="Cabang" />
        <select id="branch-{{ $user->id }}" name="branch_id" x-bind:disabled="role !== '{{ RoleSeeder::ADMIN }}'" class="mt-1 block w-full rounded-lg shadow-sm">
            @foreach ($branches as $id => $name)
                <option value="{{ $id }}" @selected($user->branch_id === $id)>{{ $name }}</option>
            @endforeach
        </select>
    </div>
    <x-primary-button class="w-full justify-center">Simpan role</x-primary-button>
</form>
