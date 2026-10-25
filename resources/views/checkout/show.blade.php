<x-app-layout title="Konfirmasi sewa">
    <div class="py-12">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-rental-stepper :step="2" /><div>
                <h2 class="mb-6 text-2xl font-bold text-[var(--ink)]">Konfirmasi sewa</h2>

                @if(session('error'))
                    <div class="mb-4">
                        <x-alert type="error">
                            {{ session('error') }}
                        </x-alert>
                    </div>
                @endif

                <div class="mb-8 border-b border-[var(--line)] pb-6">
                    @if($vehicle->image)<x-picture :src="$vehicle->image" alt="{{ $vehicle->name }}" class="mb-4 h-44 w-full object-contain" />@endif
                    <h3 class="text-lg font-semibold text-[var(--ink)]">{{ $vehicle->name }}</h3>
                    <p class="text-sm text-[var(--ink-muted)]">Harga per hari: {{ rupiah($vehicle->price_per_day) }}</p>

                    <dl class="receipt mt-4 text-sm">
                        <div><dt>Mulai</dt><dd>{{ $start->translatedFormat('D, d M Y H:i') }}</dd></div>
                        <div><dt>Selesai</dt><dd>{{ $end->translatedFormat('D, d M Y H:i') }}</dd></div>
                    </dl>

                    <div class="mt-4 flex justify-between items-center border-t border-[var(--line)] pt-4">
                        <span class="font-medium">Durasi: {{ $days }} hari</span>
                        <span class="text-lg font-bold text-[var(--accent)]">Total: {{ rupiah($vehicle->price_per_day * $days) }}</span>
                    </div>
                </div>

                <a href="{{ route('kendaraan.detail', ['vehicle' => $vehicle, 'start' => $start->toIso8601String(), 'days' => $days, 'ulang' => 1]) }}" class="mb-6 inline-flex min-h-11 items-center text-sm font-semibold underline">Ubah tanggal &amp; durasi</a>
                <x-pickup-location :vehicle="$vehicle" class="mb-8" />
                <form method="POST" action="{{ route('checkout.store', $vehicle->id) }}">
                    @csrf
                    <input type="hidden" name="start" value="{{ $start->toIso8601String() }}">
                    <input type="hidden" name="days" value="{{ $days }}">

                    <div class="mb-4">
                        <x-input-label for="customer_name" value="Nama Lengkap" />
                        <x-text-input id="customer_name" class="block mt-1 w-full" type="text" name="customer_name" value="{{ old('customer_name', auth()->user()->name) }}" required autofocus />
                        <x-input-error :messages="$errors->get('customer_name')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="customer_phone" value="Nomor WhatsApp" />
                        <x-text-input id="customer_phone" class="block mt-1 w-full" type="tel" inputmode="tel" autocomplete="tel" name="customer_phone" value="{{ old('customer_phone', auth()->user()->phone) }}" required />
                        <x-input-error :messages="$errors->get('customer_phone')" class="mt-2" />
                    </div>

                    <div class="mb-6">
                        <x-form.textarea name="notes" label="Catatan Tambahan (Opsional)" :rows="3" />
                        <x-input-error :messages="$errors->get('start')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button>
                            Lanjut bayar
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
