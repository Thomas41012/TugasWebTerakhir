<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            PortSeeder::class,

            EconomicIndicatorSeeder::class,
            WeatherRecordSeeder::class,
            CurrencyRateSeeder::class,
            MarketTrendSeeder::class,

            PositiveWordSeeder::class,
            NegativeWordSeeder::class,
            NewsSeeder::class,

            RiskScoreSeeder::class,

            UserSeeder::class,
            WatchlistSeeder::class,
        ]);
    }
}