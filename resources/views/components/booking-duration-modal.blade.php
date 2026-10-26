@props(['vehicle'])

@php
    // Nilai dari query string adalah input pengguna: di-encode lewat @js agar tidak bisa
    // keluar dari string JavaScript di atribut x-data (reflected XSS).
    $initialStart = session()->pull('booking.start_at', request('start', ''));
    $initialDays = session()->pull('booking.days', request('days', ''));
    $initialStart = is_scalar($initialStart) ? (string) $initialStart : '';
    $initialDays = is_scalar($initialDays) ? (string) $initialDays : '';
    $price = rupiah((float) $vehicle->price_per_day);
    $branchName = $vehicle->branch?->name ?? 'cabang';
@endphp

{{-- Kartu pesan: kalender rentang (gaya Airbnb) inline sticky di desktop, bottom sheet di mobile.
     Satu state; tampilan ketersediaan dari endpoint jadwal, keputusan akhir dari endpoint durasi (EG-12). --}}
<div x-data="bookingDurationModal({{ $vehicle->id }}, @js($initialStart), {{ $vehicle->price_per_day }}, @js($initialDays), {{ request()->boolean('ulang') ? 'true' : 'false' }})"
     data-max-days="{{ app(\App\Services\AvailabilityService::class)->maxDays() }}"
     data-jadwal-url="{{ route('kendaraan.jadwal', $vehicle) }}"
     x-init="init()">

    <x-modal name="booking-modal-{{ $vehicle->id }}" :show="request()->boolean('ulang')" :inline-desktop="true" aria-label="Pilih tanggal sewa" focusable>
        <div class="p-5 lg:rounded-lg lg:border lg:border-[var(--line)] lg:bg-[var(--surface)] lg:p-5 lg:shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p><span class="text-2xl font-bold text-[var(--ink)] lg:text-xl">{{ $price }}</span><span class="text-sm">/hari</span></p>
                    <h2 class="mt-1 text-base font-semibold text-[var(--ink)]" x-text="days ? `${days} hari di {{ $branchName }}` : 'Pilih tanggal sewa'">Pilih tanggal sewa</h2>
                    <p class="text-sm" x-text="endDate ? `${fmtShort(startDate)} – ${fmtShort(endDate)}` : (startDate ? 'Pilih tanggal selesai' : 'Klik tanggal mulai, lalu tanggal selesai')"></p>
                </div>
                <button type="button" class="-mr-2 -mt-2 min-h-11 min-w-11 text-2xl lg:hidden" x-on:click="$dispatch('close')" aria-label="Tutup">&times;</button>
            </div>

            <div class="hidden lg:block">
            {{-- Kalender rentang --}}
            <div class="mt-3 flex items-center justify-between lg:mt-2">
                <button type="button" class="bk-nav" @click="move(-1)" :disabled="!canPrev" aria-label="Bulan sebelumnya">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <span class="text-sm font-semibold text-[var(--ink)]" x-text="monthTitle" aria-live="polite"></span>
                <button type="button" class="bk-nav" @click="move(1)" aria-label="Bulan berikutnya">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

            <div x-data="{ get calendarMonth() { return cursor } }">@include('components.booking-calendar-grid')</div>

            <div class="mt-2 flex flex-wrap items-center justify-between gap-x-4 text-xs">
                <span class="inline-flex items-center gap-1.5"><span class="bk-dot-legend" aria-hidden="true"></span> Sebagian terpesan</span>
                <button type="button" class="inline-flex min-h-11 items-center font-semibold text-[var(--ink)] underline lg:min-h-0 lg:py-1" x-show="startDate" x-cloak @click="clearDates">Hapus tanggal</button>
            </div>
            <p class="text-xs text-[var(--danger)]" x-show="calError" x-cloak x-text="calError" role="alert"></p>

            </div>
            <button type="button" class="my-4 min-h-11 font-semibold text-[var(--ink)] underline lg:hidden" @click="editDates">Ubah tanggal</button>

            {{-- Jam mulai: muncul setelah rentang dipilih; jam selesai = jam mulai (1 hari = 24 jam) --}}
            <div class="mt-2" x-show="days" x-cloak>
                <label for="bk-time-{{ $vehicle->id }}" class="block text-sm font-medium text-[var(--ink)]">Jam mulai <span class="font-normal text-[var(--ink-muted)]">· selesai di jam yang sama</span></label>
                <select id="bk-time-{{ $vehicle->id }}" class="mt-1 block min-h-11 w-full rounded-lg" x-model="time" @change="check">
                    <option value="">Pilih jam</option>
                    <template x-for="t in timeOptions" :key="t">
                        <option :value="t" x-text="`${t} WIB`" :selected="t === time"></option>
                    </template>
                </select>
            </div>

            {{-- Status: satu sumber kebenaran /api/kendaraan/{id}/durasi --}}
            <div class="mt-2 text-sm" aria-live="polite">
                <p x-show="status === 'loading'" x-cloak role="status">Memeriksa jadwal…</p>
                <p x-show="status === 'ok'" x-cloak class="inline-flex items-center gap-1.5 font-semibold text-[var(--success)]"><span class="h-2 w-2 rounded-full bg-current" aria-hidden="true"></span> Tersedia untuk jadwal Anda</p>
                <p x-show="status === 'conflict'" x-cloak class="font-semibold text-[var(--danger)]">Bentrok dengan pesanan lain. Ganti jam atau tanggal.</p>
                <div x-show="status === 'error'" x-cloak class="flex flex-wrap items-center gap-x-3">
                    <p class="text-[var(--danger)]" role="alert">Status jadwal belum diketahui.</p>
                    <button type="button" class="inline-flex min-h-11 items-center font-semibold underline" @click="check(true)">Coba lagi</button>
                </div>
            </div>

            <div class="mt-2 border-t border-[var(--line)] pt-2 text-sm">
                <p class="flex justify-between gap-3"><span>Mulai – selesai</span><span class="text-right font-semibold text-[var(--ink)]" x-text="days && time ? `${fmtShort(startDate)}, ${time} – ${fmtShort(endDate)}` : '—'">—</span></p>
                <p class="mt-1 flex items-baseline justify-between gap-3">
                    <span class="font-semibold text-[var(--ink)]">Total sewa <span class="block text-xs font-normal text-[var(--ink-muted)]" x-show="days" x-cloak x-text="`{{ $price }} × ${days} hari`"></span></span>
                    <span class="text-lg font-bold text-[var(--ink)]" x-text="days ? formatPrice(pricePerDay * days) : '—'">—</span>
                </p>
            </div>

            <x-primary-button class="mt-3 w-full justify-center" @click="proceed" x-bind:disabled="status !== 'ok'">
                Lanjut ke checkout
            </x-primary-button>

        </div>
    </x-modal>

    <x-modal name="booking-calendar-{{ $vehicle->id }}" max-width="lg" panel-class="booking-calendar-sheet" aria-label="Ubah tanggal">
        <div class="flex h-full min-h-0 flex-col">
            <header class="flex shrink-0 items-start justify-between gap-2 px-5 pb-4 pt-6">
                <div>
                    <h2 class="text-2xl text-[var(--ink)]">Ubah tanggal</h2>
                    <p class="mt-2 text-sm" aria-live="polite" x-text="days ? `${fmtShort(startDate)} – ${fmtShort(endDate)} · ${days} hari` : (startDate ? 'Pilih tanggal selesai' : 'Pilih tanggal mulai, lalu tanggal selesai')"></p>
                </div>
                <button type="button" class="min-h-11 min-w-11 text-2xl text-[var(--ink)]" @click="$dispatch('close')" aria-label="Tutup kalender">&times;</button>
            </header>
            <div class="grid shrink-0 grid-cols-7 border-b border-[var(--line)] bg-[var(--surface)] px-5 py-3 text-center text-xs font-semibold" aria-hidden="true">
                @foreach (['S', 'S', 'R', 'K', 'J', 'S', 'M'] as $day)<span>{{ $day }}</span>@endforeach
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5">
                <p class="text-xs text-[var(--danger)]" x-show="calError" x-cloak x-text="calError" role="alert"></p>
                <button type="button" class="min-h-11 text-sm font-semibold underline" x-show="calError" x-cloak @click="mobileMonths.forEach(month => loadMonth(month))">Coba lagi</button>
                <template x-for="calendarMonth in mobileMonths" :key="calendarMonth">
                    <section class="py-5" :aria-label="titleFor(calendarMonth)">
                        <h3 class="mb-3 text-base" x-text="titleFor(calendarMonth)"></h3>
                        @include('components.booking-calendar-grid', ['mobile' => true])
                    </section>
                </template>
                <button type="button" class="mb-5 min-h-11 w-full font-semibold underline" @click="moreMonths">Muat bulan berikutnya</button>
            </div>
            <footer data-calendar-footer class="flex shrink-0 items-center justify-between gap-4 border-t border-[var(--line)] bg-[var(--surface)] px-5 pt-4" style="padding-bottom: max(1rem, env(safe-area-inset-bottom));">
                <button type="button" class="min-h-11 font-semibold text-[var(--ink)] underline" @click="clearDates">Hapus tanggal</button>
                <x-primary-button type="button" @click="saveDates" x-bind:disabled="!validRange">Simpan</x-primary-button>
            </footer>
        </div>
    </x-modal>

    {{-- Mobile: bar bawah tetap membuka sheet yang sama --}}
    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-[var(--line)] bg-[var(--surface)] px-4 pt-3 lg:hidden" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));">
        <div class="mx-auto flex max-w-[1320px] items-center justify-between gap-3">
            <p class="min-w-0 text-sm">
                <span x-show="!days"><span class="text-lg font-bold text-[var(--ink)]">{{ $price }}</span>/hari</span>
                <span x-show="days" x-cloak><span class="text-lg font-bold text-[var(--ink)]" x-text="formatPrice(pricePerDay * days)"></span> · <span x-text="`${days} hari`"></span></span>
            </p>
            <x-primary-button type="button" class="shrink-0" x-ref="bookingTrigger" @click="openModal"><span x-text="status === 'ok' ? 'Lanjut' : 'Pilih tanggal'">Pilih tanggal</span></x-primary-button>
        </div>
    </div>
