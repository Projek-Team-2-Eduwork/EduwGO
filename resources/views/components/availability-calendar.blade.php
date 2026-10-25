@props(['vehicle'])

{{-- Kalender ketersediaan bulanan per unit (EG-41). Data: GET /api/kendaraan/{id}/jadwal?month= --}}
<section x-data="availabilityCalendar('{{ route('kendaraan.jadwal', $vehicle) }}')" x-init="load()" class="card-surface w-full max-w-md p-5 sm:p-6" aria-labelledby="kalender-heading">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 id="kalender-heading" class="text-lg text-[var(--navy-900)]">Ketersediaan Unit</h2>
            <p class="mt-1 text-xs">Hari berarsir sudah dipesan (termasuk jeda <span x-text="buffer"></span> menit antar sewa).</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="move(-1)" :disabled="!canGoPrev" class="calendar-nav" aria-label="Bulan sebelumnya">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <span class="min-w-[8.5rem] text-center text-sm font-semibold text-[var(--navy-900)]" x-text="title" aria-live="polite"></span>
            <button type="button" @click="move(1)" class="calendar-nav" aria-label="Bulan berikutnya">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-7 gap-1 text-center text-[11px] font-semibold uppercase tracking-wide sm:gap-1.5">
        <template x-for="d in ['Sen','Sel','Rab','Kam','Jum','Sab','Min']" :key="d">
            <div x-text="d"></div>
        </template>
    </div>

    <div class="mt-1 grid grid-cols-7 gap-1 sm:gap-1.5" :class="{ 'opacity-60': loading }">
        <template x-for="cell in cells" :key="cell.key">
            <div class="relative">
                <template x-if="cell.day">
                    <button type="button"
                            class="calendar-day"
                            :class="{ 'calendar-day-booked': cell.ranges.length, 'calendar-day-past': cell.past, 'calendar-day-today': cell.today }"
                            :aria-label="cell.label"
                            @mouseenter="active = cell.key" @mouseleave="active = null"
                            @focus="active = cell.key" @blur="active = null"
                            @click="active = (active === cell.key ? null : cell.key)">
                        <span x-text="cell.day"></span>
                    </button>
                </template>

                <div x-show="active === cell.key && cell.ranges.length" x-cloak style="display: none"
                     class="calendar-tip" :class="cell.col >= 5 ? 'right-0' : (cell.col <= 1 ? 'left-0' : 'left-1/2 -translate-x-1/2')" role="tooltip">
                    <p class="font-semibold" x-text="cell.date"></p>
                    <template x-for="r in cell.ranges" :key="r">
                        <p x-text="r"></p>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <p class="mt-3 text-xs text-red-700 dark:text-red-300" x-show="error" x-cloak style="display: none" x-text="error" role="alert"></p>

    <div class="mt-4 flex flex-wrap items-center gap-4 text-xs">
        <span class="inline-flex items-center gap-1.5"><span class="calendar-swatch" aria-hidden="true"></span> Tersedia</span>
        <span class="inline-flex items-center gap-1.5"><span class="calendar-swatch calendar-swatch-booked" aria-hidden="true"></span> Ada booking</span>
    </div>
</section>

@once
    <style>
        .calendar-nav { display: inline-flex; height: 2rem; width: 2rem; align-items: center; justify-content: center; border-radius: 0.5rem; border: 1px solid var(--border); color: var(--navy-900); transition: background-color .15s; }
        .calendar-nav:hover:not(:disabled) { background: var(--bg); }
        .calendar-nav:disabled { opacity: .4; cursor: not-allowed; }
        .calendar-day { display: flex; width: 100%; aspect-ratio: 1 / 1; align-items: center; justify-content: center; border-radius: 0.5rem; border: 1px solid var(--border); background: var(--surface); font-size: 0.8125rem; color: var(--navy-900); }
        .calendar-day-past { opacity: .45; }
        .calendar-day-today { outline: 2px solid var(--orange-500); outline-offset: -2px; font-weight: 700; }
        .calendar-day-booked,
        .calendar-swatch-booked { border-color: var(--orange-500); background: repeating-linear-gradient(45deg, color-mix(in srgb, var(--orange-500) 28%, transparent) 0 4px, transparent 4px 8px), var(--surface); }
        .calendar-day:focus-visible { outline: 2px solid var(--navy-900); outline-offset: 2px; }
        .calendar-swatch { display: inline-block; height: 0.875rem; width: 0.875rem; border-radius: 0.25rem; border: 1px solid var(--border); background: var(--surface); }
        .calendar-tip { position: absolute; top: calc(100% + 4px); z-index: 20; min-width: 7.5rem; border-radius: 0.5rem; border: 1px solid var(--border); background: var(--surface); padding: 0.5rem 0.625rem; font-size: 0.75rem; color: var(--navy-900); box-shadow: 0 6px 18px rgb(0 0 0 / .18); white-space: nowrap; }
        @media (prefers-reduced-motion: no-preference) { .calendar-day { transition: background-color .15s, border-color .15s; } }
    </style>
@endonce

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('availabilityCalendar', (url) => ({
            cursor: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
            booked: {},
            buffer: 60,
            loading: false,
            error: null,
            active: null,
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],

            get title() {
                return `${this.monthNames[this.cursor.getMonth()]} ${this.cursor.getFullYear()}`;
            },

            get monthKey() {
                return `${this.cursor.getFullYear()}-${String(this.cursor.getMonth() + 1).padStart(2, '0')}`;
            },

            // Bulan lalu tidak berguna untuk memilih sewa baru.
            get canGoPrev() {
                const now = new Date();
                return this.cursor > new Date(now.getFullYear(), now.getMonth(), 1);
            },

            get cells() {
                const y = this.cursor.getFullYear();
                const m = this.cursor.getMonth();
                const offset = (new Date(y, m, 1).getDay() + 6) % 7; // Senin = 0
                const total = new Date(y, m + 1, 0).getDate();
                const today = new Date();
                const todayKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                const cells = [];

                for (let i = 0; i < offset; i++) cells.push({ key: `e${i}`, day: null, ranges: [], col: i });

                for (let d = 1; d <= total; d++) {
                    const key = `${this.monthKey}-${String(d).padStart(2, '0')}`;
                    const ranges = this.booked[key] || [];
                    cells.push({
                        key,
                        day: d,
                        ranges,
                        col: (offset + d - 1) % 7,
                        today: key === todayKey,
                        past: key < todayKey,
                        date: `${d} ${this.title}`,
                        label: `${d} ${this.title}` + (ranges.length ? `, ada booking: ${ranges.join(', ')}` : ', tersedia'),
                    });
                }

                return cells;
            },

            async load() {
                this.loading = true;
                this.error = null;
                const month = this.monthKey;

                try {
                    const response = await fetch(`${url}?month=${month}`, { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error();
                    const data = await response.json();
                    if (month !== this.monthKey) return; // pindah bulan lagi saat menunggu
                    this.booked = data.days;
                    this.buffer = data.buffer_minutes;
                } catch (e) {
                    this.booked = {};
                    this.error = 'Jadwal belum bisa dimuat. Coba lagi nanti.';
                } finally {
                    this.loading = false;
                }
            },

            move(step) {
                this.cursor = new Date(this.cursor.getFullYear(), this.cursor.getMonth() + step, 1);
                this.active = null;
                this.load();
            },
        }));
    });
</script>
