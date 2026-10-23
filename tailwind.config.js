import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Tipografi mengikuti Figma ED.RENT: Plus Jakarta Sans untuk body & heading.
                sans: ['Plus Jakarta Sans', 'Inter', ...defaultTheme.fontFamily.sans],
                // Nama utility `font-serif` dipertahankan supaya view lama tidak perlu diubah,
                // tetapi sekarang memakai Plus Jakarta Sans (bukan serif).
                serif: ['Plus Jakarta Sans', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Palet EduwGo dipetakan ke CSS variable (docs/SPEC.md bagian 8)
                surface: 'var(--surface)',
                'navy-900': 'var(--navy-900)',
                'navy-700': 'var(--navy-700)',
                'ink-muted': 'var(--ink-muted)',
                'orange-500': 'var(--orange-500)',
                'coral-300': 'var(--coral-300)',
                'line-token': 'var(--border)',
                'gold-tan': 'var(--gold-tan)',
            },
        },
    },

    plugins: [forms],
};
