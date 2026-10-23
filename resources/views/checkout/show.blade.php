<x-app-layout>
    <div class="py-12">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="card-surface p-6">
                <h2 class="mb-6 text-2xl font-bold text-[var(--navy-900)]">Konfirmasi Checkout</h2>

                @if(session('error'))
                    <div class="mb-4">
                        <x-alert type="error">
                            {{ session('error') }}
                        </x-alert>
                    </div>
                @endif

                <div class="mb-6 rounded-lg border border-[var(--border)] bg-[var(--bg)] p-4">
                    <h3 class="text-lg font-semibold text-[var(--navy-900)]">{{ $vehicle->name }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Harga per hari: Rp {{ number_format($vehicle->price_per_day, 0, ',', '.') }}</p>

                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs">Mulai</span>
                            <span class="font-medium text-[var(--navy-900)]">{{ $start->translatedFormat('D, d M Y H:i') }}</span>
                        </div>
                        <div>
                            <span class="block text-xs">Selesai</span>
                            <span class="font-medium text-[var(--navy-900)]">{{ $end->translatedFormat('D, d M Y H:i') }}</span>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-between items-center border-t border-[var(--border)] pt-4">
                        <span class="font-medium">Durasi: {{ $days }} hari</span>
                        <span class="text-lg font-bold text-[var(--orange-500)]">Total: Rp {{ number_format($vehicle->price_per_day * $days, 0, ',', '.') }}</span>
                    </div>
                </div>

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
                        <x-text-input id="customer_phone" class="block mt-1 w-full" type="text" name="customer_phone" value="{{ old('customer_phone', auth()->user()->phone) }}" required />
                        <x-input-error :messages="$errors->get('customer_phone')" class="mt-2" />
                    </div>

                    <div class="mb-6">
                        <x-form.textarea name="notes" label="Catatan Tambahan (Opsional)" :rows="3" />
                        <x-input-error :messages="$errors->get('start')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button>
                            Konfirmasi & Bayar
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>