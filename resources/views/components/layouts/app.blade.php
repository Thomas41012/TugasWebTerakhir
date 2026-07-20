<!DOCTYPE html>
<html lang="en" class="dark">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Global Supply Chain Intelligence Platform"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        {{ $title ?? 'Global Supply Chain Intelligence' }}
    </title>

    {{-- ============================================================
         VITE ASSETS
    ============================================================ --}}

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    {{-- ============================================================
         LEAFLET CSS
    ============================================================ --}}

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    {{-- ============================================================
         LEAFLET MARKER CLUSTER CSS
    ============================================================ --}}

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"
    >

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"
    >

    {{-- ============================================================
         LIVEWIRE STYLES
    ============================================================ --}}

    @livewireStyles

    {{-- ============================================================
         ADDITIONAL PAGE STYLES
    ============================================================ --}}

    @stack('styles')

</head>

<body
    class="min-h-screen bg-slate-950 text-slate-100 antialiased"
>

    <div class="flex min-h-screen flex-col bg-slate-950">

        {{-- ========================================================
             HEADER
        ======================================================== --}}

        <header
            class="sticky top-0 z-50 border-b border-slate-800
                   bg-slate-950/90 backdrop-blur-xl"
        >

            <div
                class="mx-auto flex w-full max-w-[1600px]
                       items-center justify-between
                       px-4 py-4 lg:px-8"
            >

                {{-- =================================================
                     BRAND
                ================================================= --}}

                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    class="group"
                >

                    <div class="flex items-center gap-3">

                        <div
                            class="flex h-10 w-10 items-center
                                   justify-center rounded-xl
                                   border border-emerald-500/30
                                   bg-emerald-500/10"
                        >
                            <span
                                class="text-lg font-bold text-emerald-400"
                            >
                                GS
                            </span>
                        </div>

                        <div>

                            <h1
                                class="text-lg font-bold tracking-wide
                                       text-white transition
                                       group-hover:text-emerald-400"
                            >
                                Global Supply Chain
                            </h1>

                            <p class="text-xs text-emerald-400">
                                Intelligence Platform
                            </p>

                        </div>

                    </div>

                </a>

                {{-- =================================================
                     DESKTOP NAVIGATION
                ================================================= --}}

                <nav
                    class="hidden items-center gap-6 md:flex"
                    aria-label="Main Navigation"
                >

                    <a
                        href="{{ route('dashboard') }}"
                        wire:navigate
                        class="text-sm text-slate-300
                               transition duration-300
                               hover:text-emerald-400"
                    >
                        Dashboard
                    </a>

                    <a
                        href="#global-map"
                        class="text-sm text-slate-300
                               transition duration-300
                               hover:text-cyan-400"
                    >
                        Global Map
                    </a>

                    <a
                        href="#risk-overview"
                        class="text-sm text-slate-300
                               transition duration-300
                               hover:text-rose-400"
                    >
                        Risk
                    </a>

                    <a
                        href="#trend-analytics"
                        class="text-sm text-slate-300
                               transition duration-300
                               hover:text-orange-400"
                    >
                        Analytics
                    </a>

                    <a
                        href="#compare-mode"
                        class="text-sm text-slate-300
                               transition duration-300
                               hover:text-violet-400"
                    >
                        Compare
                    </a>

                </nav>

                {{-- =================================================
                     SYSTEM STATUS
                ================================================= --}}

                <div class="flex items-center gap-4">

                    <div
                        class="hidden items-center gap-2
                               rounded-full
                               border border-emerald-500/20
                               bg-emerald-500/10
                               px-3 py-1.5 sm:flex"
                    >

                        <span
                            class="h-2 w-2 animate-pulse
                                   rounded-full bg-emerald-400"
                        ></span>

                        <span
                            class="text-xs font-medium
                                   text-emerald-400"
                        >
                            SYSTEM ONLINE
                        </span>

                    </div>

                    @auth
                        <div class="flex items-center gap-3 pl-3 border-l border-slate-800">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-sky-400 to-indigo-600 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="hidden lg:block text-left">
                                <p class="text-xs font-semibold text-white truncate max-w-[120px]">{{ auth()->user()->name }}</p>
                                <p class="text-[10px] text-slate-400 truncate max-w-[120px]">{{ auth()->user()->email }}</p>
                            </div>
                            
                            <a href="{{ route('profile') }}" wire:navigate class="text-xs text-slate-300 hover:text-white transition-colors">
                                Profile
                            </a>
                            
                            @if(auth()->user()->role === 'admin')
                                <a href="{{ route('admin.dashboard') }}" wire:navigate class="px-2.5 py-1 text-xs font-medium bg-sky-500/10 text-sky-400 border border-sky-500/20 rounded-lg hover:bg-sky-500/20 transition-all">
                                    Admin
                                </a>
                            @endif

                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-slate-400 hover:text-red-400 transition-colors ml-1 flex items-center" title="Logout">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                </button>
                            </form>
                        </div>
                    @endauth

                </div>

            </div>

        </header>

        {{-- ========================================================
             MAIN CONTENT
        ======================================================== --}}

        <main class="flex-1">

            {{ $slot }}

        </main>

        {{-- ========================================================
             FOOTER
        ======================================================== --}}

        <footer
            class="border-t border-slate-800 bg-slate-950"
        >

            <div
                class="mx-auto flex w-full max-w-[1600px]
                       flex-col items-center justify-between
                       gap-3 px-4 py-6
                       text-center
                       sm:flex-row
                       sm:text-left
                       lg:px-8"
            >

                <div>

                    <p class="text-sm text-slate-500">
                        Global Supply Chain Intelligence Platform
                    </p>

                    <p class="mt-1 text-xs text-slate-600">
                        Multi-API Risk Monitoring and Data Analytics
                    </p>

                </div>

                <div
                    class="flex items-center gap-2
                           text-xs text-slate-600"
                >

                    <span
                        class="h-2 w-2 rounded-full
                               bg-emerald-400"
                    ></span>

                    <span>
                        Real-Time Intelligence System
                    </span>

                </div>

            </div>

        </footer>

    </div>

    {{-- ============================================================
         APEXCHARTS
    ============================================================ --}}

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    {{-- ============================================================
         DAY.JS
    ============================================================ --}}

    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/plugin/utc.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/plugin/timezone.js"></script>

    <script>
        if (typeof dayjs !== 'undefined') {
            dayjs.extend(window.dayjs_plugin_utc);
            dayjs.extend(window.dayjs_plugin_timezone);
        }
    </script>

    {{-- ============================================================
         LEAFLET JS
    ============================================================ --}}

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    {{-- ============================================================
         LEAFLET MARKER CLUSTER JS
    ============================================================ --}}

    <script
        src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"
    ></script>

    {{-- ============================================================
         LIVEWIRE SCRIPTS
    ============================================================ --}}

    @livewireScripts

    {{-- ============================================================
         ADDITIONAL PAGE SCRIPTS
    ============================================================ --}}

    @stack('scripts')

</body>

</html>