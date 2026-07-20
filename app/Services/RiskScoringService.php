<?php

namespace App\Services;

use App\Models\Country;
use App\Models\RiskScore;
use Illuminate\Support\Collection;

class RiskScoringService
{
    /*
    |--------------------------------------------------------------------------
    | Main Risk Weights
    |--------------------------------------------------------------------------
    |
    | Total bobot harus = 1.00 atau 100%.
    |
    */

    private const WEATHER_WEIGHT = 0.25;

    private const INFLATION_WEIGHT = 0.15;

    private const CURRENCY_WEIGHT = 0.10;

    private const POLITICAL_WEIGHT = 0.35;

    private const PORT_WEIGHT = 0.15;

    /*
    |--------------------------------------------------------------------------
    | Calculate Country Risk
    |--------------------------------------------------------------------------
    */

    public function calculate(Country $country): RiskScore
    {
        /*
        |--------------------------------------------------------------------------
        | Clear Loaded Relationships
        |--------------------------------------------------------------------------
        |
        | Diperlukan setelah proses sinkronisasi agar service membaca data
        | terbaru yang baru saja dimasukkan ke database.
        |
        */

        $country->unsetRelation('latestWeatherRecord');

        $country->unsetRelation('latestCurrencyRate');

        $country->unsetRelation('latestEconomicIndicator');

        $country->unsetRelation('news');

        $country->unsetRelation('ports');

        /*
        |--------------------------------------------------------------------------
        | Load Latest Intelligence Data
        |--------------------------------------------------------------------------
        */

        $country->load([
            'latestWeatherRecord',

            'latestCurrencyRate',

            'latestEconomicIndicator',

            'news' => function ($query): void {
                $query
                    ->whereNotNull('published_at')
                    ->where(
                        'published_at',
                        '>=',
                        now()->subDays(7)
                    )
                    ->latest('published_at');
            },

            'ports',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Individual Risk Scores
        |--------------------------------------------------------------------------
        */

        $weatherScore = $this
            ->calculateWeatherScore($country);

        $inflationScore = $this
            ->calculateInflationScore($country);

        $currencyScore = $this
            ->calculateCurrencyScore($country);

        $politicalScore = $this
            ->calculatePoliticalScore($country);

        $portScore = $this
            ->calculatePortScore($country);

        /*
        |--------------------------------------------------------------------------
        | Weighted Risk Score
        |--------------------------------------------------------------------------
        */

        $weatherWeighted =
            $weatherScore
            * self::WEATHER_WEIGHT;

        $inflationWeighted =
            $inflationScore
            * self::INFLATION_WEIGHT;

        $currencyWeighted =
            $currencyScore
            * self::CURRENCY_WEIGHT;

        $politicalWeighted =
            $politicalScore
            * self::POLITICAL_WEIGHT;

        $portWeighted =
            $portScore
            * self::PORT_WEIGHT;

        /*
        |--------------------------------------------------------------------------
        | Total Score
        |--------------------------------------------------------------------------
        */

        $totalScore =
            $weatherWeighted
            + $inflationWeighted
            + $currencyWeighted
            + $politicalWeighted
            + $portWeighted;

        $totalScore = $this->normalizeScore(
            $totalScore
        );

        /*
        |--------------------------------------------------------------------------
        | Risk Level
        |--------------------------------------------------------------------------
        */

        $riskLevel = $this->getRiskLevel(
            $totalScore
        );

        /*
        |--------------------------------------------------------------------------
        | Save Risk Score
        |--------------------------------------------------------------------------
        */

        return RiskScore::create([
            'country_id' =>
                $country->id,

            'weather_score' =>
                $weatherScore,

            'inflation_score' =>
                $inflationScore,

            'currency_score' =>
                $currencyScore,

            'political_score' =>
                $politicalScore,

            'port_score' =>
                $portScore,

            'total_score' =>
                $totalScore,

            'risk_level' =>
                $riskLevel,

            'calculation_details' => [
                /*
                |--------------------------------------------------------------------------
                | Weight Configuration
                |--------------------------------------------------------------------------
                */

                'weights' => [
                    'weather' =>
                        self::WEATHER_WEIGHT,

                    'inflation' =>
                        self::INFLATION_WEIGHT,

                    'currency' =>
                        self::CURRENCY_WEIGHT,

                    'political' =>
                        self::POLITICAL_WEIGHT,

                    'port' =>
                        self::PORT_WEIGHT,
                ],

                /*
                |--------------------------------------------------------------------------
                | Raw Scores
                |--------------------------------------------------------------------------
                */

                'raw_scores' => [
                    'weather' =>
                        $weatherScore,

                    'inflation' =>
                        $inflationScore,

                    'currency' =>
                        $currencyScore,

                    'political' =>
                        $politicalScore,

                    'port' =>
                        $portScore,
                ],

                /*
                |--------------------------------------------------------------------------
                | Weighted Scores
                |--------------------------------------------------------------------------
                */

                'weighted_scores' => [
                    'weather' =>
                        round(
                            $weatherWeighted,
                            2
                        ),

                    'inflation' =>
                        round(
                            $inflationWeighted,
                            2
                        ),

                    'currency' =>
                        round(
                            $currencyWeighted,
                            2
                        ),

                    'political' =>
                        round(
                            $politicalWeighted,
                            2
                        ),

                    'port' =>
                        round(
                            $portWeighted,
                            2
                        ),
                ],

                /*
                |--------------------------------------------------------------------------
                | Data Information
                |--------------------------------------------------------------------------
                */

                'data_information' => [
                    'weather_available' =>
                        $country
                            ->latestWeatherRecord
                            !== null,

                    'currency_available' =>
                        $country
                            ->latestCurrencyRate
                            !== null,

                    'economic_available' =>
                        $country
                            ->latestEconomicIndicator
                            !== null,

                    'news_count' =>
                        $country
                            ->news
                            ->count(),

                    'ports_count' =>
                        $country
                            ->ports
                            ->count(),
                ],

                /*
                |--------------------------------------------------------------------------
                | Final Result
                |--------------------------------------------------------------------------
                */

                'total_score' =>
                    $totalScore,

                'risk_level' =>
                    $riskLevel,

                'calculated_at' =>
                    now()->toDateTimeString(),
            ],

            'calculated_at' => now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Sync Alias
    |--------------------------------------------------------------------------
    */

    public function sync(Country $country): RiskScore
    {
        return $this->calculate($country);
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate All Countries
    |--------------------------------------------------------------------------
    */

    public function calculateAllCountries(): Collection
    {
        return Country::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(
                fn (Country $country): RiskScore =>
                    $this->calculate($country)
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Sync All Countries Alias
    |--------------------------------------------------------------------------
    */

    public function syncAllCountries(): Collection
    {
        return $this->calculateAllCountries();
    }

    /*
    |--------------------------------------------------------------------------
    | Weather Risk
    |--------------------------------------------------------------------------
    */

    private function calculateWeatherScore(
        Country $country
    ): float {
        $weather =
            $country->latestWeatherRecord;

        if ($weather === null) {
            return 0.0;
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Weather Risk Score
        |--------------------------------------------------------------------------
        */

        if (
            isset($weather->weather_risk_score)
            && $weather->weather_risk_score !== null
            && (float) $weather->weather_risk_score > 0
        ) {
            return $this->normalizeScore(
                (float) $weather->weather_risk_score
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Temperature Risk
        |--------------------------------------------------------------------------
        */

        $temperatureRisk =
            $this->calculateTemperatureRisk(
                (float) (
                    $weather->temperature
                    ?? 0
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Wind Risk
        |--------------------------------------------------------------------------
        */

        $windRisk =
            $this->calculateWindRisk(
                (float) (
                    $weather->wind_speed
                    ?? 0
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Precipitation Risk
        |--------------------------------------------------------------------------
        */

        $precipitationRisk =
            $this->calculatePrecipitationRisk(
                (float) (
                    $weather->precipitation
                    ?? 0
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Weather Code Risk
        |--------------------------------------------------------------------------
        */

        $weatherCodeRisk =
            $this->calculateWeatherCodeRisk(
                (int) (
                    $weather->weather_code
                    ?? 0
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Weather Weighted Score
        |--------------------------------------------------------------------------
        |
        | Temperature   = 20%
        | Wind          = 30%
        | Precipitation = 20%
        | Weather Code  = 30%
        |
        */

        $score =
            ($temperatureRisk * 0.20)
            + ($windRisk * 0.30)
            + ($precipitationRisk * 0.20)
            + ($weatherCodeRisk * 0.30);

        return $this->normalizeScore($score);
    }

    /*
    |--------------------------------------------------------------------------
    | Temperature Risk
    |--------------------------------------------------------------------------
    */

    private function calculateTemperatureRisk(
        float $temperature
    ): float {
        return match (true) {
            $temperature >= 45 =>
                100.0,

            $temperature >= 40 =>
                80.0,

            $temperature >= 35 =>
                60.0,

            $temperature >= 30 =>
                30.0,

            $temperature <= -20 =>
                100.0,

            $temperature <= -10 =>
                80.0,

            $temperature <= 0 =>
                60.0,

            $temperature <= 5 =>
                30.0,

            default =>
                10.0,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Wind Risk
    |--------------------------------------------------------------------------
    */

    private function calculateWindRisk(
        float $windSpeed
    ): float {
        return match (true) {
            $windSpeed >= 120 =>
                100.0,

            $windSpeed >= 90 =>
                85.0,

            $windSpeed >= 60 =>
                70.0,

            $windSpeed >= 40 =>
                50.0,

            $windSpeed >= 20 =>
                25.0,

            default =>
                5.0,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Precipitation Risk
    |--------------------------------------------------------------------------
    */

    private function calculatePrecipitationRisk(
        float $precipitation
    ): float {
        return match (true) {
            $precipitation >= 100 =>
                100.0,

            $precipitation >= 50 =>
                80.0,

            $precipitation >= 25 =>
                60.0,

            $precipitation >= 10 =>
                40.0,

            $precipitation > 0 =>
                15.0,

            default =>
                0.0,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | WMO Weather Code Risk
    |--------------------------------------------------------------------------
    */

    private function calculateWeatherCodeRisk(
        int $weatherCode
    ): float {
        return match (true) {
            in_array(
                $weatherCode,
                [95, 96, 99],
                true
            ) => 100.0,

            in_array(
                $weatherCode,
                [85, 86],
                true
            ) => 85.0,

            in_array(
                $weatherCode,
                [80, 81, 82],
                true
            ) => 75.0,

            in_array(
                $weatherCode,
                [71, 73, 75, 77],
                true
            ) => 70.0,

            in_array(
                $weatherCode,
                [66, 67],
                true
            ) => 65.0,

            in_array(
                $weatherCode,
                [61, 63, 65],
                true
            ) => 50.0,

            in_array(
                $weatherCode,
                [56, 57],
                true
            ) => 45.0,

            in_array(
                $weatherCode,
                [51, 53, 55],
                true
            ) => 30.0,

            in_array(
                $weatherCode,
                [45, 48],
                true
            ) => 25.0,

            in_array(
                $weatherCode,
                [1, 2, 3],
                true
            ) => 10.0,

            default =>
                0.0,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Inflation Risk
    |--------------------------------------------------------------------------
    */

    private function calculateInflationScore(
        Country $country
    ): float {
        $economic =
            $country->latestEconomicIndicator;

        if ($economic === null) {
            return 0.0;
        }

        $inflation = (float) (
            $economic->inflation_rate
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Inflation Risk Classification
        |--------------------------------------------------------------------------
        */

        return $this->normalizeScore(
            match (true) {
                $inflation >= 30 =>
                    100.0,

                $inflation >= 20 =>
                    90.0,

                $inflation >= 15 =>
                    80.0,

                $inflation >= 10 =>
                    70.0,

                $inflation >= 7 =>
                    55.0,

                $inflation >= 5 =>
                    40.0,

                $inflation >= 3 =>
                    25.0,

                $inflation >= 0 =>
                    10.0,

                $inflation >= -2 =>
                    25.0,

                $inflation >= -5 =>
                    50.0,

                default =>
                    80.0,
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Currency Risk
    |--------------------------------------------------------------------------
    */

    private function calculateCurrencyScore(
        Country $country
    ): float {
        $currency =
            $country->latestCurrencyRate;

        if ($currency === null) {
            return 0.0;
        }

        $percentageChange = abs(
            (float) (
                $currency->percentage_change
                ?? 0
            )
        );

        return $this->normalizeScore(
            match (true) {
                $percentageChange >= 20 =>
                    100.0,

                $percentageChange >= 15 =>
                    90.0,

                $percentageChange >= 10 =>
                    75.0,

                $percentageChange >= 7 =>
                    60.0,

                $percentageChange >= 5 =>
                    45.0,

                $percentageChange >= 3 =>
                    30.0,

                $percentageChange >= 1 =>
                    15.0,

                default =>
                    5.0,
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Political / News Risk
    |--------------------------------------------------------------------------
    */

    private function calculatePoliticalScore(
        Country $country
    ): float {
        $recentNews = $country->news;

        if ($recentNews->isEmpty()) {
            return 0.0;
        }

        $totalNews =
            $recentNews->count();

        $negativeNews =
            $recentNews
                ->filter(
                    fn ($news): bool =>
                        strtolower(
                            (string) $news->sentiment
                        ) === 'negative'
                )
                ->count();

        $negativeRatio =
            $negativeNews
            / max(1, $totalNews);

        $ratioScore =
            $negativeRatio * 100;

        /*
        |--------------------------------------------------------------------------
        | Negative Sentiment Intensity
        |--------------------------------------------------------------------------
        */

        $negativeIntensity =
            $recentNews
                ->avg(
                    fn ($news): float =>
                        (float) (
                            $news->negative_score
                            ?? 0
                        )
                );

        /*
         * Jika SentimentService menghasilkan nilai 0 - 1,
         * konversi menjadi 0 - 100.
         */

        if (
            $negativeIntensity > 0
            && $negativeIntensity <= 1
        ) {
            $negativeIntensity *= 100;
        }

        /*
        |--------------------------------------------------------------------------
        | Political Score
        |--------------------------------------------------------------------------
        |
        | Negative News Ratio = 70%
        | Sentiment Intensity = 30%
        |
        */

        $score =
            ($ratioScore * 0.70)
            + ($negativeIntensity * 0.30);

        return $this->normalizeScore($score);
    }

    /*
    |--------------------------------------------------------------------------
    | Port Risk
    |--------------------------------------------------------------------------
    */

    private function calculatePortScore(
        Country $country
    ): float {
        if ($country->ports->isEmpty()) {
            return 0.0;
        }

        $portsWithRisk =
            $country
                ->ports
                ->filter(
                    fn ($port): bool =>
                        $port->risk_score !== null
                );

        if ($portsWithRisk->isEmpty()) {
            return 0.0;
        }

        /*
        |--------------------------------------------------------------------------
        | Average Port Risk
        |--------------------------------------------------------------------------
        */

        $averageRisk = (float) (
            $portsWithRisk->avg('risk_score')
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | High Risk Port Ratio
        |--------------------------------------------------------------------------
        */

        $highRiskPorts =
            $portsWithRisk
                ->filter(
                    fn ($port): bool =>
                        (float) $port->risk_score >= 70
                )
                ->count();

        $highRiskRatio =
            (
                $highRiskPorts
                / max(
                    1,
                    $portsWithRisk->count()
                )
            ) * 100;

        /*
        |--------------------------------------------------------------------------
        | Final Port Score
        |--------------------------------------------------------------------------
        |
        | Average Risk    = 70%
        | High Risk Ratio = 30%
        |
        */

        $score =
            ($averageRisk * 0.70)
            + ($highRiskRatio * 0.30);

        return $this->normalizeScore($score);
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Score
    |--------------------------------------------------------------------------
    */

    private function normalizeScore(
        float $score
    ): float {
        return round(
            min(
                100,
                max(
                    0,
                    $score
                )
            ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Determine Risk Level
    |--------------------------------------------------------------------------
    */

    private function getRiskLevel(
        float $score
    ): string {
        return match (true) {
            $score >= 75 =>
                'critical',

            $score >= 50 =>
                'high',

            $score >= 25 =>
                'medium',

            default =>
                'low',
        };
    }
}