# VRISTO Template Integration Guide

## 🎨 Overview

This guide provides comprehensive instructions for integrating the VRISTO admin template with Laravel 12 + Vue 3 + Inertia.js for SSL Monitor v3.

**VRISTO Template**: Professional admin dashboard template with TailwindCSS, Alpine.js, and modern UI components.

---

## 📁 VRISTO Template Structure Analysis

### Available Template Files
```
vristo-html-starter/
├── assets/
│   ├── css/
│   │   ├── style.css           # Compiled TailwindCSS
│   │   ├── tailwind.css        # Source TailwindCSS
│   │   ├── perfect-scrollbar.min.css
│   │   └── animate.css
│   └── js/
│       ├── perfect-scrollbar.min.js
│       └── popper.min.js
├── index.html                  # Main dashboard layout
├── package.json               # NPM dependencies
├── tailwind.config.js         # TailwindCSS configuration
└── favicon.png

vristo-html-main/
├── auth-cover-login.html       # Login page template
├── index.html                  # Full dashboard
└── [100+ additional templates]
```

### VRISTO Key Features
- **TailwindCSS v3** with custom configuration
- **Alpine.js** for interactive components
- **Perfect Scrollbar** for custom scrollbars
- **Animate.css** for smooth animations
- **Dark/Light Theme** switching
- **Responsive Design** with mobile sidebar
- **Professional Components** (tables, modals, forms)

---

## 🚀 Integration Strategy

### Phase 1: Asset Extraction and Setup

#### 1.1 Extract VRISTO Assets
```bash
# Copy VRISTO assets to Laravel project
mkdir -p resources/js/vristo
mkdir -p resources/css/vristo
mkdir -p public/vristo

# Copy CSS assets
cp vristo-html-starter/assets/css/* resources/css/vristo/
cp vristo-html-starter/assets/js/* resources/js/vristo/

# Copy TailwindCSS configuration
cp vristo-html-starter/tailwind.config.js ./tailwind.config.js

# Copy package.json dependencies
# Merge VRISTO dependencies into Laravel's package.json
```

#### 1.2 Update Laravel's package.json
```json
{
  "devDependencies": {
    "@tailwindcss/forms": "^0.5.1",
    "@tailwindcss/typography": "^0.5.2",
    "prettier": "^2.8.4",
    "prettier-plugin-tailwindcss": "^0.1.13",
    "tailwindcss": "^3.4.1",
    "@vitejs/plugin-vue": "^4.0.0",
    "vue": "^3.3.0"
  },
  "dependencies": {
    "animate.css": "^4.1.1",
    "perfect-scrollbar": "^1.5.5",
    "@inertiajs/vue3": "^1.0.0",
    "alpinejs": "^3.12.0"
  }
}
```

#### 1.3 Configure Vite for VRISTO Assets
```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/vristo/style.css',
                'resources/js/app.js',
                'resources/js/vristo/perfect-scrollbar.min.js'
            ],
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
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '@vristo': '/resources/js/vristo',
        },
    },
});
```

### Phase 2: TailwindCSS Configuration

