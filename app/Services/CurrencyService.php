<?php

namespace App\Services;

use App\Models\Country;
use App\Models\CurrencyRate;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class CurrencyService
{
    /**
     * Cache response Exchange Rate API agar tidak memanggil HTTP berkali-kali
     * dalam satu proses sinkronisasi (satu request untuk semua negara).
     */
    private static ?array $cachedRates = null;
    private static int $cachedStatusCode = 200;
    private static int $cachedResponseTimeMs = 0;

    public function __construct(
        protected ApiLogService $apiLogService
    ) {
    }

    /**
     * Sinkronisasi kurs satu negara.
     */
    public function sync(Country $country): CurrencyRate
    {
        $currencyCode = strtoupper(
            trim((string) $country->currency_code)
        );

        if ($currencyCode === '') {
            throw new RuntimeException(
                "Currency code tidak tersedia untuk {$country->name}."
            );
        }

        $baseUrl = rtrim(
            (string) config(
                'services.exchange_rate.url',
                'https://open.er-api.com/v6'
            ),
            '/'
        );

        $endpoint = "{$baseUrl}/latest/USD";

        $requestData = [
            'country_id' => $country->id,
            'country' => $country->name,
            'iso2' => $country->iso2,
            'base_currency' => 'USD',
            'target_currency' => $currencyCode,
        ];

        $startedAt = microtime(true);

        $failureLogged = false;

        try {
            /*
            |--------------------------------------------------------------------------
            | Request Exchange Rate API (dengan caching per-proses)
            | API dipanggil hanya sekali, hasil di-cache untuk negara berikutnya.
            |--------------------------------------------------------------------------
            */

            if (self::$cachedRates === null) {
                $response = Http::acceptJson()
                    ->connectTimeout(15)
                    ->timeout(30)
                    ->retry(
                        2,
                        500,
                        throw: false
                    )
                    ->get($endpoint);

                $freshResponseTimeMs = $this->calculateResponseTime($startedAt);

                if ($response->failed()) {
                    $failureLogged = true;
                    $this->apiLogService->failure(
                        service: 'Exchange Rate',
                        endpoint: $endpoint,
                        error: "Exchange Rate API gagal dengan HTTP {$response->status()}.",
                        statusCode: $response->status(),
                        responseTimeMs: $freshResponseTimeMs,
                        requestData: $requestData,
                        method: 'GET',
                    );
                    throw new RuntimeException(
                        "Exchange Rate API gagal dengan HTTP {$response->status()}."
                    );
                }

                $parsedData = $response->json();
                if (!is_array($parsedData)) {
                    $failureLogged = true;
                    $this->apiLogService->failure(
                        service: 'Exchange Rate',
                        endpoint: $endpoint,
                        error: 'Exchange Rate API mengembalikan data yang tidak valid.',
                        statusCode: $response->status(),
                        responseTimeMs: $freshResponseTimeMs,
                        requestData: $requestData,
                        method: 'GET',
                    );
                    throw new RuntimeException(
                        'Exchange Rate API mengembalikan data yang tidak valid.'
                    );
                }

                self::$cachedRates = $parsedData;
                self::$cachedStatusCode = $response->status();
                self::$cachedResponseTimeMs = $freshResponseTimeMs;
            }

            // Gunakan data dari cache
            $data = self::$cachedRates;
            $responseTimeMs = self::$cachedResponseTimeMs;

            // Buat dummy response object agar kode di bawah tetap berjalan
            // (validasi result, ambil rate, dsb) menggunakan data cache
            $cachedResponseStatus = self::$cachedStatusCode;



            /*
            |--------------------------------------------------------------------------
            | Validate API Result
            |--------------------------------------------------------------------------
            */

            if (
                isset($data['result'])
                && strtolower((string) $data['result']) !== 'success'
            ) {
                $failureLogged = true;

                $errorMessage = (string) (
                    $data['error-type']
                    ?? 'Exchange Rate API mengembalikan status gagal.'
                );

                $this->apiLogService->failure(
                    service: 'Exchange Rate',

                    endpoint: $endpoint,

                    error: $errorMessage,

                    statusCode: $cachedResponseStatus,

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
            | Get Currency Rate
            |--------------------------------------------------------------------------
            */

            $rate = data_get(
                $data,
                "rates.{$currencyCode}"
            );

            if (! is_numeric($rate)) {
                $failureLogged = true;

                $errorMessage =
                    "Kurs {$currencyCode} tidak ditemukan dari Exchange Rate API.";

                $this->apiLogService->failure(
                    service: 'Exchange Rate',

                    endpoint: $endpoint,

                    error: $errorMessage,

                    statusCode: $cachedResponseStatus,

                    responseTimeMs: $responseTimeMs,

                    requestData: $requestData,

                    method: 'GET',
                );

                throw new RuntimeException(
                    $errorMessage
                );
            }

            $rate = (float) $rate;

            /*
            |--------------------------------------------------------------------------
            | Get Previous Currency Rate
            |--------------------------------------------------------------------------
            */

            $previous = CurrencyRate::query()
                ->where(
                    'country_id',
                    $country->id
                )
                ->latest('recorded_at')
                ->first();

            $previousRate = $previous
                ? (float) $previous->exchange_rate
                : $rate;

            /*
            |--------------------------------------------------------------------------
            | Calculate Percentage Change
            |--------------------------------------------------------------------------
            */

            $percentageChange = $previousRate > 0
                ? (
                    ($rate - $previousRate)
                    / $previousRate
                ) * 100
                : 0.0;

            /*
            |--------------------------------------------------------------------------
            | Save Currency Rate
            |--------------------------------------------------------------------------
            */

            $currencyRate = CurrencyRate::query()->create([
                'country_id' => $country->id,

                'base_currency' => 'USD',

                'target_currency' => $currencyCode,

                'exchange_rate' => $rate,

                'previous_rate' => $previousRate,

                'percentage_change' => round(
                    $percentageChange,
                    4
                ),

                'recorded_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Success API Log
            |--------------------------------------------------------------------------
            */

            $this->apiLogService->success(
                service: 'Exchange Rate',

                endpoint: $endpoint,

                statusCode: $cachedResponseStatus,

                responseTimeMs: $responseTimeMs,

                requestData: $requestData,

                method: 'GET',
            );

            return $currencyRate;
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
            | Failure API Log
            |--------------------------------------------------------------------------
            |
            | Hanya mencatat jika error belum dicatat sebelumnya.
            |
            */

            if (! $failureLogged) {
                $this->apiLogService->failure(
                    service: 'Exchange Rate',

                    endpoint: $endpoint,

                    error: $exception,

                    statusCode: null,

                    responseTimeMs: $responseTimeMs,

                    requestData: $requestData,

                    method: 'GET',
                );
            }

            throw new RuntimeException(
                "Gagal sinkronisasi kurs {$country->name}: "
                . $exception->getMessage(),
                previous: $exception
            );
        }
    }

    /**
     * Alias untuk kompatibilitas kode lama.
     */
    public function syncCountryCurrency(
        Country $country
    ): ?CurrencyRate {
        try {
            return $this->sync($country);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * Sinkronisasi kurs seluruh negara aktif.
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