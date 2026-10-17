<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">Pengaturan Cabang</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="mx-auto max-w-3xl space-y-6">
            @if(session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
            @if($errors->any()) <x-alert type="error">{{ $errors->first() }}</x-alert> @endif

            <div class="card-surface p-4 sm:p-6">
                <h3 class="text-lg font-bold">{{ $branch->name }}</h3>
                <p class="mt-1 text-sm">Data ini tampil ke penyewa di halaman Tentang, footer, dan beranda. Nama cabang dan status aktif diatur superadmin.</p>

                <form action="{{ route('admin.pengaturan-cabang.update') }}" method="POST" class="mt-6 grid gap-4 sm:grid-cols-2">
                    @csrf @method('PUT')

                    <div class="sm:col-span-2">
                        <x-input-label for="address" value="Alamat" />
                        <x-text-input id="address" name="address" type="text" class="mt-1 w-full" :value="old('address', $branch->address)" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Kontak / WhatsApp" />
                        <x-text-input id="phone" name="phone" type="text" inputmode="tel" class="mt-1 w-full" placeholder="08xxxxxxxxxx" :value="old('phone', $branch->phone)" />
                    </div>
                    <div>
                        <x-input-label for="operating_hours" value="Jam Operasional" />
                        <x-text-input id="operating_hours" name="operating_hours" type="text" class="mt-1 w-full" :value="old('operating_hours', $branch->operating_hours)" />
                    </div>

                    @foreach (\App\Models\Branch::SOCIALS as $key => $label)
                        <div>
                            <x-input-label for="social-{{ $key }}" value="{{ $label }}" />
                            <x-text-input id="social-{{ $key }}" name="social_links[{{ $key }}]" type="url" class="mt-1 w-full" placeholder="https://" :value="old('social_links.'.$key, $branch->social_links[$key] ?? null)" />
                        </div>
                    @endforeach

                    <div class="flex justify-end sm:col-span-2">
                        <x-primary-button class="px-5 py-2.5">Simpan</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
