import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                forest: {
                    DEFAULT: '#1f2b6c',
                    deep: '#0a1128',
                    mid: '#2a3a85',
                    soft: '#eef0f6',
                },
                clay: '#ef3d32',
                sand: {
                    DEFAULT: '#f7f8fc',
                    dark: '#e8eaf2',
                },
                ink: '#1f2b6c',
            },
            fontFamily: {
                sans: ['Poppins', ...defaultTheme.fontFamily.sans],
                display: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card: '0 1px 0 rgba(31, 43, 108, 0.06), 0 4px 24px rgba(31, 43, 108, 0.05)',
                red: '0 4px 16px rgba(239, 61, 50, 0.22)',
            },
        },
    },

    plugins: [forms],
};