#### 2.1 Update tailwind.config.js with VRISTO Settings
```javascript
// tailwind.config.js
const { fontFamily } = require('tailwindcss/defaultTheme');

module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],
    darkMode: 'class',
    theme: {
        container: {
            center: true,
        },
        extend: {
            fontFamily: {
                nunito: ['Nunito', 'sans-serif'],
                sans: ['Nunito', ...fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: '#4361ee',
                    light: '#eaf1ff',
                    'dark-light': 'rgba(67,97,238,.15)',
                },
                secondary: {
                    DEFAULT: '#805dca',
                    light: '#ebe4f7',
                    'dark-light': 'rgba(128,93,202,.15)',
                },
                success: {
                    DEFAULT: '#00ab55',
                    light: '#ddf5f0',
                    'dark-light': 'rgba(0,171,85,.15)',
                },
                danger: {
                    DEFAULT: '#e7515a',
                    light: '#fff5f5',
                    'dark-light': 'rgba(231,81,90,.15)',
                },
                warning: {
                    DEFAULT: '#e2a03f',
                    light: '#fff9ed',
                    'dark-light': 'rgba(226,160,63,.15)',
                },
                info: {
                    DEFAULT: '#2196f3',
                    light: '#e7f7ff',
                    'dark-light': 'rgba(33,150,243,.15)',
                },
                dark: {
                    DEFAULT: '#3b3f5c',
                    light: '#eaeaec',
                    'dark-light': 'rgba(59,63,92,.15)',
                },
                black: {
                    DEFAULT: '#0e1726',
                    light: '#e3e4eb',
                    'dark-light': 'rgba(14,23,38,.15)',
                },
                white: {
                    DEFAULT: '#ffffff',
                    light: '#e0e6ed',
                    dark: '#888ea8',
                },
            },
            spacing: {
                4.5: '18px',
            },
            boxShadow: {
                '3xl': '0 2px 2px 0 rgba(0, 0, 0, 0.14), 0 3px 1px -2px rgba(0, 0, 0, 0.12), 0 1px 5px 0 rgba(0, 0, 0, 0.20)',
            },
            typography: ({ theme }) => ({
                DEFAULT: {
                    css: {
                        '--tw-prose-invert-headings': theme('colors.white.dark'),
                        '--tw-prose-invert-links': theme('colors.white.dark'),
                        h1: { fontSize: '40px', marginBottom: '0.5rem', marginTop: 0 },
                        h2: { fontSize: '32px', marginBottom: '0.5rem', marginTop: 0 },
                        h3: { fontSize: '28px', marginBottom: '0.5rem', marginTop: 0 },
                        h4: { fontSize: '24px', marginBottom: '0.5rem', marginTop: 0 },
                        h5: { fontSize: '20px', marginBottom: '0.5rem', marginTop: 0 },
                        h6: { fontSize: '16px', marginBottom: '0.5rem', marginTop: 0 },
                    },
                },
            }),
        },
    },
    plugins: [
        require('@tailwindcss/forms')({
            strategy: 'class',
        }),
        require('@tailwindcss/typography'),
    ],
};
```

#### 2.2 Setup Main CSS File
```css
/* resources/css/app.css */
@import 'tailwindcss/base';
@import 'tailwindcss/components';
@import 'tailwindcss/utilities';

/* Import Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap');

/* Import VRISTO specific styles */
@import './vristo/style.css';
@import './vristo/animate.css';
@import './vristo/perfect-scrollbar.min.css';

/* Custom VRISTO components */
.btn {
    @apply inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-150;
}

.btn-primary {
    @apply bg-primary text-white hover:bg-primary/90;
}

.btn-secondary {
    @apply bg-secondary text-white hover:bg-secondary/90;
}

.btn-outline-primary {
    @apply border border-primary text-primary hover:bg-primary hover:text-white;
}

/* Dark mode variables */
:root {
    --sidebar-width: 260px;
}

.dark {
    --tw-bg-opacity: 1;
    background-color: rgb(14 23 38 / var(--tw-bg-opacity));
}
```

### Phase 3: Vue Component Integration

#### 3.1 Create Main Layout Component
```vue
<!-- resources/js/Layouts/VristoLayout.vue -->
<template>
    <div
        x-data="main"
        class="relative overflow-x-hidden font-nunito text-sm font-normal antialiased"
        :class="[
            $store.app.sidebar ? 'toggle-sidebar' : '',
            $store.app.theme === 'dark' || $store.app.isDarkMode ? 'dark' : '',
            $store.app.menu,
            $store.app.layout,
            $store.app.rtlClass
        ]"
    >
        <!-- Sidebar overlay for mobile -->
        <div
            x-cloak
            class="fixed inset-0 z-50 bg-[black]/60 lg:hidden"
            :class="{'hidden' : !$store.app.sidebar}"
            @click="$store.app.toggleSidebar()"
        ></div>

        <!-- Screen loader -->
        <VristoLoader />

        <!-- Scroll to top button -->
        <ScrollToTop />

        <!-- Sidebar -->
        <VristoSidebar />

        <!-- Main content wrapper -->
        <div class="main-content flex flex-col min-h-screen">
            <!-- Header -->
            <VristoHeader />

            <!-- Page content -->
            <div class="animate__animated p-6" :class="[$store.app.animation]">
                <slot />
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import VristoLoader from '@/Components/Vristo/VristoLoader.vue';
import ScrollToTop from '@/Components/Vristo/ScrollToTop.vue';
import VristoSidebar from '@/Components/Vristo/VristoSidebar.vue';
import VristoHeader from '@/Components/Vristo/VristoHeader.vue';

onMounted(() => {
    // Initialize VRISTO Alpine.js store
    if (window.Alpine) {
        window.Alpine.start();
    }
});
</script>
```

