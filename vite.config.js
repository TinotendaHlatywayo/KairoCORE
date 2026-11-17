import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/marketing.css',
                'resources/css/kairo-site.css',
                'resources/css/filament-custom.css',
                'resources/css/panel-tailwind.css',
                'resources/css/academics.css',
                'resources/css/kairo-v2/home.css',
                'resources/css/kairo-v2/features.css',
                'resources/css/kairo-v2/about.css',
                'resources/css/kairo-v2/contact.css',
                'resources/css/kairo-v2/blog.css',
                'resources/css/kairo-v2/responsive.css',
                'resources/js/app.js',
                'resources/js/kairo-site.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
