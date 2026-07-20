<?php

use App\Models\Country;
use App\Models\News;
use App\Models\Port;
use App\Services\GlobalSyncService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

new class extends Component
{
    public array $statistics = [
        'total_countries' => 0,
        'total_ports' => 0,
        'average_risk' => 0,
        'high_risk_countries' => 0,
        'total_news' => 0,
    ];

    public Collection $countries;

    public ?string $syncMessage = null;

    public string $syncStatus = '';

    public bool $isSynchronizing = false;

    public function mount(): void
    {
        $this->countries = new Collection();

        $this->refreshDashboard();
    }

    public function refreshDashboard(): void
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
            ->get();

        $latestRiskScores = $this->countries
            ->pluck('latestRiskScore')
            ->filter();

        $averageRisk = $latestRiskScores->isNotEmpty()
            ? round(
                (float) $latestRiskScores->avg('total_score'),
                2
            )
            : 0;

        $highRiskCountries = $latestRiskScores
            ->filter(function ($riskScore): bool {
                $riskLevel = strtolower(
                    (string) ($riskScore->risk_level ?? '')
                );

                return in_array(
                    $riskLevel,
                    [
                        'high',
                        'critical',
                    ],
                    true
                );
            })
            ->count();

        $this->statistics = [
            'total_countries' => $this->countries->count(),

            'total_ports' => Port::count(),

            'average_risk' => $averageRisk,

            'high_risk_countries' => $highRiskCountries,

            'total_news' => News::count(),
        ];
    }

    public function syncGlobalData(
        GlobalSyncService $globalSyncService
    ): void {
        if ($this->isSynchronizing) {
            return;
        }

        $this->isSynchronizing = true;

        $this->syncMessage = null;

        $this->syncStatus = '';

        try {
            $results = $globalSyncService->syncAll();

            $successfulCountries = 0;

            $failedCountries = 0;

            $successfulServices = 0;

            $failedServices = 0;

            $fallbackProfiles = 0;

            $processedNews = 0;

            foreach ($results as $result) {
                $summary = $result['summary'] ?? [];

                $successfulServices += (int) (
                    $summary['successful'] ?? 0
                );

                $failedServices += (int) (
                    $summary['failed'] ?? 0
                );

                $processedNews += (int) (
                    $result['news_count'] ?? 0
                );

                if (
                    (bool) (
                        $result['profile_fallback'] ?? false
                    )
                ) {
                    $fallbackProfiles++;
                }

                if (
                    (bool) (
                        $summary['success'] ?? false
                    )
                ) {
                    $successfulCountries++;
                } else {
                    $failedCountries++;
                }
            }

            $this->refreshDashboard();

            if ($failedCountries === 0) {
                $this->syncStatus = 'success';

                $this->syncMessage =
                    'Global synchronization completed successfully. '
                    . "{$successfulCountries} countries synchronized, "
                    . "{$successfulServices} services successful, "
                    . "and {$processedNews} news articles processed.";

                if ($fallbackProfiles > 0) {
                    $this->syncMessage .=
                        " {$fallbackProfiles} country profiles used existing database data.";
                }
            } else {
                $this->syncStatus = 'warning';

                $this->syncMessage =
                    'Global synchronization completed with some failures. '
                    . "{$successfulCountries} countries successful, "
                    . "{$failedCountries} countries failed, "
                    . "{$successfulServices} services successful, "
                    . "{$failedServices} services failed, "
                    . "and {$processedNews} news articles processed.";
            }

            $this->dispatch('global-sync-completed');
        } catch (\Throwable $exception) {
            report($exception);

            $this->syncStatus = 'error';

            $this->syncMessage =
                'Global synchronization failed: '
                . $exception->getMessage();
        } finally {
            $this->isSynchronizing = false;
        }
    }

    public function clearSyncMessage(): void
    {
        $this->syncMessage = null;

        $this->syncStatus = '';
    }
};

?>

<div
    wire:poll.300s="refreshDashboard"
    class="min-h-screen"
