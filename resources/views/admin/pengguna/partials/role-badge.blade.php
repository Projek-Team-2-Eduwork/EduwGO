@use('Database\Seeders\RoleSeeder')
@php
    $isSuper = $user->hasRole(RoleSeeder::SUPERADMIN);
    $isAdmin = $user->hasRole(RoleSeeder::ADMIN);
@endphp
<span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $isSuper || $isAdmin ? 'bg-[var(--accent-soft)] text-[var(--ink)]' : 'bg-[var(--bg)] text-[var(--ink)] dark:bg-[var(--bg)] dark:text-[var(--ink-muted)]' }}">
    {{ $isSuper ? 'Superadmin' : ($isAdmin ? 'Admin' : 'Penyewa') }}
</span>
@if ($isAdmin && ($showBranch ?? true))
    <span class="ml-1 text-xs">{{ $user->branch?->name ?? 'Belum ada cabang' }}</span>
@endif