</div>

@once
    <style>
        .bk-nav { display: inline-flex; height: 2.75rem; width: 2.75rem; align-items: center; justify-content: center; border-radius: 9999px; color: var(--ink); }
        .bk-nav:hover:not(:disabled) { background: var(--bg); }
        .bk-nav:disabled { opacity: .35; cursor: not-allowed; }
        .bk-nav:focus-visible, .bk-day:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
        .bk-day { position: relative; z-index: 1; display: inline-flex; height: 2.75rem; width: 2.75rem; align-items: center; justify-content: center; border-radius: 9999px; font-size: .875rem; color: var(--ink); }
        .bk-day:hover:not([aria-disabled='true']):not(.bk-day-edge) { box-shadow: inset 0 0 0 1.5px var(--ink); }
        .bk-day-today:not(.bk-day-edge) { font-weight: 700; color: var(--accent); }
        .bk-day-off { color: var(--ink-muted); opacity: .6; text-decoration: line-through; cursor: not-allowed; }
        .bk-day-edge { background: var(--accent); color: var(--on-accent); font-weight: 700; }
        /* Pita rentang menyambung antar sel (sel tanpa celah); ujung hanya setengah supaya menempel ke lingkaran */
        .bk-band { position: absolute; top: 2px; bottom: 2px; left: 0; right: 0; background: color-mix(in srgb, var(--accent) 18%, var(--surface)); }
        .bk-band-start { left: 50%; }
        .bk-band-end { right: 50%; }
        .bk-dot { position: absolute; bottom: 5px; height: 4px; width: 4px; border-radius: 9999px; background: currentColor; }
        .bk-dot-legend { display: inline-block; height: 4px; width: 4px; border-radius: 9999px; background: var(--ink); }
        /* Desktop (pointer) lebih rapat supaya kartu sticky muat satu layar; mobile tetap 44px */
        @media (min-width: 1024px) { .bk-day, .bk-nav { height: 2.125rem; width: 2.125rem; } .bk-dot { bottom: 2px; } .bk-band { top: 0; bottom: 0; } }
        @media (prefers-reduced-motion: no-preference) { .bk-day { transition: background-color .15s, box-shadow .15s; } }
    </style>
