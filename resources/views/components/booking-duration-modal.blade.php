@props(['vehicle'])

<div x-data="bookingDurationModal({{ $vehicle->id }}, '{{ session()->pull('booking.start_at', request('start', '')) }}', {{$vehicle->price_per_day }}, '{{ session()->pull('booking.days', request('days', '')) }}', {{ request()->boolean('ulang') ? 'true' : 'false' }})"
     @if(request()->boolean('ulang')) x-init="fetchDurations()" @endif>
    
    <x-primary-button type="button" @click="openModal">Booking</x-primary-button>

    <x-modal name="booking-modal-{{ $vehicle->id }}" :show="request()->boolean('ulang')" focusable>
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Pilih Durasi Sewa
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Durasi sewa 24 jam/hari
            </p>

            <div class="mt-4">
                <x-input-label for="start_at" value="Waktu Mulai" />
                <x-text-input id="start_at" type="datetime-local" class="mt-1 block w-full"
                    x-model="startAt" @change="fetchDurations" step="300"
                    :min="now()->format('Y-m-d\TH:i')" />
            </div>

            <div class="mt-4" x-show="isLoading">
                <p class="text-sm text-gray-500 dark:text-gray-400">Memuat ketersediaan...</p>
            </div>

            <div class="mt-4" x-show="error">
                <p class="text-sm text-red-600 dark:text-red-400" x-text="error"></p>
            </div>

            <div class="mt-4 space-y-2" x-show="!isLoading && durations">
                <template x-for="(data, days) in durations" :key="days">
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer"
                        :class="data.available ? 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' : 'border-gray-100 dark:border-gray-800 opacity-50 cursor-not-allowed'"
                        :title="!data.available ? 'Unit tidak tersedia di rentang ini' : ''">
                        <input type="radio" name="duration_days" :value="days" x-model="selectedDays"
                            class="text-blue-600 border-gray-300 focus:ring-blue-500"
                            :disabled="!data.available">
                        <div class="ml-3 w-full flex justify-between items-center">
                            <div>
                                <span class="block text-sm font-medium text-gray-900 dark:text-gray-100" x-text="`${days} hari`"></span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400" x-text="`Selesai: ${formatDate(data.end_at)}`"></span>
                            </div>
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100" x-text="formatPrice(pricePerDay * days)"></div>
                        </div>
                    </label>
                </template>
            </div>

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

            openModal() {
                this.$dispatch('open-modal', `booking-modal-${vehicleId}`);
                if (this.startAt) {
                    this.fetchDurations();
                }
            },

            async fetchDurations() {
                if (!this.startAt) return;
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