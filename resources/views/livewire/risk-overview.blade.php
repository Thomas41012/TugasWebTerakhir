<?php

use App\Models\Country;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    public array $riskData = [];

    public array $statistics = [
        'total_countries' => 0,
        'low_risk' => 0,
        'medium_risk' => 0,
        'high_risk' => 0,
        'critical_risk' => 0,
        'average_risk' => 0,
        'highest_risk_country' => '-',
    ];

    public function mount(): void
    {
        $this->loadRiskData();
    }

    #[On('global-sync-completed')]
    public function handleGlobalSyncCompleted(): void
    {
        $this->refreshRisk();
    }

    public function refreshRisk(): void
    {
        $this->loadRiskData();

        $this->dispatch(
            'risk-chart-updated',
            riskData: $this->riskData
        );
    }

    private function loadRiskData(): void
    {
        $this->riskData = Country::query()
            ->where('is_active', true)
            ->with('latestRiskScore')
            ->orderBy('name')
            ->get()
            ->map(function (Country $country): array {
                $riskScore = $country->latestRiskScore;

                $riskLevel = strtolower(
                    trim(
                        (string) (
                            $riskScore?->risk_level
                            ?? 'low'
                        )
                    )
                );

                if (
                    ! in_array(
                        $riskLevel,
                        [
                            'low',
                            'medium',
                            'high',
                            'critical',
                        ],
                        true
                    )
                ) {
                    $riskLevel = 'low';
                }

                return [
                    'country_id' => $country->id,

                    'country' => $country->name,

                    'iso2' => $country->iso2,

                    'iso3' => $country->iso3,

                    'total_score' => round(
                        (float) (
                            $riskScore?->total_score
                            ?? 0
                        ),
                        2
                    ),

                    'weather_score' => round(
                        (float) (
                            $riskScore?->weather_score
                            ?? 0
                        ),
                        2
                    ),

                    'inflation_score' => round(
                        (float) (
                            $riskScore?->inflation_score
                            ?? 0
                        ),
                        2
                    ),

                    'currency_score' => round(
                        (float) (
                            $riskScore?->currency_score
                            ?? 0
                        ),
                        2
                    ),

                    'political_score' => round(
                        (float) (
                            $riskScore?->political_score
                            ?? 0
                        ),
                        2
                    ),

                    'port_score' => round(
                        (float) (
                            $riskScore?->port_score
                            ?? 0
                        ),
                        2
                    ),

                    'risk_level' => $riskLevel,

                    'calculated_at' =>
                        $riskScore?->calculated_at
                            ?->format('d M Y H:i:s'),

                    'calculated_at_human' =>
                        $riskScore?->calculated_at
                            ?->diffForHumans()
                        ?? 'Never',
                ];
            })
            ->sortByDesc('total_score')
            ->values()
            ->all();

        $this->calculateStatistics();
    }

    private function calculateStatistics(): void
    {
        $risks = collect(
            $this->riskData
        );

        $highestRisk = $risks
            ->sortByDesc('total_score')
            ->first();

        $this->statistics = [
            'total_countries' =>
                $risks->count(),

            'low_risk' =>
                $risks
                    ->where(
                        'risk_level',
                        'low'
                    )
                    ->count(),

            'medium_risk' =>
                $risks
                    ->where(
                        'risk_level',
                        'medium'
                    )
                    ->count(),

            'high_risk' =>
                $risks
                    ->where(
                        'risk_level',
                        'high'
                    )
                    ->count(),

            'critical_risk' =>
                $risks
                    ->where(
                        'risk_level',
                        'critical'
                    )
                    ->count(),

            'average_risk' =>
                $risks->isNotEmpty()
                    ? round(
                        (float) $risks->avg(
                            'total_score'
                        ),
                        2
                    )
                    : 0,

            'highest_risk_country' =>
                $highestRisk['country']
                ?? '-',
        ];
    }
};

?>

