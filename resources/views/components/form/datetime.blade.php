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
        {{ $attributes->except('id')->merge(['class' => 'mt-1 block w-full rounded-md shadow-sm']) }}
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
                    document.querySelectorAll('input[data-picker]').forEach((input) => {
                        const label = document.querySelector(`label[for="${input.id}"]`);

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
                            static: true,
                            disableMobile: false,
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
                        });
                    });
                }
            </script>
        @endpush
    @endonce
@endif
