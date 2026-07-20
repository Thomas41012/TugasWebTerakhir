<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\MarketTrend;
use Illuminate\Database\Seeder;

class MarketTrendSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Country::with([
            'currencyRates',
            'economicIndicators'
        ])->get() as $country) {
            for ($day = 29; $day >= 0; $day--) {
                $currency = $country->currencyRates()
                    ->orderBy('recorded_at')
                    ->skip(29 - $day)
                    ->first();

                $economic = $country->economicIndicators()
                    ->latest('year')
                    ->first();

                $exchangeChange = $currency?->percentage_change ?? 0;
                $inflation = $economic?->inflation_rate ?? 0;

                $impactScore = min(
                    100,
                    abs((float) $exchangeChange) * 10
                    + ((float) $inflation * 5)
                );

                MarketTrend::create([
                    'country_id' => $country->id,
                    'exchange_rate' => $currency?->exchange_rate ?? 0,
                    'exchange_rate_change' => $exchangeChange,
                    'inflation_rate' => $inflation,
                    'inflation_change' => fake()->randomFloat(2, -1, 1),
                    'market_impact_score' => $impactScore,
                    'trend_status' => $impactScore >= 60
                        ? 'negative'
                        : ($impactScore >= 30 ? 'volatile' : 'stable'),
                    'recorded_at' => now()->subDays($day),
                ]);
            }
        }
    }
}