<div class="section-plain p-4" x-data="{ open: {{ $errors->any() && old('_form') === 'admin' ? 'true' : 'false' }} }">
    <button type="button" @click="open = ! open" class="flex w-full items-center justify-between text-left text-sm font-semibold" :aria-expanded="open.toString()">
        <span>+ Buat Admin Cabang</span>
        <svg class="h-4 w-4 transition" :class="open ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
    </button>
    <form x-show="open" x-cloak action="{{ route('admin.pengguna.store') }}" method="POST" class="mt-4 grid gap-4 border-t border-[var(--line)] pt-4 sm:grid-cols-2">
        @csrf
        <input type="hidden" name="_form" value="admin">
        <x-form.input name="name" id="new-name" label="Nama *" :value="old('name')" required />
        <x-form.input name="email" id="new-email" type="email" label="Email *" :value="old('email')" required />
        <x-form.input name="phone" id="new-phone" label="Nomor WhatsApp" :value="old('phone')" inputmode="tel" />
        <x-form.select name="branch_id" id="new-branch" label="Cabang *" :options="$branches" :value="old('branch_id')" placeholder="Pilih cabang" required />
        <x-form.input name="password" id="new-password" type="password" label="Password * (min. 8 karakter)" autocomplete="new-password" required />
        <x-form.input name="password_confirmation" id="new-password-confirmation" type="password" label="Konfirmasi Password *" autocomplete="new-password" required />
        <div class="sm:col-span-2">
            <x-primary-button class="px-5 py-2.5">Buat Admin</x-primary-button>
        </div>
    </form>
</div>
