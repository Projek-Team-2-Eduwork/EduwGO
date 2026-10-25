@props(['name', 'show' => false, 'maxWidth' => '2xl', 'inlineDesktop' => false])
@php
    $width = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-md', 'lg' => 'sm:max-w-lg', 'xl' => 'sm:max-w-xl', '2xl' => 'sm:max-w-2xl'][$maxWidth];
    // 'contents': di desktop kotak modal hilang, isinya ikut grid induk (field satu form ditata bebas).
    $desktopDisplay = $inlineDesktop === 'contents' ? 'lg:contents' : 'lg:block';
@endphp
<div x-data="{ show: @js($show), desktop: false, opener: null,
        focusables() { return [...$el.querySelectorAll('a[href],button,input,textarea,select,[tabindex]')].filter(el => !el.disabled && el.tabIndex >= 0 && el.getClientRects().length) },
        trap(event) {
            if (!show || desktop) return;
            const items = this.focusables();
            if (!items.length) { event.preventDefault(); return; }
            const first = items[0], last = items[items.length - 1];
            if (event.shiftKey && (document.activeElement === first || !items.includes(document.activeElement))) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && (document.activeElement === last || !items.includes(document.activeElement))) { event.preventDefault(); first.focus(); }
        }
    }"
    x-init="if (@js($inlineDesktop)) { const media = window.matchMedia('(min-width: 1024px)'); desktop = media.matches; media.addEventListener('change', event => { desktop = event.matches; if (desktop) show = false; }); } $watch('show', value => { document.body.classList.toggle('overflow-y-hidden', value); if(value) { opener = document.activeElement; $nextTick(() => (focusables()[0] || $refs.dialog).focus()); } else { opener?.focus(); } })"
    x-on:open-modal.window="if ($event.detail === @js($name)) show = true"
    x-on:close-modal.window="if ($event.detail === @js($name)) show = false"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="if (show && !$event.defaultPrevented) { $event.preventDefault(); show = false; }"
    x-on:keydown.tab="trap($event)"
    x-show="show || desktop" x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-6 {{ $inlineDesktop ? 'lg:static lg:z-auto '.$desktopDisplay.' lg:p-0' : '' }}"
    style="display: {{ $show ? 'flex' : 'none' }};">
    <div x-show="!desktop" class="scrim absolute inset-0" x-on:click="show = false" x-transition.opacity aria-hidden="true"></div>
    <div x-ref="dialog" role="dialog" aria-modal="true" :role="desktop ? null : 'dialog'" :aria-modal="desktop ? null : 'true'" aria-label="{{ $attributes->get('aria-label', 'Dialog konfirmasi') }}" tabindex="-1"
        class="relative max-h-[90dvh] w-full overflow-y-auto rounded-t-lg bg-[var(--surface)] text-[var(--ink-muted)] sm:rounded-lg {{ $width }} {{ $inlineDesktop ? 'lg:max-h-none lg:max-w-none lg:overflow-visible lg:rounded-none lg:bg-transparent '.($inlineDesktop === 'contents' ? 'lg:contents' : '') : '' }}" x-transition>
        {{ $slot }}
    </div>
</div>
