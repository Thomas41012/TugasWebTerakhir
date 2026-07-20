<?php

namespace App\Services;

use App\Models\Country;
use App\Models\CurrencyRate;
use App\Models\EconomicIndicator;
use App\Models\MarketTrend;
use RuntimeException;
use Throwable;

class MarketTrendService
{
    /*
    |--------------------------------------------------------------------------
    | Calculate And Store Market Trend
    |--------------------------------------------------------------------------
    |
    | Method utama untuk menghitung market trend satu negara.
    |
    */

    public function calculate(Country $country): MarketTrend
    {
        try {
            /*
            |--------------------------------------------------------------------------
            | Latest Currency Rate
            |--------------------------------------------------------------------------
            */

            $latestCurrency = CurrencyRate::query()
                ->where('country_id', $country->id)
                ->latest('recorded_at')
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Previous Currency Rate
            |--------------------------------------------------------------------------
            */

            $previousCurrency = CurrencyRate::query()
                ->where('country_id', $country->id)
                ->when(
                    $latestCurrency,
                    fn ($query) =>
                        $query->where(
                            'id',
                            '!=',
                            $latestCurrency->id
                        )
                )
                ->latest('recorded_at')
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Latest Economic Indicator
            |--------------------------------------------------------------------------
            */

            $latestEconomic = EconomicIndicator::query()
                ->where('country_id', $country->id)
                ->latest('year')
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Previous Economic Indicator
            |--------------------------------------------------------------------------
            */

            $previousEconomic = EconomicIndicator::query()
                ->where('country_id', $country->id)
                ->when(
                    $latestEconomic,
                    fn ($query) =>
                        $query->where(
                            'id',
                            '!=',
                            $latestEconomic->id
                        )
                )
                ->latest('year')
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Validate Available Data
            |--------------------------------------------------------------------------
            */

            if (
                $latestCurrency === null
                && $latestEconomic === null
            ) {
                throw new RuntimeException(
                    "Data currency dan ekonomi tidak tersedia untuk {$country->name}."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Exchange Rate
            |--------------------------------------------------------------------------
            */

            $exchangeRate = $latestCurrency
                ? (float) $latestCurrency->exchange_rate
                : 0.0;

            /*
            |--------------------------------------------------------------------------
            | Exchange Rate Change
            |--------------------------------------------------------------------------
            */

            $exchangeRateChange =
                $this->calculateExchangeRateChange(
                    latestCurrency: $latestCurrency,
                    previousCurrency: $previousCurrency
                );

            /*
            |--------------------------------------------------------------------------
            | Inflation Rate
            |--------------------------------------------------------------------------
            */

            $inflationRate =
                $latestEconomic?->inflation_rate !== null
                    ? (float) $latestEconomic->inflation_rate
                    : 0.0;

            /*
            |--------------------------------------------------------------------------
            | Inflation Change
            |--------------------------------------------------------------------------
            */

            $inflationChange =
                $this->calculateInflationChange(
                    latestEconomic: $latestEconomic,
                    previousEconomic: $previousEconomic
                );

            /*
            |--------------------------------------------------------------------------
            | Market Impact Score
            |--------------------------------------------------------------------------
            */

            $marketImpactScore =
                $this->calculateMarketImpactScore(
                    exchangeRateChange:
                        $exchangeRateChange,

                    inflationRate:
                        $inflationRate,

                    inflationChange:
                        $inflationChange
                );

            /*
            |--------------------------------------------------------------------------
            | Trend Status
            |--------------------------------------------------------------------------
            */

            $trendStatus =
                $this->determineTrendStatus(
                    exchangeRateChange:
                        $exchangeRateChange,

                    inflationRate:
                        $inflationRate,

                    inflationChange:
                        $inflationChange,

                    marketImpactScore:
                        $marketImpactScore
                );

            /*
            |--------------------------------------------------------------------------
            | Create Market Trend
            |--------------------------------------------------------------------------
            */

            return MarketTrend::create([
                'country_id' =>
                    $country->id,

                'exchange_rate' =>
                    $exchangeRate,

                'exchange_rate_change' =>
                    round(
                        $exchangeRateChange,
                        4
                    ),

                'inflation_rate' =>
                    round(
                        $inflationRate,
                        3
                    ),

                'inflation_change' =>
                    round(
                        $inflationChange,
                        3
                    ),

                'market_impact_score' =>
                    round(
                        $marketImpactScore,
                        2
                    ),

                'trend_status' =>
                    $trendStatus,

                'recorded_at' =>
                    now(),
            ]);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Gagal menghitung market trend {$country->name}: "
                . $exception->getMessage(),
                previous: $exception
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Alias Calculate Country
    |--------------------------------------------------------------------------
    */

    public function calculateCountry(
        Country $country
    ): ?MarketTrend {
        try {
            return $this->calculate(
                $country
            );
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate All Active Countries
    |--------------------------------------------------------------------------
    */

    public function calculateAllCountries(): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
        ];

        Country::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->each(
                function (
                    Country $country
                ) use (&$results): void {
                    try {
                        $this->calculate(
                            $country
                        );

                        $results['success']++;
                    } catch (Throwable $exception) {
                        report($exception);

                        $results['failed']++;
                    }
                }
            );

        return $results;
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Exchange Rate Change
    |--------------------------------------------------------------------------
    */

    private function calculateExchangeRateChange(
        ?CurrencyRate $latestCurrency,
        ?CurrencyRate $previousCurrency
    ): float {
        /*
         * Gunakan percentage_change yang sudah dihitung
         * oleh CurrencyService jika tersedia.
         */

        if (
            $latestCurrency !== null
            && $latestCurrency->percentage_change !== null
        ) {
            return (float)
                $latestCurrency->percentage_change;
        }

        /*
         * Jika percentage_change tidak tersedia,
         * hitung berdasarkan dua record terakhir.
         */

        if (
            $latestCurrency === null
            || $previousCurrency === null
        ) {
            return 0.0;
        }

        $latestRate =
            (float) $latestCurrency->exchange_rate;

        $previousRate =
            (float) $previousCurrency->exchange_rate;

        if ($previousRate == 0.0) {
            return 0.0;
        }

        return (
            ($latestRate - $previousRate)
            / $previousRate
        ) * 100;
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Inflation Change
    |--------------------------------------------------------------------------
    */

    private function calculateInflationChange(
        ?EconomicIndicator $latestEconomic,
        ?EconomicIndicator $previousEconomic
    ): float {
        if (
            $latestEconomic === null
            || $previousEconomic === null
            || $latestEconomic->inflation_rate === null
            || $previousEconomic->inflation_rate === null
        ) {
            return 0.0;
        }

        return
            (float) $latestEconomic->inflation_rate
            -
            (float) $previousEconomic->inflation_rate;
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Market Impact Score
    |--------------------------------------------------------------------------
    |
    | Score:
    |
    | Exchange volatility = 40%
    | Inflation level      = 35%
    | Inflation change     = 25%
    |
    | Final score berada pada range 0 - 100.
    |
    */

    private function calculateMarketImpactScore(
        float $exchangeRateChange,
        float $inflationRate,
        float $inflationChange
    ): float {
        /*
        |--------------------------------------------------------------------------
        | Currency Volatility Score
        |--------------------------------------------------------------------------
        |
        | Perubahan kurs 10% atau lebih dianggap sangat tinggi.
        |
        */

        $currencyScore = min(
            100,
            abs($exchangeRateChange) * 10
        );

        /*
        |--------------------------------------------------------------------------
        | Inflation Level Score
        |--------------------------------------------------------------------------
        |
        | Inflasi 15% atau lebih dianggap sangat tinggi.
        |
        */

        $inflationScore = min(
            100,
            max(
                0,
                abs($inflationRate) / 15 * 100
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Inflation Change Score
        |--------------------------------------------------------------------------
        |
        | Perubahan inflasi 5% atau lebih dianggap tinggi.
        |
        */

        $inflationChangeScore = min(
            100,
            abs($inflationChange) / 5 * 100
        );

        /*
        |--------------------------------------------------------------------------
        | Weighted Score
        |--------------------------------------------------------------------------
        */

        $score =
            ($currencyScore * 0.40)
            +
            ($inflationScore * 0.35)
            +
            ($inflationChangeScore * 0.25);

        return min(
            100,
            max(
                0,
                $score
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Determine Trend Status
    |--------------------------------------------------------------------------
    */

    private function determineTrendStatus(
        float $exchangeRateChange,
        float $inflationRate,
        float $inflationChange,
        float $marketImpactScore
    ): string {
        /*
        |--------------------------------------------------------------------------
        | Negative Market Trend
        |--------------------------------------------------------------------------
        |
        | Risiko pasar tinggi atau inflasi meningkat tajam.
        |
        */

        if (
            $marketImpactScore >= 60
            || $inflationRate >= 10
            || $inflationChange >= 3
        ) {
            return 'negative';
        }

        /*
        |--------------------------------------------------------------------------
        | Positive Market Trend
        |--------------------------------------------------------------------------
        |
        | Risiko pasar rendah, inflasi relatif rendah,
        | dan perubahan inflasi tidak meningkat signifikan.
        |
        */

        if (
            $marketImpactScore < 30
            && $inflationRate < 5
            && $inflationChange <= 0.5
            && abs($exchangeRateChange) < 3
        ) {
            return 'positive';
        }

        /*
        |--------------------------------------------------------------------------
        | Neutral Market Trend
        |--------------------------------------------------------------------------
        */

        return 'neutral';
    }
}