<?php

namespace App\Services;

use App\Models\Country;
use App\Models\WeatherRecord;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class WeatherService
{
    public function __construct(
        protected ApiLogService $apiLogService
    ) {
    }

    /**
     * Sinkronisasi data cuaca terbaru untuk satu negara.
     */
    public function sync(Country $country): WeatherRecord
    {
        $latitude = $country->latitude;
        $longitude = $country->longitude;

        if ($latitude === null || $longitude === null) {
            throw new RuntimeException(
                "Latitude atau longitude tidak tersedia untuk {$country->name}."
            );
        }

        $baseUrl = rtrim(
            (string) config(
                'services.open_meteo.url',
                'https://api.open-meteo.com/v1'
            ),
            '/'
        );

        $endpoint = $baseUrl . '/forecast';

        $parameters = [
            'latitude' => $latitude,

            'longitude' => $longitude,

            'current' => implode(',', [
                'temperature_2m',
                'relative_humidity_2m',
                'apparent_temperature',
                'precipitation',
                'rain',
                'weather_code',
                'cloud_cover',
                'pressure_msl',
                'wind_speed_10m',
                'wind_direction_10m',
            ]),

            'timezone' => 'UTC',
        ];

        /*
        |--------------------------------------------------------------------------
        | Start Response Timer
        |--------------------------------------------------------------------------
        */

        $startedAt = microtime(true);

        try {
            /*
            |--------------------------------------------------------------------------
            | Request Open-Meteo API
            |--------------------------------------------------------------------------
            */

            $response = Http::acceptJson()
                ->connectTimeout(30)
                ->timeout(60)
                ->retry(
                    3,
                    1000,
                    throw: false
                )
                ->get(
                    $endpoint,
                    $parameters
                );

            /*
            |--------------------------------------------------------------------------
            | Calculate Response Time
            |--------------------------------------------------------------------------
            */

            $responseTimeMs = (int) round(
                (microtime(true) - $startedAt) * 1000
            );

            /*
            |--------------------------------------------------------------------------
            | Validasi Response
            |--------------------------------------------------------------------------
            */

            if ($response->failed()) {
                /*
                 * Catat request gagal.
                 */

                $this->apiLogService->failure(
                    service: 'Open-Meteo',

                    endpoint: $endpoint,

                    error:
                        "Open-Meteo request gagal dengan HTTP {$response->status()}.",

                    statusCode: $response->status(),

                    responseTimeMs: $responseTimeMs,

                    requestData: [
                        'country_id' => $country->id,

                        'country' => $country->name,

                        'iso2' => $country->iso2,

                        'parameters' => $parameters,
                    ],

                    method: 'GET',
                );

                throw new RuntimeException(
                    "Open-Meteo request gagal dengan HTTP {$response->status()}."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Ambil Data JSON
            |--------------------------------------------------------------------------
            */

            $data = $response->json();

            $current = $data['current'] ?? null;

            if (! is_array($current)) {
                /*
                 * Request berhasil tetapi format data tidak valid.
                 */

                $this->apiLogService->failure(
                    service: 'Open-Meteo',

                    endpoint: $endpoint,

                    error:
                        "Data cuaca Open-Meteo tidak valid untuk {$country->name}.",

                    statusCode: $response->status(),

                    responseTimeMs: $responseTimeMs,

                    requestData: [
                        'country_id' => $country->id,

                        'country' => $country->name,

                        'iso2' => $country->iso2,

                        'parameters' => $parameters,
                    ],

                    method: 'GET',
                );

                throw new RuntimeException(
                    "Data cuaca Open-Meteo tidak valid untuk {$country->name}."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Simpan Weather Record
            |--------------------------------------------------------------------------
            */

            $weatherRecord = WeatherRecord::query()->create([
                'country_id' => $country->id,

                'temperature' => $this->number(
                    $current['temperature_2m'] ?? null
                ),

                'feels_like' => $this->number(
                    $current['apparent_temperature'] ?? null
                ),

                'humidity' => $this->number(
                    $current['relative_humidity_2m'] ?? null
                ),

                'precipitation' => $this->number(
                    $current['precipitation'] ?? null
                ),

                'rain' => $this->number(
                    $current['rain'] ?? null
                ),

                'cloud_cover' => $this->number(
                    $current['cloud_cover'] ?? null
                ),

                'pressure' => $this->number(
                    $current['pressure_msl'] ?? null
                ),

                'wind_speed' => $this->number(
                    $current['wind_speed_10m'] ?? null
                ),

                'wind_direction' => $this->number(
                    $current['wind_direction_10m'] ?? null
                ),

                'weather_code' => (int) (
                    $current['weather_code'] ?? 0
                ),

                'weather_condition' => $this->weatherCondition(
                    (int) (
                        $current['weather_code'] ?? 0
                    )
                ),

                'recorded_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Success API Log
            |--------------------------------------------------------------------------
            */

            $this->apiLogService->success(
                service: 'Open-Meteo',

                endpoint: $endpoint,

                statusCode: $response->status(),

                responseTimeMs: $responseTimeMs,

                requestData: [
                    'country_id' => $country->id,

                    'country' => $country->name,

                    'iso2' => $country->iso2,

                    'parameters' => $parameters,
                ],

                method: 'GET',
            );

            return $weatherRecord;
        } catch (Throwable $exception) {
            /*
            |--------------------------------------------------------------------------
            | Catat Exception
            |--------------------------------------------------------------------------
            |
            | Hindari duplicate log jika error HTTP atau format data sudah
            | dicatat sebelumnya.
            |
            */

            if (! isset($responseTimeMs)) {
                $responseTimeMs = (int) round(
                    (microtime(true) - $startedAt) * 1000
                );

                $this->apiLogService->failure(
                    service: 'Open-Meteo',

                    endpoint: $endpoint,

                    error: $exception,

                    statusCode: null,

                    responseTimeMs: $responseTimeMs,

                    requestData: [
                        'country_id' => $country->id,

                        'country' => $country->name,

                        'iso2' => $country->iso2,

                        'parameters' => $parameters,
                    ],

                    method: 'GET',
                );
            }

            throw new RuntimeException(
                "Gagal sinkronisasi cuaca {$country->name}: "
                . $exception->getMessage(),
                previous: $exception
            );
        }
    }

    /**
     * Alias untuk kompatibilitas dengan Controller atau Service lama.
     */
    public function syncWeather(
        Country $country
    ): WeatherRecord {
        return $this->sync($country);
    }

    /**
     * Konversi nilai API menjadi float.
     */
    private function number(
        mixed $value
    ): float {
        return is_numeric($value)
            ? (float) $value
            : 0.0;
    }

    /**
     * Konversi WMO Weather Code.
     */
    private function weatherCondition(
        int $code
    ): string {
        return match (true) {
            $code === 0
                => 'Clear Sky',

            in_array(
                $code,
                [1, 2, 3],
                true
            )
                => 'Partly Cloudy',

            in_array(
                $code,
                [45, 48],
                true
            )
                => 'Fog',

            in_array(
                $code,
                [51, 53, 55],
                true
            )
                => 'Drizzle',

            in_array(
                $code,
                [56, 57],
                true
            )
                => 'Freezing Drizzle',

            in_array(
                $code,
                [61, 63, 65],
                true
            )
                => 'Rain',

            in_array(
                $code,
                [66, 67],
                true
            )
                => 'Freezing Rain',

            in_array(
                $code,
                [71, 73, 75, 77],
                true
            )
                => 'Snow',

            in_array(
                $code,
                [80, 81, 82],
                true
            )
                => 'Rain Showers',

            in_array(
                $code,
                [85, 86],
                true
            )
                => 'Snow Showers',

            in_array(
                $code,
                [95, 96, 99],
                true
            )
                => 'Thunderstorm',

            default
                => 'Unknown',
        };
    }
}