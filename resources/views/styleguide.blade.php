@php
    use App\Enums\BookingStatus;
    use App\Models\Vehicle;
    use App\Models\VehicleType;
    use Illuminate\Pagination\LengthAwarePaginator;

    // Data contoh in-memory (tidak menyentuh DB).
    $sampleVehicle = (new Vehicle([
        'name' => 'Honda Vario 160',
        'plate_number' => 'B 1234 EDU',
        'price_per_day' => 75000,
        'image' => null,
    ]))->setRelation('type', new VehicleType(['name' => 'Matic']));

    $samplePaginator = (new LengthAwarePaginator(collect(range(1, 10)), 60, 10, 3))->setPath(url('/styleguide'));
@endphp

<x-app-layout>
    <x-slot name="header">
        <h1 class="font-serif text-3xl">EduwGo Styleguide</h1>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-12 px-4 py-10 sm:px-6 lg:px-8">
        {{-- Palet --}}
        <section>
            <h2 class="mb-3 font-serif text-2xl">Palet</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach (['bg', 'surface', 'navy-900', 'navy-700', 'ink-muted', 'orange-500', 'coral-300', 'gold-tan'] as $token)
                    <div class="card-surface p-3">
                        <div class="mb-2 h-12 rounded" style="background-color: var(--{{ $token }}); border: 1px solid var(--border);"></div>
                        <p class="text-sm">--{{ $token }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Tipografi --}}
        <section>
            <h2 class="mb-3 font-serif text-2xl">Tipografi</h2>
            <p class="mb-2 font-serif text-3xl">Heading serif (Instrument Serif)</p>
            <p>Body sans (Inter) — teks paragraf biasa dipakai di seluruh halaman.</p>
        </section>

        {{-- Tombol --}}
        <section>
            <h2 class="mb-3 font-serif text-2xl">Tombol</h2>
            <div class="flex flex-wrap items-center gap-3">
                <x-primary-button type="button">Tombol Utama</x-primary-button>
                <x-secondary-button>Tombol Sekunder</x-secondary-button>
                <span class="badge-accent">Badge</span>
            </div>
        </section>

        {{-- badge-status --}}
        <section>
            <h2 class="mb-3 font-serif text-2xl">badge-status</h2>
            <div class="flex flex-wrap gap-2">
                @foreach (BookingStatus::cases() as $status)
                    <x-badge-status :status="$status" />
                @endforeach
            </div>
        </section>

        {{-- vehicle-card --}}
        <section>
            <h2 class="mb-3 font-serif text-2xl">vehicle-card</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <x-vehicle-card :vehicle="$sampleVehicle" :available="true" href="#" />
                <x-vehicle-card :vehicle="$sampleVehicle" :available="false" href="#" />
            </div>
        </section>

        {{-- alert --}}
        <section class="space-y-3">
            <h2 class="mb-3 font-serif text-2xl">alert</h2>
            <x-alert type="success" dismissible>Pembayaran berhasil dikonfirmasi.</x-alert>
            <x-alert type="info">Booking kamu menunggu pembayaran.</x-alert>
            <x-alert type="warning">Waktu sewa sudah lewat, segera kembalikan unit.</x-alert>
            <x-alert type="error">Unit tidak tersedia di rentang tanggal ini.</x-alert>
        </section>

        {{-- filter-bar + form --}}
        <section>
            <h2 class="mb-3 font-serif text-2xl">filter-bar &amp; form</h2>
            <x-filter-bar action="#">
                <x-form.input name="q" label="Cari motor" placeholder="Nama motor" />
                <x-form.select name="type" label="Tipe" placeholder="Semua tipe" :options="['matic' => 'Matic', 'cub' => 'Cub']" />
                <x-form.datetime name="start_at" label="Mulai sewa" />
                <x-slot name="actions">
                    <x-primary-button class="w-full">Cari</x-primary-button>
                </x-slot>
            </x-filter-bar>

            <div class="card-surface mt-6 grid gap-4 p-6 md:grid-cols-2">
                <x-form.input name="sg_name" label="Nama lengkap" hint="Sesuai identitas." />
                <x-form.select name="sg_select" label="Durasi" :options="[1 => '1 hari', 2 => '2 hari', 3 => '3 hari']" :value="2" />
                <x-form.datetime name="sg_datetime" label="Tanggal & jam" />
                <x-form.textarea name="sg_notes" label="Catatan" />
            </div>
        </section>

        {{-- modal --}}
        <section>
            <h2 class="mb-3 font-serif text-2xl">modal</h2>
            <x-primary-button type="button" x-data x-on:click="$dispatch('open-modal', 'styleguide-modal')">Buka modal</x-primary-button>

            <x-modal name="styleguide-modal" maxWidth="md" focusable>
                <div class="p-6">
                    <h2 class="font-serif text-2xl">Judul modal</h2>
                    <p class="mt-2 text-sm">Isi modal memakai token surface dan border, dan bisa ditutup dengan Esc atau klik latar.</p>
                    <div class="mt-6 flex justify-end">
                        <x-secondary-button x-on:click="$dispatch('close')">Tutup</x-secondary-button>
                    </div>
                </div>
            </x-modal>
        </section>

        {{-- empty-state --}}
        <section>
            <h2 class="mb-3 font-serif text-2xl">empty-state</h2>
            <x-empty-state
                title="Belum ada pesanan"
                description="Pesanan kamu akan tampil di sini setelah kamu booking motor."
                action-label="Pilih Kendaraan"
                action-href="#"
            />
        </section>

        {{-- pagination --}}
        <section>
            <h2 class="mb-3 font-serif text-2xl">pagination</h2>
            {{ $samplePaginator->links('components.pagination') }}
        </section>
    </div>
</x-app-layout>
