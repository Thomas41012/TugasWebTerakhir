<?php

use App\Models\Country;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public array $countries = [];

    public function mount(): void
    {
        $this->loadCountries();
    }

    #[On('global-sync-completed')]
    public function handleGlobalSyncCompleted(): void
    {
        $this->refreshCountries();
    }

    public function refreshCountries(): void
    {
        $this->loadCountries();

        $this->dispatch(
            'country-statistics-updated',
            countries: $this->countries
        );
    }

    private function loadCountries(): void
    {
        $this->countries = Country::query()
            ->where('is_active', true)
            ->with([
                'latestRiskScore',
                'latestMarketTrend',
                'latestWeatherRecord',
                'latestCurrencyRate',
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Country $country): array {
                return [
                    'id' => $country->id,

                    'name' => $country->name,

                    'iso2' => $country->iso2,

                    'iso3' => $country->iso3,

                    'capital' => $country->capital,

                    'region' => $country->region,

                    'population' => (int) (
                        $country->population ?? 0
                    ),

                    'currency_code' => (
                        $country->currency_code ?? '-'
                    ),

                    'flag_url' => $country->flag_url,

                    /*
                    |--------------------------------------------------------------------------
                    | Country Timezone
                    |--------------------------------------------------------------------------
                    */

                    'timezone' => (
                        $country->timezone ?? 'UTC'
                    ),

                    /*
                    |--------------------------------------------------------------------------
                    | Risk Data
                    |--------------------------------------------------------------------------
                    */

                    'risk_score' => (float) (
                        $country->latestRiskScore?->total_score ?? 0
                    ),

                    'risk_level' => (
                        $country->latestRiskScore?->risk_level ?? 'low'
                    ),

                    /*
                    |--------------------------------------------------------------------------
                    | Weather Data
                    |--------------------------------------------------------------------------
                    */

                    'temperature' => (float) (
                        $country->latestWeatherRecord?->temperature ?? 0
                    ),

                    'weather_condition' => (
                        $country->latestWeatherRecord?->weather_condition
                        ?? 'Unknown'
                    ),

                    /*
                    |--------------------------------------------------------------------------
                    | Currency Data
                    |--------------------------------------------------------------------------
                    */

                    'exchange_rate' => (float) (
                        $country->latestCurrencyRate?->exchange_rate ?? 0
                    ),

                    'currency_change' => (float) (
                        $country->latestCurrencyRate?->percentage_change ?? 0
                    ),

                    /*
                    |--------------------------------------------------------------------------
                    | Market Data
                    |--------------------------------------------------------------------------
                    */

                    'inflation_rate' => (float) (
                        $country->latestMarketTrend?->inflation_rate ?? 0
                    ),

                    'market_impact_score' => (float) (
                        $country->latestMarketTrend?->market_impact_score ?? 0
                    ),

                    'trend_status' => (
                        $country->latestMarketTrend?->trend_status
                        ?? 'neutral'
                    ),
                ];
            })
            ->values()
            ->toArray();
    }
};
?>

