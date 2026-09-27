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
                sans: ['Inter', 'Manrope', ...defaultTheme.fontFamily.sans],
                serif: ['Instrument Serif', 'Playfair Display', ...defaultTheme.fontFamily.serif],
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
