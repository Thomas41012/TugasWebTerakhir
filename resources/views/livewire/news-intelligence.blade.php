<?php

use App\Models\Country;
use App\Models\News;
use App\Services\CurrencyService;
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

    // Currency Converter State
    public string $calcAmount = '100000';
    public string $fromCurrency = 'IDR';
    public string $toCurrency = 'USD';
    public array $liveRates = [];
    public string $lastRatesUpdated = '';

    public function mount(CurrencyService $currencyService): void
    {
        $this->loadCountries();
        $this->fetchRates($currencyService);
    }

    #[On('global-sync-completed')]
    public function handleGlobalSyncCompleted(CurrencyService $currencyService): void
    {
        $this->refreshNews();
        $this->fetchRates($currencyService, true);
    }

    public function fetchRates(CurrencyService $currencyService, bool $force = false): void
    {
        $this->liveRates = $currencyService->getLiveExchangeRates($force);
        $this->lastRatesUpdated = now()->format('H:i:s T');
    }

    public function refreshLiveRates(CurrencyService $currencyService): void
    {
        $this->fetchRates($currencyService, true);
    }

    public function setQuickAmount(string $amount): void
    {
        $this->calcAmount = $amount;
    }

    public function swapCurrencies(): void
    {
        $temp = $this->fromCurrency;
        $this->fromCurrency = $this->toCurrency;
        $this->toCurrency = $temp;
    }

    public function parseNumericAmount(string $val): float
    {
        $val = trim($val);
        if (preg_match('/^([\d\.,]+)\s*k$/i', $val, $m)) {
            return (float) str_replace(['.', ','], '', $m[1]) * 1000;
        }
        if (preg_match('/^([\d\.,]+)\s*m$/i', $val, $m)) {
            return (float) str_replace(['.', ','], '', $m[1]) * 1000000;
        }
        $clean = str_replace(['.', ','], '', $val);
        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    private function loadCountries(): void
    {
        $this->countries = Country::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'iso2', 'iso3', 'currency_code', 'currency_symbol'])
            ->map(fn (Country $country): array => [
                'id' => $country->id,
                'name' => $country->name,
                'iso2' => $country->iso2,
                'iso3' => $country->iso3,
                'currency_code' => $country->currency_code,
                'currency_symbol' => $country->currency_symbol,
            ])
            ->values()
            ->toArray();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSentiment(): void
    {
        if (! in_array($this->sentiment, ['', 'positive', 'neutral', 'negative'], true)) {
            $this->sentiment = '';
        }
        $this->resetPage();
    }

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

    public function resetFilters(): void
    {
        $this->reset(['search', 'sentiment', 'countryId']);
        $this->resetPage();
    }

    public function refreshNews(): void
    {
        $this->loadCountries();
        if ($this->countryId !== '') {
            $countryExists = collect($this->countries)->contains(
                fn (array $c): bool => (string) $c['id'] === (string) $this->countryId
            );
            if (! $countryExists) {
                $this->countryId = '';
            }
        }
        $this->resetPage();
    }

    public function with(CurrencyService $currencyService): array
    {
        $news = News::query()
            ->with(['country:id,name,iso2,iso3'])
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $search = trim($this->search);
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($this->sentiment !== '', fn (Builder $query) => $query->where('sentiment', $this->sentiment))
            ->when($this->countryId !== '', fn (Builder $query) => $query->where('country_id', (int) $this->countryId))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(6);

        $statistics = [
            'total' => News::query()->count(),
            'positive' => News::query()->where('sentiment', 'positive')->count(),
            'neutral' => News::query()->where('sentiment', 'neutral')->count(),
            'negative' => News::query()->where('sentiment', 'negative')->count(),
        ];

        // Currency Calculations
        $numericAmount = $this->parseNumericAmount($this->calcAmount);
        $convertedResult = $currencyService->convert(
            $numericAmount,
            $this->fromCurrency,
            $this->toCurrency,
            $this->liveRates
        );

        $unitRate = $currencyService->convert(1, $this->fromCurrency, $this->toCurrency, $this->liveRates);

        // Target Currencies List for Comparison
        $targetCurrencies = [
            ['code' => 'USD', 'name' => 'Dolar AS', 'symbol' => '$', 'flag' => 'us'],
            ['code' => 'CNY', 'name' => 'Yuan China', 'symbol' => '¥', 'flag' => 'cn'],
            ['code' => 'EUR', 'name' => 'Euro Eropa', 'symbol' => '€', 'flag' => 'de'],
            ['code' => 'JPY', 'name' => 'Yen Jepang', 'symbol' => '¥', 'flag' => 'jp'],
            ['code' => 'SGD', 'name' => 'Dolar Singapura', 'symbol' => 'S$', 'flag' => 'sg'],
            ['code' => 'MYR', 'name' => 'Ringgit Malaysia', 'symbol' => 'RM', 'flag' => 'my'],
            ['code' => 'GBP', 'name' => 'Pound Inggris', 'symbol' => '£', 'flag' => 'gb'],
            ['code' => 'AUD', 'name' => 'Dolar Australia', 'symbol' => 'A$', 'flag' => 'au'],
            ['code' => 'KRW', 'name' => 'Won Korea', 'symbol' => '₩', 'flag' => 'kr'],
            ['code' => 'SAR', 'name' => 'Riyal Arab Saudi', 'symbol' => 'SR', 'flag' => 'sa'],
            ['code' => 'THB', 'name' => 'Baht Thailand', 'symbol' => '฿', 'flag' => 'th'],
            ['code' => 'VND', 'name' => 'Dong Vietnam', 'symbol' => '₫', 'flag' => 'vn'],
            ['code' => 'TRY', 'name' => 'Lira Turki', 'symbol' => '₺', 'flag' => 'tr'],
            ['code' => 'CAD', 'name' => 'Dolar Kanada', 'symbol' => 'C$', 'flag' => 'ca'],
            ['code' => 'BRL', 'name' => 'Real Brasil', 'symbol' => 'R$', 'flag' => 'br'],
            ['code' => 'RUB', 'name' => 'Ruble Rusia', 'symbol' => '₽', 'flag' => 'ru'],
        ];

        $comparisonList = [];
        foreach ($targetCurrencies as $curr) {
            if ($curr['code'] === $this->fromCurrency) {
                continue;
            }
            $val = $currencyService->convert($numericAmount, $this->fromCurrency, $curr['code'], $this->liveRates);
            $comparisonList[] = array_merge($curr, [
                'converted_value' => $val,
            ]);
        }

        return [
            'news' => $news,
            'statistics' => $statistics,
            'numericAmount' => $numericAmount,
            'convertedResult' => $convertedResult,
            'unitRate' => $unitRate,
            'comparisonList' => $comparisonList,
        ];
    }
};
?>

