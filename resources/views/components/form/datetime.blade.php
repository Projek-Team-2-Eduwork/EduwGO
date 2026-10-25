@props([
    'name',
    'label' => null,
    'value' => null,   // string/Carbon; diformat ke Y-m-d\TH:i untuk datetime-local
    'hint' => null,
    'picker' => false,  // true = picker flatpickr bertema token; nilai tetap Y-m-d\TH:i pada input asli
])

@php
    $id = $attributes->get('id', $name);
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $current = old($errorKey, $value);

    if ($current instanceof \DateTimeInterface) {
        $current = $current->format('Y-m-d\TH:i');
    }
@endphp

<div>
    @if ($label)
        <x-input-label :for="$id" :value="$label" />
    @endif

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="datetime-local"
        value="{{ $current }}"
        {{ $attributes->except('id')->merge(['class' => 'mt-1 block w-full rounded-lg shadow-sm']) }}
        @if ($picker) data-picker @endif
        @error($errorKey) aria-invalid="true" @enderror
    >

    @if ($hint)
        <p class="mt-1 text-xs">{{ $hint }}</p>
    @endif

    <x-input-error :messages="$errors->get($errorKey)" class="mt-2" />
</div>

@if ($picker)
    @once
        @push('scripts')
            {{-- flatpickr 4.6.13 via cdnjs (versi dikunci + SRI); tema ada di resources/css/app.css --}}
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css" integrity="sha384-RkASv+6KfBMW9eknReJIJ6b3UnjKOKC5bOUaNgIY778NFbQ8MtWq9Lr/khUgqtTt" crossorigin="anonymous" referrerpolicy="no-referrer">
            <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js" integrity="sha384-5JqMv4L/Xa0hfvtF06qboNdhvuYXUku9ZrhZh3bSk8VXF0A/RuSLHpLsSV9Zqhl6" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/l10n/id.min.js" integrity="sha384-E0CxBAGfod9gCki31XKBNEzeN7gE91l9etJFBFtmzfLNZPUEHY9j7ntGktCIvPU7" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <script>
                // Tanpa CDN (offline) input native datetime-local tetap berfungsi.
                if (window.flatpickr) {
                    // Kalender dipasang di <body> (static: false). Scroll tidak bubbling ke
                    // window, jadi posisinya kita ikuti manual supaya tetap menempel ke input.
                    const pickerAktif = [];
                    // Geser kalender seperlunya supaya selalu utuh di dalam layar (tidak kepotong
                    // atas/bawah) — mis. saat ruang di atas & bawah input sama-sama sempit.
                    const rapikanDiLayar = (fp) => {
                        if (! fp.isOpen) return;
                        const cal = fp.calendarContainer;
                        const kotak = cal.getBoundingClientRect();
                        const tepi = 8;
                        let geser = 0;
                        if (kotak.top < tepi) geser = tepi - kotak.top;
                        else if (kotak.bottom > window.innerHeight - tepi) geser = (window.innerHeight - tepi) - kotak.bottom;
                        if (geser) cal.style.top = `${cal.offsetTop + geser}px`;
                    };
                    const ikutiScroll = () => pickerAktif.forEach((fp) => {
                        if (fp.isOpen && typeof fp._positionCalendar === 'function') {
                            fp._positionCalendar();
                            rapikanDiLayar(fp);
                        }
                    });
                    window.addEventListener('scroll', ikutiScroll, true);
                    window.addEventListener('resize', ikutiScroll);

                    document.querySelectorAll('input[data-picker]').forEach((input) => {
                        const label = document.querySelector(`label[for="${input.id}"]`);

                        // Batas bawah = jam min dari server (kalau ada) atau sekarang.
                        const batasBawah = () => {
                            const minAttr = input.getAttribute('min');
                            const minMs = minAttr ? new Date(minAttr).getTime() : 0;
                            return new Date(Math.max(Date.now(), Number.isNaN(minMs) ? 0 : minMs));
                        };
                        // Slot 15 menit berikutnya (sesuai minuteIncrement).
                        const slotBerikutnya = (dari) => {
                            const d = new Date(dari);
                            d.setSeconds(0, 0);
                            d.setMinutes(d.getMinutes() + (15 - (d.getMinutes() % 15)));
                            return d;
                        };
                        const jamAwal = slotBerikutnya(batasBawah());

                        flatpickr(input, {
                            locale: 'id',
                            enableTime: true,
                            time_24hr: true,
                            minuteIncrement: 15,
                            dateFormat: 'Y-m-d\\TH:i',
                            altInput: true,
                            altFormat: 'd M Y, H:i',
                            altInputClass: input.className,
                            minDate: input.min || null,
                            // Jam default saat tanggal dipilih: slot berikutnya, bukan jam yang sudah lewat.
                            defaultHour: jamAwal.getHours(),
                            defaultMinute: jamAwal.getMinutes(),
                            // Nempel ke <body> supaya kalender tidak terpotong kotak modal.
                            static: false,
                            // Di HP juga pakai kalender ini (picker bawaan HP butuh tombol "Selesai").
                            disableMobile: true,
                            // Nilai yang diketik ikut tersimpan saat pindah fokus, tanpa Enter.
                            allowInput: true,
                            onOpen(selectedDates, value, fp) {
                                if (! pickerAktif.includes(fp)) pickerAktif.push(fp);
                                // Setelah layout siap, pastikan kalender kelihatan utuh di layar.
                                setTimeout(() => rapikanDiLayar(fp), 0);
                            },
                            onClose(selectedDates, value, fp) {
                                const idx = pickerAktif.indexOf(fp);
                                if (idx > -1) pickerAktif.splice(idx, 1);
                            },
                            onReady(selectedDates, value, fp) {
                                fp.altInput.placeholder = 'Pilih tanggal & jam';
                                fp.altInput.setAttribute('aria-label', label ? label.textContent.trim() : 'Tanggal & jam');

                                const clear = document.createElement('button');
                                clear.type = 'button';
                                clear.className = 'flatpickr-clear-btn';
                                clear.textContent = 'Hapus pilihan';
                                clear.addEventListener('click', () => { fp.clear(); fp.close(); });
                                fp.calendarContainer.appendChild(clear);

                                // Klik tanggal menutup; saat atur jam, jam pindah fokus ke menit, menit menutup.
                                fp.calendarContainer.addEventListener('click', (e) => {
                                    if (e.target.closest('.flatpickr-day') && !e.target.closest('.flatpickr-disabled')) {
                                        fp.close();
                                    }
                                });
                                ['change', 'increment'].forEach((evt) => {
                                    fp.hourElement.addEventListener(evt, () => fp.minuteElement.focus());
                                    fp.minuteElement.addEventListener(evt, () => setTimeout(() => fp.close(), 0));
                                });
                            },
                            onValueUpdate(selectedDates, value, fp) {
                                const pilih = selectedDates && selectedDates[0];

                                // Nilai di masa lalu (mis. pilih "hari ini" tapi jamnya keburu lewat)
                                // dimajukan ke slot berikutnya supaya API ketersediaan tidak menolak (422).
                                if (pilih && pilih.getTime() < batasBawah().getTime()) {
                                    fp.setDate(slotBerikutnya(batasBawah()), true);
                                    return;
                                }

                                // Kirim "input" dulu baru "change": flatpickr sendiri mengirim
                                // "change" lebih dulu, sehingga @change (fetch durasi) membaca nilai lama.
                                fp.input.dispatchEvent(new Event('input', { bubbles: true }));
                                fp.input.dispatchEvent(new Event('change', { bubbles: true }));
                            },
                        });
                    });
                }
            </script>
        @endpush
    @endonce
@endif
