import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Add comprehensive error logging
console.log('🚀 App.ts loading...');

// Global error handlers
window.addEventListener('error', (event) => {
    console.error('🔥 Global JavaScript Error:', {
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno,
        error: event.error,
        stack: event.error?.stack
    });
});

window.addEventListener('unhandledrejection', (event) => {
    console.error('🔥 Unhandled Promise Rejection:', {
        reason: event.reason,
        promise: event.promise
    });
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        console.log('🔍 Resolving page component:', name);
        try {
            // Use lazy loading for better code splitting
            const component = resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue', { eager: false }));
            console.log('✅ Component resolved successfully:', name);
            return component;
        } catch (error) {
            console.error('🔥 Error resolving component:', name, error);
            throw error;
        }
    },
    setup({ el, App, props, plugin }) {
        console.log('🏗️ Setting up Vue app...', { el, props });

        try {
            const pinia = createPinia();
            console.log('📦 Pinia created');

            const app = createApp({ render: () => h(App, props) });
            console.log('🎯 Vue app created');

            app.use(plugin);
            console.log('🔌 Inertia plugin added');

            app.use(pinia);
            console.log('🗄️ Pinia added');

            // Add global error handler to Vue app
            app.config.errorHandler = (err, vm, info) => {
                console.error('🔥 Vue Error Handler:', { err, vm, info });
            };

            app.mount(el);
            console.log('🎉 Vue app mounted successfully');
        } catch (error) {
            console.error('🔥 Error in setup:', error);
            throw error;
        }
    },
    progress: {
        color: '#4361ee',
    },
}).then(() => {
    console.log('✨ Inertia app created successfully');
}).catch((error) => {
    console.error('🔥 Error creating Inertia app:', error);
});

// This will set light / dark mode on page load...
try {
    console.log('🎨 Initializing theme...');
    initializeTheme();
    console.log('✅ Theme initialized successfully');
} catch (error) {
    console.error('🔥 Error initializing theme:', error);
}
