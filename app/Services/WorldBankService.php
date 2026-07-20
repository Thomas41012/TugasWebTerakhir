<?php

namespace App\Services;

use App\Models\Country;
use App\Models\EconomicIndicator;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class WorldBankService
{
    public function __construct(
        protected ApiLogService $apiLogService
    ) {
    }

    /**
     * Sinkronisasi data ekonomi satu negara.
     */
    public function sync(Country $country): bool
    {
        try {
            /*
            |--------------------------------------------------------------------------
            | Fetch Economic Indicators
            |--------------------------------------------------------------------------
            */

            $gdp = $this->fetchIndicator(
                $country,
                'NY.GDP.MKTP.CD'
            );

            $gdpGrowth = $this->fetchIndicator(
                $country,
                'NY.GDP.MKTP.KD.ZG'
            );

            $inflation = $this->fetchIndicator(
                $country,
                'FP.CPI.TOTL.ZG'
            );

            $exports = $this->fetchIndicator(
                $country,
                'NE.EXP.GNFS.CD'
            );

            $imports = $this->fetchIndicator(
                $country,
                'NE.IMP.GNFS.CD'
            );

            $population = $this->fetchIndicator(
                $country,
                'SP.POP.TOTL'
            );

            /*
            |--------------------------------------------------------------------------
            | Gabungkan Seluruh Tahun
            |--------------------------------------------------------------------------
            */

            $years = collect([
                ...array_keys($gdp),

                ...array_keys($gdpGrowth),

                ...array_keys($inflation),

                ...array_keys($exports),

                ...array_keys($imports),

                ...array_keys($population),
            ])
                ->unique()
                ->sortDesc()
                ->values();

            /*
            |--------------------------------------------------------------------------
            | Validasi Data
            |--------------------------------------------------------------------------
            */

            if ($years->isEmpty()) {
                throw new RuntimeException(
                    "Data World Bank tidak tersedia untuk {$country->name}."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Simpan Economic Indicators
            |--------------------------------------------------------------------------
            */

            foreach ($years as $year) {
                EconomicIndicator::query()->updateOrCreate(
                    [
                        'country_id' => $country->id,

                        'year' => (int) $year,
                    ],
                    [
                        'gdp' => $gdp[$year] ?? null,

                        'gdp_growth' =>
                            $gdpGrowth[$year] ?? null,

                        'inflation_rate' =>
                            $inflation[$year] ?? null,

                        'exports' =>
                            $exports[$year] ?? null,

                        'imports' =>
                            $imports[$year] ?? null,

                        'population' =>
                            $population[$year] ?? null,
                    ]
                );
            }

            return true;
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Gagal sinkronisasi data ekonomi {$country->name}: "
                . $exception->getMessage(),
                previous: $exception
            );
        }
    }

    /**
     * Alias kompatibilitas kode lama.
     */
    public function syncCountryEconomy(
        Country $country
    ): bool {
        try {
            return $this->sync($country);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * Sinkronisasi seluruh negara aktif.
     */
    public function syncAllCountries(): array
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
                        $this->sync($country);

                        $results['success']++;
                    } catch (Throwable $exception) {
                        report($exception);

                        $results['failed']++;
                    }
                }
            );

        return $results;
    }

    /**
     * Mengambil satu indikator dari World Bank API.
     */
    private function fetchIndicator(
        Country $country,
        string $indicator
    ): array {
        /*
        |--------------------------------------------------------------------------
        | API Configuration
        |--------------------------------------------------------------------------
        */

        $baseUrl = rtrim(
            (string) config(
                'services.world_bank.url',
                'https://api.worldbank.org/v2'
            ),
            '/'
        );

        $endpoint =
            "{$baseUrl}/country/"
            . "{$country->iso3}/indicator/{$indicator}";

        $parameters = [
            'format' => 'json',

            'per_page' => 10,
        ];

        /*
        |--------------------------------------------------------------------------
        | API Log Request Data
        |--------------------------------------------------------------------------
        */

        $requestData = [
            'country_id' => $country->id,

            'country' => $country->name,

            'iso2' => $country->iso2,

            'iso3' => $country->iso3,

            'indicator' => $indicator,

            'parameters' => $parameters,
        ];

        /*
        |--------------------------------------------------------------------------
        | Start Timer
        |--------------------------------------------------------------------------
        */

        $startedAt = microtime(true);

        $failureLogged = false;

        try {
            /*
            |--------------------------------------------------------------------------
            | Request World Bank API
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

            $responseTimeMs = $this->calculateResponseTime(
                $startedAt
            );

            /*
            |--------------------------------------------------------------------------
            | Validate HTTP Response
            |--------------------------------------------------------------------------
            */

            if ($response->failed()) {
                $failureLogged = true;

                $errorMessage =
                    "World Bank request gagal dengan HTTP {$response->status()}.";

                $this->apiLogService->failure(
                    service: 'World Bank',

                    endpoint: $endpoint,

                    error: $errorMessage,

                    statusCode: $response->status(),

                    responseTimeMs: $responseTimeMs,

                    requestData: $requestData,

                    method: 'GET',
                );

                throw new RuntimeException(
                    $errorMessage
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Parse JSON Response
            |--------------------------------------------------------------------------
            */

            $responseData = $response->json();

            /*
            |--------------------------------------------------------------------------
            | Validate Response Structure
            |--------------------------------------------------------------------------
            */

            if (
                ! is_array($responseData)
                || ! isset($responseData[1])
                || ! is_array($responseData[1])
            ) {
                $failureLogged = true;

                $errorMessage =
                    "Format data World Bank tidak valid untuk "
                    . "{$country->name}, indikator {$indicator}.";

                $this->apiLogService->failure(
                    service: 'World Bank',

                    endpoint: $endpoint,

                    error: $errorMessage,

                    statusCode: $response->status(),

                    responseTimeMs: $responseTimeMs,

                    requestData: $requestData,

                    method: 'GET',
                );

                throw new RuntimeException(
                    $errorMessage
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Get Rows
            |--------------------------------------------------------------------------
            */

            $rows = $responseData[1];

            /*
            |--------------------------------------------------------------------------
            | Format Data Berdasarkan Tahun
            |--------------------------------------------------------------------------
            */

            $data = collect($rows)
                ->filter(
                    fn (array $row): bool =>
                        isset($row['date'])
                        && array_key_exists(
                            'value',
                            $row
                        )
                        && $row['value'] !== null
                )
                ->mapWithKeys(
                    fn (array $row): array => [
                        (int) $row['date'] =>
                            $row['value'],
                    ]
                )
                ->all();

            /*
            |--------------------------------------------------------------------------
            | API Success Log
            |--------------------------------------------------------------------------
            |
            | Response tetap dianggap sukses walaupun data kosong,
            | selama HTTP request dan format response valid.
            |
            */

            $this->apiLogService->success(
                service: 'World Bank',

                endpoint: $endpoint,

                statusCode: $response->status(),

                responseTimeMs: $responseTimeMs,

                requestData: $requestData,

                method: 'GET',
            );

            return $data;
        } catch (Throwable $exception) {
            /*
            |--------------------------------------------------------------------------
            | Calculate Exception Response Time
            |--------------------------------------------------------------------------
            */

            $responseTimeMs = $this->calculateResponseTime(
                $startedAt
            );

            /*
            |--------------------------------------------------------------------------
            | API Failure Log
            |--------------------------------------------------------------------------
            */

            if (! $failureLogged) {
                $this->apiLogService->failure(
                    service: 'World Bank',

                    endpoint: $endpoint,

                    error: $exception,

                    statusCode: null,

                    responseTimeMs: $responseTimeMs,

                    requestData: $requestData,

                    method: 'GET',
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Return Empty Data
            |--------------------------------------------------------------------------
            |
            | Satu indikator yang gagal tidak langsung menghentikan
            | sinkronisasi indikator World Bank lainnya.
            |
            */

            return [];
        }
    }

    /**
     * Hitung response time dalam millisecond.
     */
    private function calculateResponseTime(
        float $startedAt
    ): int {
        return (int) round(
            (microtime(true) - $startedAt) * 1000
        );
    }
}