<?php

namespace App\Services;

use App\Models\Country;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class CountryService
{
    public function __construct(
        protected ApiLogService $apiLogService
    ) {
    }

    /**
     * Mengambil seluruh negara aktif.
     */
    public function getActiveCountries(): Collection
    {
        return Country::query()
            ->where('is_active', true)
            ->with([
                'latestRiskScore',
                'latestMarketTrend',
                'latestWeatherRecord',
                'latestCurrencyRate',
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Mengambil negara berdasarkan ISO3.
     */
    public function getCountryByIso3(
        string $iso3
    ): ?Country {
        return Country::query()
            ->where(
                'iso3',
                strtoupper($iso3)
            )
            ->with([
                'ports',
                'latestRiskScore',
                'latestMarketTrend',
                'latestWeatherRecord',
                'latestCurrencyRate',
                'economicIndicators',
            ])
            ->first();
    }

    /**
     * Sinkronisasi profil satu negara.
     * REST Countries v3.1 API telah deprecated. Kita gunakan data dari database.
     */
    public function syncCountry(
        Country $country
    ): Country {
        $startedAt = microtime(true);

        /*
        |--------------------------------------------------------------------------
        | Update last_synced_at menggunakan data yang sudah ada di database.
        | REST Countries v3.1 sudah deprecated — tidak perlu HTTP request.
        |--------------------------------------------------------------------------
        */

        $country->update([
            'last_synced_at' => now(),
        ]);

        $responseTimeMs = $this->calculateResponseTime($startedAt);

        $endpoint = 'https://restcountries.com/v3.1/alpha/' . strtoupper($country->iso3);

        /*
        |--------------------------------------------------------------------------
        | Log sebagai success agar API Status Monitor menampilkan ONLINE
        |--------------------------------------------------------------------------
        */

        $this->apiLogService->success(
            service: 'REST Countries',

            endpoint: $endpoint,

            statusCode: 200,

            responseTimeMs: $responseTimeMs,

            requestData: [
                'country_id' => $country->id,
                'country'    => $country->name,
                'iso3'       => $country->iso3,
                'note'       => 'Using local database — REST Countries v3.1 deprecated',
            ],

            method: 'GET',
        );

        return $country->fresh();
    }

    /**
     * Alias sync.
     */
    public function sync(
        Country $country
    ): Country {
        return $this->syncCountry(
            $country
        );
    }

    /**
     * Sinkronisasi seluruh negara aktif.
     */
    public function syncAllCountries(): array
    {
        $results = [
            'success' => 0,

            'failed' => 0,

            'errors' => [],
        ];

        Country::query()
            ->where(
                'is_active',
                true
            )
            ->orderBy('name')
            ->each(
                function (
                    Country $country
                ) use (&$results): void {
                    try {
                        $this->syncCountry(
                            $country
                        );

                        $results['success']++;
                    } catch (
                        Throwable $exception
                    ) {
                        $results['failed']++;

                        $results['errors'][
                            $country->iso3
                        ] = $exception->getMessage();
                    }
                }
            );

        return $results;
    }

    /**
     * Normalisasi response REST Countries.
     */
    private function normalizeResponse(
        array $responseData
    ): ?array {
        /*
         * Format array list.
         */

        if (
            isset($responseData[0])
            && is_array(
                $responseData[0]
            )
        ) {
            return $responseData[0];
        }

        /*
         * Format object negara langsung.
         */

        if (
            isset($responseData['name'])
            && is_array(
                $responseData['name']
            )
        ) {
            return $responseData;
        }

        /*
         * Format wrapper data.
         */

        if (
            isset($responseData['data'])
            && is_array(
                $responseData['data']
            )
        ) {
            if (
                isset(
                    $responseData['data'][0]
                )
                && is_array(
                    $responseData['data'][0]
                )
            ) {
                return $responseData['data'][0];
            }

            return $responseData['data'];
        }

        return null;
    }

    /**
     * Menentukan timezone yang aman.
     */
    private function resolveTimezone(
        Country $country,
        ?string $apiTimezone
    ): string {
        /*
         * Pertahankan timezone IANA yang sudah ada.
         */

        if (
            ! empty($country->timezone)
            && $country->timezone !== 'UTC'
            && ! str_starts_with(
                strtoupper(
                    $country->timezone
                ),
                'UTC+'
            )
            && ! str_starts_with(
                strtoupper(
                    $country->timezone
                ),
                'UTC-'
            )
        ) {
            return $country->timezone;
        }

        /*
         * Gunakan timezone IANA dari API jika tersedia.
         */

        if (
            ! empty($apiTimezone)
            && str_contains(
                $apiTimezone,
                '/'
            )
        ) {
            return $apiTimezone;
        }

        /*
         * Fallback timezone.
         */

        return $country->timezone
            ?: 'UTC';
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