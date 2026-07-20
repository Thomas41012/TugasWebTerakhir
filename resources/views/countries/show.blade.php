<x-layouts.app>
    <div class="min-h-screen bg-slate-950 text-white">
        <div class="mx-auto max-w-[1600px] px-4 py-8 lg:px-8">

            {{-- HEADER --}}
            <section class="mb-8 flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
                <div class="flex items-center gap-5">
                    @if ($country->flag_url)
                        <img
                            src="{{ $country->flag_url }}"
                            alt="Flag of {{ $country->name }}"
                            class="h-20 w-28 rounded-xl border border-slate-700 object-cover"
                        >
                    @endif

                    <div>
                        <a
                            href="{{ route('dashboard') }}"
                            class="mb-3 inline-flex text-sm text-emerald-400 transition hover:text-emerald-300"
                        >
                            ← Back to Dashboard
                        </a>

                        <h1 class="text-3xl font-bold tracking-tight md:text-4xl">
                            {{ $country->name }}
                        </h1>

                        <p class="mt-2 text-slate-400">
                            {{ $country->official_name ?? $country->name }}
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-full border border-slate-700 bg-slate-900 px-3 py-1 text-xs text-slate-300">
                                {{ $country->iso2 }}
                            </span>

                            <span class="rounded-full border border-slate-700 bg-slate-900 px-3 py-1 text-xs text-slate-300">
                                {{ $country->iso3 }}
                            </span>

                            <span class="rounded-full border border-slate-700 bg-slate-900 px-3 py-1 text-xs text-slate-300">
                                {{ $country->region ?? 'Unknown Region' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row lg:items-center">
                    <button
                        id="sync-country-button"
                        type="button"
                        data-country-id="{{ $country->id }}"
                        class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-3 text-sm font-semibold text-emerald-400 transition hover:bg-emerald-500/20 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Sync Country Data
                    </button>

                    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-6 py-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">
                            Local Time
                        </p>

                        <p
                            id="country-detail-clock"
                            class="mt-2 font-mono text-xl font-semibold text-emerald-400"
                        >
                            --
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            {{ $country->timezone ?? 'UTC' }}
                        </p>
                    </div>
                </div>
            </section>

            {{-- SYNC RESULT --}}
            <section
                id="sync-result-container"
                class="mb-8 hidden rounded-2xl border border-slate-800 bg-slate-900/80 p-5"
            >
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="font-bold text-white">
                            Synchronization Result
                        </h2>

                        <p id="sync-summary" class="mt-1 text-sm text-slate-400">
                            Synchronizing country data...
                        </p>
                    </div>

                    <div
                        id="sync-overall-status"
                        class="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-3 py-1 text-xs font-semibold text-cyan-400"
                    >
                        PROCESSING
                    </div>
                </div>

                <div
                    id="sync-services"
                    class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7"
                ></div>
            </section>

            {{-- COUNTRY PROFILE --}}
            <section class="mb-8 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <div class="mb-5">
                    <h2 class="text-xl font-bold">
                        Country Profile
                    </h2>

                    <p class="mt-1 text-sm text-slate-400">
                        General country and supply chain information.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    @php
                        $profileStatistics = [
                            'Capital' => $country->capital ?? '-',
                            'Region' => $country->region ?? '-',
                            'Subregion' => $country->subregion ?? '-',
                            'Population' => number_format($country->population ?? 0),
                            'Currency' => $country->currency_code ?? '-',
                            'Strategic Ports' => number_format($country->ports->count()),
                        ];
                    @endphp

                    @foreach ($profileStatistics as $label => $value)
                        <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                            <p class="text-xs text-slate-500">
                                {{ $label }}
                            </p>

                            <p class="mt-2 font-semibold">
                                {{ $value }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- MAIN STATISTICS --}}
            <section class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                    <p class="text-sm text-slate-400">Risk Score</p>

                    <p class="mt-3 text-3xl font-bold text-rose-400">
                        {{ number_format($latestRisk?->total_score ?? 0, 2) }}
                    </p>

                    <p class="mt-2 text-xs text-slate-500">
                        {{ strtoupper($latestRisk?->risk_level ?? 'UNKNOWN') }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                    <p class="text-sm text-slate-400">Temperature</p>

                    <p class="mt-3 text-3xl font-bold text-cyan-400">
                        {{ number_format($latestWeather?->temperature ?? 0, 1) }}°C
                    </p>

                    <p class="mt-2 text-xs text-slate-500">
                        {{ $latestWeather?->weather_condition ?? 'No data' }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                    <p class="text-sm text-slate-400">Exchange Rate</p>

                    <p class="mt-3 text-2xl font-bold text-emerald-400">
                        {{ number_format($latestCurrency?->exchange_rate ?? 0, 2) }}
                    </p>

                    <p class="mt-2 text-xs text-slate-500">
                        USD / {{ $country->currency_code ?? '-' }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                    <p class="text-sm text-slate-400">GDP</p>

                    <p class="mt-3 text-2xl font-bold text-violet-400">
                        ${{ number_format(($latestEconomicIndicator?->gdp ?? 0) / 1000000000000, 2) }}T
                    </p>

                    <p class="mt-2 text-xs text-slate-500">
                        Latest World Bank data
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                    <p class="text-sm text-slate-400">Inflation</p>

                    <p class="mt-3 text-3xl font-bold text-orange-400">
                        {{ number_format($latestEconomicIndicator?->inflation_rate ?? 0, 2) }}%
                    </p>

                    <p class="mt-2 text-xs text-slate-500">
                        Consumer price index
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                    <p class="text-sm text-slate-400">Market Impact</p>

                    <p class="mt-3 text-3xl font-bold text-blue-400">
                        {{ number_format($latestMarketTrend?->market_impact_score ?? 0, 2) }}
                    </p>

                    <p class="mt-2 text-xs text-slate-500">
                        {{ strtoupper($latestMarketTrend?->trend_status ?? 'STABLE') }}
                    </p>
                </div>
            </section>

            {{-- WEATHER DETAIL --}}
            <section class="mb-8 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <div class="mb-5">
                    <h2 class="text-xl font-bold">
                        Current Weather Intelligence
                    </h2>

                    <p class="mt-1 text-sm text-slate-400">
                        Latest weather conditions affecting supply chain operations.
                    </p>
                </div>

                @php
                    $weatherStatistics = [
                        'Temperature' => number_format($latestWeather?->temperature ?? 0, 1) . '°C',
                        'Feels Like' => number_format($latestWeather?->feels_like ?? 0, 1) . '°C',
                        'Humidity' => number_format($latestWeather?->humidity ?? 0, 0) . '%',
                        'Precipitation' => number_format($latestWeather?->precipitation ?? 0, 1) . ' mm',
                        'Rain' => number_format($latestWeather?->rain ?? 0, 1) . ' mm',
                        'Cloud Cover' => number_format($latestWeather?->cloud_cover ?? 0, 0) . '%',
                        'Pressure' => number_format($latestWeather?->pressure ?? 0, 1) . ' hPa',
                        'Wind Speed' => number_format($latestWeather?->wind_speed ?? 0, 1) . ' km/h',
                    ];
                @endphp

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                    @foreach ($weatherStatistics as $label => $value)
                        <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                            <p class="text-xs text-slate-500">
                                {{ $label }}
                            </p>

                            <p class="mt-2 font-semibold text-cyan-400">
                                {{ $value }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ECONOMIC CHART --}}
            <section class="mb-8 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h2 class="text-xl font-bold">
                    Economic Intelligence
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    GDP growth and inflation history.
                </p>

                <div class="mt-6 h-[400px]">
                    <canvas id="economic-chart"></canvas>
                </div>
            </section>

            {{-- WEATHER AND CURRENCY --}}
            <section class="mb-8 grid gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                    <h2 class="text-xl font-bold">
                        Weather Intelligence
                    </h2>

                    <p class="mt-1 text-sm text-slate-400">
                        Temperature and weather risk history.
                    </p>

                    <div class="mt-6 h-[350px]">
                        <canvas id="weather-chart"></canvas>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                    <h2 class="text-xl font-bold">
                        Currency Intelligence
                    </h2>

                    <p class="mt-1 text-sm text-slate-400">
                        Exchange rate history.
                    </p>

                    <div class="mt-6 h-[350px]">
                        <canvas id="currency-chart"></canvas>
                    </div>
                </div>
            </section>

            {{-- MARKET TREND --}}
            <section class="mb-8 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-bold">
                            Market Trend Analytics
                        </h2>

                        <p class="mt-1 text-sm text-slate-400">
                            Currency, inflation and market impact analysis.
                        </p>
                    </div>

                    <span class="rounded-full border border-violet-500/30 bg-violet-500/10 px-3 py-1 text-xs font-semibold text-violet-400">
                        {{ strtoupper($latestMarketTrend?->trend_status ?? 'STABLE') }}
                    </span>
                </div>

                <div class="mt-6 h-[400px]">
                    <canvas id="market-trend-chart"></canvas>
                </div>
            </section>

            {{-- RISK CHART --}}
            <section class="mb-8 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h2 class="text-xl font-bold">
                    Supply Chain Risk History
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Historical supply chain risk components and total score.
                </p>

                <div class="mt-6 h-[400px]">
                    <canvas id="risk-chart"></canvas>
                </div>
            </section>

            {{-- PORTS --}}
            <section class="mb-8 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h2 class="text-xl font-bold">
                    Strategic Ports
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Logistics infrastructure in {{ $country->name }}.
                </p>

                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($country->ports as $port)
                        <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-semibold">
                                        {{ $port->name }}
                                    </h3>

                                    <p class="mt-2 text-sm text-slate-400">
                                        {{ $port->city ?? $country->name }}
                                    </p>
                                </div>

                                <span class="rounded-full border border-slate-700 px-2 py-1 text-xs text-slate-400">
                                    {{ strtoupper($port->status ?? 'UNKNOWN') }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-2 text-sm">
                                <div class="flex justify-between gap-4">
                                    <span class="text-slate-500">UN/LOCODE</span>
                                    <span>{{ $port->unlocode ?? '-' }}</span>
                                </div>

                                <div class="flex justify-between gap-4">
                                    <span class="text-slate-500">Congestion</span>
                                    <span class="text-orange-400">
                                        {{ number_format($port->congestion_level ?? 0) }}%
                                    </span>
                                </div>

                                <div class="flex justify-between gap-4">
                                    <span class="text-slate-500">Risk Score</span>
                                    <span class="text-rose-400">
                                        {{ number_format($port->risk_score ?? 0, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">
                            No port data available.
                        </p>
                    @endforelse
                </div>
            </section>

            {{-- NEWS --}}
            <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6" x-data="{ openNews: null }">
                <h2 class="text-xl font-bold">
                    Intelligence News
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Latest supply chain intelligence for {{ $country->name }}.
                </p>

                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($country->news as $article)
                        <article class="flex flex-col rounded-xl border border-slate-800 bg-slate-950/70 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs text-cyan-400">
                                    {{ $article->source ?? 'Global News' }}
                                </span>

                                <span class="rounded-full border border-slate-700 px-2 py-1 text-xs text-slate-400">
                                    {{ ucfirst($article->sentiment ?? 'neutral') }}
                                </span>
                            </div>

                            <h3 class="mt-4 font-semibold leading-6 text-white">
                                {{ $article->title }}
                            </h3>

                            <p class="mt-3 line-clamp-3 flex-1 text-sm text-slate-400">
                                {{ $article->description ?? 'No description available.' }}
                            </p>

                            <div class="mt-4 flex items-center justify-between gap-4">
                                <p class="text-xs text-slate-600">
                                    {{ optional($article->published_at)->diffForHumans() }}
                                </p>

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
                                        class="text-xs font-medium text-emerald-400 transition hover:text-emerald-300"
                                    >
                                        Read Article →
                                    </button>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">
                            No intelligence news available.
                        </p>
                    @endforelse
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
                            <button @click="openNews = null" class="px-4 py-2 text-xs font-semibold bg-slate-850 hover:bg-slate-850 text-white rounded-lg border border-slate-700 transition-all">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const timezone = @json($country->timezone ?? 'UTC');

            const economicData = @json($economicChart ?? []);
            const weatherData = @json($weatherChart ?? []);
            const currencyData = @json($currencyChart ?? []);
            const riskData = @json($riskChart ?? []);
            const marketTrendData = @json($marketTrendChart ?? []);

            /*
            |--------------------------------------------------------------------------
            | COUNTRY CLOCK
            |--------------------------------------------------------------------------
            */

            const clockElement =
                document.getElementById('country-detail-clock');

            function updateCountryClock() {
                if (!clockElement) {
                    return;
                }

                try {
                    clockElement.textContent =
                        new Intl.DateTimeFormat(
                            'en-GB',
                            {
                                timeZone: timezone,
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                                hour12: false
                            }
                        ).format(new Date());
                } catch (error) {
                    clockElement.textContent = '--';
                }
            }

            updateCountryClock();

            setInterval(updateCountryClock, 1000);

            /*
            |--------------------------------------------------------------------------
            | CHART DEFAULTS
            |--------------------------------------------------------------------------
            */

            Chart.defaults.color = '#94a3b8';

            Chart.defaults.borderColor =
                'rgba(51, 65, 85, 0.5)';

            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,

                interaction: {
                    intersect: false,
                    mode: 'index'
                },

                plugins: {
                    legend: {
                        labels: {
                            usePointStyle: true
                        }
                    }
                }
            };

            /*
            |--------------------------------------------------------------------------
            | ECONOMIC CHART
            |--------------------------------------------------------------------------
            */

            const economicCanvas =
                document.getElementById('economic-chart');

            if (economicCanvas) {
                new Chart(economicCanvas, {
                    type: 'line',

                    data: {
                        labels:
                            economicData.map(
                                item => item.year
                            ),

                        datasets: [
                            {
                                label: 'GDP Growth (%)',

                                data:
                                    economicData.map(
                                        item =>
                                            Number(
                                                item.gdp_growth ?? 0
                                            )
                                    ),

                                borderColor: '#10b981',
                                tension: 0.4
                            },
                            {
                                label: 'Inflation (%)',

                                data:
                                    economicData.map(
                                        item =>
                                            Number(
                                                item.inflation_rate ?? 0
                                            )
                                    ),

                                borderColor: '#f97316',
                                tension: 0.4
                            }
                        ]
                    },

                    options: commonOptions
                });
            }

            /*
            |--------------------------------------------------------------------------
            | WEATHER CHART
            |--------------------------------------------------------------------------
            */

            const weatherCanvas =
                document.getElementById('weather-chart');

            if (weatherCanvas) {
                new Chart(weatherCanvas, {
                    type: 'line',

                    data: {
                        labels:
                            weatherData.map(
                                item => item.date
                            ),

                        datasets: [
                            {
                                label: 'Temperature °C',

                                data:
                                    weatherData.map(
                                        item =>
                                            Number(
                                                item.temperature ?? 0
                                            )
                                    ),

                                borderColor: '#06b6d4',
                                tension: 0.4
                            },
                            {
                                label: 'Weather Risk',

                                data:
                                    weatherData.map(
                                        item =>
                                            Number(
                                                item.weather_risk_score ?? 0
                                            )
                                    ),

                                borderColor: '#f43f5e',
                                tension: 0.4
                            }
                        ]
                    },

                    options: commonOptions
                });
            }

            /*
            |--------------------------------------------------------------------------
            | CURRENCY CHART
            |--------------------------------------------------------------------------
            */

            const currencyCanvas =
                document.getElementById('currency-chart');

            if (currencyCanvas) {
                new Chart(currencyCanvas, {
                    type: 'line',

                    data: {
                        labels:
                            currencyData.map(
                                item => item.date
                            ),

                        datasets: [
                            {
                                label:
                                    @json(
                                        'USD / '
                                        . ($country->currency_code ?? '-')
                                    ),

                                data:
                                    currencyData.map(
                                        item =>
                                            Number(
                                                item.exchange_rate ?? 0
                                            )
                                    ),

                                borderColor: '#10b981',
                                tension: 0.4
                            }
                        ]
                    },

                    options: commonOptions
                });
            }

            /*
            |--------------------------------------------------------------------------
            | MARKET TREND CHART
            |--------------------------------------------------------------------------
            */

            const marketTrendCanvas =
                document.getElementById('market-trend-chart');

            if (marketTrendCanvas) {
                new Chart(marketTrendCanvas, {
                    type: 'line',

                    data: {
                        labels:
                            marketTrendData.map(
                                item => item.date
                            ),

                        datasets: [
                            {
                                label: 'Exchange Rate Change',

                                data:
                                    marketTrendData.map(
                                        item =>
                                            Number(
                                                item.exchange_rate_change ?? 0
                                            )
                                    ),

                                borderColor: '#06b6d4',
                                tension: 0.4
                            },
                            {
                                label: 'Inflation Change',

                                data:
                                    marketTrendData.map(
                                        item =>
                                            Number(
                                                item.inflation_change ?? 0
                                            )
                                    ),

                                borderColor: '#f97316',
                                tension: 0.4
                            },
                            {
                                label: 'Market Impact Score',

                                data:
                                    marketTrendData.map(
                                        item =>
                                            Number(
                                                item.market_impact_score ?? 0
                                            )
                                    ),

                                borderColor: '#8b5cf6',
                                tension: 0.4
                            }
                        ]
                    },

                    options: commonOptions
                });
            }

            /*
            |--------------------------------------------------------------------------
            | RISK CHART
            |--------------------------------------------------------------------------
            */

            const riskCanvas =
                document.getElementById('risk-chart');

            if (riskCanvas) {
                new Chart(riskCanvas, {
                    type: 'line',

                    data: {
                        labels:
                            riskData.map(
                                item => item.date
                            ),

                        datasets: [
                            {
                                label: 'Weather',

                                data:
                                    riskData.map(
                                        item =>
                                            Number(item.weather_score ?? 0)
                                    ),

                                borderColor: '#06b6d4',
                                tension: 0.4
                            },
                            {
                                label: 'Inflation',

                                data:
                                    riskData.map(
                                        item =>
                                            Number(item.inflation_score ?? 0)
                                    ),

                                borderColor: '#f97316',
                                tension: 0.4
                            },
                            {
                                label: 'Currency',

                                data:
                                    riskData.map(
                                        item =>
                                            Number(item.currency_score ?? 0)
                                    ),

                                borderColor: '#10b981',
                                tension: 0.4
                            },
                            {
                                label: 'Political',

                                data:
                                    riskData.map(
                                        item =>
                                            Number(item.political_score ?? 0)
                                    ),

                                borderColor: '#8b5cf6',
                                tension: 0.4
                            },
                            {
                                label: 'Port',

                                data:
                                    riskData.map(
                                        item =>
                                            Number(item.port_score ?? 0)
                                    ),

                                borderColor: '#eab308',
                                tension: 0.4
                            },
                            {
                                label: 'Total Risk',

                                data:
                                    riskData.map(
                                        item =>
                                            Number(item.total_score ?? 0)
                                    ),

                                borderColor: '#f43f5e',
                                borderWidth: 3,
                                tension: 0.4
                            }
                        ]
                    },

                    options: {
                        ...commonOptions,

                        scales: {
                            y: {
                                beginAtZero: true,
                                suggestedMax: 100
                            }
                        }
                    }
                });
            }

            /*
            |--------------------------------------------------------------------------
            | SYNC COUNTRY
            |--------------------------------------------------------------------------
            */

            const syncButton =
                document.getElementById('sync-country-button');

            const syncContainer =
                document.getElementById('sync-result-container');

            const syncSummary =
                document.getElementById('sync-summary');

            const syncOverallStatus =
                document.getElementById('sync-overall-status');

            const syncServices =
                document.getElementById('sync-services');

            const serviceNames = {
                profile: 'Profile',
                weather: 'Weather',
                currency: 'Currency',
                market: 'Market',
                market_trend: 'Market Trend',
                news: 'News',
                risk: 'Risk'
            };

            function createServiceCard(
                label,
                status,
                message,
                success
            ) {
                const card =
                    document.createElement('div');

                card.className =
                    success === null
                        ? 'rounded-xl border border-slate-800 bg-slate-950/70 p-4'
                        : success
                            ? 'rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-4'
                            : 'rounded-xl border border-rose-500/20 bg-rose-500/5 p-4';

                const labelElement =
                    document.createElement('p');

                labelElement.className =
                    'text-xs uppercase tracking-wider text-slate-500';

                labelElement.textContent = label;

                const statusElement =
                    document.createElement('p');

                statusElement.className =
                    success === null
                        ? 'mt-2 text-sm font-bold text-cyan-400'
                        : success
                            ? 'mt-2 text-sm font-bold text-emerald-400'
                            : 'mt-2 text-sm font-bold text-rose-400';

                statusElement.textContent = status;

                card.appendChild(labelElement);
                card.appendChild(statusElement);

                if (message) {
                    const messageElement =
                        document.createElement('p');

                    messageElement.className =
                        'mt-2 break-words text-xs text-slate-500';

                    messageElement.textContent = message;

                    card.appendChild(messageElement);
                }

                return card;
            }

            function renderProcessingServices() {
                syncServices.replaceChildren();

                Object.values(serviceNames)
                    .forEach(label => {
                        syncServices.appendChild(
                            createServiceCard(
                                label,
                                'PROCESSING',
                                null,
                                null
                            )
                        );
                    });
            }

            function renderServiceResults(services) {
                syncServices.replaceChildren();

                Object.entries(serviceNames)
                    .forEach(([key, label]) => {
                        const service =
                            services?.[key] ?? {
                                success: false,
                                message: 'No result returned.'
                            };

                        syncServices.appendChild(
                            createServiceCard(
                                label,
                                service.success
                                    ? 'SUCCESS'
                                    : 'FAILED',
                                service.message ?? '-',
                                service.success === true
                            )
                        );
                    });
            }

            if (syncButton) {
                syncButton.addEventListener(
                    'click',

                    async function () {
                        const countryId =
                            syncButton.dataset.countryId;

                        const originalText =
                            syncButton.textContent.trim();

                        syncButton.disabled = true;

                        syncButton.textContent =
                            'Synchronizing...';

                        syncContainer.classList.remove('hidden');

                        syncSummary.textContent =
                            'Synchronizing all country intelligence services...';

                        syncOverallStatus.className =
                            'rounded-full border border-cyan-500/30 bg-cyan-500/10 px-3 py-1 text-xs font-semibold text-cyan-400';

                        syncOverallStatus.textContent =
                            'PROCESSING';

                        renderProcessingServices();

                        try {
                            const response =
                                await fetch(
                                    `/api/v1/countries/${countryId}/sync`,
                                    {
                                        method: 'POST',

                                        headers: {
                                            'Accept':
                                                'application/json',

                                            'Content-Type':
                                                'application/json',

                                            'X-CSRF-TOKEN':
                                                document
                                                    .querySelector(
                                                        'meta[name="csrf-token"]'
                                                    )
                                                    ?.getAttribute('content')
                                                ?? ''
                                        }
                                    }
                                );

                            const result =
                                await response.json();

                            const services =
                                result?.data?.services ?? {};

                            const summary =
                                result?.data?.summary ?? {
                                    successful: 0,
                                    failed: 0,
                                    total: 0
                                };

                            renderServiceResults(services);

                            if (
                                result.success === true
                                && summary.failed === 0
                            ) {
                                syncOverallStatus.className =
                                    'rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-400';

                                syncOverallStatus.textContent =
                                    'SUCCESS';

                                syncSummary.textContent =
                                    `${summary.successful} of ${summary.total} services synchronized successfully. Reloading latest data...`;

                                setTimeout(
                                    () => window.location.reload(),
                                    1500
                                );

                                return;
                            }

                            if (
                                summary.successful > 0
                                && summary.failed > 0
                            ) {
                                syncOverallStatus.className =
                                    'rounded-full border border-orange-500/30 bg-orange-500/10 px-3 py-1 text-xs font-semibold text-orange-400';

                                syncOverallStatus.textContent =
                                    'PARTIAL SUCCESS';

                                syncSummary.textContent =
                                    `${summary.successful} services succeeded and ${summary.failed} services failed.`;
                            } else {
                                syncOverallStatus.className =
                                    'rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-400';

                                syncOverallStatus.textContent =
                                    'FAILED';

                                syncSummary.textContent =
                                    result.message
                                    ?? 'Synchronization failed.';
                            }
                        } catch (error) {
                            console.error(
                                'Country synchronization error:',
                                error
                            );

                            syncOverallStatus.className =
                                'rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-400';

                            syncOverallStatus.textContent =
                                'ERROR';

                            syncSummary.textContent =
                                error.message
                                ?? 'Unable to synchronize country data.';
                        } finally {
                            syncButton.disabled = false;

                            syncButton.textContent =
                                originalText;
                        }
                    }
                );
            }
        });
    </script>
</x-layouts.app>