#### 3.2 Create Alpine.js Store Integration
```javascript
// resources/js/vristo-store.js
document.addEventListener('alpine:init', () => {
    Alpine.store('app', {
        sidebar: false,
        theme: localStorage.getItem('theme') || 'light',
        isDarkMode: false,
        menu: 'vertical',
        layout: 'full',
        rtlClass: 'ltr',
        animation: '',
        navbar: 'navbar-sticky',

        toggleSidebar() {
            this.sidebar = !this.sidebar;
        },

        toggleTheme(theme) {
            this.theme = theme || (this.theme === 'dark' ? 'light' : 'dark');
            localStorage.setItem('theme', this.theme);

            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },

        setAnimation(animation) {
            this.animation = animation;
            // Remove animation class after animation completes
            setTimeout(() => {
                this.animation = '';
            }, 1000);
        }
    });
});

// Main Alpine.js data function
window.main = () => ({
    init() {
        // Initialize theme
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
            this.$store.app.theme = 'dark';
        }
    }
});
```

#### 3.3 Create VRISTO Components

##### Sidebar Component
```vue
<!-- resources/js/Components/Vristo/VristoSidebar.vue -->
<template>
    <nav
        x-cloak
        class="sidebar fixed top-0 bottom-0 z-50 h-full min-h-screen w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] transition-all duration-300"
        :class="{'ltr:-left-[260px] rtl:-right-[260px]': !$store.app.sidebar}"
    >
        <div class="h-full bg-white dark:bg-black">
            <!-- Logo -->
            <div class="flex items-center justify-between px-4 py-3">
                <Link
                    :href="route('dashboard')"
                    class="main-logo flex shrink-0 items-center"
                >
                    <img
                        class="ml-[5px] h-8 w-8 flex-none"
                        src="/images/ssl-monitor-logo.svg"
                        alt="SSL Monitor"
                    />
                    <span class="align-middle text-2xl font-semibold ltr:ml-1.5 rtl:mr-1.5 dark:text-white-light lg:inline">
                        SSL Monitor
                    </span>
                </Link>

                <button
                    type="button"
                    class="collapse-icon flex h-8 w-8 items-center rounded-full transition duration-300 hover:bg-gray-500/10 dark:text-white-light dark:hover:bg-dark-light/10 rtl:rotate-180"
                    @click="$store.app.toggleSidebar()"
                >
                    <svg class="m-auto h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Menu -->
            <perfect-scrollbar
                :options="{ suppressScrollX: true }"
                class="relative h-[calc(100vh-80px)]"
            >
                <ul class="relative space-y-0.5 p-4 py-0 font-semibold">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <Link
                            :href="route('dashboard')"
                            class="group"
                            :class="{'active': $page.component === 'Dashboard'}"
                        >
                            <div class="flex items-center">
                                <svg class="shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path opacity="0.5" d="M2 12.2039C2 9.91549 2 8.77128 2.5192 7.82274C3.0384 6.87421 3.98695 6.28551 5.88403 5.10813L7.88403 3.86687C9.88939 2.62229 10.8921 2 12 2C13.1079 2 14.1106 2.62229 16.116 3.86687L18.116 5.10812C20.0131 6.28551 20.9616 6.87421 21.4808 7.82274C22 8.77128 22 9.91549 22 12.2039V13.725C22 17.6258 22 19.5763 20.8284 20.7881C19.6569 22 17.7712 22 14 22H10C6.22876 22 4.34315 22 3.17157 20.7881C2 19.5763 2 17.6258 2 13.725V12.2039Z" fill="currentColor"/>
                                    <path d="M9 17.25C8.58579 17.25 8.25 17.5858 8.25 18C8.25 18.4142 8.58579 18.75 9 18.75H15C15.4142 18.75 15.75 18.4142 15.75 18C15.75 17.5858 15.4142 17.25 15 17.25H9Z" fill="currentColor"/>
                                </svg>
                                <span class="text-black ltr:pl-3 rtl:pr-3 dark:text-[#506690] dark:group-hover:text-white-dark">
                                    Dashboard
                                </span>
                            </div>
                        </Link>
                    </li>

                    <!-- Websites -->
                    <li class="nav-item">
                        <Link
                            :href="route('websites.index')"
                            class="group"
                            :class="{'active': $page.component.startsWith('Websites')}"
                        >
                            <div class="flex items-center">
                                <svg class="shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM4 12c0-4.42 3.58-8 8-8 1.85 0 3.55.63 4.9 1.69L5.69 16.9C4.63 15.55 4 13.85 4 12zm8 8c-1.85 0-3.55-.63-4.9-1.69L18.31 7.1C19.37 8.45 20 10.15 20 12c0 4.42-3.58 8-8 8z" fill="currentColor"/>
                                </svg>
                                <span class="text-black ltr:pl-3 rtl:pr-3 dark:text-[#506690] dark:group-hover:text-white-dark">
                                    Websites
                                </span>
                            </div>
                        </Link>
                    </li>

                    <!-- Settings -->
                    <li class="nav-item">
                        <button
                            type="button"
                            class="nav-link group w-full"
                            :class="{'active': $page.component.startsWith('Settings')}"
                            @click="toggleMenu('settings')"
                        >
                            <div class="flex items-center">
                                <svg class="shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
                                    <path opacity="0.5" d="M13.7654 2.15224C13.3978 2 12.9319 2 12 2C11.0681 2 10.6022 2 10.2346 2.15224C9.74457 2.35523 9.35522 2.74458 9.15223 3.23463C9.05957 3.45834 9.0233 3.7185 9.00911 4.09799C8.98826 4.65568 8.70226 5.17189 8.21894 5.45093C7.73564 5.72996 7.14559 5.71954 6.65219 5.45876C6.31645 5.2813 6.07301 5.18262 5.83294 5.15102C5.30704 5.08178 4.77518 5.22429 4.35436 5.5472C4.03874 5.78938 3.80577 6.1929 3.33983 6.99993C2.87389 7.80697 2.64092 8.21048 2.58899 8.60491C2.51976 9.1308 2.66227 9.66266 2.98518 10.0835C3.13256 10.2756 3.3397 10.437 3.66119 10.6044C4.1338 10.8564 4.43789 11.4001 4.43786 12C4.43783 12.5999 4.13375 13.1436 3.66307 13.3956C3.34059 13.563 3.13345 13.7244 2.98607 13.9165C2.66316 14.3373 2.52065 14.8692 2.58988 15.3951C2.64181 15.7895 2.87478 16.193 3.34072 17.0001C3.80666 17.8071 4.03963 18.2106 4.35525 18.4528C4.77607 18.7757 5.30793 18.9182 5.83383 18.8489C6.0739 18.8173 6.31734 18.7187 6.65308 18.5412C7.14648 18.2804 7.73653 18.27 8.21983 18.549C8.70315 18.8281 8.98915 19.3443 9.01 19.902C9.02419 20.2815 9.06046 20.5417 9.15312 20.7654C9.35611 21.2554 9.74546 21.6448 10.2355 21.8478C10.6031 22 11.0691 22 12.0009 22C12.9328 22 13.3987 22 13.7663 21.8478C14.2563 21.6448 14.6457 21.2554 14.8487 20.7654C14.9413 20.5417 14.9776 20.2815 14.9918 19.902C15.0126 19.3443 15.2986 18.8281 15.7819 18.549C16.2652 18.27 16.8553 18.2804 17.3487 18.5412C17.6844 18.7187 17.9279 18.8173 18.168 18.8489C18.6939 18.9182 19.2257 18.7757 19.6465 18.4528C19.9621 18.2106 20.1951 17.8071 20.661 17.0001C21.1270 16.193 21.3599 15.7895 21.4119 15.3951C21.4811 14.8692 21.3386 14.3373 21.0157 13.9165C20.8683 13.7244 20.6612 13.563 20.3397 13.3956C19.8670 13.1436 19.5629 12.5999 19.5629 12C19.5629 11.4001 19.8670 10.8564 20.3397 10.6044C20.6612 10.437 20.8683 10.2756 21.0157 10.0835C21.3386 9.66266 21.4811 9.1308 21.4119 8.60491C21.3599 8.21048 21.1270 7.80697 20.661 6.99993C20.1951 6.1929 19.9621 5.78938 19.6465 5.5472C19.2257 5.22429 18.6939 5.08178 18.168 5.15102C17.9279 5.18262 17.6844 5.2813 17.3487 5.45876C16.8553 5.71954 16.2652 5.72996 15.7819 5.45093C15.2986 5.17189 15.0126 4.65568 14.9918 4.09799C14.9776 3.7185 14.9413 3.45834 14.8487 3.23463C14.6457 2.74458 14.2563 2.35523 13.7663 2.15224Z" fill="currentColor"/>
                                </svg>
                                <span class="text-black ltr:pl-3 rtl:pr-3 dark:text-[#506690] dark:group-hover:text-white-dark">
                                    Settings
                                </span>
                            </div>
                            <div class="rtl:rotate-180" :class="{'!rotate-90': activeDropdown === 'settings'}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </button>

                        <AnimatedCollapse>
                            <ul v-show="activeDropdown === 'settings'" class="sub-menu text-gray-500">
                                <li>
                                    <Link :href="route('settings.profile')">Profile</Link>
                                </li>
                                <li>
                                    <Link :href="route('settings.email')">Email</Link>
                                </li>
                                <li>
                                    <Link :href="route('settings.team')">Team</Link>
                                </li>
                                <li>
                                    <Link :href="route('settings.appearance')">Appearance</Link>
                                </li>
                            </ul>
                        </AnimatedCollapse>
                    </li>
                </ul>
            </perfect-scrollbar>
        </div>
    </nav>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AnimatedCollapse from '@/Components/Vristo/AnimatedCollapse.vue';

const activeDropdown = ref(null);

const toggleMenu = (menu) => {
    activeDropdown.value = activeDropdown.value === menu ? null : menu;
};
</script>

<style scoped>
.nav-link {
    @apply flex h-10 w-full items-center justify-between rounded px-4 py-2 text-gray-500 transition-all duration-300 hover:bg-gray-100 hover:text-gray-700 dark:text-[#506690] dark:hover:bg-[#1b2e4b] dark:hover:text-white-dark;
}

.nav-link.active {
    @apply bg-gray-100 text-primary dark:bg-[#1b2e4b] dark:text-white-light;
}

.sub-menu {
    @apply space-y-2 p-2 text-sm;
}

.sub-menu li a {
    @apply block rounded px-4 py-2 transition-all duration-300 hover:bg-gray-50 hover:text-primary dark:hover:bg-[#1b2e4b] dark:hover:text-white-light;
}
</style>
```

