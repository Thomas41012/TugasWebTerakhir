<?php

use App\Models\Country;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    public array $countries = [];

    public array $chartData = [];

    public function mount(): void
    {
        $this->loadAnalytics();
    }

    #[On('global-sync-completed')]
    public function handleGlobalSyncCompleted(): void
    {
        $this->refreshAnalytics();
    }

    public function refreshAnalytics(): void
    {
        $this->loadAnalytics();

        $this->dispatch(
            'trend-analytics-updated',
            chartData: $this->chartData
        );
    }

    private function loadAnalytics(): void
    {
        $countries = Country::query()
            ->where('is_active', true)
            ->with([
                'latestRiskScore',
                'latestMarketTrend',
                'latestCurrencyRate',
            ])
            ->orderBy('name')
            ->get();

        $this->countries = $countries
            ->map(function (Country $country): array {
                return [
                    'id' => $country->id,

                    'name' => $country->name,

                    'iso2' => $country->iso2,

                    'iso3' => $country->iso3,

                    'risk_score' => (float) (
                        $country->latestRiskScore?->total_score
                        ?? 0
                    ),

                    'market_impact_score' => (float) (
                        $country->latestMarketTrend?->market_impact_score
                        ?? 0
                    ),

                    'inflation_rate' => (float) (
                        $country->latestMarketTrend?->inflation_rate
                        ?? 0
                    ),

                    'currency_change' => (float) (
                        $country->latestCurrencyRate?->percentage_change
                        ?? 0
                    ),

                    'trend_status' => (
                        $country->latestMarketTrend?->trend_status
                        ?? 'neutral'
                    ),
                ];
            })
            ->values()
            ->toArray();

        $this->chartData = [
            'categories' => collect($this->countries)
                ->pluck('name')
                ->values()
                ->toArray(),

            'risk_scores' => collect($this->countries)
                ->pluck('risk_score')
                ->values()
                ->toArray(),

            'market_impact_scores' => collect($this->countries)
                ->pluck('market_impact_score')
                ->values()
                ->toArray(),

            'inflation_rates' => collect($this->countries)
                ->pluck('inflation_rate')
                ->values()
                ->toArray(),

            'currency_changes' => collect($this->countries)
                ->pluck('currency_change')
                ->values()
                ->toArray(),
        ];
    }
};
?>

