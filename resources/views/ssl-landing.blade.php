<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SSL Monitoring by INTERMEDIEN</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Custom INTERMEDIEN Brand Colors -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        :root {
            --im-blue: #122c4f;
            --im-green: #a0cc3a;
        }
        .bg-im-blue { background-color: var(--im-blue); }
        .text-im-blue { color: var(--im-blue); }
        .bg-im-green { background-color: var(--im-green); }
        .text-im-green { color: var(--im-green); }
        .border-im-green { border-color: var(--im-green); }
        .hover\:bg-im-green-dark:hover { background-color: #8eb635; }
        .hover\:text-im-green:hover { color: var(--im-green); }
    </style>
</head>
<body class="bg-white text-gray-800 flex flex-col" style="min-height: calc(100vh - 4rem);">

    <!-- Header Section -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Left side: Logo -->
                <div class="flex items-center">
                    <div>
                        <h1 class="text-2xl font-extrabold text-im-blue leading-none">
                            INTERMEDIEN
                        </h1>
                        <p class="text-xs text-im-green font-medium uppercase tracking-wide leading-none mt-1">
                            Business Solutions
                        </p>
                    </div>
                </div>

                <!-- Right side: Login and Register buttons -->
                @if (Route::has('login'))
                    <div class="flex items-center">
                        @auth
                            <a href="{{ url('/dashboard') }}" 
                               class="bg-im-green text-white px-6 py-2 rounded-md font-bold hover:bg-im-green-dark transition duration-300 shadow-sm">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" 
                               class="text-gray-600 hover:text-im-blue font-medium transition duration-300">
                                Login
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" 
                                   class="bg-im-green text-white px-4 py-2 rounded-md font-bold hover:bg-im-green-dark transition duration-300 shadow-sm" 
                                   style="margin-left: 3rem;">
                                    Register
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center">
        <section class="text-center px-6 w-full max-w-4xl">
            <div class="mx-auto">
                <!-- SSL Shield Illustration -->
                <div class="max-w-md mx-auto mb-8">
                    <svg viewBox="0 0 200 120" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="shieldGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#122c4f;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#1a3e6d;stop-opacity:1" />
                            </linearGradient>
                            <filter id="glow" x="-50%" y="-50%" width="200%" height="200%">
                                <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                                <feMerge>
                                    <feMergeNode in="coloredBlur"/>
                                    <feMergeNode in="SourceGraphic"/>
                                </feMerge>
                            </filter>
                        </defs>
                        
                        <!-- Central Shield -->
                        <path d="M 100,20 L 130,40 L 130,70 C 130,100 100,120 100,120 C 100,120 70,100 70,70 L 70,40 Z" fill="url(#shieldGradient)" stroke="#a0cc3a" stroke-width="2" filter="url(#glow)"/>
                        
                        <!-- Checkmark Icon inside shield -->
                        <path d="M92 65 l6 6 l10 -12" stroke="#a0cc3a" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>

                        <!-- Orbiting nodes -->
                        <circle cx="30" cy="50" r="6" fill="#122c4f" stroke="#a0cc3a" stroke-width="1.5"/>
                        <path d="M 35,53 C 50,70 75,75 95,70" stroke="#122c4f" stroke-width="1.5" fill="none" stroke-dasharray="3 3"/>
                        
                        <circle cx="170" cy="60" r="8" fill="#122c4f" stroke="#a0cc3a" stroke-width="1.5"/>
                        <path d="M 165,63 C 150,80 125,85 105,80" stroke="#122c4f" stroke-width="1.5" fill="none" stroke-dasharray="3 3"/>
                        
                        <circle cx="80" cy="10" r="5" fill="#122c4f" stroke="#a0cc3a" stroke-width="1.5"/>
                        <path d="M 82,15 C 85,25 90,35 98,40" stroke="#122c4f" stroke-width="1.5" fill="none" stroke-dasharray="3 3"/>
                    </svg>
                </div>

                <div class="max-w-2xl mx-auto">
                    <h2 class="text-4xl md:text-5xl font-extrabold text-im-blue leading-tight mb-4">
                        SSL Certificate Monitoring
                    </h2>
                    <p class="text-lg md:text-xl text-gray-600 mb-8">
                        A simple, reliable tool to monitor your website's SSL certificates. 
                        @guest
                            Log in or register to add your domains.
                        @else
                            Welcome back! Manage your SSL monitoring from your dashboard.
                        @endguest
                    </p>
                    
                    <!-- Additional CTA for authenticated users -->
                    @auth
                        <div class="mt-8">
                            <a href="{{ route('dashboard') }}" 
                               class="inline-block bg-im-green text-white px-8 py-3 rounded-lg font-bold hover:bg-im-green-dark transition duration-300 shadow-lg text-lg">
                                Go to Dashboard
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-100">
        <div class="container mx-auto px-6 py-6">
            <div class="flex flex-col md:flex-row items-center justify-between text-gray-600">
                <p class="text-sm">&copy; {{ date('Y') }} INTERMEDIEN. All rights reserved.</p>
                <div class="flex space-x-4 mt-4 md:mt-0 text-sm">
                    <span class="text-gray-500">Professional SSL monitoring solution</span>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>