<div wire:poll.300s="refreshNews" x-data="{ openNews: null }">

    {{-- ============================================================
         REAL-TIME CURRENCY CONVERTER & COMPARISON SECTION
    ============================================================ --}}
    <div class="mb-6 overflow-hidden rounded-2xl border border-cyan-500/20 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-6 shadow-xl shadow-cyan-950/20 backdrop-blur-md">
        
        <div class="flex flex-col justify-between gap-4 border-b border-slate-800/80 pb-5 md:flex-row md:items-center">
            <div>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            Kalkulator & Perbandingan Mata Uang Real-Time
                            <span class="inline-flex items-center gap-1 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-400">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                LIVE API
                            </span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Konversi dan bandingkan mata uang global berdasarkan kondisi forex terkini.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-400 font-mono hidden sm:inline">
                    Updated: {{ $lastRatesUpdated ?: 'Real-Time' }}
                </span>
                <button type="button" 
                        wire:click="refreshLiveRates" 
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl border border-cyan-500/30 bg-cyan-500/10 px-3 py-2 text-xs font-semibold text-cyan-300 transition hover:bg-cyan-500/20 active:scale-95">
                    <svg wire:loading.class="animate-spin" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Sync Live Rates
                </button>
            </div>
        </div>

        {{-- CONVERTER FORM --}}
        <div class="mt-5 grid gap-5 lg:grid-cols-12">
            
            {{-- LEFT: INPUT & SELECTION --}}
            <div class="space-y-4 lg:col-span-7">
                
                {{-- QUICK PRESET BUTTONS --}}
                <div>
                    <label class="block text-xs font-medium uppercase tracking-wider text-slate-400 mb-2">
                        Pilih Nominal Cepat (Preset)
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="setQuickAmount('100000')" 
                                class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition {{ $calcAmount === '100000' || strtolower($calcAmount) === '100k' ? 'border-cyan-400 bg-cyan-500/20 text-cyan-200' : 'border-slate-800 bg-slate-900/80 text-slate-400 hover:border-slate-700 hover:text-white' }}">
                            100 Ribu (100k)
                        </button>
                        <button type="button" wire:click="setQuickAmount('1000000')" 
                                class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition {{ $calcAmount === '1000000' || strtolower($calcAmount) === '1m' ? 'border-cyan-400 bg-cyan-500/20 text-cyan-200' : 'border-slate-800 bg-slate-900/80 text-slate-400 hover:border-slate-700 hover:text-white' }}">
                            1 Juta (1M)
                        </button>
                        <button type="button" wire:click="setQuickAmount('10000000')" 
                                class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition {{ $calcAmount === '10000000' || strtolower($calcAmount) === '10m' ? 'border-cyan-400 bg-cyan-500/20 text-cyan-200' : 'border-slate-800 bg-slate-900/80 text-slate-400 hover:border-slate-700 hover:text-white' }}">
                            10 Juta (10M)
                        </button>
                        <button type="button" wire:click="setQuickAmount('100000000')" 
                                class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition {{ $calcAmount === '100000000' || strtolower($calcAmount) === '100m' ? 'border-cyan-400 bg-cyan-500/20 text-cyan-200' : 'border-slate-800 bg-slate-900/80 text-slate-400 hover:border-slate-700 hover:text-white' }}">
                            100 Juta (100M)
                        </button>
                    </div>
                </div>

                {{-- AMOUNT INPUT & CURRENCIES --}}
                <div class="grid gap-3 sm:grid-cols-12">
                    
                    {{-- NOMINAL INPUT --}}
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-medium text-slate-400 mb-1">
                            Jumlah / Nominal
                        </label>
                        <input type="text" 
                               wire:model.live.debounce.300ms="calcAmount"
                               placeholder="Contoh: 100k / 100000"
                               class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white outline-none focus:border-cyan-400 transition" />
                    </div>

                    {{-- FROM CURRENCY --}}
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-medium text-slate-400 mb-1">
                            Dari
                        </label>
                        <select wire:model.live="fromCurrency" 
                                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm font-semibold text-white outline-none focus:border-cyan-400 transition">
                            <option value="IDR">🇮🇩 IDR (Rupiah)</option>
                            <option value="USD">🇺🇸 USD (Dolar AS)</option>
                            <option value="CNY">🇨🇳 CNY (Yuan China)</option>
                            <option value="EUR">🇪🇺 EUR (Euro)</option>
                            <option value="JPY">🇯🇵 JPY (Yen)</option>
                            <option value="SGD">🇸🇬 SGD (Dolar SG)</option>
                            <option value="MYR">🇲🇾 MYR (Ringgit)</option>
                            <option value="GBP">🇬🇧 GBP (Pound)</option>
                            <option value="AUD">🇦🇺 AUD (Dolar AU)</option>
                            <option value="KRW">🇰🇷 KRW (Won Korea)</option>
                            <option value="SAR">🇸🇦 SAR (Riyal)</option>
                            <option value="THB">🇹🇭 THB (Baht)</option>
                            <option value="VND">🇻🇳 VND (Dong)</option>
                            <option value="TRY">🇹🇷 TRY (Lira)</option>
                            <option value="CAD">🇨🇦 CAD (Dolar CA)</option>
                            <option value="BRL">🇧🇷 BRL (Real)</option>
                            <option value="RUB">🇷🇺 RUB (Ruble)</option>
                        </select>
                    </div>

                    {{-- SWAP BUTTON --}}
                    <div class="sm:col-span-1 flex items-end justify-center pb-0.5">
                        <button type="button" 
                                wire:click="swapCurrencies"
                                title="Tukar Mata Uang"
                                class="rounded-xl border border-slate-700 bg-slate-900 p-2.5 text-slate-400 transition hover:border-cyan-400 hover:text-white active:scale-90">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </button>
                    </div>

                    {{-- TO CURRENCY --}}
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-medium text-slate-400 mb-1">
                            Ke
                        </label>
                        <select wire:model.live="toCurrency" 
                                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm font-semibold text-white outline-none focus:border-cyan-400 transition">
                            <option value="USD">🇺🇸 USD (Dolar AS)</option>
                            <option value="CNY">🇨🇳 CNY (Yuan China)</option>
                            <option value="IDR">🇮🇩 IDR (Rupiah)</option>
                            <option value="EUR">🇪🇺 EUR (Euro)</option>
                            <option value="JPY">🇯🇵 JPY (Yen)</option>
                            <option value="SGD">🇸🇬 SGD (Dolar SG)</option>
                            <option value="MYR">🇲🇾 MYR (Ringgit)</option>
                            <option value="GBP">🇬🇧 GBP (Pound)</option>
                            <option value="AUD">🇦🇺 AUD (Dolar AU)</option>
                            <option value="KRW">🇰🇷 KRW (Won Korea)</option>
                            <option value="SAR">🇸🇦 SAR (Riyal)</option>
                            <option value="THB">🇹🇭 THB (Baht)</option>
                            <option value="VND">🇻🇳 VND (Dong)</option>
                            <option value="TRY">🇹🇷 TRY (Lira)</option>
                            <option value="CAD">🇨🇦 CAD (Dolar CA)</option>
                            <option value="BRL">🇧🇷 BRL (Real)</option>
                            <option value="RUB">🇷🇺 RUB (Ruble)</option>
                        </select>
                    </div>

                </div>

            </div>

            {{-- RIGHT: RESULT CARD --}}
            <div class="flex flex-col justify-between rounded-xl border border-cyan-500/30 bg-gradient-to-br from-cyan-950/40 via-slate-900/90 to-slate-950 p-4 lg:col-span-5">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-cyan-400 uppercase tracking-wider">
                            Hasil Konversi Real-Time
                        </span>
                        <span class="text-[10px] text-slate-400">
                            1 {{ $fromCurrency }} = {{ number_format($unitRate, 6) }} {{ $toCurrency }}
                        </span>
                    </div>

                    <div class="mt-3">
                        <div class="text-xs text-slate-400">
                            {{ number_format($numericAmount) }} {{ $fromCurrency }} =
                        </div>
                        <div class="mt-1 text-2xl font-extrabold text-white tracking-tight sm:text-3xl text-cyan-300">
                            @if($toCurrency === 'USD' || $toCurrency === 'AUD' || $toCurrency === 'SGD' || $toCurrency === 'CAD') $ @elseif($toCurrency === 'EUR') € @elseif($toCurrency === 'GBP') £ @elseif($toCurrency === 'JPY' || $toCurrency === 'CNY') ¥ @elseif($toCurrency === 'IDR') Rp @else {{ $toCurrency }} @endif 
                            {{ number_format($convertedResult, $convertedResult >= 100 ? 2 : 4) }}
                            <span class="text-sm font-semibold text-cyan-400/80">{{ $toCurrency }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-slate-800/80 pt-3 text-[11px] text-slate-400">
                    <span>
                        Informasi Pasar Global
                    </span>
                    <span class="font-medium text-emerald-400 flex items-center gap-1">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Tersinkronisasi Real-Time
                    </span>
                </div>
            </div>

        </div>

        {{-- MULTI-CURRENCY COMPARISON GRID --}}
        <div class="mt-6 border-t border-slate-800/80 pt-5">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">
                    Perbandingan Serentak {{ number_format($numericAmount) }} {{ $fromCurrency }} ke Mata Uang Dunia
                </h4>
                <span class="text-[11px] text-slate-400">
                    Perbandingan nilai riil di {{ count($comparisonList) }} mata uang utama
                </span>
            </div>

            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                @foreach ($comparisonList as $item)
                    <div class="rounded-xl border border-slate-800/90 bg-slate-950/80 p-3 transition hover:border-cyan-500/40 hover:bg-slate-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-200">
                                {{ $item['code'] }}
                            </span>
                            <img src="https://flagcdn.com/w40/{{ $item['flag'] }}.png" 
                                 alt="{{ $item['code'] }}" 
                                 class="h-3.5 w-5 rounded-sm object-cover shadow-sm" />
                        </div>
                        <div class="mt-2 text-sm font-extrabold text-cyan-300 truncate">
                            {{ $item['symbol'] }} {{ number_format($item['converted_value'], $item['converted_value'] >= 1000 ? 0 : ($item['converted_value'] >= 10 ? 2 : 4)) }}
                        </div>
                        <div class="text-[10px] text-slate-500 truncate mt-0.5">
                            {{ $item['name'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- ============================================================
         MAIN NEWS SECTION
    ============================================================ --}}
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">

        {{-- HEADER --}}
        <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">

            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-white">
                        News Intelligence
                    </h2>

                    <span class="rounded-full border border-violet-500/20 bg-violet-500/10 px-2.5 py-1 text-[10px] font-semibold text-violet-400">
                        LIVE INTELLIGENCE
                    </span>
                </div>

                <p class="mt-1 text-sm text-slate-400">
                    Global supply chain news and sentiment monitoring across 20 countries.
                </p>
            </div>

            {{-- FILTER --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">

                {{-- SEARCH --}}
                <input type="search"
                       wire:model.live.debounce.500ms="search"
                       placeholder="Search intelligence..."
                       class="min-w-[220px] rounded-xl border border-slate-700 bg-slate-950 px-4 py-2 text-sm text-white outline-none transition placeholder:text-slate-600 focus:border-violet-400">

                {{-- COUNTRY FILTER --}}
                <select wire:model.live="countryId"
                        class="rounded-xl border border-slate-700 bg-slate-950 px-4 py-2 text-sm text-white outline-none transition focus:border-cyan-400">
                    <option value="">All Countries (20 Negara)</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country['id'] }}">
                            {{ $country['name'] }} ({{ $country['iso3'] }})
                        </option>
                    @endforeach
                </select>

                {{-- SENTIMENT FILTER --}}
                <select wire:model.live="sentiment"
                        class="rounded-xl border border-slate-700 bg-slate-950 px-4 py-2 text-sm text-white outline-none transition focus:border-emerald-400">
                    <option value="">All Sentiments</option>
                    <option value="positive">Positive</option>
                    <option value="neutral">Neutral</option>
                    <option value="negative">Negative</option>
                </select>

                {{-- RESET FILTER --}}
                @if ($search !== '' || $countryId !== '' || $sentiment !== '')
                    <button type="button"
                            wire:click="resetFilters"
                            class="rounded-xl border border-slate-700 bg-slate-950 px-4 py-2 text-sm text-slate-400 transition hover:border-rose-500/40 hover:text-rose-400">
                        Reset
                    </button>
                @endif

            </div>

        </div>

        {{-- NEWS STATISTICS --}}
        <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">

            {{-- TOTAL --}}
            <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                <p class="text-xs uppercase tracking-wider text-slate-500">
                    Total Intelligence
                </p>
                <p class="mt-2 text-2xl font-bold text-violet-400">
                    {{ number_format($statistics['total']) }}
                </p>
            </div>

            {{-- POSITIVE --}}
            <div class="rounded-xl border border-emerald-500/10 bg-emerald-500/5 p-4">
                <p class="text-xs uppercase tracking-wider text-slate-500">
                    Positive
                </p>
                <p class="mt-2 text-2xl font-bold text-emerald-400">
                    {{ number_format($statistics['positive']) }}
                </p>
            </div>

            {{-- NEUTRAL --}}
            <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                <p class="text-xs uppercase tracking-wider text-slate-500">
                    Neutral
                </p>
                <p class="mt-2 text-2xl font-bold text-slate-300">
                    {{ number_format($statistics['neutral']) }}
                </p>
            </div>

            {{-- NEGATIVE --}}
            <div class="rounded-xl border border-rose-500/10 bg-rose-500/5 p-4">
                <p class="text-xs uppercase tracking-wider text-slate-500">
                    Negative
                </p>
                <p class="mt-2 text-2xl font-bold text-rose-400">
                    {{ number_format($statistics['negative']) }}
                </p>
            </div>

        </div>

        {{-- LOADING STATE --}}
        <div wire:loading.flex
             wire:target="search, sentiment, countryId, resetFilters, refreshNews"
             class="mt-6 items-center justify-center rounded-xl border border-slate-800 bg-slate-950/70 p-5">
            <svg class="mr-3 h-5 w-5 animate-spin text-violet-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span class="text-sm text-slate-400">Updating intelligence news...</span>
        </div>

        {{-- NEWS GRID --}}
        <div wire:loading.remove
             wire:target="search, sentiment, countryId, resetFilters, refreshNews"
             class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">

            @forelse ($news as $article)
                @php
                    $isExternalUrl = !empty($article->url) && str_starts_with($article->url, 'http');
                @endphp

                <article wire:key="news-{{ $article->id }}"
                         class="group flex h-full flex-col overflow-hidden rounded-xl border border-slate-800 bg-slate-950/70 transition duration-300 hover:border-violet-500/30">

                    @if ($article->image_url)
                        <div class="relative h-44 overflow-hidden bg-slate-900">
                            @if($isExternalUrl)
                                <a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer" class="block h-full w-full">
                                    <img src="{{ $article->image_url }}"
                                         alt="{{ $article->title }}"
                                         loading="lazy"
                                         class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                         onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?q=80&w=800&auto=format&fit=crop';" />
                                </a>
                            @else
                                <img src="{{ $article->image_url }}"
                                     alt="{{ $article->title }}"
                                     loading="lazy"
                                     class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                     onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?q=80&w=800&auto=format&fit=crop';" />
                            @endif

                            @if ($article->country)
                                <div class="absolute left-3 top-3 flex items-center gap-1.5 rounded-full border border-slate-800/80 bg-slate-950/80 px-2.5 py-1 backdrop-blur-md">
                                    @if ($article->country->iso2)
                                        <img src="https://flagcdn.com/w20/{{ strtolower($article->country->iso2) }}.png"
                                             alt="{{ $article->country->name }}"
                                             class="h-3 w-4 rounded-sm object-cover" />
                                    @endif
                                    <span class="text-[10px] font-semibold text-slate-300">
                                        {{ $article->country->name }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="flex flex-1 flex-col p-4">
                        <div class="flex items-center justify-between text-[11px] text-slate-500">
                            <span class="font-medium text-slate-400">
                                {{ $article->source ?? 'Global News' }}
                            </span>
                            <span>
                                {{ $article->published_at ? $article->published_at->diffForHumans() : 'Recent' }}
                            </span>
                        </div>

                        <h3 class="mt-2 text-base font-bold text-white transition group-hover:text-violet-300 line-clamp-2">
                            @if($isExternalUrl)
                                <a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer" class="hover:underline flex items-start gap-1">
                                    <span>{{ $article->title }}</span>
                                    <svg class="h-4 w-4 shrink-0 text-slate-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            @else
                                {{ $article->title }}
                            @endif
                        </h3>

                        <p class="mt-2 text-xs leading-relaxed text-slate-400 line-clamp-3">
                            {{ $article->description ?? $article->content }}
                        </p>

                        <div class="mt-auto pt-4 flex items-center justify-between border-t border-slate-900 text-xs">
                            <span class="rounded-md border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider
                                  {{ $article->sentiment === 'positive' ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400' : '' }}
                                  {{ $article->sentiment === 'negative' ? 'border-rose-500/20 bg-rose-500/10 text-rose-400' : '' }}
                                  {{ $article->sentiment === 'neutral' ? 'border-slate-700 bg-slate-800/50 text-slate-400' : '' }}">
                                {{ $article->sentiment ?? 'Neutral' }}
                            </span>

                            @if($isExternalUrl)
                                <a href="{{ $article->url }}" 
                                   target="_blank" 
                                   rel="noopener noreferrer" 
                                   class="inline-flex items-center gap-1.5 font-semibold text-cyan-400 transition hover:text-cyan-300">
                                    <span>Buka Website Berita</span>
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            @else
                                <button type="button"
                                        @click="openNews = {{ json_encode($article) }}"
                                        class="font-semibold text-violet-400 transition hover:text-violet-300">
                                    Baca Detail &rarr;
                                </button>
                            @endif
                        </div>
                    </div>

                </article>

            @empty

                <div class="col-span-full rounded-xl border border-slate-800 bg-slate-950/40 p-12 text-center">
                    <p class="text-sm text-slate-400">
                        Tidak ada berita intelligence yang ditemukan sesuai filter.
                    </p>
                </div>

            @endforelse

        </div>

        {{-- PAGINATION --}}
        @if ($news->hasPages())
            <div class="mt-6">
                {{ $news->links() }}
            </div>
        @endif

    </div>

    {{-- MODAL DETAIL BERITA --}}
    <template x-if="openNews">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm"
             @keydown.escape.window="openNews = null">
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-2xl"
                 @click.away="openNews = null">
                
                <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                    <span class="rounded-full border border-violet-500/20 bg-violet-500/10 px-3 py-1 text-xs font-semibold text-violet-400"
                          x-text="openNews.category ? openNews.category.toUpperCase() : 'INTELLIGENCE'"></span>
                    <button type="button" @click="openNews = null" class="text-slate-400 hover:text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4 max-h-[70vh] overflow-y-auto space-y-4 pr-1">
                    <h3 class="text-xl font-bold text-white" x-text="openNews.title"></h3>
                    
                    <div class="flex items-center gap-3 text-xs text-slate-400">
                        <span x-text="openNews.source"></span>
                        <span>&bull;</span>
                        <span x-text="openNews.published_at ? new Date(openNews.published_at).toLocaleString('id-ID') : 'Recent'"></span>
                    </div>

                    <p class="text-sm text-slate-300 leading-relaxed" x-text="openNews.content || openNews.description"></p>
                </div>

                <div class="mt-6 flex items-center justify-between border-t border-slate-800 pt-4">
                    <template x-if="openNews.url && openNews.url.startsWith('http')">
                        <a :href="openNews.url" 
                           target="_blank" 
                           rel="noopener noreferrer" 
                           class="inline-flex items-center gap-2 rounded-xl border border-cyan-500/30 bg-cyan-500/10 px-4 py-2 text-xs font-semibold text-cyan-300 hover:bg-cyan-500/20">
                            <span>🌐 Buka Website Berita Asli (External Source)</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </template>
                    <button type="button" @click="openNews = null" class="ml-auto rounded-xl border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    </template>

</div>