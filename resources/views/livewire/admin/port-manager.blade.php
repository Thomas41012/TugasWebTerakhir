<div>
    @if(session()->has('success'))
        <div class="mb-4 px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    {{-- Port Stats --}}
    <div class="grid grid-cols-3 gap-5 mb-8">
        <div class="bg-white/5 rounded-2xl p-5 border border-white/5">
            <p class="text-sm text-slate-400 mb-1">Active Ports</p>
            <p class="text-3xl font-bold text-emerald-400">{{ $activePorts }}</p>
        </div>
        <div class="bg-white/5 rounded-2xl p-5 border border-white/5">
            <p class="text-sm text-slate-400 mb-1">High Congestion</p>
            <p class="text-3xl font-bold text-amber-400">{{ $highCongestion }}</p>
        </div>
        <div class="bg-white/5 rounded-2xl p-5 border border-white/5">
            <p class="text-sm text-slate-400 mb-1">High Risk</p>
            <p class="text-3xl font-bold text-red-400">{{ $highRisk }}</p>
        </div>
    </div>

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-white">Manage Ports</h2>
        <button wire:click="create()" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-xl transition-colors">
            + Add Port
        </button>
    </div>

    {{-- Ports Table --}}
    <div class="bg-white/5 rounded-2xl border border-white/5 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/5 bg-white/5">
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Port Name</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Country</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Congestion</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Risk</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($ports as $port)
                    <tr class="hover:bg-white/3 transition-colors">
                        <td class="px-6 py-3 font-medium text-white">{{ $port->name }}</td>
                        <td class="px-6 py-3 text-slate-400">{{ $port->country?->name ?? 'N/A' }}</td>
                        <td class="px-6 py-3 text-slate-400 capitalize">{{ $port->type ?? 'N/A' }}</td>
                        <td class="px-6 py-3">
                            @if($port->status === 'active')
                                <span class="px-2 py-0.5 text-xs bg-emerald-500/20 text-emerald-400 rounded-full">Active</span>
                            @else
                                <span class="px-2 py-0.5 text-xs bg-slate-500/20 text-slate-400 rounded-full capitalize">{{ $port->status }}</span>
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
                        <td class="px-6 py-3 text-right">
                            <button wire:click="edit({{ $port->id }})" class="px-3 py-1.5 text-[11px] font-semibold bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 border border-sky-500/30 rounded-lg transition-all mr-2">Edit</button>
                            <button wire:click="delete({{ $port->id }})" onclick="confirm('Yakin ingin menghapus port ini?') || event.stopImmediatePropagation()" class="px-3 py-1.5 text-[11px] font-semibold bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 border border-rose-500/30 rounded-lg transition-all">Hapus</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-4">
        {{ $ports->links() }}
    </div>

    {{-- Modal --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">{{ $portId ? 'Edit Port' : 'Add Port' }}</h3>
                <button wire:click="closeModal()" class="text-slate-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form wire:submit.prevent="store" class="p-6 overflow-y-auto space-y-4 max-h-[70vh]">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Port Name</label>
                    <input type="text" wire:model="name" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500" placeholder="e.g. Port of Singapore">
                    @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Country</label>
                    <select wire:model="country_id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500">
                        <option value="">Select Country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                        @endforeach
                    </select>
                    @error('country_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Latitude</label>
                        <input type="text" wire:model="latitude" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500">
                        @error('latitude') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Longitude</label>
                        <input type="text" wire:model="longitude" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500">
                        @error('longitude') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Status</label>
                        <select wire:model="status" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                        @error('status') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Type</label>
                        <input type="text" wire:model="type" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500">
                        @error('type') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Congestion Level (0-100)</label>
                        <input type="number" wire:model="congestion_level" min="0" max="100" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500">
                        @error('congestion_level') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Risk Score (0-100)</label>
                        <input type="number" wire:model="risk_score" min="0" max="100" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500">
                        @error('risk_score') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" wire:click="closeModal()" class="px-4 py-2 text-sm font-semibold bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 rounded-lg transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-semibold bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg transition-all">Save</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
