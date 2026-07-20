<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\WeatherRecord;
use Illuminate\Database\Seeder;

class WeatherRecordSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Country::all() as $country) {
            for ($day = 14; $day >= 0; $day--) {
                $temperature = fake()->randomFloat(2, 10, 36);
                $precipitation = fake()->randomFloat(2, 0, 45);
                $windSpeed = fake()->randomFloat(2, 5, 90);

                $risk = min(
                    100,
                    ($precipitation * 0.8) + ($windSpeed * 0.5)
                );

                WeatherRecord::create([
                    'country_id' => $country->id,
                    'temperature' => $temperature,
                    'precipitation' => $precipitation,
                    'wind_speed' => $windSpeed,
                    'humidity' => fake()->randomFloat(2, 45, 95),
                    'weather_code' => fake()->randomElement([0, 1, 2, 3, 61, 63, 80, 95]),
                    'weather_condition' => $risk >= 60
                        ? 'Extreme Weather'
                        : ($risk >= 30 ? 'Moderate' : 'Normal'),
                    'weather_risk_score' => round($risk, 2),
                    'extreme_weather' => $risk >= 60,
                    'recorded_at' => now()->subDays($day),
                ]);
            }
        }
    }
}