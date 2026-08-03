import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';
import { copyFileSync, mkdirSync } from 'fs';

export default defineConfig({
    base: '/build/',
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),

        {
            name: 'copy-pdf-worker',
            closeBundle() {
                try {
                    mkdirSync(resolve('public/vendor'), { recursive: true });
                    copyFileSync(
                        resolve('node_modules/pdfjs-dist/build/pdf.worker.min.mjs'),
                        resolve('public/vendor/pdf.worker.min.js')
                    );
                    console.log('✅ pdf.worker.min.js copiado a public/vendor/');
                } catch (e) {
                    console.error('❌ Error copiando pdf worker:', e);
                }
            },
        },
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
});
