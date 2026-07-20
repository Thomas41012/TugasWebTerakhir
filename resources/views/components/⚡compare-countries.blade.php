<?php

use App\Models\Country;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public array $countries = [];

    public array $selectedCountries = [];
    public bool $loading = false;

    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
{
    $this->loading = true;

    $this->loadCountries();

    $this->selectedCountries = collect($this->countries)
        ->take(2)
        ->pluck('id')
        ->map(fn ($id) => (string) $id)
        ->values()
        ->toArray();

    $this->loading = false;
}
    /*
    |--------------------------------------------------------------------------
    | Global Sync Listener
    |--------------------------------------------------------------------------
    */

    #[On('global-sync-completed')]
    public function handleGlobalSyncCompleted(): void
    {
        $this->refreshComparison();
    }

    /*
    |--------------------------------------------------------------------------
    | Selected Countries Updated
    |--------------------------------------------------------------------------
    */

    public function updatedSelectedCountries(): void
    {
        $selectedCountries = array_filter(
            $this->selectedCountries,
            fn ($id): bool =>
                $id !== null
                && $id !== ''
        );

        $selectedCountries = array_map(
            'strval',
            $selectedCountries
        );

        $selectedCountries = array_unique(
            $selectedCountries
        );

        $this->selectedCountries = array_values(
            array_slice(
                $selectedCountries,
                0,
                4
            )
        );

        unset($this->comparisonData);

        $this->dispatchComparisonData();
    }

    /*
    |--------------------------------------------------------------------------
    | Refresh Comparison
    |--------------------------------------------------------------------------
    */

   public function refreshComparison(): void
{
    $this->loading = true;

    $this->loadCountries();

    $availableCountryIds = collect($this->countries)
        ->pluck('id')
        ->map(fn ($id) => (string) $id)
        ->toArray();

    $this->selectedCountries = array_values(
        array_filter(
            $this->selectedCountries,
            fn ($id) => in_array(
                (string) $id,
                $availableCountryIds,
                true
            )
        )
    );

    if (
        empty($this->selectedCountries)
        && ! empty($this->countries)
    ) {
        $this->selectedCountries = collect($this->countries)
            ->take(2)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->toArray();
    }

    unset($this->comparisonData);

    $this->dispatchComparisonData();

    $this->loading = false;
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
    ->map(fn (Country $country) => [
        'id' => (string) $country->id,
        'name' => $country->name,
        'iso2' => $country->iso2,
        'iso3' => $country->iso3,
    ])
    ->values()
    ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Comparison Data
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function comparisonData(): array
    {
        if (empty($this->selectedCountries)) {
            return [];
        }

        $selectedCountryIds = collect(
            $this->selectedCountries
        )
            ->map(
                fn ($id): int => (int) $id
            )
            ->filter(
                fn (int $id): bool => $id > 0
            )
            ->unique()
            ->take(4)
            ->values()
            ->toArray();

        if (empty($selectedCountryIds)) {
            return [];
        }

        return Country::query()
            ->where('is_active', true)
            ->whereIn(
                'id',
                $selectedCountryIds
            )
           ->with([
    'latestRiskScore',
    'latestWeatherRecord',
    'latestCurrencyRate',
    'latestMarketTrend',
    'economicIndicators' => fn ($q)
        => $q->latest('year')->limit(1),
])
            ->get()
            ->sortBy(
                function (Country $country) use (
                    $selectedCountryIds
                ): int {
                    $position = array_search(
                        $country->id,
                        $selectedCountryIds,
                        true
                    );

                    return $position === false
                        ? PHP_INT_MAX
                        : $position;
                }
            )
            ->map(
                function (Country $country): array {
                    $risk =
                        $country->latestRiskScore;

                    $weather =
                        $country->latestWeatherRecord;

                    $currency =
                        $country->latestCurrencyRate;

                    $marketTrend =
                        $country->latestMarketTrend;

                    $economic =
                        $country->economicIndicators
                            ->sortByDesc('year')
                            ->first();

                    return [
                        /*
                        |--------------------------------------------------------------------------
                        | Country
                        |--------------------------------------------------------------------------
                        */

                        'id' =>
                            $country->id,

                        'name' =>
                            $country->name,

                        'iso2' =>
                            $country->iso2,

                        'iso3' =>
                            $country->iso3,

                        'population' => (int) (
                            $country->population
                            ?? $economic?->population
                            ?? 0
                        ),

                        /*
                        |--------------------------------------------------------------------------
                        | Risk Scores
                        |--------------------------------------------------------------------------
                        */

                        'risk_score' => (float) (
                            $risk?->total_score
                            ?? 0
                        ),

                        'weather_score' => (float) (
                            $risk?->weather_score
                            ?? 0
                        ),

                        'inflation_score' => (float) (
                            $risk?->inflation_score
                            ?? 0
                        ),

                        'currency_score' => (float) (
                            $risk?->currency_score
                            ?? 0
                        ),

                        'political_score' => (float) (
                            $risk?->political_score
                            ?? 0
                        ),

                        'port_score' => (float) (
                            $risk?->port_score
                            ?? 0
                        ),

                        'risk_level' => strtolower(
                            (string) (
                                $risk?->risk_level
                                ?? 'low'
                            )
                        ),

                        /*
                        |--------------------------------------------------------------------------
                        | Weather
                        |--------------------------------------------------------------------------
                        */

                        'temperature' => (float) (
                            $weather?->temperature
                            ?? 0
                        ),

                        'humidity' => (float) (
                            $weather?->humidity
                            ?? 0
                        ),

                        'wind_speed' => (float) (
                            $weather?->wind_speed
                            ?? 0
                        ),

                        'weather_condition' => (
                            $weather?->weather_condition
                            ?? 'Unknown'
                        ),

                        /*
                        |--------------------------------------------------------------------------
                        | Economic Data
                        |--------------------------------------------------------------------------
                        */

                        'inflation_rate' => (float) (
                            $economic?->inflation_rate
                            ?? $marketTrend?->inflation_rate
                            ?? 0
                        ),

                        'gdp' => (float) (
                            $economic?->gdp
                            ?? 0
                        ),

                        'gdp_growth' => (float) (
                            $economic?->gdp_growth
                            ?? 0
                        ),

                        'exports' => (float) (
                            $economic?->exports
                            ?? 0
                        ),

                        'imports' => (float) (
                            $economic?->imports
                            ?? 0
                        ),

                        /*
                        |--------------------------------------------------------------------------
                        | Currency
                        |--------------------------------------------------------------------------
                        */

                        'exchange_rate' => (float) (
                            $currency?->exchange_rate
                            ?? 0
                        ),

                        'percentage_change' => (float) (
                            $currency?->percentage_change
                            ?? 0
                        ),

                        /*
                        |--------------------------------------------------------------------------
                        | Market Trend
                        |--------------------------------------------------------------------------
                        */

                        'market_impact_score' => (float) (
                            $marketTrend?->market_impact_score
                            ?? 0
                        ),

                        'trend_status' => strtolower(
                            (string) (
                                $marketTrend?->trend_status
                                ?? 'stable'
                            )
                        ),
                    ];
                }
            )
            ->values()
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Dispatch Comparison Data
    |--------------------------------------------------------------------------
    */

    private function dispatchComparisonData(): void
    {
        unset($this->comparisonData);

        $this->dispatch(
            'comparison-updated',
            comparisonData: $this->comparisonData
        );
    }
};
?>

<div wire:poll.300s="refreshComparison">

    <div
        class="rounded-2xl border border-slate-800
               bg-slate-900/80 p-5"
    >

        {{-- Header --}}

        <div
            class="flex flex-col justify-between gap-4
                   lg:flex-row lg:items-center"
        >

            <div>

                <h2 class="text-xl font-bold text-white">
                    Compare Mode
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Compare risk, market, economic, currency
                    and weather intelligence for up to 4 countries.
                </p>

            </div>

            <div
                class="rounded-xl border border-violet-500/20
                       bg-violet-500/10 px-4 py-2"
            >

                <span
                    class="text-xs font-medium text-violet-400"
                >
                    {{ count($selectedCountries) }} / 4 SELECTED
                </span>

            </div>

        </div>

        {{-- Country Selector --}}

        <div class="mt-6 flex flex-wrap gap-2">

            @foreach ($countries as $country)

                @php

                    $isSelected = in_array(
                        (string) $country['id'],
                        $selectedCountries,
                        true
                    );

                    $selectionLimitReached =
                        count($selectedCountries) >= 4
                        && ! $isSelected;

                @endphp

                <label
                    wire:key="compare-selector-{{ $country['id'] }}"
                    @class([
                        'rounded-lg border px-3 py-2 text-sm transition',

                        'cursor-pointer border-violet-400 bg-violet-500/10 text-violet-300'
                            => $isSelected,

                        'cursor-pointer border-slate-700 bg-slate-950 text-slate-300 hover:border-violet-400'
                            => ! $isSelected
                                && ! $selectionLimitReached,

                        'cursor-not-allowed border-slate-800 bg-slate-950/50 text-slate-600 opacity-60'
                            => $selectionLimitReached,
                    ])
                >

                    <input
                        type="checkbox"
                        wire:model.live="selectedCountries"
                        value="{{ $country['id'] }}"
                        @disabled($selectionLimitReached)
                        class="mr-2"
                    >

                    {{ $country['name'] }}

                </label>

            @endforeach

        </div>

        {{-- Loading --}}

        <div
            wire:loading.flex
            wire:target="selectedCountries,refreshComparison"
            class="mt-6 items-center justify-center
                   rounded-xl border border-slate-800
                   bg-slate-950/70 p-4"
        >

            <svg
                class="mr-2 h-5 w-5 animate-spin text-violet-400"
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
                Updating country comparison...
            </span>

        </div>

        {{-- Empty State --}}

        @if (empty($this->comparisonData))

            <div
                wire:loading.remove
                wire:target="selectedCountries,refreshComparison"
                class="mt-6 rounded-xl border
                       border-dashed border-slate-700
                       bg-slate-950/50
                       p-10 text-center"
            >

                <p class="text-sm text-slate-400">
                    Select at least one country
                    to start comparison.
                </p>

            </div>

        @else

            {{-- ApexCharts --}}

            <div
                wire:loading.remove
                wire:target="selectedCountries,refreshComparison"
                class="mt-6 rounded-xl
                       border border-slate-800
                       bg-slate-950/50 p-4"
            >

                <div class="mb-4">

                    <h3 class="font-semibold text-white">
                        Country Risk Comparison
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Comparison of risk components
                        across selected countries.
                    </p>

                </div>

                <div
                    wire:ignore
                    id="country-comparison-chart"
                    class="min-h-[450px]"
                ></div>

            </div>

            {{-- Comparison Cards --}}

            <div
                class="mt-6 grid gap-4
                       md:grid-cols-2
                       xl:grid-cols-4"
            >

                @foreach ($this->comparisonData as $country)

                    <article
                        wire:key="comparison-card-{{ $country['id'] }}"
                        class="rounded-xl border
                               border-slate-800
                               bg-slate-950/70 p-4
                               transition
                               hover:border-violet-500/30"
                    >

                        {{-- Country Header --}}

                        <div
                            class="flex items-center
                                   justify-between gap-3"
                        >

                            <div>

                                <h3 class="font-semibold text-white">
                                    {{ $country['name'] }}
                                </h3>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $country['iso3'] }}
                                </p>

                            </div>

                            <span
                                @class([
                                    'rounded-full px-2 py-1 text-xs font-semibold',

                                    'bg-rose-500/10 text-rose-400'
                                        => in_array(
                                            $country['risk_level'],
                                            ['high', 'critical'],
                                            true
                                        ),

                                    'bg-orange-500/10 text-orange-400'
                                        => $country['risk_level'] === 'medium',

                                    'bg-emerald-500/10 text-emerald-400'
                                        => ! in_array(
                                            $country['risk_level'],
                                            [
                                                'medium',
                                                'high',
                                                'critical',
                                            ],
                                            true
                                        ),
                                ])
                            >
                                {{ strtoupper($country['risk_level']) }}
                            </span>

                        </div>

                        {{-- Market Trend --}}

                        <div
                            class="mt-4 flex items-center
                                   justify-between rounded-lg
                                   border border-slate-800
                                   bg-slate-900/60 p-3"
                        >

                            <div>

                                <p class="text-xs text-slate-500">
                                    Market Trend
                                </p>

                                <p
                                    @class([
                                        'mt-1 text-sm font-semibold',

                                        'text-emerald-400'
                                            => $country['trend_status']
                                                === 'positive',

                                        'text-rose-400'
                                            => $country['trend_status']
                                                === 'negative',

                                        'text-slate-300'
                                            => ! in_array(
                                                $country['trend_status'],
                                                [
                                                    'positive',
                                                    'negative',
                                                ],
                                                true
                                            ),
                                    ])
                                >
                                    {{
                                        strtoupper(
                                            $country['trend_status']
                                        )
                                    }}
                                </p>

                            </div>

                            <div class="text-right">

                                <p class="text-xs text-slate-500">
                                    Market Impact
                                </p>

                                <p
                                    class="mt-1 text-sm
                                           font-semibold text-orange-400"
                                >
                                    {{
                                        number_format(
                                            $country[
                                                'market_impact_score'
                                            ],
                                            2
                                        )
                                    }}
                                </p>

                            </div>

                        </div>

                        {{-- Risk Information --}}

                        <div
                            class="mt-4 space-y-3
                                   border-t border-slate-800
                                   pt-4"
                        >

                            @php

                                $riskItems = [
                                    'Total Risk' =>
                                        $country['risk_score'],

                                    'Weather Risk' =>
                                        $country['weather_score'],

                                    'Inflation Risk' =>
                                        $country['inflation_score'],

                                    'Currency Risk' =>
                                        $country['currency_score'],

                                    'Political Risk' =>
                                        $country['political_score'],

                                    'Port Risk' =>
                                        $country['port_score'],
                                ];

                            @endphp

                            @foreach ($riskItems as $label => $value)

                                <div
                                    class="flex justify-between text-sm"
                                >

                                    <span class="text-slate-500">
                                        {{ $label }}
                                    </span>

                                    <span class="font-medium text-white">
                                        {{
                                            number_format(
                                                $value,
                                                2
                                            )
                                        }}
                                    </span>

                                </div>

                            @endforeach

                        </div>

                        {{-- Intelligence Information --}}

                        <div
                            class="mt-4 space-y-3
                                   border-t border-slate-800
                                   pt-4"
                        >

                            <div class="flex justify-between text-sm">

                                <span class="text-slate-500">
                                    Temperature
                                </span>

                                <span class="font-medium text-cyan-400">
                                    {{
                                        number_format(
                                            $country['temperature'],
                                            1
                                        )
                                    }}°C
                                </span>

                            </div>

                            <div class="flex justify-between text-sm">

                                <span class="text-slate-500">
                                    Humidity
                                </span>

                                <span class="font-medium text-white">
                                    {{
                                        number_format(
                                            $country['humidity'],
                                            1
                                        )
                                    }}%
                                </span>

                            </div>

                            <div class="flex justify-between text-sm">

                                <span class="text-slate-500">
                                    Inflation
                                </span>

                                <span class="font-medium text-orange-400">
                                    {{
                                        number_format(
                                            $country['inflation_rate'],
                                            2
                                        )
                                    }}%
                                </span>

                            </div>

                            <div class="flex justify-between text-sm">

                                <span class="text-slate-500">
                                    GDP Growth
                                </span>

                                <span class="font-medium text-violet-400">
                                    {{
                                        number_format(
                                            $country['gdp_growth'],
                                            2
                                        )
                                    }}%
                                </span>

                            </div>

                            <div class="flex justify-between text-sm">

                                <span class="text-slate-500">
                                    Exchange Rate
                                </span>

                                <span class="font-medium text-emerald-400">
                                    {{
                                        number_format(
                                            $country['exchange_rate'],
                                            2
                                        )
                                    }}
                                </span>

                            </div>

                            <div class="flex justify-between text-sm">

                                <span class="text-slate-500">
                                    Currency Change
                                </span>

                                <span
                                    @class([
                                        'font-medium',

                                        'text-emerald-400'
                                            => $country[
                                                'percentage_change'
                                            ] > 0,

                                        'text-rose-400'
                                            => $country[
                                                'percentage_change'
                                            ] < 0,

                                        'text-slate-400'
                                            => $country[
                                                'percentage_change'
                                            ] == 0,
                                    ])
                                >

                                    @if (
                                        $country[
                                            'percentage_change'
                                        ] > 0
                                    )
                                        +
                                    @endif

                                    {{
                                        number_format(
                                            $country[
                                                'percentage_change'
                                            ],
                                            2
                                        )
                                    }}%

                                </span>

                            </div>

                            <div class="flex justify-between text-sm">

                                <span class="text-slate-500">
                                    Population
                                </span>

                                <span class="font-medium text-white">
                                    {{
                                        number_format(
                                            $country['population']
                                        )
                                    }}
                                </span>

                            </div>

                        </div>

                    </article>

                @endforeach

            </div>

        @endif

    </div>

    @script

        <script>

            let countryComparisonChart = null;

            function renderCountryComparisonChart(
                comparisonData
            ) {

                if (
                    typeof ApexCharts === 'undefined'
                ) {
                    console.error(
                        'ApexCharts is not loaded.'
                    );

                    return;
                }

                const chartElement =
                    document.querySelector(
                        '#country-comparison-chart'
                    );

                if (! chartElement) {
                    return;
                }

                if (countryComparisonChart) {

                    countryComparisonChart.destroy();

                    countryComparisonChart = null;

                }

                const categories = [
                    'Weather',
                    'Inflation',
                    'Currency',
                    'Political',
                    'Port',
                    'Market Impact',
                ];

                const series = (
                    comparisonData ?? []
                ).map(
                    function (country) {

                        return {
                            name:
                                country.name,

                            data: [
                                Number(
                                    country.weather_score
                                    ?? 0
                                ),

                                Number(
                                    country.inflation_score
                                    ?? 0
                                ),

                                Number(
                                    country.currency_score
                                    ?? 0
                                ),

                                Number(
                                    country.political_score
                                    ?? 0
                                ),

                                Number(
                                    country.port_score
                                    ?? 0
                                ),

                                Number(
                                    country.market_impact_score
                                    ?? 0
                                ),
                            ],
                        };

                    }
                );

                countryComparisonChart =
                    new ApexCharts(
                        chartElement,
                        {
                            chart: {
                                type: 'radar',

                                height: 450,

                                background:
                                    'transparent',

                                toolbar: {
                                    show: true,
                                },
                            },

                            series: series,

                            xaxis: {
                                categories:
                                    categories,

                                labels: {
                                    style: {
                                        colors: [
                                            '#94a3b8',
                                            '#94a3b8',
                                            '#94a3b8',
                                            '#94a3b8',
                                            '#94a3b8',
                                            '#94a3b8',
                                        ],
                                    },
                                },
                            },

                            yaxis: {
                                min: 0,

                                max: 100,

                                tickAmount: 5,

                                labels: {
                                    style: {
                                        colors:
                                            '#94a3b8',
                                    },
                                },
                            },

                            stroke: {
                                width: 2,
                            },

                            fill: {
                                opacity: 0.15,
                            },

                            markers: {
                                size: 4,
                            },

                            dataLabels: {
                                enabled: false,
                            },

                            legend: {
                                position: 'bottom',

                                labels: {
                                    colors:
                                        '#cbd5e1',
                                },
                            },

                            grid: {
                                borderColor:
                                    '#1e293b',
                            },

                            tooltip: {
                                theme: 'dark',
                            },

                            theme: {
                                mode: 'dark',
                            },
                        }
                    );

                countryComparisonChart.render();

            }

            /*
            |--------------------------------------------------------------------------
            | Initial Render
            |--------------------------------------------------------------------------
            */

            renderCountryComparisonChart(
                @js($this->comparisonData)
            );

            /*
            |--------------------------------------------------------------------------
            | Livewire Update
            |--------------------------------------------------------------------------
            */

            $wire.on(
                'comparison-updated',
                function (event) {

                    const comparisonData =
                        event.comparisonData
                        ?? event[0]?.comparisonData
                        ?? [];

                    setTimeout(
                        function () {

                            renderCountryComparisonChart(
                                comparisonData
                            );

                        },
                        100
                    );

                }
            );

        </script>

    @endscript

</div>