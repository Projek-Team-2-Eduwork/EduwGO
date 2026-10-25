@php
    // Dipakai dua alur: pendaftar tertunda (belum punya akun) dan user lama yang login tapi belum verifikasi.
    $pendingRegistration ??= false;
    $email ??= auth()->user()->email;
    $verifyAction ??= route('verification.verify');
    $resendAction ??= route('verification.send');
    $resendIn ??= 60;
@endphp

<x-guest-layout title="Masukkan Kode Verifikasi">
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
        <strong class="break-all text-[var(--ink)]">{{ $email }}</strong>.
        Kode berlaku {{ \App\Services\EmailVerificationCodeService::TTL_MINUTES }} menit.
        {{ $pendingRegistration ? 'Akun Anda dibuat setelah kode benar.' : 'Verifikasi diperlukan sebelum Anda bisa checkout.' }}
    </p>

    @if (session('status') == 'verification-code-sent')
        <div class="mt-4 font-medium text-sm text-[var(--success)] dark:text-[var(--success)]">
            Kode verifikasi baru sudah dikirim ke email yang Anda daftarkan.
        </div>
    @endif

    <form novalidate
        method="POST"
        action="{{ $verifyAction }}"
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
                    :class="hasError ? 'ring-[var(--line)] dark:ring-[var(--line)]' : 'ring-[var(--line)] focus-within:ring-[var(--ink-muted)]'"
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
            <p x-show="hasError" x-cloak class="font-medium text-[var(--danger)] dark:text-[var(--danger)]">{{ $errors->first('code') }}</p>
            <p x-show="submitting" x-cloak class="text-[var(--ink-muted)]">Memverifikasi…</p>
        </div>
    </form>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3" x-data="{ countdown: {{ (int) $resendIn }} }" x-init="setInterval(() => { if (countdown > 0) countdown-- }, 1000)">
        <div class="text-sm">
            <span class="text-[var(--ink-muted)]">Tidak menerima kode?</span>
            <span x-show="countdown > 0" class="text-[var(--ink-muted)]">Kirim ulang dalam <span x-text="countdown"></span> detik</span>
            <form novalidate x-show="countdown === 0" x-cloak method="POST" action="{{ $resendAction }}" class="inline">
                @csrf
                <button type="submit" class="font-medium underline text-[var(--ink)] rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent)]">Kirim ulang kode</button>
            </form>
        </div>

        @if ($pendingRegistration)
            <form novalidate method="POST" action="{{ route('register.restart') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm underline text-[var(--ink)] rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent)]">
                    Ubah email / daftar ulang
                </button>
            </form>
        @else
            <form novalidate method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm underline text-[var(--ink)] rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent)]">
                    Keluar
                </button>
            </form>
        @endif
    </div>
</x-guest-layout>
