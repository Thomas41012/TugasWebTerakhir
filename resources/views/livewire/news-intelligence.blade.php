<?php

use App\Models\Country;
use App\Models\News;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sentiment = '';

    public string $countryId = '';

    public array $countries = [];

    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->loadCountries();
    }

    /*
    |--------------------------------------------------------------------------
    | Global Synchronization Listener
    |--------------------------------------------------------------------------
    */

    #[On('global-sync-completed')]
    public function handleGlobalSyncCompleted(): void
    {
        $this->refreshNews();
    }

    /*
    |--------------------------------------------------------------------------
    | Load Countries
    |--------------------------------------------------------------------------
    */

    private function loadCountries(): void
    {
        $this->countries = Country::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'iso2',
                'iso3',
            ])
            ->map(
                fn (Country $country): array => [
                    'id' => $country->id,
                    'name' => $country->name,
                    'iso2' => $country->iso2,
                    'iso3' => $country->iso3,
                ]
            )
            ->values()
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Search Updated
    |--------------------------------------------------------------------------
    */

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | Sentiment Updated
    |--------------------------------------------------------------------------
    */

    public function updatedSentiment(): void
    {
        if (
            ! in_array(
                $this->sentiment,
                [
                    '',
                    'positive',
                    'neutral',
                    'negative',
                ],
                true
            )
        ) {
            $this->sentiment = '';
        }

        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | Country Updated
    |--------------------------------------------------------------------------
    */

    public function updatedCountryId(): void
    {
        if ($this->countryId !== '') {
            $countryExists = Country::query()
                ->where('is_active', true)
                ->whereKey((int) $this->countryId)
                ->exists();

            if (! $countryExists) {
                $this->countryId = '';
            }
        }

        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | Reset Filters
    |--------------------------------------------------------------------------
    */

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'sentiment',
            'countryId',
        ]);

        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | Refresh News
    |--------------------------------------------------------------------------
    */

    public function refreshNews(): void
    {
        $this->loadCountries();

        /*
         * Pastikan negara yang sedang dipilih
         * masih tersedia.
         */

        if ($this->countryId !== '') {
            $countryExists = collect($this->countries)
                ->contains(
                    fn (array $country): bool =>
                        (string) $country['id']
                        === (string) $this->countryId
                );

            if (! $countryExists) {
                $this->countryId = '';
            }
        }

        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | Render Data
    |--------------------------------------------------------------------------
    */

    public function with(): array
    {
        /*
        |--------------------------------------------------------------------------
        | News Query
        |--------------------------------------------------------------------------
        */

        $news = News::query()
            ->with([
                'country:id,name,iso2,iso3',
            ])

            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            ->when(
                trim($this->search) !== '',
                function (Builder $query): void {
                    $search = trim($this->search);

                    $query->where(
                        function (Builder $subQuery) use ($search): void {
                            $subQuery
                                ->where(
                                    'title',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'description',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'content',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'source',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'category',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Sentiment Filter
            |--------------------------------------------------------------------------
            */

            ->when(
                $this->sentiment !== '',
                fn (Builder $query) =>
                    $query->where(
                        'sentiment',
                        $this->sentiment
                    )
            )

            /*
            |--------------------------------------------------------------------------
            | Country Filter
            |--------------------------------------------------------------------------
            */

            ->when(
                $this->countryId !== '',
                fn (Builder $query) =>
                    $query->where(
                        'country_id',
                        (int) $this->countryId
                    )
            )

            /*
            |--------------------------------------------------------------------------
            | Latest News
            |--------------------------------------------------------------------------
            */

            ->orderByDesc('published_at')
            ->orderByDesc('id')

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            ->paginate(6);

        /*
        |--------------------------------------------------------------------------
        | Global News Statistics
        |--------------------------------------------------------------------------
        */

        $statistics = [
            'total' => News::query()->count(),

            'positive' => News::query()
                ->where('sentiment', 'positive')
                ->count(),

            'neutral' => News::query()
                ->where('sentiment', 'neutral')
                ->count(),

            'negative' => News::query()
                ->where('sentiment', 'negative')
                ->count(),
        ];

        return [
            'news' => $news,
            'statistics' => $statistics,
        ];
    }
};
?>

<div wire:poll.300s="refreshNews" x-data="{ openNews: null }">

    <div
        class="rounded-2xl border border-slate-800
               bg-slate-900/80 p-5"
    >

        {{-- ============================================================
             HEADER
        ============================================================ --}}

        <div
            class="flex flex-col justify-between gap-4
                   xl:flex-row xl:items-center"
        >

            <div>

                <div class="flex items-center gap-3">

                    <h2 class="text-xl font-bold text-white">
                        News Intelligence
                    </h2>

                    <span
                        class="rounded-full
                               border border-violet-500/20
                               bg-violet-500/10
                               px-2.5 py-1
                               text-[10px] font-semibold
                               text-violet-400"
                    >
                        LIVE INTELLIGENCE
                    </span>

                </div>

                <p class="mt-1 text-sm text-slate-400">
                    Global supply chain news and sentiment monitoring.
                </p>

            </div>

            {{-- ========================================================
                 FILTER
            ======================================================== --}}

            <div
                class="flex flex-col gap-3
                       sm:flex-row sm:flex-wrap"
            >

                {{-- SEARCH --}}

                <input
                    type="search"
                    wire:model.live.debounce.500ms="search"
                    placeholder="Search intelligence..."
                    class="min-w-[220px] rounded-xl
                           border border-slate-700
                           bg-slate-950
                           px-4 py-2
                           text-sm text-white
                           outline-none transition
                           placeholder:text-slate-600
                           focus:border-violet-400"
                >

                {{-- COUNTRY FILTER --}}

                <select
                    wire:model.live="countryId"
                    class="rounded-xl
                           border border-slate-700
                           bg-slate-950
                           px-4 py-2
                           text-sm text-white
                           outline-none transition
                           focus:border-cyan-400"
                >

                    <option value="">
                        All Countries
                    </option>

                    @foreach ($countries as $country)

                        <option
                            value="{{ $country['id'] }}"
                        >
                            {{ $country['name'] }}
                        </option>

                    @endforeach

                </select>

                {{-- SENTIMENT FILTER --}}

                <select
                    wire:model.live="sentiment"
                    class="rounded-xl
                           border border-slate-700
                           bg-slate-950
                           px-4 py-2
                           text-sm text-white
                           outline-none transition
                           focus:border-emerald-400"
                >

                    <option value="">
                        All Sentiments
                    </option>

                    <option value="positive">
                        Positive
                    </option>

                    <option value="neutral">
                        Neutral
                    </option>

                    <option value="negative">
                        Negative
                    </option>

                </select>

                {{-- RESET FILTER --}}

                @if (
                    $search !== ''
                    || $countryId !== ''
                    || $sentiment !== ''
                )

                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="rounded-xl
                               border border-slate-700
                               bg-slate-950
                               px-4 py-2
                               text-sm text-slate-400
                               transition
                               hover:border-rose-500/40
                               hover:text-rose-400"
                    >
                        Reset
                    </button>

                @endif

            </div>

        </div>

        {{-- ============================================================
             NEWS STATISTICS
        ============================================================ --}}

        <div
            class="mt-6 grid gap-3
                   sm:grid-cols-2
                   lg:grid-cols-4"
        >

            {{-- TOTAL --}}

            <div
                class="rounded-xl border border-slate-800
                       bg-slate-950/70 p-4"
            >

                <p
                    class="text-xs uppercase
                           tracking-wider text-slate-500"
                >
                    Total Intelligence
                </p>

                <p
                    class="mt-2 text-2xl
                           font-bold text-violet-400"
                >
                    {{
                        number_format(
                            $statistics['total']
                        )
                    }}
                </p>

            </div>

            {{-- POSITIVE --}}

            <div
                class="rounded-xl
                       border border-emerald-500/10
                       bg-emerald-500/5 p-4"
            >

                <p
                    class="text-xs uppercase
                           tracking-wider text-slate-500"
                >
                    Positive
                </p>

                <p
                    class="mt-2 text-2xl
                           font-bold text-emerald-400"
                >
                    {{
                        number_format(
                            $statistics['positive']
                        )
                    }}
                </p>

            </div>

            {{-- NEUTRAL --}}

            <div
                class="rounded-xl border border-slate-800
                       bg-slate-950/70 p-4"
            >

                <p
                    class="text-xs uppercase
                           tracking-wider text-slate-500"
                >
                    Neutral
                </p>

                <p
                    class="mt-2 text-2xl
                           font-bold text-slate-300"
                >
                    {{
                        number_format(
                            $statistics['neutral']
                        )
                    }}
                </p>

            </div>

            {{-- NEGATIVE --}}

            <div
                class="rounded-xl
                       border border-rose-500/10
                       bg-rose-500/5 p-4"
            >

                <p
                    class="text-xs uppercase
                           tracking-wider text-slate-500"
                >
                    Negative
                </p>

                <p
                    class="mt-2 text-2xl
                           font-bold text-rose-400"
                >
                    {{
                        number_format(
                            $statistics['negative']
                        )
                    }}
                </p>

            </div>

        </div>

        {{-- ============================================================
             LOADING STATE
        ============================================================ --}}

        <div
            wire:loading.flex
            wire:target="
                search,
                sentiment,
                countryId,
                resetFilters,
                refreshNews
            "
            class="mt-6 items-center justify-center
                   rounded-xl
                   border border-slate-800
                   bg-slate-950/70 p-5"
        >

            <svg
                class="mr-3 h-5 w-5
                       animate-spin text-violet-400"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >

                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                ></circle>

                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                ></path>

            </svg>

            <span class="text-sm text-slate-400">
                Updating intelligence news...
            </span>

        </div>

        {{-- ============================================================
             NEWS GRID
        ============================================================ --}}

        <div
            wire:loading.remove
            wire:target="
                search,
                sentiment,
                countryId,
                resetFilters,
                refreshNews
            "
            class="mt-6 grid gap-4
                   md:grid-cols-2
                   xl:grid-cols-3"
        >

            @forelse ($news as $article)

                <article
                    wire:key="news-{{ $article->id }}"
                    class="group flex h-full
                           flex-col overflow-hidden
                           rounded-xl
                           border border-slate-800
                           bg-slate-950/70
                           transition duration-300
                           hover:border-violet-500/30"
                >

                    {{-- =================================================
                         NEWS IMAGE
                    ================================================= --}}

                    @if ($article->image_url)

                        <div
                            class="relative h-44
                                   overflow-hidden bg-slate-900"
                        >

                            <img
                                src="{{ $article->image_url }}"
                                alt="{{ $article->title }}"
                                loading="lazy"
                                class="h-full w-full
                                       object-cover
                                       transition duration-500
                                       group-hover:scale-105"
                                onerror="
                                    this.parentElement.style.display='none';
                                "
                            >

                            <div
                                class="absolute inset-0
                                       bg-gradient-to-t
                                       from-slate-950
                                       via-transparent
                                       to-transparent"
                            ></div>

                        </div>

                    @endif

                    {{-- =================================================
                         ARTICLE CONTENT
                    ================================================= --}}

                    <div class="flex flex-1 flex-col p-5">

                        {{-- HEADER --}}

                        <div
                            class="flex items-center
                                   justify-between gap-3"
                        >

                            <span
                                class="text-xs font-medium
                                       text-cyan-400"
                            >
                                {{
                                    $article->country?->name
                                    ?? 'Global'
                                }}
                            </span>

                            {{-- SENTIMENT BADGE --}}

                            <span
                                @class([
                                    'rounded-full border px-2 py-1 text-xs',

                                    'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                                        => $article->sentiment === 'positive',

                                    'border-rose-500/30 bg-rose-500/10 text-rose-400'
                                        => $article->sentiment === 'negative',

                                    'border-slate-700 bg-slate-800/50 text-slate-400'
                                        => ! in_array(
                                            $article->sentiment,
                                            [
                                                'positive',
                                                'negative',
                                            ],
                                            true
                                        ),
                                ])
                            >
                                {{
                                    ucfirst(
                                        $article->sentiment
                                        ?? 'neutral'
                                    )
                                }}
                            </span>

                        </div>

                        {{-- CATEGORY --}}

                        @if ($article->category)

                            <p
                                class="mt-3 text-[10px]
                                       font-semibold uppercase
                                       tracking-wider
                                       text-violet-400"
                            >
                                {{ $article->category }}
                            </p>

                        @endif

                        {{-- TITLE --}}

                        <h3
                            class="mt-3 line-clamp-2
                                   font-semibold leading-6
                                   text-white transition
                                   group-hover:text-violet-300"
                        >
                            {{ $article->title }}
                        </h3>

                        {{-- DESCRIPTION --}}

                        <p
                            class="mt-3 line-clamp-3
                                   text-sm leading-6
                                   text-slate-400"
                        >
                            {{
                                $article->description
                                ?: 'No description available.'
                            }}
                        </p>

                        {{-- SENTIMENT SCORE --}}

                        <div
                            class="mt-4 grid grid-cols-3 gap-2"
                        >

                            {{-- POSITIVE --}}

                            <div
                                class="rounded-lg
                                       border border-emerald-500/10
                                       bg-emerald-500/5
                                       p-2 text-center"
                            >

                                <p
                                    class="text-[10px]
                                           uppercase text-slate-500"
                                >
                                    Positive
                                </p>

                                <p
                                    class="mt-1 text-xs
                                           font-semibold
                                           text-emerald-400"
                                >
                                    {{
                                        number_format(
                                            (float) (
                                                $article->positive_score
                                                ?? 0
                                            ),
                                            2
                                        )
                                    }}
                                </p>

                            </div>

                            {{-- NEGATIVE --}}

                            <div
                                class="rounded-lg
                                       border border-rose-500/10
                                       bg-rose-500/5
                                       p-2 text-center"
                            >

                                <p
                                    class="text-[10px]
                                           uppercase text-slate-500"
                                >
                                    Negative
                                </p>

                                <p
                                    class="mt-1 text-xs
                                           font-semibold
                                           text-rose-400"
                                >
                                    {{
                                        number_format(
                                            (float) (
                                                $article->negative_score
                                                ?? 0
                                            ),
                                            2
                                        )
                                    }}
                                </p>

                            </div>

                            {{-- NORMALIZED SCORE --}}

                            <div
                                class="rounded-lg
                                       border border-cyan-500/10
                                       bg-cyan-500/5
                                       p-2 text-center"
                            >

                                <p
                                    class="text-[10px]
                                           uppercase text-slate-500"
                                >
                                    Score
                                </p>

                                <p
                                    class="mt-1 text-xs
                                           font-semibold
                                           text-cyan-400"
                                >
                                    {{
                                        number_format(
                                            (float) (
                                                $article->sentiment_score
                                                ?? 0
                                            ),
                                            4
                                        )
                                    }}
                                </p>

                            </div>

                        </div>

                        {{-- FOOTER --}}

                        <div
                            class="mt-auto pt-5"
                        >

                            <div
                                class="flex items-center
                                       justify-between gap-3
                                       border-t border-slate-800
                                       pt-4"
                            >

                                <div class="min-w-0">

                                    <p
                                        class="truncate
                                               text-xs text-slate-500"
                                    >
                                        {{
                                            $article->source
                                            ?: 'Unknown Source'
                                        }}
                                    </p>

                                    <p
                                        class="mt-1
                                               text-[10px]
                                               text-slate-600"
                                    >

                                        @if ($article->published_at)

                                            {{
                                                $article
                                                    ->published_at
                                                    ->diffForHumans()
                                            }}

                                        @else

                                            Unknown date

                                        @endif

                                    </p>

                                </div>

                                {{-- READ ARTICLE --}}

                                @if ($article->url)

                                    <button
                                        type="button"
                                        @click="openNews = {
                                            title: @js($article->title),
                                            content: @js($article->content ?? $article->description ?? 'No content available.'),
                                            source: @js($article->source ?? 'Global News'),
                                            published_at: @js($article->published_at ? $article->published_at->format('d M Y H:i') : 'Unknown date'),
                                            sentiment: @js($article->sentiment ?? 'neutral')
                                        }"
                                        class="flex-shrink-0
                                               rounded-lg
                                               border border-violet-500/20
                                               bg-violet-500/10
                                               px-3 py-2
                                               text-xs font-medium
                                               text-violet-400
                                               transition
                                               hover:border-violet-400
                                               hover:bg-violet-500/20"
                                    >
                                        Read Article
                                    </button>

                                @endif

                            </div>

                        </div>

                    </div>

                </article>

            @empty

                {{-- =====================================================
                     EMPTY STATE
                ===================================================== --}}

                <div
                    class="col-span-full
                           rounded-xl
                           border border-dashed
                           border-slate-700
                           bg-slate-950/70
                           p-10 text-center"
                >

                    <p
                        class="text-sm font-medium
                               text-slate-400"
                    >
                        No intelligence news available.
                    </p>

                    <p
                        class="mt-2 text-xs
                               text-slate-600"
                    >
                        Try changing the search,
                        country, or sentiment filters.
                    </p>

                    @if (
                        $search !== ''
                        || $countryId !== ''
                        || $sentiment !== ''
                    )

                        <button
                            type="button"
                            wire:click="resetFilters"
                            class="mt-4 rounded-lg
                                   border border-violet-500/20
                                   bg-violet-500/10
                                   px-4 py-2
                                   text-xs font-medium
                                   text-violet-400
                                   transition
                                   hover:bg-violet-500/20"
                        >
                            Reset Filters
                        </button>

                    @endif

                </div>

            @endforelse

        </div>

        {{-- ============================================================
             PAGINATION
        ============================================================ --}}

        @if ($news->hasPages())

            <div
                wire:loading.remove
                wire:target="
                    search,
                    sentiment,
                    countryId,
                    resetFilters,
                    refreshNews
                "
                class="mt-6 border-t
                       border-slate-800 pt-5"
            >
                {{ $news->links() }}
            </div>

        @endif

    </div>

    <!-- Modal Detail News -->
    <div 
        x-show="openNews" 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
        x-transition
        @keydown.escape.window="openNews = null"
        style="display: none;"
    >
        <div 
            @click.away="openNews = null" 
            class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[85vh]"
        >
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-xs text-cyan-400 font-medium" x-text="openNews?.source"></span>
                    <span 
                        class="px-2 py-0.5 text-[10px] font-medium rounded-full uppercase border border-slate-700 text-slate-400"
                        x-text="openNews?.sentiment || 'Neutral'"
                    ></span>
                </div>
                <button @click="openNews = null" class="text-slate-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <!-- Modal Body (Scrollable) -->
            <div class="p-6 overflow-y-auto space-y-4">
                <h2 class="text-xl font-bold text-white leading-snug" x-text="openNews?.title"></h2>
                
                <p class="text-xs text-slate-500" x-text="openNews?.published_at"></p>
                
                <div class="text-sm text-slate-300 leading-relaxed space-y-3 whitespace-pre-line" x-text="openNews?.content"></div>
            </div>
            
            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-800 flex justify-end">
                <button @click="openNews = null" class="px-4 py-2 text-xs font-semibold bg-slate-850 hover:bg-slate-800 text-white rounded-lg border border-slate-700 transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>