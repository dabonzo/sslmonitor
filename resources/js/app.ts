import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import { initializeTheme } from './composables/useAppearance';
import { useToast } from './composables/useToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        // Use lazy loading for better code splitting
        return resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue', { eager: false }));
    },
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        const app = createApp({ render: () => h(App, props) });

        app.use(plugin);
        app.use(pinia);
        app.mount(el);
    },
    progress: {
        color: '#4361ee',
    },
}).then(() => {
    // Handle Laravel flash messages
    const toast = useToast();

    router.on('success', (event) => {
        const page = event.detail.page as any;

        if (!page || !page.props) {
            return;
        }

        const flash = page.props.flash;

        if (flash) {
            if (flash.success) {
                toast.success(flash.success);
            }

            if (flash.error) {
                toast.error(flash.error);
            }

            if (flash.warning) {
                toast.warning(flash.warning);
            }

            if (flash.info) {
                toast.info(flash.info);
            }

            if (flash.message) {
                toast.toast(flash.message);
            }
        }
    });
});

// Initialize theme on page load
initializeTheme();
