{{-- Form cabang (tambah & edit). $branch = null untuk tambah; $prefix membedakan id antar form di satu halaman. --}}
@php
    $branch = $branch ?? null;
    $isNew = $branch === null;
    // old() hanya untuk form tambah; form edit lain di halaman yang sama tidak ikut terisi input gagal
    $v = fn (string $key, $fallback = null) => $isNew ? old($key, $fallback) : $fallback;
@endphp
<form action="{{ $isNew ? route('admin.cabang.store') : route('admin.cabang.update', $branch) }}" method="POST" class="grid gap-4 sm:grid-cols-2">
    @csrf
    @unless ($isNew) @method('PUT') @endunless

    <div>
        <x-input-label for="{{ $prefix }}-name" value="Nama Cabang *" />
        <x-text-input id="{{ $prefix }}-name" name="name" type="text" class="mt-1 w-full" required placeholder="Contoh: Cabang Malang Kota" :value="$v('name', $branch?->name)" />
    </div>
    <div>
        <x-input-label for="{{ $prefix }}-address" value="Alamat" />
        <x-text-input id="{{ $prefix }}-address" name="address" type="text" class="mt-1 w-full" placeholder="Jl. ... No. ..., Kota" :value="$v('address', $branch?->address)" />
    </div>
    <div>
        <x-input-label for="{{ $prefix }}-phone" value="Kontak / WhatsApp" />
        <x-text-input id="{{ $prefix }}-phone" name="phone" type="text" inputmode="tel" class="mt-1 w-full" placeholder="08xxxxxxxxxx" :value="$v('phone', $branch?->phone)" />
    </div>
    <div>
        <x-input-label for="{{ $prefix }}-hours" value="Jam Operasional" />
        <x-text-input id="{{ $prefix }}-hours" name="operating_hours" type="text" class="mt-1 w-full" placeholder="Setiap hari, 08.00 – 20.00 WIB" :value="$v('operating_hours', $branch?->operating_hours)" />
    </div>

    @foreach (\App\Models\Branch::SOCIALS as $key => $label)
        <div>
            <x-input-label for="{{ $prefix }}-{{ $key }}" value="{{ $label }}" />
            <x-text-input id="{{ $prefix }}-{{ $key }}" name="social_links[{{ $key }}]" type="url" class="mt-1 w-full" placeholder="https://" :value="$v('social_links.'.$key, $branch?->social_links[$key] ?? null)" />
        </div>
    @endforeach

    <div class="flex flex-wrap items-center gap-4 sm:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked($v('is_active', $branch?->is_active ?? true)) class="rounded border-[var(--border)] text-[var(--orange-500)] shadow-sm focus:ring-[var(--orange-500)]">
            Aktif (tampil ke penyewa)
        </label>
        <div class="ml-auto flex gap-2">
            {{ $actions ?? '' }}
            <x-primary-button class="px-4 py-2">{{ $isNew ? 'Tambah Cabang' : 'Simpan' }}</x-primary-button>
        </div>
    </div>
    @unless ($isNew)
        <span class="text-xs sm:col-span-2">Slug tetap: <span class="font-mono">{{ $branch->slug }}</span></span>
    @endunless
</form>