@endonce

<script>
    document.addEventListener('alpine:init', () => {
        // Tanggal = string 'YYYY-MM-DD' (zona WIB), dihitung lewat UTC supaya tidak terpengaruh zona browser.
        const toUtc = (k) => { const [y, m, d] = k.split('-').map(Number); return Date.UTC(y, m - 1, d); };
        const addDays = (k, n) => new Date(toUtc(k) + n * 864e5).toISOString().slice(0, 10);
        const diffDays = (a, b) => Math.round((toUtc(b) - toUtc(a)) / 864e5);
        const wib = (opts) => new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Jakarta', ...opts }).format(new Date());
        const fmt = (k, opts) => new Intl.DateTimeFormat('id-ID', { timeZone: 'UTC', ...opts }).format(new Date(toUtc(k)));

        Alpine.data('bookingDurationModal', (vehicleId, initialStart, pricePerDay, initialDays, autoOpen) => ({
            pricePerDay: pricePerDay,
            autoOpen: autoOpen,
            maxDays: 5,
            mobileMonths: [],
            datesSaved: false,
            jadwalUrl: '',
            today: wib({ year: 'numeric', month: '2-digit', day: '2-digit' }),
            cursor: '',          // 'YYYY-MM' bulan yang tampil
            booked: {},          // { 'YYYY-MM-DD': ['08:00–12:00', ...] } dari endpoint jadwal (sudah termasuk buffer)
            loadedMonths: {},
            pendingMonths: 0,
            get calLoading() { return this.pendingMonths > 0; },
            calError: null,
            startDate: null,
            endDate: null,
            hoverKey: null,
            focusKey: null,
            time: '',
            status: null,        // null | loading | ok | conflict | error
            durations: null,
            durationsFor: null,

            init() {
                this.maxDays = parseInt(this.$el.dataset.maxDays, 10) || 5;
                this.jadwalUrl = this.$el.dataset.jadwalUrl;
                this.cursor = this.today.slice(0, 7);

                // Jadwal dari katalog / pesan ulang: 'YYYY-MM-DDTHH:mm' + jumlah hari.
                const match = /^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/.exec(initialStart || '');
                const days = parseInt(initialDays, 10);
                if (match && match[1] >= this.today && days >= 1 && days <= this.maxDays) {
                    this.startDate = match[1];
                    this.endDate = addDays(match[1], days);
                    this.time = match[2];
                    this.cursor = match[1].slice(0, 7);
                }
                this.loadAround();
                if (this.time) this.check();
            },

            get days() { return this.startDate && this.endDate ? diffDays(this.startDate, this.endDate) : 0; },
            get monthKey() { return this.cursor; },
            titleFor(month) { return fmt(`${month}-01`, { month: 'long', year: 'numeric' }); },
            get monthTitle() { return this.titleFor(this.cursor); },
            get validRange() {
                if (!this.days || this.startDate < this.today || this.isFull(this.startDate) || !this.canEnd(this.endDate)) return false;
                for (let k = this.startDate; k <= this.endDate; k = addDays(k, 1)) if (this.loadedMonths[k.slice(0, 7)] !== 'ready') return false;
                return true;
            },
            moreMonths() {
                let month = this.mobileMonths.length ? this.nextMonth(this.mobileMonths.at(-1)) : this.today.slice(0, 7);
                for (let i = 0; i < 6; i++) { this.mobileMonths.push(month); this.loadMonth(month); month = this.nextMonth(month); }
            },
            editDates() {
                this.$refs.bookingTrigger?.focus();
                this.$dispatch('close-modal', `booking-modal-${vehicleId}`);
                if (!this.mobileMonths.length) { this.moreMonths(); this.moreMonths(); }
                else this.mobileMonths.forEach(month => this.loadMonth(month));
                this.$dispatch('open-modal', `booking-calendar-${vehicleId}`);
            },
            saveDates() {
                if (!this.validRange) return;
                this.$refs.bookingTrigger?.focus();
                this.datesSaved = true;
                this.$dispatch('close-modal', `booking-calendar-${vehicleId}`);
                this.$dispatch('open-modal', `booking-modal-${vehicleId}`);
            },
            get canPrev() { return this.cursor > this.today.slice(0, 7); },

            weeksFor(month) {
                const first = `${month}-01`;
                const offset = (new Date(toUtc(first)).getUTCDay() + 6) % 7; // Senin = 0
                const total = diffDays(first, addDays(`${this.nextMonth(month)}-01`, 0));
                const cells = [];
                for (let i = 0; i < offset; i++) cells.push({ key: `e${month}${i}`, day: null });
                for (let d = 0; d < total; d++) { const key = addDays(first, d); cells.push({ key, day: d + 1 }); }
                while (cells.length % 7) cells.push({ key: `t${month}${cells.length}`, day: null });
                const weeks = [];
                for (let i = 0; i < cells.length; i += 7) weeks.push(cells.slice(i, i + 7));
                return weeks;
            },

            // Tanggal yang menerima Tab (roving tabindex): fokus terakhir / mulai / hari ini / tanggal 1.
            tabKeyFor(month) {
                for (const k of [this.focusKey, this.startDate, this.today]) if (k && k.startsWith(month)) return k;
                return `${month}-01`;
            },

            get timeOptions() {
                const opts = [];
                // Hari ini: mulai paling cepat jam penuh berikutnya (sama dengan aturan filter katalog).
                const minHour = this.startDate === this.today ? parseInt(wib({ hour: '2-digit', hourCycle: 'h23' }), 10) + 1 : 0;
                for (let h = minHour; h < 24; h++) opts.push(`${String(h).padStart(2, '0')}:00`);
                if (this.time && !opts.includes(this.time)) opts.unshift(this.time);
                return opts;
            },

            nextMonth(mk) { const [y, m] = mk.split('-').map(Number); return m === 12 ? `${y + 1}-01` : `${y}-${String(m + 1).padStart(2, '0')}`; },
            prevMonth(mk) { const [y, m] = mk.split('-').map(Number); return m === 1 ? `${y - 1}-12` : `${y}-${String(m - 1).padStart(2, '0')}`; },

            // Bulan tampil + bulan berikutnya (rentang bisa melewati akhir bulan).
            loadAround() { this.loadMonth(this.cursor); this.loadMonth(this.nextMonth(this.cursor)); },

            async loadMonth(mk) {
                if (this.loadedMonths[mk]) return;
                this.loadedMonths[mk] = true;
                this.pendingMonths++;
                try {
                    const response = await fetch(`${this.jadwalUrl}?month=${mk}`, { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error();
                    const data = await response.json();
                    this.booked = { ...this.booked, ...data.days };
                    this.loadedMonths[mk] = 'ready';
                    this.calError = null;
                } catch (e) {
                    this.loadedMonths[mk] = false;
                    this.calError = 'Jadwal kalender belum bisa dimuat. Ketersediaan tetap dicek saat memilih jam.';
                } finally {
                    this.pendingMonths--;
                }
            },

            move(step) {
                if (step < 0 && !this.canPrev) return;
                this.cursor = step < 0 ? this.prevMonth(this.cursor) : this.nextMonth(this.cursor);
                this.loadAround();
            },

            // --- Tampilan saja; keputusan akhir dari endpoint durasi ---
            isFull(k) { return (this.booked[k] || []).some(r => r === '00:00–24:00'); },
            isPartial(k) { return (this.booked[k] || []).length > 0 && !this.isFull(k); },
            partialText(k) { return this.isPartial(k) ? `Terpesan ${this.booked[k].join(', ')}` : ''; },

            // Tanggal selesai valid: setelah mulai, ≤ maks hari, hari selesai tidak penuh,
            // dan hari di antaranya tanpa pesanan (sewa menutup hari-hari itu penuh).
            canEnd(k) {
                if (!this.startDate || k <= this.startDate || diffDays(this.startDate, k) > this.maxDays || this.isFull(k)) return false;
                for (let d = addDays(this.startDate, 1); d < k; d = addDays(d, 1)) if ((this.booked[d] || []).length) return false;
                return true;
            },

            isOff(k) {
                if (k < this.today || this.isFull(k)) return true;
                if (this.startDate && !this.endDate && k > this.startDate) return !this.canEnd(k);
                return false;
            },

            get rangeEnd() {
                if (this.endDate) return this.endDate;
                return this.startDate && this.hoverKey && this.canEnd(this.hoverKey) ? this.hoverKey : null;
            },

            isEdge(k) { return k === this.startDate || k === this.endDate; },

            band(k) {
                const end = this.rangeEnd;
                if (!this.startDate || !end) return '';
                if (k === this.startDate) return 'start';
                if (k === end) return 'end';
                return k > this.startDate && k < end ? 'mid' : '';
            },

            label(k) {
                let text = fmt(k, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                if (k === this.startDate) text += ', tanggal mulai';
                if (k === this.endDate) text += ', tanggal selesai';
                if (this.isOff(k)) return `${text}, tidak bisa dipilih`;
                return this.isPartial(k) ? `${text}, ${this.partialText(k).toLowerCase()}` : `${text}, tersedia`;
            },

            pick(k) {
                if (this.isOff(k)) return;
                if (this.startDate && !this.endDate && k > this.startDate) {
                    this.endDate = k;
                } else if (k === this.startDate && !this.endDate) {
                    this.startDate = null;
                } else {
                    this.startDate = k;
                    this.endDate = null;
                }
                this.focusKey = k;
                this.check();
            },

            clearDates() {
                this.startDate = this.endDate = this.hoverKey = null;
                this.time = '';
                this.status = null;
            },

            onKey(event) {
                const k = event.target.dataset?.date;
                const step = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[event.key];
                if (!k || !step) return;
                event.preventDefault();
                const next = addDays(k, step);
                if (next.slice(0, 7) < this.today.slice(0, 7)) return;
                if (!next.startsWith(this.cursor)) { this.cursor = next.slice(0, 7); this.loadAround(); }
                if (this.mobileMonths.length && next.slice(0, 7) > this.mobileMonths.at(-1)) this.moreMonths();
                this.focusKey = next;
                this.$nextTick(() => [...this.$el.querySelectorAll(`[data-date="${next}"]`)].find(el => el.getClientRects().length)?.focus());
            },

            // Validasi akhir lewat endpoint durasi yang sama dengan checkout (days = selisih hari).
            async check(force = false) {
                if (!this.days || !this.time) { this.status = null; return; }
                const start = `${this.startDate}T${this.time}`;
                const days = this.days;
                if (force === true || this.durationsFor !== start) {
                    this.status = 'loading';
                    try {
                        const response = await fetch(`/api/kendaraan/${vehicleId}/durasi?start=${start}`, { headers: { Accept: 'application/json' } });
                        if (!response.ok) throw new Error();
                        this.durations = await response.json();
                        this.durationsFor = start;
                    } catch (e) {
                        this.durationsFor = null;
                        this.status = 'error';
                        return;
                    }
                }
                // Pilihan bisa berubah saat menunggu respons.
                if (start !== `${this.startDate}T${this.time}` || days !== this.days) return;
                this.status = this.durations?.[days]?.available ? 'ok' : 'conflict';
            },

            fmtShort(k) { return k ? fmt(k, { day: 'numeric', month: 'short', year: 'numeric' }) : ''; },

            formatPrice(price) { return window.formatRupiah(price); },

            openModal() {
                if (this.datesSaved && this.days) this.$dispatch('open-modal', `booking-modal-${vehicleId}`);
                else this.editDates();
            },

            proceed() {
                if (this.status !== 'ok') return;
                // Kontrak lama checkout: start = Y-m-d\TH:i (WIB, tanpa zona), days = jumlah hari.
                window.location.href = `/checkout/${vehicleId}?start=${this.startDate}T${this.time}&days=${this.days}`;
            }
        }));
    });
</script>
