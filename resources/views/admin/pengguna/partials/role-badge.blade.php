@use('Database\Seeders\RoleSeeder')
@php
    $isSuper = $user->hasRole(RoleSeeder::SUPERADMIN);
    $isAdmin = $user->hasRole(RoleSeeder::ADMIN);
@endphp
<span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $isSuper || $isAdmin ? 'bg-[var(--coral-300)] text-[var(--navy-900)]' : 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-100' }}">
    {{ $isSuper ? 'Superadmin' : ($isAdmin ? 'Admin' : 'Penyewa') }}
</span>
@if ($isAdmin)
    <span class="ml-1 text-xs">{{ $user->branch?->name ?? 'Belum ada cabang' }}</span>
@endif
