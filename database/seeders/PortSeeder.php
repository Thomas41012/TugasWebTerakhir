<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Port;
use Illuminate\Database\Seeder;

class PortSeeder extends Seeder
{
    public function run(): void
    {
        $ports = [
            ['IDN', 'Port of Tanjung Priok', 'IDTPP', 'Jakarta', 'container', -6.1040, 106.8860, 45, 38.50],
            ['IDN', 'Port of Tanjung Perak', 'IDSUB', 'Surabaya', 'container', -7.1960, 112.7320, 37, 32.00],
            ['IDN', 'Belawan Port', 'IDBLW', 'Medan', 'container', 3.7850, 98.6940, 29, 25.00],

            ['CHN', 'Port of Shanghai', 'CNSHA', 'Shanghai', 'container', 31.2304, 121.4737, 78, 72.50],
            ['CHN', 'Port of Ningbo-Zhoushan', 'CNNGB', 'Ningbo', 'container', 29.8683, 121.5440, 64, 58.00],
            ['CHN', 'Port of Shenzhen', 'CNSZX', 'Shenzhen', 'container', 22.5431, 114.0579, 61, 55.50],

            ['JPN', 'Port of Tokyo', 'JPTYO', 'Tokyo', 'container', 35.6167, 139.7833, 35, 27.50],
            ['JPN', 'Port of Yokohama', 'JPYOK', 'Yokohama', 'container', 35.4437, 139.6380, 31, 24.00],
            ['JPN', 'Port of Kobe', 'JPUKB', 'Kobe', 'container', 34.6901, 135.1955, 28, 22.50],

            ['USA', 'Port of Los Angeles', 'USLAX', 'Los Angeles', 'container', 33.7405, -118.2720, 69, 61.50],
            ['USA', 'Port of Long Beach', 'USLGB', 'Long Beach', 'container', 33.7542, -118.2165, 66, 59.00],
            ['USA', 'Port of New York and New Jersey', 'USNYC', 'New York', 'container', 40.6840, -74.0430, 51, 44.50],

            ['DEU', 'Port of Hamburg', 'DEHAM', 'Hamburg', 'container', 53.5461, 9.9661, 43, 35.50],
            ['DEU', 'Port of Bremerhaven', 'DEBRV', 'Bremerhaven', 'container', 53.5396, 8.5809, 34, 29.00],

            ['AUS', 'Port Botany', 'AUBTB', 'Sydney', 'container', -33.9700, 151.2200, 32, 26.00],
            ['AUS', 'Port of Melbourne', 'AUMEL', 'Melbourne', 'container', -37.8136, 144.9631, 39, 31.50],

            ['IND', 'Jawaharlal Nehru Port', 'INNSA', 'Mumbai', 'container', 18.9497, 72.9510, 57, 48.00],
            ['IND', 'Port of Chennai', 'INMAA', 'Chennai', 'container', 13.0827, 80.2707, 49, 41.00],

            ['SGP', 'Port of Singapore', 'SGSIN', 'Singapore', 'container', 1.2644, 103.8400, 58, 47.50],

            ['MYS', 'Port Klang', 'MYPKG', 'Klang', 'container', 3.0000, 101.4000, 46, 39.00],
            ['MYS', 'Port of Tanjung Pelepas', 'MYTPP', 'Johor', 'container', 1.3620, 103.5480, 41, 34.50],

            ['KOR', 'Port of Busan', 'KRPUS', 'Busan', 'container', 35.1028, 129.0403, 53, 45.00],
            ['KOR', 'Port of Incheon', 'KRINC', 'Incheon', 'container', 37.4563, 126.7052, 38, 30.50],
        ];

        foreach ($ports as $port) {
            $country = Country::where('iso3', $port[0])->firstOrFail();

            Port::updateOrCreate(
                ['unlocode' => $port[2]],
                [
                    'country_id' => $country->id,
                    'name' => $port[1],
                    'city' => $port[3],
                    'port_type' => $port[4],
                    'latitude' => $port[5],
                    'longitude' => $port[6],
                    'status' => 'active',
                    'congestion_level' => $port[7],
                    'risk_score' => $port[8],
                    'metadata' => [
                        'source' => 'initial-seeder',
                        'category' => 'international',
                    ],
                    'last_synced_at' => now(),
                ]
            );
        }
    }
}