##### Header Component
```vue
<!-- resources/js/Components/Vristo/VristoHeader.vue -->
<template>
    <header class="z-40" :class="[$store.app.navbar]">
        <div class="shadow-sm">
            <div class="relative flex w-full items-center bg-white px-6 py-0 dark:bg-black">
                <!-- Mobile menu button -->
                <div class="horizontal-logo flex items-center justify-between ltr:mr-2 rtl:ml-2 lg:hidden">
                    <Link :href="route('dashboard')" class="main-logo flex shrink-0 items-center">
                        <img class="inline w-8 ltr:-ml-1 rtl:-mr-1" src="/images/ssl-monitor-logo.svg" alt="SSL Monitor" />
                        <span class="hidden align-middle text-2xl font-semibold transition-all duration-300 ltr:ml-1.5 rtl:mr-1.5 dark:text-white-light md:inline">
                            SSL Monitor
                        </span>
                    </Link>

                    <button
                        type="button"
                        class="collapse-icon flex flex-none rounded-full bg-white-light/40 p-2 hover:bg-white-light/90 hover:text-primary ltr:ml-2 rtl:mr-2 dark:bg-dark/40 dark:text-[#d0d2d6] dark:hover:bg-dark/60 dark:hover:text-primary lg:hidden"
                        @click="$store.app.toggleSidebar()"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 7L4 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <path opacity="0.5" d="M20 12L4 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M20 17L4 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>

                <!-- Search (optional) -->
                <div class="hidden sm:block sm:ltr:mr-2 sm:rtl:ml-2">
                    <form class="absolute inset-y-0 ltr:left-11 rtl:right-11 hidden sm:block" @submit.prevent="search">
                        <button type="submit" class="flex h-full w-12 items-center justify-center hover:text-primary">
                            <svg class="mx-auto h-5 w-5 dark:text-[#d0d2d6]" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="11.5" cy="11.5" r="9.5" stroke="currentColor" stroke-width="1.5" opacity="0.5"/>
                                <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </button>
                        <input
                            v-model="searchTerm"
                            type="text"
                            placeholder="Search..."
                            class="form-input relative h-12 w-full bg-[#f4f4f4] placeholder-[#777] ltr:pl-12 ltr:pr-4 rtl:pl-4 rtl:pr-12 dark:bg-[#1b2e4b] sm:w-56"
                        />
                    </form>
                </div>

                <!-- Right side menu -->
                <div class="flex items-center space-x-1.5 ltr:ml-auto rtl:mr-auto rtl:space-x-reverse dark:text-[#d0d2d6] sm:flex-1 sm:ltr:ml-0 sm:rtl:mr-0">
                    <div class="sm:ltr:mr-auto sm:rtl:ml-auto"></div>

                    <!-- Theme toggle -->
                    <div>
                        <button
                            type="button"
                            class="flex items-center rounded-full bg-white-light/40 p-2 hover:bg-white-light/90 hover:text-primary dark:bg-dark/40 dark:hover:bg-dark/60"
                            @click="$store.app.toggleTheme()"
                        >
                            <svg v-if="$store.app.theme === 'light'" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M12 2V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M12 20V22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M4 12L2 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M22 12L20 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path opacity="0.5" d="M19.0708 4.92969L17.6566 6.34375" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path opacity="0.5" d="M6.34375 17.6562L4.92969 19.0703" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path opacity="0.5" d="M19.0708 19.0703L17.6566 17.6562" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path opacity="0.5" d="M6.34375 6.34375L4.92969 4.92969" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21.0672 11.8568L20.4253 11.469L21.0672 11.8568ZM12.1432 2.93276L11.7553 2.29085V2.29085L12.1432 2.93276Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path opacity="0.5" d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C12.3394 2 12.6734 2.0212 13.0006 2.06274C12.4006 4.24803 13.0758 6.54935 14.7236 8.19709C16.3714 9.84483 18.6727 10.5201 20.8579 9.92006C20.8994 10.2472 20.9206 10.5811 20.9206 10.9205C20.9206 11.0291 20.9176 11.1371 20.9117 11.2445C21.6501 11.7171 22 12.5 22 12Z" fill="currentColor"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Notifications -->
                    <NotificationDropdown />

                    <!-- User menu -->
                    <UserDropdown :user="$page.props.auth.user" />
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import NotificationDropdown from '@/Components/Vristo/NotificationDropdown.vue';
import UserDropdown from '@/Components/Vristo/UserDropdown.vue';

const searchTerm = ref('');

const search = () => {
    // Implement search functionality
    console.log('Searching for:', searchTerm.value);
};
</script>
```

