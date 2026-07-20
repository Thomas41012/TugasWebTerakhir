<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Services\GlobalSyncService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CountryController extends Controller
{
    public function __construct(
        protected GlobalSyncService $globalSyncService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Countries API Index
    |--------------------------------------------------------------------------
    |
    | GET /api/v1/countries
    |
    */

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'region' => [
                'nullable',
                'string',
                'max:100',
            ],

            'active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $search = isset($validated['search'])
            ? trim($validated['search'])
            : null;

        $region = isset($validated['region'])
            ? trim($validated['region'])
            : null;

        $isActive = array_key_exists(
            'active',
            $validated
        )
            ? (bool) $validated['active']
            : true;

        $countries = Country::query()
            ->where(
                'is_active',
                $isActive
            )
            ->with([
                'latestRiskScore',

                'latestMarketTrend',

                'latestWeatherRecord',

                'latestCurrencyRate',
            ])
            ->when(
                $search,
                function (
                    Builder $query
                ) use (
                    $search
                ): void {
                    $query->where(
                        function (
                            Builder $subQuery
                        ) use (
                            $search
                        ): void {
                            $subQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'official_name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'iso2',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'iso3',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'capital',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'region',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'subregion',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                $region,
                fn (Builder $query) =>
                    $query->where(
                        'region',
                        $region
                    )
            )
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,

            'message' =>
                'Countries retrieved successfully.',

            'data' =>
                $countries,

            'meta' => [
                'total' =>
                    $countries->count(),

                'filters' => [
                    'search' =>
                        $search,

                    'region' =>
                        $region,

                    'active' =>
                        $isActive,
                ],
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Country API Show
    |--------------------------------------------------------------------------
    |
    | GET /api/v1/countries/{country}
    |
    */

    public function show(
        Country $country
    ): JsonResponse {
        $this->loadCountryRelations(
            $country
        );

        return response()->json([
            'success' => true,

            'message' =>
                'Country retrieved successfully.',

            'data' =>
                $country,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Country Detail Page
    |--------------------------------------------------------------------------
    |
    | GET /countries/{country}
    |
    */

    public function detail(
        Country $country
    ): View {
        /*
        |--------------------------------------------------------------------------
        | Load Country Relations
        |--------------------------------------------------------------------------
        */

        $this->loadCountryRelations(
            $country
        );

        /*
        |--------------------------------------------------------------------------
        | Latest Economic Indicator
        |--------------------------------------------------------------------------
        */

        $latestEconomicIndicator =
            $country
                ->economicIndicators
                ->sortByDesc('year')
                ->first();

        /*
        |--------------------------------------------------------------------------
        | Latest Data
        |--------------------------------------------------------------------------
        */

        $latestWeather =
            $country->latestWeatherRecord;

        $latestCurrency =
            $country->latestCurrencyRate;

        $latestRisk =
            $country->latestRiskScore;

        $latestMarketTrend =
            $country->latestMarketTrend;

        /*
        |--------------------------------------------------------------------------
        | Economic Chart
        |--------------------------------------------------------------------------
        */

        $economicChart = $country
            ->economicIndicators
            ->sortBy('year')
            ->map(
                function ($indicator): array {
                    return [
                        'year' =>
                            (int) $indicator->year,

                        'gdp' =>
                            (float) (
                                $indicator->gdp
                                ?? 0
                            ),

                        'gdp_growth' =>
                            (float) (
                                $indicator->gdp_growth
                                ?? 0
                            ),

                        'inflation_rate' =>
                            (float) (
                                $indicator->inflation_rate
                                ?? 0
                            ),

                        'exports' =>
                            (float) (
                                $indicator->exports
                                ?? 0
                            ),

                        'imports' =>
                            (float) (
                                $indicator->imports
                                ?? 0
                            ),

                        'population' =>
                            (int) (
                                $indicator->population
                                ?? 0
                            ),
                    ];
                }
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Weather Chart
        |--------------------------------------------------------------------------
        */

        $weatherChart = $country
            ->weatherRecords
            ->sortBy('recorded_at')
            ->map(
                function ($weather): array {
                    return [
                        'date' =>
                            optional(
                                $weather->recorded_at
                            )->format(
                                'd M H:i'
                            ),

                        'temperature' =>
                            (float) (
                                $weather->temperature
                                ?? 0
                            ),

                        'feels_like' =>
                            (float) (
                                $weather->feels_like
                                ?? 0
                            ),

                        'humidity' =>
                            (float) (
                                $weather->humidity
                                ?? 0
                            ),

                        'precipitation' =>
                            (float) (
                                $weather->precipitation
                                ?? 0
                            ),

                        'rain' =>
                            (float) (
                                $weather->rain
                                ?? 0
                            ),

                        'cloud_cover' =>
                            (float) (
                                $weather->cloud_cover
                                ?? 0
                            ),

                        'pressure' =>
                            (float) (
                                $weather->pressure
                                ?? 0
                            ),

                        'wind_speed' =>
                            (float) (
                                $weather->wind_speed
                                ?? 0
                            ),

                        'weather_risk_score' =>
                            (float) (
                                $weather->weather_risk_score
                                ?? 0
                            ),

                        'extreme_weather' =>
                            (bool) (
                                $weather->extreme_weather
                                ?? false
                            ),
                    ];
                }
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Currency Chart
        |--------------------------------------------------------------------------
        */

        $currencyChart = $country
            ->currencyRates
            ->sortBy('recorded_at')
            ->map(
                function ($currency): array {
                    return [
                        'date' =>
                            optional(
                                $currency->recorded_at
                            )->format(
                                'd M H:i'
                            ),

                        'base_currency' =>
                            $currency->base_currency,

                        'target_currency' =>
                            $currency->target_currency,

                        'exchange_rate' =>
                            (float) (
                                $currency->exchange_rate
                                ?? 0
                            ),

                        'previous_rate' =>
                            (float) (
                                $currency->previous_rate
                                ?? 0
                            ),

                        'percentage_change' =>
                            (float) (
                                $currency->percentage_change
                                ?? 0
                            ),
                    ];
                }
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Risk Chart
        |--------------------------------------------------------------------------
        */

        $riskChart = $country
            ->riskScores
            ->sortBy('calculated_at')
            ->map(
                function ($risk): array {
                    return [
                        'date' =>
                            optional(
                                $risk->calculated_at
                            )->format(
                                'd M H:i'
                            ),

                        'weather_score' =>
                            (float) (
                                $risk->weather_score
                                ?? 0
                            ),

                        'inflation_score' =>
                            (float) (
                                $risk->inflation_score
                                ?? 0
                            ),

                        'currency_score' =>
                            (float) (
                                $risk->currency_score
                                ?? 0
                            ),

                        'political_score' =>
                            (float) (
                                $risk->political_score
                                ?? 0
                            ),

                        'port_score' =>
                            (float) (
                                $risk->port_score
                                ?? 0
                            ),

                        'total_score' =>
                            (float) (
                                $risk->total_score
                                ?? 0
                            ),

                        'risk_level' =>
                            $risk->risk_level
                            ?? 'unknown',
                    ];
                }
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Market Trend Chart
        |--------------------------------------------------------------------------
        */

        $marketTrendChart = $country
            ->marketTrends
            ->sortBy('recorded_at')
            ->map(
                function ($trend): array {
                    return [
                        'date' =>
                            optional(
                                $trend->recorded_at
                            )->format(
                                'd M H:i'
                            ),

                        'exchange_rate' =>
                            (float) (
                                $trend->exchange_rate
                                ?? 0
                            ),

                        'exchange_rate_change' =>
                            (float) (
                                $trend->exchange_rate_change
                                ?? 0
                            ),

                        'inflation_rate' =>
                            (float) (
                                $trend->inflation_rate
                                ?? 0
                            ),

                        'inflation_change' =>
                            (float) (
                                $trend->inflation_change
                                ?? 0
                            ),

                        'market_impact_score' =>
                            (float) (
                                $trend->market_impact_score
                                ?? 0
                            ),

                        'trend_status' =>
                            $trend->trend_status
                            ?? 'stable',
                    ];
                }
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Port Statistics
        |--------------------------------------------------------------------------
        */

        $portStatistics = [
            'total' =>
                $country->ports->count(),

            'active' =>
                $country
                    ->ports
                    ->where(
                        'status',
                        'active'
                    )
                    ->count(),

            'high_congestion' =>
                $country
                    ->ports
                    ->where(
                        'congestion_level',
                        '>=',
                        70
                    )
                    ->count(),

            'high_risk' =>
                $country
                    ->ports
                    ->where(
                        'risk_score',
                        '>=',
                        70
                    )
                    ->count(),

            'average_risk' =>
                round(
                    (float) (
                        $country
                            ->ports
                            ->avg('risk_score')
                        ?? 0
                    ),
                    2
                ),
        ];

        /*
        |--------------------------------------------------------------------------
        | News Statistics
        |--------------------------------------------------------------------------
        */

        $newsStatistics = [
            'total' =>
                $country->news->count(),

            'positive' =>
                $country
                    ->news
                    ->where(
                        'sentiment',
                        'positive'
                    )
                    ->count(),

            'neutral' =>
                $country
                    ->news
                    ->where(
                        'sentiment',
                        'neutral'
                    )
                    ->count(),

            'negative' =>
                $country
                    ->news
                    ->where(
                        'sentiment',
                        'negative'
                    )
                    ->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Return View
        |--------------------------------------------------------------------------
        */

        return view(
            'countries.show',
            [
                'country' =>
                    $country,

                'latestEconomicIndicator' =>
                    $latestEconomicIndicator,

                'latestWeather' =>
                    $latestWeather,

                'latestCurrency' =>
                    $latestCurrency,

                'latestRisk' =>
                    $latestRisk,

                'latestMarketTrend' =>
                    $latestMarketTrend,

                'economicChart' =>
                    $economicChart,

                'weatherChart' =>
                    $weatherChart,

                'currencyChart' =>
                    $currencyChart,

                'riskChart' =>
                    $riskChart,

                'marketTrendChart' =>
                    $marketTrendChart,

                'portStatistics' =>
                    $portStatistics,

                'newsStatistics' =>
                    $newsStatistics,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Synchronize Country
    |--------------------------------------------------------------------------
    |
    | POST /api/v1/countries/{country}/sync
    |
    */

    public function sync(
        Country $country
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Check Active Country
        |--------------------------------------------------------------------------
        */

        if (! $country->is_active) {
            return response()->json(
                [
                    'success' => false,

                    'message' =>
                        'Synchronization is not available for inactive countries.',
                ],
                422
            );
        }

        try {
            /*
            |--------------------------------------------------------------------------
            | Global Synchronization
            |--------------------------------------------------------------------------
            |
            | Menggunakan GlobalSyncService agar proses API dan Artisan Command
            | menggunakan alur sinkronisasi yang sama.
            |
            */

            $result = $this
                ->globalSyncService
                ->syncCountry($country);

            /*
            |--------------------------------------------------------------------------
            | Reload Country
            |--------------------------------------------------------------------------
            */

            $country->refresh();

            $country->load([
                'latestRiskScore',

                'latestMarketTrend',

                'latestWeatherRecord',

                'latestCurrencyRate',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Overall Status
            |--------------------------------------------------------------------------
            */

            $success = (bool) (
                $result['summary']['success']
                ?? false
            );

            /*
            |--------------------------------------------------------------------------
            | JSON Response
            |--------------------------------------------------------------------------
            */

            return response()->json(
                [
                    'success' =>
                        $success,

                    'message' =>
                        $success
                            ? 'All country data synchronized successfully.'
                            : 'Country synchronization completed with some failures.',

                    'data' => [
                        'country' =>
                            $country,

                        'synchronization' =>
                            $result,
                    ],
                ],
                $success ? 200 : 207
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(
                [
                    'success' => false,

                    'message' =>
                        'Country synchronization failed.',

                    'error' =>
                        config('app.debug')
                            ? $exception->getMessage()
                            : null,
                ],
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Load Country Relations
    |--------------------------------------------------------------------------
    */

    private function loadCountryRelations(
        Country $country
    ): void {
        $country->load([
            /*
            |--------------------------------------------------------------------------
            | Ports
            |--------------------------------------------------------------------------
            */

            'ports' =>
                fn ($query) =>
                    $query
                        ->orderBy('name'),

            /*
            |--------------------------------------------------------------------------
            | Latest Data
            |--------------------------------------------------------------------------
            */

            'latestRiskScore',

            'latestMarketTrend',

            'latestWeatherRecord',

            'latestCurrencyRate',

            /*
            |--------------------------------------------------------------------------
            | Market Trends
            |--------------------------------------------------------------------------
            */

            'marketTrends' =>
                fn ($query) =>
                    $query
                        ->latest(
                            'recorded_at'
                        )
                        ->limit(30),

            /*
            |--------------------------------------------------------------------------
            | Weather Records
            |--------------------------------------------------------------------------
            */

            'weatherRecords' =>
                fn ($query) =>
                    $query
                        ->latest(
                            'recorded_at'
                        )
                        ->limit(30),

            /*
            |--------------------------------------------------------------------------
            | Currency Rates
            |--------------------------------------------------------------------------
            */

            'currencyRates' =>
                fn ($query) =>
                    $query
                        ->latest(
                            'recorded_at'
                        )
                        ->limit(30),

            /*
            |--------------------------------------------------------------------------
            | Economic Indicators
            |--------------------------------------------------------------------------
            */

            'economicIndicators' =>
                fn ($query) =>
                    $query
                        ->latest('year')
                        ->limit(10),

            /*
            |--------------------------------------------------------------------------
            | Risk Scores
            |--------------------------------------------------------------------------
            */

            'riskScores' =>
                fn ($query) =>
                    $query
                        ->latest(
                            'calculated_at'
                        )
                        ->limit(30),

            /*
            |--------------------------------------------------------------------------
            | News
            |--------------------------------------------------------------------------
            */

            'news' =>
                fn ($query) =>
                    $query
                        ->latest(
                            'published_at'
                        )
                        ->limit(20),
        ]);
    }
}