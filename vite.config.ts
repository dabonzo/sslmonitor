import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // Split vendor dependencies
                    'vendor-vue': ['vue', '@inertiajs/vue3'],
                    'vendor-ui': ['lucide-vue-next'],
                    'vendor-utils': ['clsx', 'tailwind-merge', 'class-variance-authority'],
                }
            }
        },
        // Optimize chunk size
        chunkSizeWarningLimit: 1000,
        // Enable source maps for production debugging
        sourcemap: false,
        // Optimize assets
        assetsInlineLimit: 4096,
    },
    resolve: {
        alias: {
            '@': '/resources/js',
            '@components': '/resources/js/Components',
            '@pages': '/resources/js/Pages',
            '@layouts': '/resources/js/Layouts',
        }
    },
    // Optimize dependencies
    optimizeDeps: {
        include: [
            'vue',
            '@inertiajs/vue3',
            'lucide-vue-next'
        ]
    }
});
