<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\User;
use App\Models\Watchlist;
use App\Models\UserFavorite;
use Illuminate\Database\Seeder;

class WatchlistSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where(
            'email',
            'admin233@gmail.com'
        )->firstOrFail();

        $countries = Country::whereIn(
            'iso3',
            [
                'IDN',
                'CHN',
                'JPN',
                'USA',
                'DEU',
            ]
        )->get();

        foreach ($countries as $country) {
            Watchlist::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'country_id' => $country->id,
                ],
                [
                    'risk_alerts' => true,
                    'weather_alerts' => true,
                    'currency_alerts' => true,
                ]
            );

            UserFavorite::updateOrCreate([
                'user_id' => $user->id,
                'country_id' => $country->id,
            ]);
        }
    }
}