<div>
    @if(session()->has('success'))
        <div class="mb-4 px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-5 mb-8">
        <div class="bg-white/5 rounded-2xl p-5 border border-white/5">
            <p class="text-sm text-slate-400 mb-1">Positive Words</p>
            <p class="text-3xl font-bold text-emerald-400">{{ $totalPositive }}</p>
        </div>
        <div class="bg-white/5 rounded-2xl p-5 border border-white/5">
            <p class="text-sm text-slate-400 mb-1">Negative Words</p>
            <p class="text-3xl font-bold text-rose-400">{{ $totalNegative }}</p>
        </div>
    </div>

    <div class="flex justify-between items-center mb-4">
        <div class="flex space-x-2">
            <button wire:click="switchTab('positive')" class="px-4 py-2 text-sm font-medium rounded-xl transition-colors {{ $activeTab === 'positive' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' }}">
                Positive Dictionary
            </button>
            <button wire:click="switchTab('negative')" class="px-4 py-2 text-sm font-medium rounded-xl transition-colors {{ $activeTab === 'negative' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' }}">
                Negative Dictionary
            </button>
        </div>
        <button wire:click="create()" class="px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-xl transition-colors">
            + Add Word
        </button>
    </div>

    {{-- Table --}}
    <div class="bg-white/5 rounded-2xl border border-white/5 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/5 bg-white/5">
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Word</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Weight</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($words as $item)
                    <tr class="hover:bg-white/3 transition-colors">
                        <td class="px-6 py-3 font-medium text-white">{{ $item->word }}</td>
                        <td class="px-6 py-3 text-slate-400">{{ $item->weight }}</td>
                        <td class="px-6 py-3 text-right">
                            <button wire:click="edit({{ $item->id }})" class="px-3 py-1.5 text-[11px] font-semibold bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 border border-sky-500/30 rounded-lg transition-all mr-2">Edit</button>
                            <button wire:click="delete({{ $item->id }})" onclick="confirm('Yakin ingin menghapus kata ini?') || event.stopImmediatePropagation()" class="px-3 py-1.5 text-[11px] font-semibold bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 border border-rose-500/30 rounded-lg transition-all">Hapus</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-4">
        {{ $words->links() }}
    </div>

    {{-- Modal --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl flex flex-col">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">{{ $wordId ? 'Edit Word' : 'Add Word' }} ({{ ucfirst($activeTab) }})</h3>
                <button wire:click="closeModal()" class="text-slate-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form wire:submit.prevent="store" class="p-6 overflow-y-auto space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Word</label>
                    <input type="text" wire:model="word" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-sky-500">
                    @error('word') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Weight (0.1 to 10)</label>
                    <input type="number" step="0.1" wire:model="weight" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-sky-500">
                    @error('weight') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" wire:click="closeModal()" class="px-4 py-2 text-sm font-semibold bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 rounded-lg transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-semibold bg-sky-500 hover:bg-sky-600 text-white rounded-lg transition-all">Save</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
