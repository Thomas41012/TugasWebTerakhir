<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users – Admin</title>
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
            <a href="{{ route('admin.users') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-sky-500/10 text-sky-400 font-medium text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Manage Users
            </a>
            <a href="{{ route('admin.ports') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:bg-white/5 hover:text-white text-sm transition-all">
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
                <h1 class="text-xl font-bold text-white">Manage Users</h1>
                <p class="text-xs text-slate-400">{{ \App\Models\User::count() }} registered users</p>
            </div>
        </header>

        <div class="p-8" x-data="{ openUser: null }">
            
            @if(session('success'))
            <div class="mb-4 px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl text-sm font-medium">
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="mb-4 px-4 py-3 bg-red-500/10 border border-red-500/20 text-red-400 rounded-xl text-sm font-medium">
                {{ session('error') }}
            </div>
            @endif

            <div class="bg-white/5 rounded-2xl border border-white/5 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">User</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Verified</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Joined</th>
                                <th class="px-6 py-4 text-right text-xs font-medium text-slate-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach(\App\Models\User::with('activities')->latest()->get() as $user)
                            <tr class="hover:bg-white/3 transition-colors cursor-pointer"
                                @click="openUser = {
                                    id: @js($user->id),
                                    name: @js($user->name),
                                    email: @js($user->email),
                                    role: @js($user->role),
                                    verified: @js($user->email_verified_at ? 'Verified' : 'Unverified'),
                                    joined: @js($user->created_at->format('d M Y')),
                                    activities: @js($user->activities->map(fn($act) => [
                                        'type' => $act->activity_type,
                                        'desc' => $act->description,
                                        'time' => $act->created_at->diffForHumans(),
                                        'ip' => $act->ip_address
                                    ])->toArray())
                                }">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-sky-400 to-indigo-600 flex items-center justify-center text-xs font-bold text-white">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <span class="font-medium text-white">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-400">{{ $user->email }}</td>
                                <td class="px-6 py-4">
                                    @if($user->role === 'admin')
                                        <span class="px-2.5 py-1 text-xs font-medium bg-sky-500/20 text-sky-400 rounded-full border border-sky-500/30">Admin</span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-medium bg-slate-500/20 text-slate-300 rounded-full border border-slate-500/20">User</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($user->email_verified_at)
                                        <span class="flex items-center gap-1 text-emerald-400 text-xs"><span class="w-1.5 h-1.5 bg-emerald-400 rounded-full"></span>Verified</span>
                                    @else
                                        <span class="flex items-center gap-1 text-slate-500 text-xs"><span class="w-1.5 h-1.5 bg-slate-500 rounded-full"></span>Unverified</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-400">{{ $user->created_at->format('d M Y') }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if($user->role !== 'admin')
                                    <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" @click.stop onsubmit="return confirm('Apakah kamu yakin ingin menghapus user ini secara permanen?');" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 text-[11px] font-semibold bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 border border-rose-500/30 rounded-lg transition-all">
                                            Hapus
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Detail User & Activities -->
            <div 
                x-show="openUser" 
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
                x-transition
                @keydown.escape.window="openUser = null"
                style="display: none;"
            >
                <div 
                    @click.away="openUser = null" 
                    class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-xl overflow-hidden shadow-2xl flex flex-col max-h-[80vh]"
                >
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-white">User Activity Logs</h3>
                        <button @click="openUser = null" class="text-slate-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-6 overflow-y-auto space-y-6">
                        <!-- Profile Card -->
                        <div class="flex items-center gap-4 bg-white/5 p-4 rounded-xl border border-white/5">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-sky-400 to-indigo-600 flex items-center justify-center text-base font-bold text-white">
                                <span x-text="openUser?.name.substring(0,1).toUpperCase()"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-white truncate" x-text="openUser?.name"></h4>
                                <p class="text-xs text-slate-400 truncate" x-text="openUser?.email"></p>
                            </div>
                            <div class="flex flex-col items-end gap-1.5">
                                <span class="px-2 py-0.5 text-[10px] font-semibold uppercase rounded-full border border-sky-500/30 text-sky-400 bg-sky-500/10"
                                      x-show="openUser?.role === 'admin'">Admin</span>
                                <span class="px-2 py-0.5 text-[10px] font-semibold uppercase rounded-full border border-slate-700 text-slate-300 bg-slate-500/10"
                                      x-show="openUser?.role !== 'admin'">User</span>
                                <span class="text-[10px] text-slate-500" x-text="'Joined ' + openUser?.joined"></span>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="space-y-4">
                            <h5 class="text-sm font-semibold text-white">Activity History</h5>
                            
                            <template x-if="openUser?.activities.length === 0">
                                <p class="text-xs text-slate-500 italic py-2">No activity logs recorded yet.</p>
                            </template>
                            
                            <div class="relative pl-4 border-l border-slate-800 space-y-4">
                                <template x-for="(act, index) in openUser?.activities" :key="index">
                                    <div class="relative">
                                        <!-- Indicator dot -->
                                        <div class="absolute -left-[21px] top-1.5 w-2.5 h-2.5 rounded-full border-2 border-slate-900 bg-emerald-500"
                                             :class="{
                                                 'bg-sky-400': act.type === 'login',
                                                 'bg-rose-500': act.type === 'logout',
                                                 'bg-emerald-400': act.type === 'select_country',
                                                 'bg-violet-400': act.type === 'register'
                                             }"></div>
                                        <div>
                                            <div class="flex items-center justify-between gap-4">
                                                <span class="text-xs font-semibold text-white capitalize" x-text="act.type.replace('_', ' ')"></span>
                                                <span class="text-[10px] text-slate-500" x-text="act.time"></span>
                                            </div>
                                            <p class="text-xs text-slate-400 mt-1" x-text="act.desc"></p>
                                            <p class="text-[9px] text-slate-600 mt-0.5" x-text="'IP: ' + (act.ip || 'N/A')"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-slate-800 flex justify-between items-center">
                        <form method="POST" :action="`/admin/users/${openUser?.id}`" x-show="openUser?.role !== 'admin'" onsubmit="return confirm('Apakah kamu yakin ingin menghapus user ini secara permanen?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 text-xs font-semibold bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 border border-rose-500/30 rounded-lg transition-all">
                                Hapus User
                            </button>
                        </form>
                        <div x-show="openUser?.role === 'admin'"></div>

                        <button @click="openUser = null" class="px-4 py-2 text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 rounded-lg transition-all">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
