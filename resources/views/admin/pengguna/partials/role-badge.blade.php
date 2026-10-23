@use('Database\Seeders\RoleSeeder')
@php
    $isSuper = $user->hasRole(RoleSeeder::SUPERADMIN);
    $isAdmin = $user->hasRole(RoleSeeder::ADMIN);
@endphp
<span class="inline-block rounded px-2 py-1 text-xs font-bold {{ $isSuper || $isAdmin ? 'bg-[var(--coral-300)] text-[var(--navy-900)]' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
    {{ $isSuper ? 'Superadmin' : ($isAdmin ? 'Admin' : 'Penyewa') }}
</span>
@if ($isAdmin)
    <span class="ml-1 text-xs">{{ $user->branch?->name ?? 'Belum ada cabang' }}</span>
@endif
