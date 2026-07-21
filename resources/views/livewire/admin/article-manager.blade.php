<div>
    @if(session()->has('success'))
        <div class="mb-4 px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-5 mb-8">
        <div class="bg-white/5 rounded-2xl p-5 border border-white/5">
            <p class="text-sm text-slate-400 mb-1">Total Articles</p>
            <p class="text-3xl font-bold text-emerald-400">{{ $totalArticles }}</p>
        </div>
        <div class="bg-white/5 rounded-2xl p-5 border border-white/5">
            <p class="text-sm text-slate-400 mb-1">Published</p>
            <p class="text-3xl font-bold text-sky-400">{{ $publishedArticles }}</p>
        </div>
        <div class="bg-white/5 rounded-2xl p-5 border border-white/5">
            <p class="text-sm text-slate-400 mb-1">Draft</p>
            <p class="text-3xl font-bold text-amber-400">{{ $draftArticles }}</p>
        </div>
    </div>

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-white">Manage Articles</h2>
        <button wire:click="create()" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-xl transition-colors">
            + Create Article
        </button>
    </div>

    {{-- Table --}}
    <div class="bg-white/5 rounded-2xl border border-white/5 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/5 bg-white/5">
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Author</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Country</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Published At</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($articles as $article)
                    <tr class="hover:bg-white/3 transition-colors">
                        <td class="px-6 py-3 font-medium text-white">{{ Str::limit($article->title, 40) }}</td>
                        <td class="px-6 py-3 text-slate-400">{{ $article->user?->name ?? 'N/A' }}</td>
                        <td class="px-6 py-3 text-slate-400">{{ $article->country?->name ?? 'Global' }}</td>
                        <td class="px-6 py-3">
                            @if($article->status === 'published')
                                <span class="px-2 py-0.5 text-xs bg-emerald-500/20 text-emerald-400 rounded-full">Published</span>
                            @elseif($article->status === 'draft')
                                <span class="px-2 py-0.5 text-xs bg-amber-500/20 text-amber-400 rounded-full">Draft</span>
                            @else
                                <span class="px-2 py-0.5 text-xs bg-slate-500/20 text-slate-400 rounded-full capitalize">{{ $article->status }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-slate-400">
                            {{ $article->published_at ? $article->published_at->format('d M Y') : '-' }}
                        </td>
                        <td class="px-6 py-3 text-right">
                            <button wire:click="edit({{ $article->id }})" class="px-3 py-1.5 text-[11px] font-semibold bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 border border-sky-500/30 rounded-lg transition-all mr-2">Edit</button>
                            <button wire:click="delete({{ $article->id }})" onclick="confirm('Yakin ingin menghapus artikel ini?') || event.stopImmediatePropagation()" class="px-3 py-1.5 text-[11px] font-semibold bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 border border-rose-500/30 rounded-lg transition-all">Hapus</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-4">
        {{ $articles->links() }}
    </div>

    {{-- Modal --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">{{ $articleId ? 'Edit Article' : 'Create Article' }}</h3>
                <button wire:click="closeModal()" class="text-slate-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form wire:submit.prevent="store" class="p-6 overflow-y-auto space-y-4 max-h-[70vh]">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Title</label>
                    <input type="text" wire:model="title" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500">
                    @error('title') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Country (Optional)</label>
                        <select wire:model="country_id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500">
                            <option value="">Global (No specific country)</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                        @error('country_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Status</label>
                        <select wire:model="status" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </select>
                        @error('status') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Excerpt (Optional)</label>
                    <textarea wire:model="excerpt" rows="2" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500"></textarea>
                    @error('excerpt') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Content</label>
                    <textarea wire:model="content" rows="6" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500"></textarea>
                    @error('content') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" wire:click="closeModal()" class="px-4 py-2 text-sm font-semibold bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 rounded-lg transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-semibold bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg transition-all">Save Article</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
