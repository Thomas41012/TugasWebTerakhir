@php
    $stats = [
        ['label' => 'Total Users', 'value' => \App\Models\User::count(), 'icon' => 'users', 'color' => 'sky'],
        ['label' => 'Total Countries', 'value' => \App\Models\Country::count(), 'icon' => 'globe', 'color' => 'indigo'],
        ['label' => 'Active Ports', 'value' => \App\Models\Port::where('status','active')->count(), 'icon' => 'anchor', 'color' => 'emerald'],
        ['label' => 'Articles', 'value' => \App\Models\Article::count(), 'icon' => 'document', 'color' => 'amber'],
    ];
@endphp

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – Global Supply Chain</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-[#060d1a] text-white min-h-screen">

    {{-- SIDEBAR + LAYOUT --}}
    <div class="flex h-screen overflow-hidden">

        {{-- SIDEBAR --}}
        <aside class="w-64 bg-[#0a1628]/80 border-r border-white/5 flex flex-col flex-shrink-0">
            <div class="p-6 border-b border-white/5">
                <a href="/" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-400 to-indigo-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-white">GSC Admin</p>
                        <p class="text-[10px] text-sky-400/70">Back Office</p>
                    </div>
                </a>
            </div>

            <nav class="flex-1 p-4 space-y-1">
                <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-widest px-3 mb-3">Main</p>

                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-sky-500/10 text-sky-400 font-medium text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                    Dashboard
                </a>

                <a href="{{ route('admin.users') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:bg-white/5 hover:text-white text-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Manage Users
                </a>

                <a href="{{ route('admin.ports') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:bg-white/5 hover:text-white text-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Port Dataset
                </a>

                <a href="{{ route('admin.articles') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:bg-white/5 hover:text-white text-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Articles
                </a>

                <div class="pt-4">
                    <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-widest px-3 mb-3">System</p>
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:bg-white/5 hover:text-white text-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Main Dashboard
                    </a>
                </div>
            </nav>

            {{-- User Info --}}
            <div class="p-4 border-t border-white/5">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-sky-400 to-indigo-600 flex items-center justify-center text-xs font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-sky-400 font-medium">Administrator</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-slate-500 hover:text-red-400 transition-colors" title="Logout">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- MAIN CONTENT --}}
        <main class="flex-1 overflow-auto">
            {{-- Top Bar --}}
            <header class="bg-[#0a1628]/60 border-b border-white/5 px-8 py-4 flex items-center justify-between sticky top-0 z-10 backdrop-blur-sm">
                <div>
                    <h1 class="text-xl font-bold text-white">Admin Dashboard</h1>
                    <p class="text-xs text-slate-400">Overview of Global Supply Chain Intelligence Platform</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-500">{{ now()->format('D, d M Y') }}</span>
                    <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                    <span class="text-xs text-emerald-400 font-medium">System Online</span>
                </div>
            </header>

            <div class="p-8 space-y-8">

                {{-- Stats Cards --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach($stats as $stat)
                    <div class="bg-white/5 rounded-2xl p-5 border border-white/5 hover:bg-white/8 transition-all">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm text-slate-400">{{ $stat['label'] }}</p>
                            @if($stat['icon'] === 'users')
                            <div class="w-9 h-9 bg-sky-500/20 rounded-xl flex items-center justify-center">
                                <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            @elseif($stat['icon'] === 'globe')
                            <div class="w-9 h-9 bg-indigo-500/20 rounded-xl flex items-center justify-center">
                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945"/></svg>
                            </div>
                            @elseif($stat['icon'] === 'anchor')
                            <div class="w-9 h-9 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>
                            </div>
                            @else
                            <div class="w-9 h-9 bg-amber-500/20 rounded-xl flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            @endif
                        </div>
                        <p class="text-3xl font-bold text-white">{{ $stat['value'] }}</p>
                    </div>
                    @endforeach
                </div>

                {{-- Quick Actions --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <a href="{{ route('admin.users') }}"
                        class="bg-white/5 rounded-2xl p-6 border border-white/5 hover:border-sky-500/30 hover:bg-sky-500/5 transition-all group">
                        <div class="w-12 h-12 bg-sky-500/20 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-sky-500/30 transition-all">
                            <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h3 class="font-semibold text-white mb-1">Manage Users</h3>
                        <p class="text-sm text-slate-400">View, edit, and manage platform users</p>
                        <div class="flex items-center gap-1 mt-4 text-sky-400 text-sm font-medium">
                            Go to Users <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </a>

                    <a href="{{ route('admin.ports') }}"
                        class="bg-white/5 rounded-2xl p-6 border border-white/5 hover:border-emerald-500/30 hover:bg-emerald-500/5 transition-all group">
                        <div class="w-12 h-12 bg-emerald-500/20 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-emerald-500/30 transition-all">
                            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <h3 class="font-semibold text-white mb-1">Port Dataset</h3>
                        <p class="text-sm text-slate-400">Manage global port data and status</p>
                        <div class="flex items-center gap-1 mt-4 text-emerald-400 text-sm font-medium">
                            Go to Ports <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </a>

                    <a href="{{ route('admin.articles') }}"
                        class="bg-white/5 rounded-2xl p-6 border border-white/5 hover:border-amber-500/30 hover:bg-amber-500/5 transition-all group">
                        <div class="w-12 h-12 bg-amber-500/20 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-amber-500/30 transition-all">
                            <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <h3 class="font-semibold text-white mb-1">Analysis Articles</h3>
                        <p class="text-sm text-slate-400">Create and manage analysis articles</p>
                        <div class="flex items-center gap-1 mt-4 text-amber-400 text-sm font-medium">
                            Go to Articles <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </a>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                    {{-- Recent Users Table --}}
                    <div class="bg-white/5 rounded-2xl border border-white/5 overflow-hidden">
                        <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                            <h2 class="text-base font-semibold text-white">Recent Users</h2>
                            <a href="{{ route('admin.users') }}" class="text-sm text-sky-400 hover:text-sky-300 transition-colors">View all →</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-white/5">
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Role</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Joined</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @foreach(\App\Models\User::latest()->take(8)->get() as $user)
                                    <tr class="hover:bg-white/3 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-sky-400 to-indigo-600 flex items-center justify-center text-xs font-bold text-white">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </div>
                                                <span class="font-medium text-white">{{ $user->name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-400">{{ $user->email }}</td>
                                        <td class="px-6 py-4">
                                            @if($user->role === 'admin')
                                                <span class="px-2 py-1 text-xs font-medium bg-sky-500/20 text-sky-400 rounded-full">Admin</span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-medium bg-slate-500/20 text-slate-400 rounded-full">User</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-slate-400">{{ $user->created_at->format('d M Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Recent Activities Panel --}}
                    <div class="bg-white/5 rounded-2xl border border-white/5 overflow-hidden flex flex-col">
                        <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                            <h2 class="text-base font-semibold text-white">Recent Activities</h2>
                            <span class="text-xs text-sky-400 font-medium">Real-time user logs</span>
                        </div>
                        <div class="p-6 flex-1 overflow-y-auto space-y-4 max-h-[400px]">
                            @forelse(\App\Models\UserActivity::with('user')->latest()->take(10)->get() as $activity)
                            <div class="flex items-start gap-3 text-sm">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                                    {{ strtoupper(substr($activity->user->name ?? '?', 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-slate-200">
                                        <strong class="text-white font-semibold">{{ $activity->user->name ?? 'Unknown User' }}</strong>
                                        <span class="text-slate-400">performed</span>
                                        <span class="px-1.5 py-0.5 text-[10px] font-semibold bg-white/10 text-white rounded capitalize">{{ str_replace('_', ' ', $activity->activity_type) }}</span>
                                    </p>
                                    <p class="text-slate-400 text-xs mt-1">{{ $activity->description }}</p>
                                    <p class="text-[10px] text-slate-600 mt-0.5">{{ $activity->created_at->diffForHumans() }} · IP: {{ $activity->ip_address ?? 'N/A' }}</p>
                                </div>
                            </div>
                            @empty
                            <p class="text-xs text-slate-500 italic">No recent user activity logs found.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>