>
    <div class="mx-auto max-w-[1600px] px-4 py-8 lg:px-8">

        {{-- HEADER --}}

        <section class="mb-8">
            <div
                class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end"
            >
                <div>
                    <div
                        class="mb-3 inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1"
                    >
                        <span
                            class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"
                        ></span>

                        <span
                            class="text-xs font-medium text-emerald-400"
                        >
                            LIVE GLOBAL INTELLIGENCE
                        </span>
                    </div>

                    <h2
                        class="text-3xl font-bold tracking-tight text-white md:text-4xl"
                    >
                        Global Supply Chain Intelligence
                    </h2>

                    <p
                        class="mt-3 max-w-3xl text-sm text-slate-400 md:text-base"
                    >
                        Monitor global logistics, ports,
                        economic trends, weather conditions,
                        market intelligence and supply chain risks.
                    </p>
                </div>

                <div class="flex-shrink-0">
                    <button
                        type="button"
                        wire:click="syncGlobalData"
                        wire:loading.attr="disabled"
                        wire:target="syncGlobalData"
                        @disabled($isSynchronizing)
                        class="inline-flex min-w-[210px] items-center justify-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-3 text-sm font-semibold text-emerald-400 transition hover:border-emerald-400 hover:bg-emerald-500/20 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span
                            wire:loading.remove
                            wire:target="syncGlobalData"
                        >
                            Sync Global Data
                        </span>

                        <span
                            wire:loading
                            wire:target="syncGlobalData"
                            class="inline-flex items-center gap-2"
                        >
                            <svg
                                class="h-4 w-4 animate-spin"
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

                            Synchronizing...
                        </span>
                    </button>
                </div>
            </div>
        </section>

        {{-- SYNCHRONIZATION MESSAGE --}}

        @if ($syncMessage)
            <section class="mb-8">
                <div
                    @class([
                        'rounded-2xl border px-5 py-4',

                        'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
                            => $syncStatus === 'success',

                        'border-amber-500/30 bg-amber-500/10 text-amber-300'
                            => $syncStatus === 'warning',

                        'border-rose-500/30 bg-rose-500/10 text-rose-300'
                            => $syncStatus === 'error',
                    ])
                >
                    <div
                        class="flex items-start justify-between gap-4"
                    >
                        <div>
                            <p class="font-semibold">
                                @if ($syncStatus === 'success')
                                    Synchronization Successful
                                @elseif ($syncStatus === 'warning')
                                    Synchronization Completed with Warning
                                @else
                                    Synchronization Failed
                                @endif
                            </p>

                            <p class="mt-1 text-sm opacity-80">
                                {{ $syncMessage }}
                            </p>
                        </div>

                        <button
                            type="button"
                            wire:click="clearSyncMessage"
                            class="text-xl leading-none opacity-60 transition hover:opacity-100"
                        >
                            &times;
                        </button>
                    </div>
                </div>
            </section>
        @endif

        {{-- COUNTRY LOCAL TIME --}}

        <section
            wire:ignore
            class="mb-8 rounded-2xl border border-slate-800 bg-slate-900/80 p-5"
        >
            <div
                class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center"
            >
                <div>
                    <p
                        class="text-xs uppercase tracking-wider text-slate-500"
                    >
                        COUNTRY LOCAL TIME
                    </p>

                    <p
                        id="country-local-time"
                        class="mt-2 font-mono text-xl font-semibold text-emerald-400"
                    >
                        --
                    </p>

                    <p
                        id="country-timezone"
                        class="mt-1 text-xs text-slate-500"
                    >
                        UTC
                    </p>
                </div>

                <div class="w-full lg:w-80">
                    <label
                        for="global-country-selector"
                        class="mb-2 block text-sm text-slate-400"
                    >
                        Select Country
                    </label>

                    <select
                        id="global-country-selector"
                        class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-emerald-500"
                    >
                        @forelse ($countries as $country)
                            <option
                                value="{{ $country->id }}"
                                data-timezone="{{ $country->timezone ?: 'UTC' }}"
                            >
                                {{ $country->name }}
                            </option>
                        @empty
                            <option
                                value=""
                                data-timezone="UTC"
                            >
                                No Countries Available
                            </option>
                        @endforelse
                    </select>
                </div>
            </div>
        </section>

        {{-- DASHBOARD STATISTICS --}}

        <section
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
        >
            <div
                class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 transition hover:border-emerald-500/40"
            >
                <p class="text-sm text-slate-400">
                    Countries
                </p>

                <p
                    class="mt-3 text-3xl font-bold text-emerald-400"
                >
                    {{
                        number_format(
                            $statistics['total_countries'] ?? 0
                        )
                    }}
                </p>

                <p class="mt-2 text-xs text-slate-500">
                    Monitored globally
                </p>
            </div>

            <div
                class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 transition hover:border-cyan-500/40"
            >
                <p class="text-sm text-slate-400">
                    Global Ports
                </p>

                <p
                    class="mt-3 text-3xl font-bold text-cyan-400"
                >
                    {{
                        number_format(
                            $statistics['total_ports'] ?? 0
                        )
                    }}
                </p>

                <p class="mt-2 text-xs text-slate-500">
                    Logistics infrastructure
                </p>
            </div>

            <div
                class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 transition hover:border-rose-500/40"
            >
                <p class="text-sm text-slate-400">
                    Average Risk
                </p>

                <p
                    class="mt-3 text-3xl font-bold text-rose-500"
                >
                    {{
                        number_format(
                            $statistics['average_risk'] ?? 0,
                            2
                        )
                    }}
                </p>

                <p class="mt-2 text-xs text-slate-500">
                    Global risk index
                </p>
            </div>

            <div
                class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 transition hover:border-orange-500/40"
            >
                <p class="text-sm text-slate-400">
                    High Risk Countries
                </p>

                <p
                    class="mt-3 text-3xl font-bold text-orange-400"
                >
                    {{
                        number_format(
                            $statistics[
                                'high_risk_countries'
                            ] ?? 0
                        )
                    }}
                </p>

                <p class="mt-2 text-xs text-slate-500">
                    Require attention
                </p>
            </div>

            <div
                class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 transition hover:border-violet-500/40"
            >
                <p class="text-sm text-slate-400">
                    Intelligence News
                </p>

                <p
                    class="mt-3 text-3xl font-bold text-violet-400"
                >
                    {{
                        number_format(
                            $statistics['total_news'] ?? 0
                        )
                    }}
                </p>

                <p class="mt-2 text-xs text-slate-500">
                    Global intelligence feeds
                </p>
            </div>
        </section>

        {{-- API STATUS MONITOR --}}

        <section
            id="api-status-monitor"
            class="mt-8"
        >
            <livewire:api-status-monitor />
        </section>

        {{-- GLOBAL MAP --}}

        <section
            id="global-map"
            class="mt-8 scroll-mt-24"
        >
            <livewire:map-component />
        </section>

        {{-- RISK OVERVIEW --}}

        <section
            id="risk-overview"
            class="mt-8 scroll-mt-24"
        >
            <livewire:risk-overview />
        </section>

        {{-- COUNTRY STATISTICS --}}

        <section
            id="country-statistics"
            class="mt-8 scroll-mt-24"
        >
            <livewire:country-statistics />
        </section>

        {{-- TREND ANALYTICS --}}

        <section
            id="trend-analytics"
            class="mt-8 scroll-mt-24"
        >
            <livewire:trend-analytics />
        </section>

        {{-- COMPARE COUNTRIES --}}

        <section
            id="compare-mode"
            class="mt-8 scroll-mt-24"
        >
            <livewire:compare-countries />
        </section>

        {{-- NEWS INTELLIGENCE --}}

        <section
            id="news-intelligence"
            class="mt-8 scroll-mt-24"
        >
            <livewire:news-intelligence />
        </section>
    </div>

    @script
        <script>
            let countryClockInterval = null;

            function initializeCountryClock() {
                const countrySelector =
                    document.getElementById(
                        'global-country-selector'
                    );

                const localTimeElement =
                    document.getElementById(
                        'country-local-time'
                    );

                const timezoneElement =
                    document.getElementById(
                        'country-timezone'
                    );

                if (
                    !countrySelector ||
                    !localTimeElement ||
                    !timezoneElement
                ) {
                    return;
                }

                let activeTimezone = 'UTC';

                function updateCountryClock() {
                    try {
                        const formatter =
                            new Intl.DateTimeFormat(
                                'en-GB',
                                {
                                    timeZone: activeTimezone,
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    second: '2-digit',
                                    hour12: false,
                                }
                            );

                        localTimeElement.textContent =
                            formatter.format(
                                new Date()
                            );

                        timezoneElement.textContent =
                            activeTimezone;
                    } catch (error) {
                        console.error(
                            'Country clock error:',
                            error
                        );

                        activeTimezone = 'UTC';

                        localTimeElement.textContent =
                            new Intl.DateTimeFormat(
                                'en-GB',
                                {
                                    timeZone: 'UTC',
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    second: '2-digit',
                                    hour12: false,
                                }
                            ).format(
                                new Date()
                            );

                        timezoneElement.textContent =
                            'UTC';
                    }
                }

                function changeCountryTimezone() {
                    const selectedOption =
                        countrySelector.options[
                            countrySelector.selectedIndex
                        ];

                    activeTimezone =
                        selectedOption?.getAttribute(
                            'data-timezone'
                        ) || 'UTC';

                    updateCountryClock();
                }

                countrySelector.onchange =
                    changeCountryTimezone;

                changeCountryTimezone();

                if (countryClockInterval !== null) {
                    clearInterval(
                        countryClockInterval
                    );
                }

                countryClockInterval =
                    setInterval(
                        updateCountryClock,
                        1000
                    );
            }

            initializeCountryClock();

            document.addEventListener(
                'livewire:navigated',
                initializeCountryClock
            );

            $wire.on(
                'global-sync-completed',
                () => {
                    setTimeout(
                        initializeCountryClock,
                        100
                    );
                }
            );
        </script>
    @endscript
</div>