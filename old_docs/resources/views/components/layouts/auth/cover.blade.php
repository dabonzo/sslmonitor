<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-background-dark" x-data>

        <!-- Vristo Cover Auth Layout -->
        <div class="absolute inset-0">
            <img src="/assets/images/auth/bg-gradient.png" alt="Background Gradient" class="h-full w-full object-cover" />
        </div>

        <div class="relative flex min-h-screen items-center justify-center bg-[url(/assets/images/auth/map.png)] bg-cover bg-center bg-no-repeat px-6 py-10 dark:bg-background-dark sm:px-16">
            <!-- Decorative Objects -->
            <img src="/assets/images/auth/coming-soon-object1.png" alt="Decorative Object" class="absolute left-0 top-1/2 h-full max-h-[893px] -translate-y-1/2" />
            <img src="/assets/images/auth/coming-soon-object2.png" alt="Decorative Object" class="absolute left-24 top-0 h-40 md:left-[30%]" />
            <img src="/assets/images/auth/coming-soon-object3.png" alt="Decorative Object" class="absolute right-0 top-0 h-[300px]" />
            <img src="/assets/images/auth/polygon-object.svg" alt="Decorative Object" class="absolute bottom-0 end-[28%]" />

            <!-- Main Container -->
            <div class="relative flex w-full max-w-[1502px] flex-col justify-between overflow-hidden rounded-md bg-white/60 backdrop-blur-lg dark:bg-black/50 lg:min-h-[758px] lg:flex-row lg:gap-10 xl:gap-0">

                <!-- Left Branded Panel -->
                <div class="relative hidden w-full items-center justify-center bg-gradient-to-r from-primary via-primary-light to-primary-dark p-5 lg:inline-flex lg:max-w-[835px] xl:-ms-32 ltr:xl:skew-x-[14deg] rtl:xl:skew-x-[-14deg]">
                    <div class="absolute inset-y-0 w-8 from-primary/10 via-transparent to-transparent ltr:-right-10 ltr:bg-gradient-to-r rtl:-left-10 rtl:bg-gradient-to-l xl:w-16 ltr:xl:-right-20 rtl:xl:-left-20"></div>
                    <div class="ltr:xl:-skew-x-[14deg] rtl:xl:skew-x-[14deg]">
                        <!-- SSL Monitor Logo -->
                        <a href="{{ route('dashboard') }}" class="w-48 block lg:w-72 ms-10" wire:navigate>
                            <div class="flex items-center space-x-3">
                                <x-app-logo class="h-12 w-12 fill-white" />
                                <span class="text-2xl font-bold text-white">SSL Monitor</span>
                            </div>
                        </a>

                        <!-- Dynamic Illustration -->
                        <div class="mt-24 hidden w-full max-w-[430px] lg:block">
                            @if(request()->routeIs('login'))
                                <img src="/assets/images/auth/login.svg" alt="Login Illustration" class="w-full" />
                            @elseif(request()->routeIs('register'))
                                <img src="/assets/images/auth/register.svg" alt="Register Illustration" class="w-full" />
                            @elseif(request()->routeIs('password.request'))
                                <img src="/assets/images/auth/reset-password.svg" alt="Password Reset Illustration" class="w-full" />
                            @else
                                <img src="/assets/images/auth/login.svg" alt="SSL Monitor Illustration" class="w-full" />
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Right Form Panel -->
                <div class="relative flex w-full flex-col items-center justify-center gap-6 px-4 pb-16 pt-6 sm:px-6 lg:max-w-[667px]">

                    <!-- Top Bar with Logo and Dark Mode Toggle -->
                    <div class="flex w-full max-w-[440px] items-center gap-2 lg:absolute lg:end-6 lg:top-6 lg:max-w-full">
                        <!-- Mobile Logo -->
                        <a href="{{ route('dashboard') }}" class="block w-8 lg:hidden" wire:navigate>
                            <x-app-logo class="h-8 w-8 fill-current text-black dark:text-white" />
                        </a>

                        <!-- Dark Mode Toggle -->
                        <div class="ms-auto flex items-center">
                            <button @click="$flux.appearance = $flux.appearance === 'dark' ? 'light' : 'dark'"
                                    class="flex items-center gap-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-black px-3 py-2 text-gray-700 dark:text-gray-300 hover:border-primary hover:text-primary transition-colors">
                                <flux:icon.moon class="h-4 w-4" x-show="$flux.appearance !== 'dark'" />
                                <flux:icon.sun class="h-4 w-4" x-show="$flux.appearance === 'dark'" />
                                <span class="text-sm font-medium" x-text="$flux.appearance === 'dark' ? 'Light' : 'Dark'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Form Content -->
                    <div class="w-full max-w-[440px] lg:mt-16">
                        {{ $slot }}
                    </div>

                    <!-- Footer -->
                    <p class="absolute bottom-6 w-full text-center text-sm text-gray-600 dark:text-gray-400">
                        © {{ date('Y') }} SSL Monitor. All Rights Reserved.
                    </p>
                </div>
            </div>
        </div>

        @fluxScripts
    </body>
</html>