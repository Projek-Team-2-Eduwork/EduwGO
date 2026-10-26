@props(['name', 'show' => false, 'maxWidth' => '2xl', 'inlineDesktop' => false, 'panelClass' => '', 'placement' => 'bottom'])
@php
    $width = ['sm' => 'lg:max-w-sm', 'md' => 'lg:max-w-md', 'lg' => 'lg:max-w-lg', 'xl' => 'lg:max-w-xl', '2xl' => 'lg:max-w-2xl'][$maxWidth];
    // 'contents': di desktop kotak modal hilang, isinya ikut grid induk (field satu form ditata bebas).
    $desktopDisplay = $inlineDesktop === 'contents' ? 'lg:contents' : 'lg:block';
@endphp
<div x-data="{ show: @js($show), desktop: false, opener: null,
        focusables() { return [...this.$el.querySelectorAll('a[href],button,input,textarea,select,[tabindex]')].filter(el => !el.disabled && el.tabIndex >= 0 && el.getClientRects().length) },
        trap(event) {
            if (!this.show || this.desktop || event.target.closest('[role=dialog]') !== this.$refs.dialog) return;
            window.trapPanelFocus(event, this.$el);
        }
    }"
    x-init="if (@js($inlineDesktop)) { const media = window.matchMedia('(min-width: 1024px)'); desktop = media.matches; if (desktop) show = false; media.addEventListener('change', event => { desktop = event.matches; if (desktop) show = false; }); } if (show && !desktop) { window.setPanelScrollLock($el, true); $el.setAttribute('data-modal-open', ''); $nextTick(() => (focusables()[0] || $refs.dialog).focus()); } $watch('show', value => { $dispatch('modal-state', { name: @js($name), show: value }); window.setPanelScrollLock($el, value); if(value) { opener = document.activeElement; $nextTick(() => (focusables()[0] || $refs.dialog).focus()); } else { $nextTick(() => { const activePanel = [...document.querySelectorAll('[data-modal-open]')].at(-1); if (opener?.getClientRects().length && (!activePanel || activePanel.contains(opener))) opener.focus(); }); } $el.toggleAttribute('data-modal-open', value); })"
    x-on:open-modal.window="if ($event.detail === @js($name)) show = true"
    x-on:close-modal.window="if ($event.detail === @js($name)) show = false"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="if (show && !$event.defaultPrevented && [...document.querySelectorAll('[data-modal-open]')].at(-1) === $el) { $event.preventDefault(); show = false; }"
    x-on:keydown.tab="trap($event)"
    x-show="show || desktop" x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center lg:items-center {{ $inlineDesktop ? 'lg:static lg:z-auto '.$desktopDisplay.' lg:p-0' : 'lg:p-6' }} {{ $placement === 'navbar' ? 'modal-from-navbar' : '' }}"
    style="display: {{ $show ? 'flex' : 'none' }};">
    <div x-show="show && !desktop" class="scrim absolute inset-0" x-on:click="show = false" x-transition:enter="scrim-enter" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="scrim-leave" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" aria-hidden="true"></div>
    <div x-show="show || desktop" x-ref="dialog" role="dialog" aria-modal="true" :role="desktop ? null : 'dialog'" :aria-modal="desktop ? null : 'true'" aria-label="{{ $attributes->get('aria-label', 'Dialog konfirmasi') }}" tabindex="-1"
        class="relative max-h-[90dvh] w-full overflow-y-auto rounded-t-3xl bg-[var(--surface)] text-[var(--ink-muted)] lg:rounded-lg {{ $width }} {{ $panelClass }} {{ $inlineDesktop ? 'lg:max-h-none lg:max-w-none lg:overflow-visible lg:rounded-none lg:bg-transparent '.($inlineDesktop === 'contents' ? 'lg:contents' : '') : '' }}" @include('components.panel-transition')>
        {{ $slot }}
    </div>
</div>
