<?php

namespace App\Services;

use App\Models\Country;
use Illuminate\Support\Facades\Log;
use Throwable;

class GlobalSyncService
{
    public function __construct(
        protected CountryService $countryService,
        protected WeatherService $weatherService,
        protected CurrencyService $currencyService,
        protected WorldBankService $worldBankService,
        protected MarketTrendService $marketTrendService,
        protected NewsService $newsService,
        protected RiskScoringService $riskScoringService,
    ) {
    }

    /**
     * Sinkronisasi seluruh negara aktif.
     */
    public function syncAll(): array
    {
        // Tambah waktu eksekusi maksimum agar tidak timeout saat sync banyak negara
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $results = [];

        $countries = Country::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        foreach ($countries as $country) {
            $results[] = $this->syncCountry($country);
        }

        return $results;
    }

    /**
     * Sinkronisasi seluruh data untuk satu negara.
     */
    public function syncCountry(Country $country): array
    {
        $result = [
            'country_id' => $country->id,
            'country' => $country->name,

            'profile' => false,
            'weather' => false,
            'currency' => false,
            'market' => false,
            'market_trend' => false,
            'news' => false,
            'risk' => false,

            'profile_fallback' => false,

            'news_count' => 0,

            'warnings' => [],

            'errors' => [],
        ];

        /*
        |--------------------------------------------------------------------------
        | Country Profile / REST Countries
        |--------------------------------------------------------------------------
        */

        try {
            $syncedCountry = $this
                ->countryService
                ->syncCountry($country);

            if ($syncedCountry !== null) {
                $country = $syncedCountry;

                $result['country_id'] = $country->id;

                $result['country'] = $country->name;

                $result['profile'] = true;
            } else {
                throw new \RuntimeException(
                    'Country profile synchronization returned no data.'
                );
            }
        } catch (Throwable $exception) {
            /*
             * REST Countries gagal.
             *
             * Gunakan data negara yang sudah tersedia
             * di database sebagai fallback.
             */

            Log::warning(
                'REST Countries unavailable. Using existing country profile.',
                [
                    'country_id' => $country->id,
                    'country' => $country->name,
                    'iso2' => $country->iso2,
                    'iso3' => $country->iso3,
                    'message' => $exception->getMessage(),
                ]
            );

            /*
             * Refresh data negara dari database.
             */

            $country->refresh();

            /*
             * Profil tetap dianggap berhasil karena
             * data database masih dapat digunakan.
             */

            $result['profile'] = true;

            /*
             * Tandai penggunaan fallback.
             */

            $result['profile_fallback'] = true;

            /*
             * Simpan warning.
             */

            $result['warnings']['profile'] =
                'REST Countries unavailable. Existing database profile used.';
        }

        /*
        |--------------------------------------------------------------------------
        | Weather
        |--------------------------------------------------------------------------
        */

        try {
            $weather = $this
                ->weatherService
                ->sync($country);

            $result['weather'] =
                $weather !== null;
        } catch (Throwable $exception) {
            $result['errors']['weather'] =
                $exception->getMessage();

            Log::error(
                'Weather synchronization failed.',
                [
                    'country_id' => $country->id,
                    'country' => $country->name,
                    'message' => $exception->getMessage(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Currency
        |--------------------------------------------------------------------------
        */

        try {
            $currency = $this
                ->currencyService
                ->sync($country);

            $result['currency'] =
                $currency !== null;
        } catch (Throwable $exception) {
            $result['errors']['currency'] =
                $exception->getMessage();

            Log::error(
                'Currency synchronization failed.',
                [
                    'country_id' => $country->id,
                    'country' => $country->name,
                    'message' => $exception->getMessage(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | World Bank / Economic Data
        |--------------------------------------------------------------------------
        */

        try {
            $market = $this
                ->worldBankService
                ->sync($country);

            $result['market'] =
                (bool) $market;

            if (! $result['market']) {
                $result['errors']['market'] =
                    'Economic synchronization returned false.';
            }
        } catch (Throwable $exception) {
            $result['errors']['market'] =
                $exception->getMessage();

            Log::error(
                'Market synchronization failed.',
                [
                    'country_id' => $country->id,
                    'country' => $country->name,
                    'message' => $exception->getMessage(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Market Trend
        |--------------------------------------------------------------------------
        |
        | Market Trend dijalankan setelah Currency dan World Bank
        | karena membutuhkan data kurs dan indikator ekonomi terbaru.
        |
        */

        try {
            /*
             * Refresh model sebelum menghitung market trend.
             */

            $country->refresh();

            $marketTrend = $this
                ->marketTrendService
                ->calculate($country);

            $result['market_trend'] =
                $marketTrend !== null;
        } catch (Throwable $exception) {
            $result['errors']['market_trend'] =
                $exception->getMessage();

            Log::error(
                'Market trend calculation failed.',
                [
                    'country_id' => $country->id,
                    'country' => $country->name,
                    'message' => $exception->getMessage(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | News
        |--------------------------------------------------------------------------
        */

        try {
            $newsCount = $this
                ->newsService
                ->sync($country);

            $result['news_count'] =
                (int) $newsCount;

            /*
             * Nilai 0 tidak selalu berarti gagal.
             *
             * Request bisa berhasil tetapi tidak
             * memiliki artikel baru.
             */

            $result['news'] = true;
        } catch (Throwable $exception) {
            $result['errors']['news'] =
                $exception->getMessage();

            Log::error(
                'News synchronization failed.',
                [
                    'country_id' => $country->id,
                    'country' => $country->name,
                    'message' => $exception->getMessage(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Risk Scoring
        |--------------------------------------------------------------------------
        |
        | Risk dijalankan terakhir karena membutuhkan data terbaru
        | dari weather, currency, market, market trend, dan news.
        |
        */

        try {
            /*
             * Refresh model agar seluruh data terbaru
             * dibaca kembali dari database.
             */

            $country->refresh();

            $risk = $this
                ->riskScoringService
                ->calculate($country);

            $result['risk'] =
                $risk !== null;
        } catch (Throwable $exception) {
            $result['errors']['risk'] =
                $exception->getMessage();

            Log::error(
                'Risk calculation failed.',
                [
                    'country_id' => $country->id,
                    'country' => $country->name,
                    'message' => $exception->getMessage(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $services = [
            'profile',
            'weather',
            'currency',
            'market',
            'market_trend',
            'news',
            'risk',
        ];

        $successful = collect($services)
            ->filter(
                fn (string $service): bool =>
                    $result[$service] === true
            )
            ->count();

        $failed =
            count($services) - $successful;

        $result['summary'] = [
            'successful' => $successful,

            'failed' => $failed,

            'total' => count($services),

            'success' => $failed === 0,

            'profile_fallback' =>
                $result['profile_fallback'],
        ];

        return $result;
    }
}