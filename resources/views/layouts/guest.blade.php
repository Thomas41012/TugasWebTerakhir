<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Global Supply Chain') }} – {{ $title ?? 'Authentication' }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Inter', sans-serif; }
            .glow-blue { box-shadow: 0 0 40px rgba(56, 189, 248, 0.15); }
            .glow-border { box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.3), 0 0 30px rgba(56, 189, 248, 0.1); }
            .grid-bg {
                background-image: linear-gradient(rgba(56,189,248,0.05) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(56,189,248,0.05) 1px, transparent 1px);
                background-size: 40px 40px;
            }
            @keyframes float {
                0%, 100% { transform: translateY(0px); }
                50% { transform: translateY(-10px); }
            }
            .float-anim { animation: float 4s ease-in-out infinite; }
            @keyframes pulse-glow {
                0%, 100% { opacity: 0.4; }
                50% { opacity: 0.8; }
            }
            .pulse-glow { animation: pulse-glow 3s ease-in-out infinite; }
        </style>
    </head>
    <body class="font-sans antialiased bg-[#060d1a] text-white">

        <div class="min-h-screen grid-bg relative overflow-hidden flex items-center justify-center p-4">

            <!-- Background Orbs -->
            <div class="absolute top-1/4 -left-32 w-96 h-96 bg-sky-500/10 rounded-full blur-3xl pulse-glow"></div>
            <div class="absolute bottom-1/4 -right-32 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pulse-glow" style="animation-delay: 1.5s;"></div>
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-px bg-gradient-to-r from-transparent via-sky-500/30 to-transparent"></div>

            <div class="w-full max-w-md relative z-10">

                <!-- Logo Header -->
                <div class="text-center mb-8 float-anim">
                    <a href="/" class="inline-flex flex-col items-center gap-3">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-sky-400 to-indigo-600 flex items-center justify-center glow-blue">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-white tracking-tight">Global Supply Chain</p>
                            <p class="text-xs text-sky-400/80 font-medium tracking-widest uppercase">Intelligence Platform</p>
                        </div>
                    </a>
                </div>

                <!-- Card -->
                <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-8 glow-border border border-white/10">
                    {{ $slot }}
                </div>

                <!-- Footer -->
                <p class="text-center text-xs text-slate-500 mt-6">
                    &copy; {{ date('Y') }} Global Supply Chain Intelligence. All rights reserved.
                </p>
            </div>
        </div>
    </body>
</html>