<div wire:poll.300s="refreshAnalytics">

    <div
        class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5"
    >

        {{-- Header --}}

        <div
            class="mb-6 flex flex-col justify-between gap-4
                   lg:flex-row lg:items-center"
        >
            <div>
                <h2 class="text-xl font-bold text-white">
                    Trend Analytics
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Interactive market, currency, inflation and risk intelligence.
                </p>
            </div>

            <div
                class="rounded-xl border border-cyan-500/20
                       bg-cyan-500/10 px-4 py-2"
            >
                <span class="text-xs font-medium text-cyan-400">
                    REAL-TIME ANALYTICS
                </span>
            </div>
        </div>

        {{-- Main Chart --}}

        <div
            class="rounded-xl border border-slate-800
                   bg-slate-950/70 p-4"
        >
            <div class="mb-4">

                <h3 class="font-semibold text-white">
                    Global Intelligence Comparison
                </h3>

                <p class="mt-1 text-xs text-slate-500">
                    Risk score and market impact comparison across countries.
                </p>

            </div>

            <div
                wire:ignore
                id="global-intelligence-chart"
                class="min-h-[400px]"
            ></div>
        </div>

        {{-- Secondary Charts --}}

        <div class="mt-5 grid gap-5 lg:grid-cols-2">

            {{-- Inflation Chart --}}

            <div
                class="rounded-xl border border-slate-800
                       bg-slate-950/70 p-4"
            >

                <div class="mb-4">

                    <h3 class="font-semibold text-white">
                        Inflation Analysis
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Latest inflation rate by country.
                    </p>

                </div>

                <div
                    wire:ignore
                    id="inflation-analytics-chart"
                    class="min-h-[320px]"
                ></div>

            </div>

            {{-- Currency Chart --}}

            <div
                class="rounded-xl border border-slate-800
                       bg-slate-950/70 p-4"
            >

                <div class="mb-4">

                    <h3 class="font-semibold text-white">
                        Currency Movement
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Latest currency percentage change by country.
                    </p>

                </div>

                <div
                    wire:ignore
                    id="currency-analytics-chart"
                    class="min-h-[320px]"
                ></div>

            </div>

        </div>

        {{-- Country Trend Summary --}}

        <div class="mt-5">

            <h3 class="mb-4 font-semibold text-white">
                Market Trend Summary
            </h3>

            <div
                class="grid gap-3 md:grid-cols-2
                       lg:grid-cols-3 xl:grid-cols-5"
            >

                @forelse ($countries as $country)

                    <div
                        wire:key="market-trend-{{ $country['id'] }}"
                        class="rounded-xl border border-slate-800
                               bg-slate-950/70 p-4"
                    >

                        <div
                            class="flex items-center
                                   justify-between gap-3"
                        >

                            <div>

                                <p class="font-semibold text-white">
                                    {{ $country['name'] }}
                                </p>

                                <p class="text-xs text-slate-500">
                                    {{ $country['iso3'] }}
                                </p>

                            </div>

                            <span
                                @class([
                                    'rounded-full px-2 py-1 text-[10px] font-semibold',

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
                                {{
                                    strtoupper(
                                        $country['trend_status']
                                    )
                                }}
                            </span>

                        </div>

                        <div
                            class="mt-4 space-y-2
                                   border-t border-slate-800 pt-3"
                        >

                            <div
                                class="flex justify-between text-xs"
                            >
                                <span class="text-slate-500">
                                    Risk
                                </span>

                                <span class="text-rose-400">
                                    {{
                                        number_format(
                                            $country['risk_score'],
                                            2
                                        )
                                    }}
                                </span>
                            </div>

                            <div
                                class="flex justify-between text-xs"
                            >
                                <span class="text-slate-500">
                                    Market Impact
                                </span>

                                <span class="text-orange-400">
                                    {{
                                        number_format(
                                            $country['market_impact_score'],
                                            2
                                        )
                                    }}
                                </span>
                            </div>

                            <div
                                class="flex justify-between text-xs"
                            >
                                <span class="text-slate-500">
                                    Inflation
                                </span>

                                <span class="text-cyan-400">
                                    {{
                                        number_format(
                                            $country['inflation_rate'],
                                            2
                                        )
                                    }}%
                                </span>
                            </div>

                        </div>

                    </div>

                @empty

                    <div
                        class="col-span-full rounded-xl
                               border border-slate-800
                               bg-slate-950/70 p-8
                               text-center"
                    >
                        <p class="text-sm text-slate-500">
                            No trend analytics data available.
                        </p>
                    </div>

                @endforelse

            </div>

        </div>

    </div>

    @script

        <script>

            let globalIntelligenceChart = null;

            let inflationAnalyticsChart = null;

            let currencyAnalyticsChart = null;

            function renderTrendAnalytics(chartData) {

                if (
                    typeof ApexCharts === 'undefined'
                ) {
                    console.error(
                        'ApexCharts is not loaded.'
                    );

                    return;
                }

                const categories =
                    chartData.categories ?? [];

                /*
                |--------------------------------------------------------------------------
                | Destroy Previous Charts
                |--------------------------------------------------------------------------
                */

                if (globalIntelligenceChart) {
                    globalIntelligenceChart.destroy();

                    globalIntelligenceChart = null;
                }

                if (inflationAnalyticsChart) {
                    inflationAnalyticsChart.destroy();

                    inflationAnalyticsChart = null;
                }

                if (currencyAnalyticsChart) {
                    currencyAnalyticsChart.destroy();

                    currencyAnalyticsChart = null;
                }

                /*
                |--------------------------------------------------------------------------
                | Global Intelligence Chart
                |--------------------------------------------------------------------------
                */

                const globalElement =
                    document.querySelector(
                        '#global-intelligence-chart'
                    );

                if (globalElement) {

                    globalIntelligenceChart =
                        new ApexCharts(
                            globalElement,
                            {
                                chart: {
                                    type: 'bar',
                                    height: 400,
                                    background: 'transparent',
                                    toolbar: {
                                        show: true,
                                    },
                                },

                                series: [
                                    {
                                        name: 'Risk Score',

                                        data:
                                            chartData.risk_scores
                                            ?? [],
                                    },

                                    {
                                        name: 'Market Impact',

                                        data:
                                            chartData.market_impact_scores
                                            ?? [],
                                    },
                                ],

                                xaxis: {
                                    categories: categories,

                                    labels: {
                                        style: {
                                            colors: '#94a3b8',
                                        },
                                    },
                                },

                                yaxis: {
                                    min: 0,

                                    max: 100,

                                    labels: {
                                        style: {
                                            colors: '#94a3b8',
                                        },
                                    },
                                },

                                grid: {
                                    borderColor: '#1e293b',
                                },

                                legend: {
                                    labels: {
                                        colors: '#cbd5e1',
                                    },
                                },

                                dataLabels: {
                                    enabled: false,
                                },

                                tooltip: {
                                    theme: 'dark',
                                },

                                theme: {
                                    mode: 'dark',
                                },
                            }
                        );

                    globalIntelligenceChart.render();
                }

                /*
                |--------------------------------------------------------------------------
                | Inflation Chart
                |--------------------------------------------------------------------------
                */

                const inflationElement =
                    document.querySelector(
                        '#inflation-analytics-chart'
                    );

                if (inflationElement) {

                    inflationAnalyticsChart =
                        new ApexCharts(
                            inflationElement,
                            {
                                chart: {
                                    type: 'area',
                                    height: 320,
                                    background: 'transparent',
                                    toolbar: {
                                        show: true,
                                    },
                                },

                                series: [
                                    {
                                        name: 'Inflation Rate',

                                        data:
                                            chartData.inflation_rates
                                            ?? [],
                                    },
                                ],

                                xaxis: {
                                    categories: categories,

                                    labels: {
                                        style: {
                                            colors: '#94a3b8',
                                        },
                                    },
                                },

                                yaxis: {
                                    labels: {
                                        formatter: function (value) {
                                            return value.toFixed(2) + '%';
                                        },

                                        style: {
                                            colors: '#94a3b8',
                                        },
                                    },
                                },

                                stroke: {
                                    curve: 'smooth',
                                    width: 3,
                                },

                                dataLabels: {
                                    enabled: false,
                                },

                                grid: {
                                    borderColor: '#1e293b',
                                },

                                tooltip: {
                                    theme: 'dark',
                                },

                                theme: {
                                    mode: 'dark',
                                },
                            }
                        );

                    inflationAnalyticsChart.render();
                }

                /*
                |--------------------------------------------------------------------------
                | Currency Chart
                |--------------------------------------------------------------------------
                */

                const currencyElement =
                    document.querySelector(
                        '#currency-analytics-chart'
                    );

                if (currencyElement) {

                    currencyAnalyticsChart =
                        new ApexCharts(
                            currencyElement,
                            {
                                chart: {
                                    type: 'line',
                                    height: 320,
                                    background: 'transparent',
                                    toolbar: {
                                        show: true,
                                    },
                                },

                                series: [
                                    {
                                        name: 'Currency Change',

                                        data:
                                            chartData.currency_changes
                                            ?? [],
                                    },
                                ],

                                xaxis: {
                                    categories: categories,

                                    labels: {
                                        style: {
                                            colors: '#94a3b8',
                                        },
                                    },
                                },

                                yaxis: {
                                    labels: {
                                        formatter: function (value) {
                                            return value.toFixed(2) + '%';
                                        },

                                        style: {
                                            colors: '#94a3b8',
                                        },
                                    },
                                },

                                stroke: {
                                    curve: 'smooth',
                                    width: 3,
                                },

                                markers: {
                                    size: 5,
                                },

                                dataLabels: {
                                    enabled: false,
                                },

                                grid: {
                                    borderColor: '#1e293b',
                                },

                                tooltip: {
                                    theme: 'dark',
                                },

                                theme: {
                                    mode: 'dark',
                                },
                            }
                        );

                    currencyAnalyticsChart.render();
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Initial Render
            |--------------------------------------------------------------------------
            */

            renderTrendAnalytics(
                @js($chartData)
            );

            /*
            |--------------------------------------------------------------------------
            | Livewire Refresh Event
            |--------------------------------------------------------------------------
            */

            $wire.on(
                'trend-analytics-updated',
                (event) => {
                    renderTrendAnalytics(
                        event.chartData
                        ?? event[0]?.chartData
                        ?? {}
                    );
                }
            );

        </script>

    @endscript

</div>