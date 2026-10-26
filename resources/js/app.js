

import Alpine from 'alpinejs';
import { initReveal } from './reveal';

window.formatRupiah = amount => 'Rp' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(amount));

window.Alpine = Alpine;

// Kunci tetap aktif ketika satu panel menyerahkan fokus ke panel lain.
const panelLocks = new Set();
window.setPanelScrollLock = (panel, locked) => {
    if (locked) panelLocks.add(panel); else panelLocks.delete(panel);
    document.body.classList.toggle('overflow-y-hidden', panelLocks.size > 0);
};

window.trapPanelFocus = (event, panel) => {
    const items = [...panel.querySelectorAll('a[href],button,input,textarea,select,[tabindex]')]
        .filter(el => !el.disabled && el.tabIndex >= 0 && el.getClientRects().length);
    const first = items[0], last = items.at(-1);
    if (!first) { event.preventDefault(); return; }
    if (event.shiftKey && (document.activeElement === first || !items.includes(document.activeElement))) {
        event.preventDefault(); last.focus();
    } else if (!event.shiftKey && (document.activeElement === last || !items.includes(document.activeElement))) {
        event.preventDefault(); first.focus();
    }
};

Alpine.start();
initReveal();
