<x-guest-layout>
    <style>
        @keyframes otp-shake { 0%, 100% { transform: translateX(0); } 20%, 60% { transform: translateX(-6px); } 40%, 80% { transform: translateX(6px); } }
        @keyframes otp-rise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .otp-shake { animation: otp-shake 0.35s ease-in-out; }
        .otp-box { animation: otp-rise 0.4s ease-out both; }
        @media (prefers-reduced-motion: reduce) { .otp-shake, .otp-box { animation: none; } }
    </style>

    <h1 class="text-3xl sm:text-4xl">Masukkan Kode Verifikasi</h1>

    <p class="mt-2 text-sm">
        Kami mengirim kode 6 angka ke
        <strong class="text-[var(--navy-900)]">{{ auth()->user()->email }}</strong>.
        Kode berlaku {{ \App\Services\EmailVerificationCodeService::TTL_MINUTES }} menit. Verifikasi diperlukan sebelum Anda bisa checkout.
    </p>

    @if (session('status') == 'verification-code-sent')
        <div class="mt-4 font-medium text-sm text-green-700 dark:text-green-400">
            Kode verifikasi baru sudah dikirim ke email yang Anda daftarkan.
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('verification.verify') }}"
        x-ref="form"
        class="mt-8"
        x-data="{
            digits: ['', '', '', '', '', ''],
            hasError: {{ $errors->has('code') ? 'true' : 'false' }},
            shake: {{ $errors->has('code') ? 'true' : 'false' }},
            submitting: false,
            box(i) { return this.$refs.boxes.querySelectorAll('input')[i]; },
            init() {
                if (this.shake) setTimeout(() => this.shake = false, 500);
                this.$nextTick(() => this.box(0).focus());
            },
            onInput(i, e) {
                const v = e.target.value.replace(/\D/g, '').slice(-1);
                e.target.value = v;
                this.digits[i] = v;
                this.hasError = false;
                if (v && i < 5) this.box(i + 1).focus();
                this.maybeSubmit();
            },
            onKeydown(i, e) {
                if (e.key === 'Backspace' && !this.digits[i] && i > 0) this.box(i - 1).focus();
                else if (e.key === 'ArrowLeft' && i > 0) this.box(i - 1).focus();
                else if (e.key === 'ArrowRight' && i < 5) this.box(i + 1).focus();
            },
            onPaste(e) {
                e.preventDefault();
                const d = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6).split('');
                d.forEach((c, k) => { this.digits[k] = c; this.box(k).value = c; });
                this.box(Math.min(d.length, 5)).focus();
                this.hasError = false;
                this.maybeSubmit();
            },
            maybeSubmit() {
                if (this.submitting || this.digits.some(d => d === '')) return;
                this.submitting = true;
                this.$nextTick(() => this.$refs.form.submit());
            },
        }"
    >
        @csrf
        <input type="hidden" name="code" :value="digits.join('')">

        <div x-ref="boxes" class="flex items-center justify-center gap-2 sm:gap-3" :class="shake && 'otp-shake'">
            <template x-for="i in 6" :key="i">
                <div
                    class="otp-box h-14 w-11 overflow-hidden rounded-lg ring-2 transition-all duration-200 sm:h-16 sm:w-14"
                    :style="`animation-delay: ${(i - 1) * 50}ms`"
                    :class="hasError ? 'ring-red-400 dark:ring-red-500' : 'ring-[var(--border)] focus-within:ring-[var(--navy-700)]'"
                >
                    <input
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="1"
                        :aria-label="`Digit ${i} dari 6`"
                        :value="digits[i - 1]"
                        :disabled="submitting"
                        x-on:input="onInput(i - 1, $event)"
                        x-on:keydown="onKeydown(i - 1, $event)"
                        x-on:paste="onPaste($event)"
                        x-on:focus="$event.target.select()"
                        class="h-full w-full !rounded-none !border-0 !p-0 text-center text-2xl font-semibold outline-none !shadow-none focus:!shadow-none sm:text-3xl"
                    >
                </div>
            </template>
        </div>

        <div class="mt-3 min-h-[1.5rem] text-center text-sm" aria-live="polite">
            <p x-show="hasError" x-cloak class="font-medium text-red-600 dark:text-red-400">{{ $errors->first('code') }}</p>
            <p x-show="submitting" x-cloak class="text-[var(--ink-muted)]">Memverifikasi…</p>
        </div>
    </form>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3" x-data="{ countdown: 60 }" x-init="setInterval(() => { if (countdown > 0) countdown-- }, 1000)">
        <div class="text-sm">
            <span class="text-[var(--ink-muted)]">Tidak menerima kode?</span>
            <span x-show="countdown > 0" class="text-[var(--ink-muted)]">Kirim ulang dalam <span x-text="countdown"></span> detik</span>
            <form x-show="countdown === 0" x-cloak method="POST" action="{{ route('verification.send') }}" class="inline">
                @csrf
                <button type="submit" class="font-medium underline text-[var(--navy-900)] rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Kirim ulang kode</button>
            </form>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm underline text-[var(--navy-900)] rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