---

## 🔧 Alpine.js Integration with Vue

### Approach Strategy
1. **Maintain Alpine.js** for VRISTO's native interactions (sidebar, dropdowns, theme switching)
2. **Use Vue Components** for complex application logic and data management
3. **Hybrid Compatibility** - Allow both frameworks to coexist without conflicts

### Implementation Guidelines

#### 1. Initialize Alpine.js After Vue
```javascript
// resources/js/app.js
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import Alpine from 'alpinejs';

// Import VRISTO Alpine.js store
import './vristo-store.js';

createInertiaApp({
    title: (title) => `${title} - SSL Monitor`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        app.mount(el);

        // Initialize Alpine.js after Vue
        window.Alpine = Alpine;
        Alpine.start();
    },
});
```

#### 2. Prevent Conflicts
```javascript
// Use x-ignore on Vue components to prevent Alpine.js processing
<template>
    <div x-ignore>
        <!-- Vue component content here -->
        <!-- Alpine.js will not process this section -->
    </div>
</template>
```

---

## 📱 Responsive Design Implementation

### Mobile Sidebar Behavior
```css
/* Custom CSS for mobile sidebar */
@media (max-width: 1024px) {
    .sidebar {
        @apply fixed z-50 -translate-x-full transition-transform duration-300;
    }

    .toggle-sidebar .sidebar {
        @apply translate-x-0;
    }

    .main-content {
        @apply ml-0;
    }
}

@media (min-width: 1024px) {
    .sidebar {
        @apply relative translate-x-0;
    }

    .main-content {
        @apply ml-[260px];
    }

    .toggle-sidebar .main-content {
        @apply ml-0;
    }
}
```