<div wire:poll.300s="refreshRisk">

    <div
        class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5"
    >

        {{-- HEADER --}}

        <div
            class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center"
        >
            <div>
                <div
                    class="mb-2 inline-flex items-center gap-2 rounded-full border border-rose-500/20 bg-rose-500/10 px-3 py-1"
                >
                    <span
                        class="h-2 w-2 animate-pulse rounded-full bg-rose-400"
                    ></span>

                    <span
                        class="text-xs font-semibold uppercase tracking-wider text-rose-400"
                    >
                        Live Risk Monitoring
                    </span>
                </div>

                <h2 class="text-xl font-bold text-white">
                    Global Risk Overview
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Comparison of supply chain risk indicators
                    across monitored countries.
                </p>
            </div>

            <button
                type="button"
                wire:click="refreshRisk"
                wire:loading.attr="disabled"
                wire:target="refreshRisk"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-cyan-500/20 bg-cyan-500/10 px-4 py-2.5 text-sm font-medium text-cyan-400 transition hover:bg-cyan-500/20 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <svg
                    wire:loading.class="animate-spin"
                    wire:target="refreshRisk"
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                </svg>

                <span wire:loading.remove wire:target="refreshRisk">
                    Refresh Risk
                </span>

                <span wire:loading wire:target="refreshRisk">
                    Updating...
                </span>
            </button>
        </div>


        {{-- STATISTICS --}}

        <div
            class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6"
        >

            <div
                class="rounded-xl border border-slate-800 bg-slate-950/70 p-4"
            >
                <p
                    class="text-xs uppercase tracking-wide text-slate-500"
                >
                    Countries
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-cyan-400"
                >
                    {{
                        number_format(
                            $statistics['total_countries']
                            ?? 0
                        )
                    }}
                </p>
            </div>


            <div
                class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-4"
            >
                <p
                    class="text-xs uppercase tracking-wide text-slate-500"
                >
                    Low Risk
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-emerald-400"
                >
                    {{
                        number_format(
                            $statistics['low_risk']
                            ?? 0
                        )
                    }}
                </p>
            </div>


            <div
                class="rounded-xl border border-orange-500/20 bg-orange-500/5 p-4"
            >
                <p
                    class="text-xs uppercase tracking-wide text-slate-500"
                >
                    Medium Risk
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-orange-400"
                >
                    {{
                        number_format(
                            $statistics['medium_risk']
                            ?? 0
                        )
                    }}
                </p>
            </div>


            <div
                class="rounded-xl border border-rose-500/20 bg-rose-500/5 p-4"
            >
                <p
                    class="text-xs uppercase tracking-wide text-slate-500"
                >
                    High Risk
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-rose-400"
                >
                    {{
                        number_format(
                            $statistics['high_risk']
                            ?? 0
                        )
                    }}
                </p>
            </div>


            <div
                class="rounded-xl border border-red-500/20 bg-red-500/5 p-4"
            >
                <p
                    class="text-xs uppercase tracking-wide text-slate-500"
                >
                    Critical
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-red-400"
                >
                    {{
                        number_format(
                            $statistics['critical_risk']
                            ?? 0
                        )
                    }}
                </p>
            </div>


            <div
                class="rounded-xl border border-violet-500/20 bg-violet-500/5 p-4"
            >
                <p
                    class="text-xs uppercase tracking-wide text-slate-500"
                >
                    Average Risk
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-violet-400"
                >
                    {{
                        number_format(
                            $statistics['average_risk']
                            ?? 0,
                            2
                        )
                    }}
                </p>
            </div>

        </div>


        {{-- HIGHEST RISK --}}

        <div
            class="mt-4 rounded-xl border border-rose-500/20 bg-rose-500/5 px-4 py-3"
        >
            <div
                class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center"
            >
                <span class="text-sm text-slate-400">
                    Highest Risk Country
                </span>

                <span class="font-semibold text-rose-400">
                    {{
                        $statistics[
                            'highest_risk_country'
                        ]
                        ?? '-'
                    }}
                </span>
            </div>
        </div>


        {{-- LOADING --}}

        <div
            wire:loading.flex
            wire:target="refreshRisk"
            class="mt-5 items-center justify-center rounded-xl border border-cyan-500/20 bg-cyan-500/5 px-4 py-3"
        >
            <svg
                class="mr-2 h-4 w-4 animate-spin text-cyan-400"
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

            <span class="text-sm text-cyan-300">
                Updating global risk intelligence...
            </span>
        </div>


        {{-- RISK CHART --}}

        <div
            class="mt-6 rounded-xl border border-slate-800 bg-slate-950/50 p-4"
        >
            <div>
                <h3 class="font-semibold text-white">
                    Risk Indicator Comparison
                </h3>

                <p class="mt-1 text-xs text-slate-500">
                    Weather, inflation, currency, political,
                    port and total supply chain risk.
                </p>
            </div>

            <div
                wire:ignore
                id="global-risk-chart"
                class="mt-4 min-h-[440px]"
            ></div>
        </div>


        {{-- COUNTRY RISK CARDS --}}

        <div
            class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
        >
            @forelse ($riskData as $risk)

                <a
                    href="{{ route('countries.detail', $risk['country_id']) }}"
                    wire:key="risk-country-{{ $risk['country_id'] }}"
                    class="block rounded-xl border border-slate-800 bg-slate-950/70 p-4 transition hover:border-slate-600 hover:bg-slate-900/50 hover:shadow-lg cursor-pointer"
                >

                    {{-- COUNTRY HEADER --}}

                    <div
                        class="flex items-start justify-between gap-3"
                    >
                        <div>
                            <h3 class="font-semibold text-white">
                                {{ $risk['country'] }}
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ $risk['iso3'] }}
                            </p>
                        </div>


                        {{-- RISK LEVEL --}}

                        <span
                            @class([
                                'rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide',

                                'border-red-500/30 bg-red-500/10 text-red-400'
                                    => $risk['risk_level'] === 'critical',

                                'border-rose-500/30 bg-rose-500/10 text-rose-400'
                                    => $risk['risk_level'] === 'high',

                                'border-orange-500/30 bg-orange-500/10 text-orange-400'
                                    => $risk['risk_level'] === 'medium',

                                'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                                    => $risk['risk_level'] === 'low',
                            ])
                        >
                            {{ $risk['risk_level'] }}
                        </span>
                    </div>


                    {{-- TOTAL RISK --}}

                    <div class="mt-5">

                        <div
                            class="flex items-end justify-between gap-4"
                        >
                            <span class="text-sm text-slate-500">
                                Risk Score
                            </span>

                            <span
                                @class([
                                    'text-2xl font-bold',

                                    'text-red-400'
                                        => $risk['risk_level'] === 'critical',

                                    'text-rose-400'
                                        => $risk['risk_level'] === 'high',

                                    'text-orange-400'
                                        => $risk['risk_level'] === 'medium',

                                    'text-emerald-400'
                                        => $risk['risk_level'] === 'low',
                                ])
                            >
                                {{
                                    number_format(
                                        $risk['total_score'],
                                        2
                                    )
                                }}
                            </span>
                        </div>


                        {{-- PROGRESS BAR --}}

                        <div
                            class="mt-3 h-2 overflow-hidden rounded-full bg-slate-800"
                        >
                            <div
                                @class([
                                    'h-full rounded-full transition-all duration-500',

                                    'bg-red-500'
                                        => $risk['risk_level'] === 'critical',

                                    'bg-rose-500'
                                        => $risk['risk_level'] === 'high',

                                    'bg-orange-500'
                                        => $risk['risk_level'] === 'medium',

                                    'bg-emerald-500'
                                        => $risk['risk_level'] === 'low',
                                ])
                                style="width: {{
                                    min(
                                        100,
                                        max(
                                            0,
                                            $risk['total_score']
                                        )
                                    )
                                }}%;"
                            ></div>
                        </div>

                    </div>


                    {{-- RISK DETAILS --}}

                    <div
                        class="mt-4 space-y-2.5 border-t border-slate-800 pt-4"
                    >

                        @foreach ([
                            'Weather' => 'weather_score',
                            'Inflation' => 'inflation_score',
                            'Currency' => 'currency_score',
                            'Political' => 'political_score',
                            'Port' => 'port_score',
                        ] as $label => $key)

                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span class="text-xs text-slate-500">
                                    {{ $label }}
                                </span>

                                <span class="text-xs font-medium text-white">
                                    {{
                                        number_format(
                                            $risk[$key],
                                            2
                                        )
                                    }}
                                </span>
                            </div>

                        @endforeach

                    </div>


                    {{-- LAST CALCULATED --}}

                    <div
                        class="mt-4 border-t border-slate-800 pt-3"
                    >
                        <div
                            class="flex items-center justify-between gap-3"
                        >
                            <span class="text-[10px] text-slate-600">
                                Last Calculated
                            </span>

                            <span
                                class="text-right text-[10px] text-cyan-500"
                                title="{{ $risk['calculated_at'] ?? 'Never' }}"
                            >
                                {{
                                    $risk[
                                        'calculated_at_human'
                                    ]
                                }}
                            </span>
                        </div>
                    </div>

                </a>

            @empty

                <div
                    class="col-span-full rounded-xl border border-slate-800 bg-slate-950/70 p-8 text-center"
                >
                    <p class="text-sm text-slate-500">
                        No risk data available.
                    </p>
                </div>

            @endforelse
        </div>

    </div>


    {{-- APEXCHARTS --}}

    @script
        <script>
            let riskChartData = @js($riskData);

            let globalRiskChart = null;


            function getRiskChartOptions(data) {
                const countries = data.map(
                    item => item.country
                );

                return {
                    chart: {
                        type: 'bar',
                        height: 440,
                        background: 'transparent',
                        toolbar: {
                            show: true,
                        },
                        animations: {
                            enabled: true,
                            speed: 500,
                        },
                    },

                    series: [
                        {
                            name: 'Weather',
                            data: data.map(
                                item => Number(
                                    item.weather_score ?? 0
                                )
                            ),
                        },
                        {
                            name: 'Inflation',
                            data: data.map(
                                item => Number(
                                    item.inflation_score ?? 0
                                )
                            ),
                        },
                        {
                            name: 'Currency',
                            data: data.map(
                                item => Number(
                                    item.currency_score ?? 0
                                )
                            ),
                        },
                        {
                            name: 'Political',
                            data: data.map(
                                item => Number(
                                    item.political_score ?? 0
                                )
                            ),
                        },
                        {
                            name: 'Port',
                            data: data.map(
                                item => Number(
                                    item.port_score ?? 0
                                )
                            ),
                        },
                        {
                            name: 'Total Risk',
                            data: data.map(
                                item => Number(
                                    item.total_score ?? 0
                                )
                            ),
                        },
                    ],

                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '65%',
                            borderRadius: 4,
                        },
                    },

                    dataLabels: {
                        enabled: false,
                    },

                    stroke: {
                        show: true,
                        width: 1,
                    },

                    xaxis: {
                        categories: countries,

                        labels: {
                            style: {
                                colors: '#94a3b8',
                                fontSize: '11px',
                            },
                        },

                        axisBorder: {
                            color: '#334155',
                        },

                        axisTicks: {
                            color: '#334155',
                        },
                    },

                    yaxis: {
                        min: 0,
                        max: 100,

                        labels: {
                            style: {
                                colors: '#94a3b8',
                            },

                            formatter: function (value) {
                                return Number(value)
                                    .toFixed(0);
                            },
                        },
                    },

                    grid: {
                        borderColor: '#1e293b',
                        strokeDashArray: 4,
                    },

                    legend: {
                        position: 'top',

                        labels: {
                            colors: '#cbd5e1',
                        },
                    },

                    tooltip: {
                        theme: 'dark',

                        y: {
                            formatter: function (value) {
                                return Number(value)
                                    .toFixed(2);
                            },
                        },
                    },

                    noData: {
                        text: 'No risk data available',

                        style: {
                            color: '#64748b',
                        },
                    },
                };
            }


            function initializeGlobalRiskChart() {
                const chartElement =
                    document.getElementById(
                        'global-risk-chart'
                    );

                if (!chartElement) {
                    return;
                }

                if (
                    typeof ApexCharts === 'undefined'
                ) {
                    console.error(
                        'ApexCharts library is not loaded.'
                    );

                    return;
                }

                if (globalRiskChart) {
                    globalRiskChart.destroy();

                    globalRiskChart = null;
                }

                globalRiskChart = new ApexCharts(
                    chartElement,
                    getRiskChartOptions(
                        riskChartData
                    )
                );

                globalRiskChart.render();
            }


            function updateGlobalRiskChart(data) {
                riskChartData = Array.isArray(data)
                    ? data
                    : [];

                if (!globalRiskChart) {
                    initializeGlobalRiskChart();

                    return;
                }

                globalRiskChart.updateOptions({
                    xaxis: {
                        categories: riskChartData.map(
                            item => item.country
                        ),
                    },
                });

                globalRiskChart.updateSeries([
                    {
                        name: 'Weather',
                        data: riskChartData.map(
                            item => Number(
                                item.weather_score ?? 0
                            )
                        ),
                    },
                    {
                        name: 'Inflation',
                        data: riskChartData.map(
                            item => Number(
                                item.inflation_score ?? 0
                            )
                        ),
                    },
                    {
                        name: 'Currency',
                        data: riskChartData.map(
                            item => Number(
                                item.currency_score ?? 0
                            )
                        ),
                    },
                    {
                        name: 'Political',
                        data: riskChartData.map(
                            item => Number(
                                item.political_score ?? 0
                            )
                        ),
                    },
                    {
                        name: 'Port',
                        data: riskChartData.map(
                            item => Number(
                                item.port_score ?? 0
                            )
                        ),
                    },
                    {
                        name: 'Total Risk',
                        data: riskChartData.map(
                            item => Number(
                                item.total_score ?? 0
                            )
                        ),
                    },
                ]);
            }


            initializeGlobalRiskChart();


            $wire.on(
                'risk-chart-updated',
                function (event) {
                    const payload =
                        Array.isArray(event)
                            ? event[0]
                            : event;

                    updateGlobalRiskChart(
                        payload?.riskData ?? []
                    );
                }
            );
        </script>
    @endscript

</div>