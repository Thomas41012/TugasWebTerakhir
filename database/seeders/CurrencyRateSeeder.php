<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\CurrencyRate;
use Illuminate\Database\Seeder;

class CurrencyRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            'IDR' => 16250,
            'CNY' => 7.25,
            'JPY' => 157.50,
            'USD' => 1,
            'EUR' => 0.92,
            'AUD' => 1.52,
            'INR' => 83.50,
            'SGD' => 1.35,
            'MYR' => 4.70,
            'KRW' => 1380,
            'GBP' => 0.79,
            'CAD' => 1.36,
            'BRL' => 5.45,
            'RUB' => 88.00,
            'SAR' => 3.75,
            'THB' => 36.50,
            'VND' => 25400,
            'TRY' => 32.80,
        ];

        foreach (Country::all() as $country) {
            $baseRate = $rates[$country->currency_code] ?? 1.0;

            for ($day = 29; $day >= 0; $day--) {
                $previousRate = $baseRate * fake()->randomFloat(4, 0.97, 1.03);
                $currentRate = $baseRate * fake()->randomFloat(4, 0.97, 1.03);

                $percentageChange = $previousRate > 0
                    ? (($currentRate - $previousRate) / $previousRate) * 100
                    : 0;

                CurrencyRate::create([
                    'country_id' => $country->id,
                    'base_currency' => 'USD',
                    'target_currency' => $country->currency_code,
                    'exchange_rate' => $currentRate,
                    'previous_rate' => $previousRate,
                    'percentage_change' => $percentageChange,
                    'recorded_at' => now()->subDays($day),
                ]);
            }
        }
    }
}