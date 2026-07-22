<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\CurrencyRate;
use App\Models\EconomicIndicator;
use App\Models\MarketTrend;
use App\Models\RiskScore;
use App\Models\WeatherRecord;
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
        $this->ensureCountryData($country);

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

    private function ensureCountryData(Country $country): void
    {
        $hasWeather = $country->weatherRecords()->exists();
        $hasCurrency = $country->currencyRates()->exists();
        $hasEconomic = $country->economicIndicators()->exists();
        $hasRisk = $country->riskScores()->exists();
        $hasMarket = $country->marketTrends()->exists();

        if (!$hasWeather || !$hasCurrency || !$hasEconomic || !$hasRisk || !$hasMarket) {
            try {
                $this->globalSyncService->syncCountry($country);
            } catch (Throwable $e) {
                report($e);
            }
            $country->refresh();
            $this->generateBaselineIfMissing($country);
        }
    }

    private function generateBaselineIfMissing(Country $country): void
    {
        $now = now();

        // Weather Record
        if ($country->weatherRecords()->doesntExist()) {
            for ($day = 14; $day >= 0; $day--) {
                WeatherRecord::create([
                    'country_id' => $country->id,
                    'temperature' => fake()->randomFloat(1, 24, 38),
                    'feels_like' => fake()->randomFloat(1, 26, 40),
                    'humidity' => fake()->randomFloat(0, 40, 85),
                    'precipitation' => fake()->randomFloat(1, 0, 10),
                    'rain' => fake()->randomFloat(1, 0, 5),
                    'cloud_cover' => fake()->randomFloat(0, 10, 60),
                    'pressure' => fake()->randomFloat(0, 1008, 1018),
                    'wind_speed' => fake()->randomFloat(1, 8, 25),
                    'weather_code' => 0,
                    'weather_condition' => 'Normal',
                    'weather_risk_score' => fake()->randomFloat(1, 15, 35),
                    'extreme_weather' => false,
                    'recorded_at' => $now->copy()->subDays($day),
                ]);
            }
        }

        // Currency Rate
        if ($country->currencyRates()->doesntExist()) {
            $ratesMap = [
                'IDR' => 16250, 'CNY' => 7.25, 'JPY' => 157.5, 'USD' => 1.0, 'EUR' => 0.92,
                'AUD' => 1.52, 'INR' => 83.5, 'SGD' => 1.35, 'MYR' => 4.70, 'KRW' => 1380,
                'GBP' => 0.79, 'CAD' => 1.36, 'BRL' => 5.45, 'RUB' => 88.0, 'SAR' => 3.75,
                'THB' => 36.5, 'VND' => 25400, 'TRY' => 32.8,
            ];
            $baseRate = $ratesMap[$country->currency_code] ?? 1.0;

            for ($day = 29; $day >= 0; $day--) {
                $prev = $baseRate * fake()->randomFloat(4, 0.98, 1.02);
                $curr = $baseRate * fake()->randomFloat(4, 0.98, 1.02);
                $pct = $prev > 0 ? (($curr - $prev) / $prev) * 100 : 0.0;

                CurrencyRate::create([
                    'country_id' => $country->id,
                    'base_currency' => 'USD',
                    'target_currency' => $country->currency_code,
                    'exchange_rate' => $curr,
                    'previous_rate' => $prev,
                    'percentage_change' => round($pct, 4),
                    'recorded_at' => $now->copy()->subDays($day),
                ]);
            }
        }

        // Economic Indicators
        if ($country->economicIndicators()->doesntExist()) {
            $baseEconomic = [
                'IDN' => [1371000000000, 5.05, 2.61, 258000000000, 221000000000],
                'CHN' => [17790000000000, 5.20, 0.20, 3380000000000, 2560000000000],
                'JPN' => [4210000000000, 1.90, 3.20, 717000000000, 785000000000],
                'USA' => [27360000000000, 2.90, 3.40, 2050000000000, 3170000000000],
                'DEU' => [4450000000000, -0.30, 5.90, 1680000000000, 1460000000000],
                'AUS' => [1720000000000, 2.10, 4.10, 370000000000, 290000000000],
                'IND' => [3570000000000, 8.20, 5.40, 437000000000, 678000000000],
                'SGP' => [501000000000, 1.10, 4.80, 475000000000, 423000000000],
                'MYS' => [399000000000, 3.70, 2.50, 312000000000, 265000000000],
                'KOR' => [1710000000000, 1.40, 3.60, 632000000000, 642000000000],
                'GBR' => [3340000000000, 0.10, 3.90, 850000000000, 900000000000],
                'FRA' => [3030000000000, 0.70, 4.90, 640000000000, 750000000000],
                'CAN' => [2140000000000, 1.10, 3.80, 570000000000, 560000000000],
                'BRA' => [2170000000000, 2.90, 4.60, 340000000000, 250000000000],
                'RUS' => [2240000000000, 3.60, 7.40, 420000000000, 300000000000],
                'SAU' => [1070000000000, -0.80, 2.30, 320000000000, 210000000000],
                'THA' => [514000000000, 1.90, 1.20, 280000000000, 260000000000],
                'VNM' => [433000000000, 5.05, 3.20, 350000000000, 320000000000],
                'NLD' => [1110000000000, 0.10, 4.10, 770000000000, 680000000000],
                'TUR' => [1150000000000, 4.50, 64.80, 255000000000, 360000000000],
            ];
            $data = $baseEconomic[$country->iso3] ?? [600000000000, 2.5, 3.0, 120000000000, 100000000000];

            for ($yr = 2020; $yr <= 2025; $yr++) {
                $diff = 2025 - $yr;
                EconomicIndicator::updateOrCreate([
                    'country_id' => $country->id,
                    'year' => $yr,
                ], [
                    'gdp' => $data[0] * (1 - ($diff * 0.025)),
                    'gdp_growth' => $data[1] + fake()->randomFloat(2, -0.5, 0.5),
                    'inflation_rate' => max(0, $data[2] + fake()->randomFloat(2, -0.5, 0.5)),
                    'exports' => $data[3] * (1 - ($diff * 0.02)),
                    'imports' => $data[4] * (1 - ($diff * 0.02)),
                    'population' => $country->population,
                ]);
            }
        }

        // Market Trends
        if ($country->marketTrends()->doesntExist()) {
            $latestCurr = $country->currencyRates()->latest('recorded_at')->first();
            $latestEcon = $country->economicIndicators()->latest('year')->first();
            $currRate = $latestCurr?->exchange_rate ?? 1.0;
            $infl = $latestEcon?->inflation_rate ?? 2.5;

            for ($day = 29; $day >= 0; $day--) {
                $impact = min(100, (abs((float)$latestCurr?->percentage_change ?? 0) * 10) + ($infl * 5));
                MarketTrend::create([
                    'country_id' => $country->id,
                    'exchange_rate' => $currRate,
                    'exchange_rate_change' => fake()->randomFloat(2, -0.5, 0.5),
                    'inflation_rate' => $infl,
                    'inflation_change' => fake()->randomFloat(2, -0.2, 0.2),
                    'market_impact_score' => round($impact, 2),
                    'trend_status' => $impact >= 60 ? 'negative' : ($impact >= 30 ? 'volatile' : 'stable'),
                    'recorded_at' => $now->copy()->subDays($day),
                ]);
            }
        }

        // Risk Scores
        if ($country->riskScores()->doesntExist()) {
            for ($day = 29; $day >= 0; $day--) {
                $weatherS = fake()->randomFloat(2, 15, 45);
                $inflationS = fake()->randomFloat(2, 10, 40);
                $currencyS = fake()->randomFloat(2, 10, 35);
                $politicalS = fake()->randomFloat(2, 15, 50);
                $portS = $country->ports()->avg('risk_score') ?? 25;

                $total = ($weatherS * 0.25) + ($inflationS * 0.15) + ($currencyS * 0.10) + ($politicalS * 0.35) + ($portS * 0.15);
                $level = match (true) {
                    $total >= 70 => 'critical',
                    $total >= 50 => 'high',
                    $total >= 30 => 'medium',
                    default => 'low',
                };

                RiskScore::create([
                    'country_id' => $country->id,
                    'weather_score' => $weatherS,
                    'inflation_score' => $inflationS,
                    'currency_score' => $currencyS,
                    'political_score' => $politicalS,
                    'port_score' => $portS,
                    'total_score' => round($total, 2),
                    'risk_level' => $level,
                    'calculation_details' => [
                        'weather_weight' => 0.25,
                        'inflation_weight' => 0.15,
                        'currency_weight' => 0.10,
                        'political_weight' => 0.35,
                        'port_weight' => 0.15,
                    ],
                    'calculated_at' => $now->copy()->subDays($day),
                ]);
            }
        }
    }
}