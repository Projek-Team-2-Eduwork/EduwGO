<section>
    <header>
        <h2 class="text-lg font-medium text-[var(--navy-900)]">
            Informasi Profil
        </h2>

        <p class="mt-1 text-sm text-[var(--ink-muted)]">
            Perbarui data diri yang dipakai saat checkout dan dicek admin.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nama" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-[var(--ink-muted)]">
                        Alamat email belum terverifikasi.

                        <button
                            form="send-verification"
                            class="underline text-sm text-[var(--orange-500)] hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--navy-700)]"
                        >
                            Klik di sini untuk mengirim ulang email verifikasi.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-[var(--orange-500)]">
                            Link verifikasi baru sudah dikirim ke email kamu.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="phone" value="Nomor WhatsApp" />
            <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $user->phone)" placeholder="0812-3456-7890" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            <p class="mt-1 text-xs text-[var(--ink-muted)]">Dipakai saat checkout dan dicek admin saat pengambilan unit.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="instagram" value="Instagram" />
                <x-text-input id="instagram" name="instagram" type="text" class="mt-1 block w-full" :value="old('instagram', $user->social_account['instagram'] ?? '')" placeholder="username" />
                <x-input-error class="mt-2" :messages="$errors->get('instagram')" />
            </div>

            <div>
                <x-input-label for="facebook" value="Facebook" />
                <x-text-input id="facebook" name="facebook" type="text" class="mt-1 block w-full" :value="old('facebook', $user->social_account['facebook'] ?? '')" placeholder="username" />
                <x-input-error class="mt-2" :messages="$errors->get('facebook')" />
            </div>
        </div>

        <div>
            <x-input-label for="theme" value="Tema tampilan" />
            <select
                id="theme"
                name="theme"
                class="mt-1 block w-full border shadow-sm"
                x-data
                @change="$dispatch('theme-changed', { theme: $event.target.value })"
            >
                <option value="light" @selected(old('theme', $user->preferredTheme()) === 'light')>Terang</option>
                <option value="dark" @selected(old('theme', $user->preferredTheme()) === 'dark')>Gelap</option>
                <option value="system" @selected(old('theme', $user->preferredTheme()) === 'system')>Ikut sistem</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('theme')" />
            <p class="mt-1 text-xs text-[var(--ink-muted)]">Sama dengan tombol tema di navbar (terang / gelap / ikut sistem).</p>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button class="btn-primary-edu">Simpan</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-[var(--ink-muted)]"
                >Tersimpan.</p>
            @endif
        </div>
    </form>
</section>