<div wire:poll.300s="refreshCountries">

    <div
        class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5"
    >

        {{-- Header --}}

        <div
            class="mb-5 flex flex-col justify-between gap-4 lg:flex-row lg:items-center"
        >
            <div>
                <h2 class="text-xl font-bold text-white">
                    Country Intelligence
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Current economic, weather, market, local time and risk information.
                </p>
            </div>

            <div
                class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-2"
            >
                <span class="text-xs font-medium text-emerald-400">
                    {{ count($countries) }} COUNTRIES MONITORED
                </span>
            </div>
        </div>

        {{-- Countries Grid --}}

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">

            @forelse ($countries as $country)

                <article
                    wire:key="country-statistic-{{ $country['id'] }}"
                    class="flex h-full flex-col rounded-xl border
                           border-slate-800 bg-slate-950/70 p-4
                           transition duration-300
                           hover:-translate-y-1
                           hover:border-emerald-500/30
                           hover:shadow-lg
                           hover:shadow-emerald-500/5"
                >

                    {{-- Country Header --}}

                    <div class="flex items-center justify-between gap-3">

                        <div>
                            <h3 class="font-semibold text-white">
                                {{ $country['name'] }}
                            </h3>

                            <p class="text-xs text-slate-500">
                                {{ $country['iso3'] }}

                                ·

                                {{ $country['region'] ?? 'Unknown' }}
                            </p>
                        </div>

                        @if (! empty($country['flag_url']))

                            <img
                                src="{{ $country['flag_url'] }}"
                                alt="{{ $country['name'] }}"
                                class="h-8 w-12 rounded object-cover"
                            >

                        @else

                            <div
                                class="flex h-8 w-12 items-center
                                       justify-center rounded bg-slate-800"
                            >
                                <span class="text-xs text-slate-500">
                                    {{ $country['iso2'] }}
                                </span>
                            </div>

                        @endif

                    </div>

                    {{-- Local Time --}}

                    <div
                        class="mt-4 rounded-lg border
                               border-violet-500/10
                               bg-violet-500/5 p-3"
                    >
                        <div class="flex items-center justify-between gap-3">

                            <div>
                                <p
                                    class="text-[10px] font-medium
                                           uppercase tracking-wider
                                           text-slate-500"
                                >
                                    Local Time
                                </p>

                                <p
                                    class="mt-1 text-[10px]
                                           text-slate-600"
                                >
                                    {{ $country['timezone'] }}
                                </p>
                            </div>

                            <span
                                data-country-timezone="{{ $country['timezone'] }}"
                                class="text-right font-mono text-xs
                                       font-semibold text-violet-400"
                            >
                                --:--:--
                            </span>

                        </div>
                    </div>

                    {{-- Risk Information --}}

                    <div class="mt-4">

                        <div class="flex items-center justify-between">

                            <span class="text-sm text-slate-500">
                                Risk Level
                            </span>

                            <span
                                @class([
                                    'rounded-full px-2 py-1 text-xs font-semibold',

                                    'bg-rose-500/10 text-rose-400' =>
                                        in_array(
                                            $country['risk_level'],
                                            ['high', 'critical'],
                                            true
                                        ),

                                    'bg-orange-500/10 text-orange-400' =>
                                        $country['risk_level'] === 'medium',

                                    'bg-emerald-500/10 text-emerald-400' =>
                                        ! in_array(
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

                        {{-- Risk Score --}}

                        <div class="mt-3">

                            <div class="flex items-end justify-between">

                                <span class="text-xs text-slate-500">
                                    Risk Score
                                </span>

                                <span class="text-xl font-bold text-rose-400">
                                    {{
                                        number_format(
                                            $country['risk_score'],
                                            2
                                        )
                                    }}
                                </span>

                            </div>

                            {{-- Risk Progress Bar --}}

                            <div
                                class="mt-2 h-1.5 overflow-hidden
                                       rounded-full bg-slate-800"
                            >
                                <div
                                    @class([
                                        'h-full rounded-full transition-all duration-500',

                                        'bg-rose-500' =>
                                            in_array(
                                                $country['risk_level'],
                                                ['high', 'critical'],
                                                true
                                            ),

                                        'bg-orange-500' =>
                                            $country['risk_level'] === 'medium',

                                        'bg-emerald-500' =>
                                            ! in_array(
                                                $country['risk_level'],
                                                [
                                                    'medium',
                                                    'high',
                                                    'critical',
                                                ],
                                                true
                                            ),
                                    ])
                                    style="width: {{
                                        min(
                                            100,
                                            max(
                                                0,
                                                $country['risk_score']
                                            )
                                        )
                                    }}%"
                                ></div>
                            </div>

                        </div>

                    </div>

                    {{-- Intelligence Information --}}

                    <div
                        class="mt-4 space-y-3 border-t
                               border-slate-800 pt-4"
                    >

                        {{-- Temperature --}}

                        <div class="flex justify-between gap-3 text-sm">

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

                        {{-- Weather --}}

                        <div class="flex justify-between gap-3 text-sm">

                            <span class="text-slate-500">
                                Weather
                            </span>

                            <span
                                class="max-w-[120px] truncate text-right
                                       font-medium text-slate-300"
                                title="{{ $country['weather_condition'] }}"
                            >
                                {{ $country['weather_condition'] }}
                            </span>

                        </div>

                        {{-- Currency --}}

                        <div class="flex justify-between gap-3 text-sm">

                            <span class="text-slate-500">
                                Currency
                            </span>

                            <span class="font-medium text-emerald-400">
                                {{ $country['currency_code'] }}
                            </span>

                        </div>

                        {{-- Exchange Rate --}}

                        <div class="flex justify-between gap-3 text-sm">

                            <span class="text-slate-500">
                                Exchange Rate
                            </span>

                            <span class="font-medium text-white">
                                {{
                                    number_format(
                                        $country['exchange_rate'],
                                        2
                                    )
                                }}
                            </span>

                        </div>

                        {{-- Currency Change --}}

                        <div class="flex justify-between gap-3 text-sm">

                            <span class="text-slate-500">
                                Currency Change
                            </span>

                            <span
                                @class([
                                    'font-medium',

                                    'text-emerald-400' =>
                                        $country['currency_change'] > 0,

                                    'text-rose-400' =>
                                        $country['currency_change'] < 0,

                                    'text-slate-400' =>
                                        $country['currency_change'] == 0,
                                ])
                            >

                                @if ($country['currency_change'] > 0)
                                    +
                                @endif

                                {{
                                    number_format(
                                        $country['currency_change'],
                                        2
                                    )
                                }}%

                            </span>

                        </div>

                        {{-- Inflation --}}

                        <div class="flex justify-between gap-3 text-sm">

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

                        {{-- Market Impact --}}

                        <div class="flex justify-between gap-3 text-sm">

                            <span class="text-slate-500">
                                Market Impact
                            </span>

                            <span
                                @class([
                                    'font-medium',

                                    'text-rose-400' =>
                                        $country['market_impact_score'] >= 60,

                                    'text-orange-400' =>
                                        $country['market_impact_score'] >= 30
                                        && $country['market_impact_score'] < 60,

                                    'text-emerald-400' =>
                                        $country['market_impact_score'] < 30,
                                ])
                            >
                                {{
                                    number_format(
                                        $country['market_impact_score'],
                                        2
                                    )
                                }}
                            </span>

                        </div>

                        {{-- Market Trend --}}

                        <div class="flex items-center justify-between gap-3 text-sm">

                            <span class="text-slate-500">
                                Market Trend
                            </span>

                            <span
                                @class([
                                    'rounded-full px-2 py-1 text-xs font-semibold',

                                    'bg-emerald-500/10 text-emerald-400' =>
                                        $country['trend_status'] === 'positive',

                                    'bg-rose-500/10 text-rose-400' =>
                                        $country['trend_status'] === 'negative',

                                    'bg-slate-500/10 text-slate-400' =>
                                        ! in_array(
                                            $country['trend_status'],
                                            [
                                                'positive',
                                                'negative',
                                            ],
                                            true
                                        ),
                                ])
                            >
                                {{ strtoupper($country['trend_status']) }}
                            </span>

                        </div>

                        {{-- Population --}}

                        <div class="flex justify-between gap-3 text-sm">

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

                        {{-- Capital --}}

                        <div class="flex justify-between gap-3 text-sm">

                            <span class="text-slate-500">
                                Capital
                            </span>

                            <span
                                class="max-w-[120px] truncate
                                       text-right font-medium
                                       text-slate-300"
                            >
                                {{ $country['capital'] ?? '-' }}
                            </span>

                        </div>

                    </div>

                    {{-- View Intelligence Button --}}

                    <div
                        class="mt-auto border-t border-slate-800 pt-4"
                    >
                        <a
                            href="{{
                                route(
                                    'countries.detail',
                                    $country['id']
                                )
                            }}"
                            class="flex w-full items-center
                                   justify-center rounded-lg
                                   border border-emerald-500/30
                                   bg-emerald-500/10
                                   px-4 py-2.5
                                   text-sm font-semibold
                                   text-emerald-400
                                   transition duration-300
                                   hover:border-emerald-400
                                   hover:bg-emerald-500/20
                                   hover:text-emerald-300"
                        >
                            View Intelligence
                        </a>
                    </div>

                </article>

            @empty

                <div
                    class="col-span-full rounded-xl border
                           border-slate-800 bg-slate-950/70
                           p-8 text-center"
                >
                    <p class="text-sm text-slate-500">
                        No country data available.
                    </p>
                </div>

            @endforelse

        </div>

    </div>

</div>