<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Port Dataset – Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-[#060d1a] text-white min-h-screen">
<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside class="w-64 bg-[#0a1628]/80 border-r border-white/5 flex flex-col flex-shrink-0">
        <div class="p-6 border-b border-white/5">
            <a href="/" class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-400 to-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945"/></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-white">GSC Admin</p>
                    <p class="text-[10px] text-sky-400/70">Back Office</p>
                </div>
            </a>
        </div>
        <nav class="flex-1 p-4 space-y-1">
            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-widest px-3 mb-3">Main</p>
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:bg-white/5 hover:text-white text-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/></svg>
                Dashboard
            </a>
            <a href="{{ route('admin.users') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:bg-white/5 hover:text-white text-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Manage Users
            </a>
            <a href="{{ route('admin.ports') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-emerald-500/10 text-emerald-400 font-medium text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>
                Port Dataset
            </a>
            <a href="{{ route('admin.articles') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:bg-white/5 hover:text-white text-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Articles
            </a>
            <div class="pt-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:bg-white/5 hover:text-white text-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Main Dashboard
                </a>
            </div>
        </nav>
        <div class="p-4 border-t border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-sky-400 to-indigo-600 flex items-center justify-center text-xs font-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-sky-400 font-medium">Administrator</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-500 hover:text-red-400 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- MAIN --}}
    <main class="flex-1 overflow-auto">
        <header class="bg-[#0a1628]/60 border-b border-white/5 px-8 py-4 flex items-center justify-between sticky top-0 z-10 backdrop-blur-sm">
            <div>
                <h1 class="text-xl font-bold text-white">Port Dataset</h1>
                <p class="text-xs text-slate-400">{{ \App\Models\Port::count() }} ports in database</p>
            </div>
        </header>

        <div class="p-8">

            {{-- Port Stats --}}
            <div class="grid grid-cols-3 gap-5 mb-8">
                <div class="bg-white/5 rounded-2xl p-5 border border-white/5">
                    <p class="text-sm text-slate-400 mb-1">Active Ports</p>
                    <p class="text-3xl font-bold text-emerald-400">{{ \App\Models\Port::where('status','active')->count() }}</p>
                </div>
                <div class="bg-white/5 rounded-2xl p-5 border border-white/5">
                    <p class="text-sm text-slate-400 mb-1">High Congestion</p>
                    <p class="text-3xl font-bold text-amber-400">{{ \App\Models\Port::where('congestion_level','>=',70)->count() }}</p>
                </div>
                <div class="bg-white/5 rounded-2xl p-5 border border-white/5">
                    <p class="text-sm text-slate-400 mb-1">High Risk</p>
                    <p class="text-3xl font-bold text-red-400">{{ \App\Models\Port::where('risk_score','>=',70)->count() }}</p>
                </div>
            </div>

            {{-- Ports Table --}}
            <div class="bg-white/5 rounded-2xl border border-white/5 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Port Name</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Country</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Congestion</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Risk</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach(\App\Models\Port::with('country')->orderBy('risk_score','desc')->take(30)->get() as $port)
                            <tr class="hover:bg-white/3 transition-colors">
                                <td class="px-6 py-3 font-medium text-white">{{ $port->name }}</td>
                                <td class="px-6 py-3 text-slate-400">{{ $port->country?->name ?? 'N/A' }}</td>
                                <td class="px-6 py-3 text-slate-400 capitalize">{{ $port->type ?? 'N/A' }}</td>
                                <td class="px-6 py-3">
                                    @if($port->status === 'active')
                                        <span class="px-2 py-0.5 text-xs bg-emerald-500/20 text-emerald-400 rounded-full">Active</span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs bg-slate-500/20 text-slate-400 rounded-full">{{ $port->status }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    @php $c = $port->congestion_level ?? 0; @endphp
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 h-1.5 bg-white/10 rounded-full">
                                            <div class="h-full rounded-full {{ $c >= 70 ? 'bg-red-400' : ($c >= 40 ? 'bg-amber-400' : 'bg-emerald-400') }}" style="width: {{ $c }}%"></div>
                                        </div>
                                        <span class="text-xs text-slate-400">{{ $c }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    @php $r = $port->risk_score ?? 0; @endphp
                                    <span class="text-xs font-semibold {{ $r >= 70 ? 'text-red-400' : ($r >= 40 ? 'text-amber-400' : 'text-emerald-400') }}">{{ $r }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
