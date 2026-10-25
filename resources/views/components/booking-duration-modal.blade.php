@props(['vehicle'])

@php
    // Nilai dari query string adalah input pengguna: di-encode lewat @js agar tidak bisa
    // keluar dari string JavaScript di atribut x-data (reflected XSS).
    $initialStart = session()->pull('booking.start_at', request('start', ''));
    $initialDays = session()->pull('booking.days', request('days', ''));
    $initialStart = is_scalar($initialStart) ? (string) $initialStart : '';
    $initialDays = is_scalar($initialDays) ? (string) $initialDays : '';
@endphp

<div x-data="bookingDurationModal({{ $vehicle->id }}, @js($initialStart), {{ $vehicle->price_per_day }}, @js($initialDays), {{ request()->boolean('ulang') ? 'true' : 'false' }})"
     @if(request()->boolean('ulang')) x-init="fetchDurations()" @endif>
    
    <x-primary-button type="button" @click="openModal">Booking</x-primary-button>

    <x-modal name="booking-modal-{{ $vehicle->id }}" :show="request()->boolean('ulang')" focusable>
        <div class="p-6">
            <h2 class="text-lg font-semibold text-[var(--navy-900)]">
                Pilih Durasi Sewa
            </h2>
            <p class="mt-1 text-sm">
                Durasi sewa 24 jam/hari
            </p>

            <div class="mt-4">
                <x-form.datetime name="start_at" label="Waktu Mulai" :picker="true"
                    x-model="startAt" @change="fetchDurations" step="300"
                    x-init="$nextTick(() => $el._flatpickr?.setDate(startAt, false))"
                    :min="now()->format('Y-m-d\TH:i')" />
            </div>

            {{-- Skeleton chip saat ketersediaan dimuat --}}
            <div class="mt-4" x-show="isLoading" x-cloak style="display: none" role="status">
                <span class="sr-only">Memuat ketersediaan...</span>
                <div class="grid grid-cols-5 gap-2" aria-hidden="true">
                    <div class="skeleton h-14"></div>
                    <div class="skeleton h-14"></div>
                    <div class="skeleton h-14"></div>
                    <div class="skeleton h-14"></div>
                    <div class="skeleton h-14"></div>
                </div>
            </div>

            <div class="mt-4" x-show="error" x-cloak style="display: none">
                <p class="text-sm text-red-700 dark:text-red-300" role="alert" x-text="error"></p>
            </div>

            <fieldset class="mt-4" x-show="!isLoading && durations" x-cloak style="display: none">
                <legend class="text-sm font-medium text-[var(--navy-900)]">Durasi sewa</legend>

                {{-- Chip 1..N hari; opsi bentrok (available=false dari API) tetap disable --}}
                <div class="mt-2 grid gap-2" style="grid-template-columns: repeat(auto-fit, minmax(3.75rem, 1fr));">
                    <template x-for="(data, days) in durations" :key="days">
                        <label class="chip-option" :title="!data.available ? 'Unit tidak tersedia di rentang ini' : ''">
                            <input type="radio" name="duration_days" :value="days" x-model="selectedDays" :disabled="!data.available">
                            <span class="chip-face chip-face-day">
                                <span class="text-lg font-bold leading-6" x-text="days"></span>
                                <span class="text-xs font-medium" x-text="data.available ? 'hari' : 'penuh'"></span>
                            </span>
                        </label>
                    </template>
                </div>

                {{-- Preview tanggal selesai: beranimasi tiap pilihan berubah --}}
                <div class="mt-4 rounded-lg border border-[var(--border)] p-3" aria-live="polite">
                    <template x-for="d in (selectedDays && durations && durations[selectedDays] ? [selectedDays] : [])" :key="d">
                        <div class="preview-pop flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <span class="block text-xs">Selesai</span>
                                <span class="block text-sm font-semibold text-[var(--navy-900)]" x-text="formatDate(durations[d].end_at)"></span>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="block text-xs" x-text="`${d} hari`"></span>
                                <span class="block text-sm font-bold text-[var(--navy-900)]" x-text="formatPrice(pricePerDay * d)"></span>
                            </div>
                        </div>
                    </template>
                    <p class="text-sm" x-show="!selectedDays">Pilih durasi untuk melihat tanggal selesai dan total harga.</p>
                </div>
            </fieldset>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-primary-button @click="proceed" x-bind:disabled="!selectedDays">
                    Checkout
                </x-primary-button>
            </div>
        </div>
    </x-modal>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('bookingDurationModal', (vehicleId, initialStart, pricePerDay, initialDays, autoOpen) => ({
            startAt: initialStart,
            initialDays: initialDays,
            autoOpen: autoOpen,
            durations: null,
            selectedDays: null,
            isLoading: false,
            error: null,
            pricePerDay: pricePerDay,
            permintaanTerakhir: null,

            openModal() {
                this.$dispatch('open-modal', `booking-modal-${vehicleId}`);
                if (this.startAt) {
                    this.fetchDurations();
                }
            },

            async fetchDurations() {
                if (!this.startAt) return;
                // Nilai yang sama persis tidak perlu request ulang (flatpickr bisa mengirim
                // event change lebih dari sekali untuk satu pilihan).
                if (this.startAt === this.permintaanTerakhir) return;
                this.permintaanTerakhir = this.startAt;
                this.isLoading = true;
                this.error = null;
                this.durations = null;
                this.selectedDays = null;
                try {
                    const response = await fetch(`/api/kendaraan/${vehicleId}/durasi?start=${this.startAt}`);
                    if (!response.ok) throw new Error('Gagal memuat ketersediaan.');
                    this.durations = await response.json();
                    
                    // Pre-select durasi lama jika unit masih available di durasi tersebut
                    if (this.initialDays && this.durations[this.initialDays] && this.durations[this.initialDays].available) {
                        this.selectedDays = String(this.initialDays);
                    }
                } catch (err) {
                    this.error = err.message;
                    // Biar pemilihan yang sama bisa dicoba ulang setelah gagal.
                    this.permintaanTerakhir = null;
                } finally {
                    this.isLoading = false;
                }
            },

            formatDate(isoString) {
                return new Intl.DateTimeFormat('id-ID', {
                    weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
                }).format(new Date(isoString));
            },

            formatPrice(price) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price);
            },

            proceed() {
                if (this.selectedDays && this.startAt) {
                    const dateObj = new Date(this.startAt);
                    const isoStart = isNaN(dateObj) ? this.startAt : dateObj.toISOString();
                    window.location.href = `/checkout/${vehicleId}?start=${isoStart}&days=${this.selectedDays}`;
                }
            }
        }));
    });
</script>