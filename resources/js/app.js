

import Alpine from 'alpinejs';
import { initReveal } from './reveal';

window.formatRupiah = amount => 'Rp' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(amount));

window.Alpine = Alpine;

Alpine.start();
initReveal();