---

## 🎨 VRISTO Component Library

### Commonly Used VRISTO Components to Create

#### 1. Data Tables
```vue
<!-- VristoDataTable.vue -->
<template>
    <div class="table-responsive">
        <table class="table-hover">
            <thead>
                <tr>
                    <th v-for="column in columns" :key="column.key">
                        {{ column.label }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in data" :key="item.id">
                    <td v-for="column in columns" :key="column.key">
                        <slot :name="`cell-${column.key}`" :item="item" :value="item[column.key]">
                            {{ item[column.key] }}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
```

#### 2. Modal Components
```vue
<!-- VristoModal.vue -->
<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[999] overflow-y-auto bg-[black]/60"
            @click.self="closeModal"
        >
            <div class="flex min-h-screen items-center justify-center px-4">
                <div
                    class="panel my-8 w-full max-w-lg overflow-hidden rounded-lg"
                    @click.stop
                >
                    <div class="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
                        <h5 class="text-lg font-bold">{{ title }}</h5>
                        <button type="button" @click="closeModal">
                            <svg><!-- Close icon --></svg>
                        </button>
                    </div>
                    <div class="p-5">
                        <slot />
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
```

#### 3. Form Components
```vue
<!-- VristoInput.vue -->
<template>
    <div class="mb-5">
        <label v-if="label" :for="id" class="label">{{ label }}</label>
        <input
            :id="id"
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :class="inputClasses"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <div v-if="error" class="mt-1 text-danger">{{ error }}</div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: String,
    type: { type: String, default: 'text' },
    label: String,
    placeholder: String,
    error: String,
    id: String,
});

const emit = defineEmits(['update:modelValue']);

const inputClasses = computed(() => [
    'form-input',
    {
        'border-danger': props.error
    }
]);
</script>
```

