<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\RiskScore;
use Illuminate\Database\Seeder;

class RiskScoreSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Country::all() as $country) {
            for ($day = 29; $day >= 0; $day--) {
                $weather = fake()->randomFloat(2, 10, 85);
                $inflation = fake()->randomFloat(2, 5, 75);
                $currency = fake()->randomFloat(2, 5, 70);
                $political = fake()->randomFloat(2, 5, 90);

                $port = $country->ports()->avg('risk_score') ?? 0;

                /*
                 * Weighted Risk Model:
                 *
                 * Weather   = 30%
                 * Inflation = 20%
                 * Political = 40%
                 * Currency  = 10%
                 *
                 * Port digunakan sebagai indikator tambahan.
                 */

                $total =
                    ($weather * 0.25)
                    + ($inflation * 0.15)
                    + ($currency * 0.10)
                    + ($political * 0.35)
                    + ($port * 0.15);

                $level = match (true) {
                    $total >= 70 => 'critical',
                    $total >= 50 => 'high',
                    $total >= 30 => 'medium',
                    default => 'low',
                };

                RiskScore::create([
                    'country_id' => $country->id,
                    'weather_score' => $weather,
                    'inflation_score' => $inflation,
                    'currency_score' => $currency,
                    'political_score' => $political,
                    'port_score' => $port,
                    'total_score' => round($total, 2),
                    'risk_level' => $level,
                    'calculation_details' => [
                        'weather_weight' => 0.25,
                        'inflation_weight' => 0.15,
                        'currency_weight' => 0.10,
                        'political_weight' => 0.35,
                        'port_weight' => 0.15,
                    ],
                    'calculated_at' => now()->subDays($day),
                ]);
            }
        }
    }
}