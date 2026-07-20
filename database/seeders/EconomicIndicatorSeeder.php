<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\EconomicIndicator;
use Illuminate\Database\Seeder;

class EconomicIndicatorSeeder extends Seeder
{
    public function run(): void
    {
        $baseData = [
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
        ];

        foreach (Country::all() as $country) {
            $data = $baseData[$country->iso3];

            for ($year = 2020; $year <= 2025; $year++) {
                $yearDifference = 2025 - $year;

                EconomicIndicator::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'year' => $year,
                    ],
                    [
                        'gdp' => $data[0] * (1 - ($yearDifference * 0.025)),
                        'gdp_growth' => $data[1] + fake()->randomFloat(2, -1.5, 1.5),
                        'inflation_rate' => max(
                            0,
                            $data[2] + fake()->randomFloat(2, -1, 1)
                        ),
                        'exports' => $data[3] * (1 - ($yearDifference * 0.02)),
                        'imports' => $data[4] * (1 - ($yearDifference * 0.02)),
                        'population' => $country->population,
                    ]
                );
            }
        }
    }
}