---

## ✅ Integration Checklist

### Phase 1: Setup
- [ ] Extract VRISTO assets to Laravel project
- [ ] Configure TailwindCSS with VRISTO settings
- [ ] Setup Vite configuration for asset compilation
- [ ] Install required NPM dependencies

### Phase 2: Layout Integration
- [ ] Create main VristoLayout.vue component
- [ ] Implement VristoSidebar.vue with navigation
- [ ] Create VristoHeader.vue with user menu
- [ ] Setup Alpine.js store integration

### Phase 3: Component Development
- [ ] Create essential VRISTO components (buttons, forms, modals)
- [ ] Implement data table components
- [ ] Add notification and alert components
- [ ] Create chart and dashboard components

### Phase 4: Theme & Responsiveness
- [ ] Implement dark/light theme switching
- [ ] Ensure mobile responsiveness
- [ ] Test cross-browser compatibility
- [ ] Validate accessibility compliance

### Phase 5: Testing
- [ ] Create component unit tests
- [ ] Test Alpine.js + Vue compatibility
- [ ] Validate responsive design across devices
- [ ] Performance testing and optimization

---

## 🔍 Troubleshooting Common Issues

### Alpine.js + Vue Conflicts
**Problem**: Alpine.js trying to process Vue directives
**Solution**: Use `x-ignore` on Vue component root elements

### TailwindCSS Compilation Issues
**Problem**: VRISTO styles not loading correctly
**Solution**: Ensure proper import order in CSS files

### Responsive Sidebar Not Working
**Problem**: Mobile sidebar not responding to clicks
**Solution**: Check Alpine.js store initialization and event handlers

### Theme Switching Not Persisting
**Problem**: Theme preferences not saved
**Solution**: Verify localStorage integration in Alpine.js store

---

This integration guide provides a complete foundation for incorporating VRISTO's professional admin template with Laravel + Vue + Inertia.js while maintaining optimal performance and user experience.