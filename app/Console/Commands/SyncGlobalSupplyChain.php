<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Services\GlobalSyncService;
use Illuminate\Console\Command;

class SyncGlobalSupplyChain extends Command
{
    protected $signature = 'supply-chain:sync
                            {--country= : Country ISO2 or ISO3 code}';

    protected $description =
        'Synchronize Global Supply Chain Intelligence data';

    public function handle(
        GlobalSyncService $globalSyncService
    ): int {
        $countryCode = $this->option('country');

        /*
        |--------------------------------------------------------------------------
        | Sync Satu Negara
        |--------------------------------------------------------------------------
        */

        if ($countryCode) {
            $countryCode = strtoupper(
                trim((string) $countryCode)
            );

            $country = Country::query()
                ->where('is_active', true)
                ->where(
                    function ($query) use ($countryCode): void {
                        $query
                            ->where('iso2', $countryCode)
                            ->orWhere('iso3', $countryCode);
                    }
                )
                ->first();

            if (! $country) {
                $this->error(
                    "Country [{$countryCode}] not found."
                );

                return self::FAILURE;
            }

            $this->info(
                "Synchronizing {$country->name}..."
            );

            $result = $globalSyncService
                ->syncCountry($country);

            $this->displayResult($result);

            return data_get(
                $result,
                'summary.success',
                false
            )
                ? self::SUCCESS
                : self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Sync Semua Negara
        |--------------------------------------------------------------------------
        */

        $this->info(
            'Starting Global Supply Chain synchronization...'
        );

        $results = $globalSyncService
            ->syncAll();

        if (empty($results)) {
            $this->warn(
                'No active countries available for synchronization.'
            );

            return self::SUCCESS;
        }

        foreach ($results as $result) {
            $this->displayResult($result);
        }

        /*
        |--------------------------------------------------------------------------
        | Global Summary
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $totalCountries = count($results);

        $totalSuccessful = collect($results)
            ->filter(
                fn (array $result): bool =>
                    (bool) data_get(
                        $result,
                        'summary.success',
                        false
                    )
            )
            ->count();

        $totalFailed =
            $totalCountries - $totalSuccessful;

        $totalServicesSuccessful = collect($results)
            ->sum(
                fn (array $result): int =>
                    (int) data_get(
                        $result,
                        'summary.successful',
                        0
                    )
            );

        $totalServicesFailed = collect($results)
            ->sum(
                fn (array $result): int =>
                    (int) data_get(
                        $result,
                        'summary.failed',
                        0
                    )
            );

        $totalNewsProcessed = collect($results)
            ->sum(
                fn (array $result): int =>
                    (int) (
                        $result['news_count']
                        ?? 0
                    )
            );

        $profileFallbacks = collect($results)
            ->filter(
                fn (array $result): bool =>
                    (bool) (
                        $result['profile_fallback']
                        ?? false
                    )
            )
            ->count();

        $this->info(
            'Global Supply Chain synchronization completed.'
        );

        $this->newLine();

        $this->table(
            [
                'Global Summary',
                'Total',
            ],
            [
                [
                    'Countries',
                    $totalCountries,
                ],

                [
                    'Successful Countries',
                    $totalSuccessful,
                ],

                [
                    'Failed Countries',
                    $totalFailed,
                ],

                [
                    'Successful Services',
                    $totalServicesSuccessful,
                ],

                [
                    'Failed Services',
                    $totalServicesFailed,
                ],

                [
                    'News Processed',
                    $totalNewsProcessed,
                ],

                [
                    'Profile Fallbacks',
                    $profileFallbacks,
                ],
            ]
        );

        return $totalFailed === 0
            ? self::SUCCESS
            : self::FAILURE;
    }

    /*
    |--------------------------------------------------------------------------
    | Display Country Result
    |--------------------------------------------------------------------------
    */

    private function displayResult(
        array $result
    ): void {
        $this->newLine();

        $this->line(
            '<fg=cyan>'
            . ($result['country'] ?? 'Unknown Country')
            . '</>'
        );

        /*
        |--------------------------------------------------------------------------
        | Service Status Table
        |--------------------------------------------------------------------------
        */

        $this->table(
            [
                'Service',
                'Status',
            ],
            [
                [
                    'Profile',

                    $this->status(
                        $result['profile']
                        ?? false
                    ),
                ],

                [
                    'Weather',

                    $this->status(
                        $result['weather']
                        ?? false
                    ),
                ],

                [
                    'Currency',

                    $this->status(
                        $result['currency']
                        ?? false
                    ),
                ],

                [
                    'Market',

                    $this->status(
                        $result['market']
                        ?? false
                    ),
                ],

                [
                    'Market Trend',

                    $this->status(
                        $result['market_trend']
                        ?? false
                    ),
                ],

                [
                    'News',

                    $this->status(
                        $result['news']
                        ?? false
                    ),
                ],

                [
                    'Risk',

                    $this->status(
                        $result['risk']
                        ?? false
                    ),
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | News Count
        |--------------------------------------------------------------------------
        */

        $this->line(
            'News Processed: '
            . (
                $result['news_count']
                ?? 0
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Profile Fallback Information
        |--------------------------------------------------------------------------
        */

        if (
            (bool) (
                $result['profile_fallback']
                ?? false
            )
        ) {
            $this->newLine();

            $this->warn(
                'PROFILE FALLBACK: Existing database profile was used.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Warning Messages
        |--------------------------------------------------------------------------
        */

        if (
            ! empty(
                $result['warnings']
                ?? []
            )
        ) {
            $this->newLine();

            foreach (
                $result['warnings']
                as $service => $message
            ) {
                $this->warn(
                    strtoupper(
                        str_replace(
                            '_',
                            ' ',
                            $service
                        )
                    )
                    . ' WARNING: '
                    . $message
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Error Messages
        |--------------------------------------------------------------------------
        */

        if (
            ! empty(
                $result['errors']
                ?? []
            )
        ) {
            $this->newLine();

            foreach (
                $result['errors']
                as $service => $message
            ) {
                $this->error(
                    strtoupper(
                        str_replace(
                            '_',
                            ' ',
                            $service
                        )
                    )
                    . ': '
                    . $message
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        if (
            isset($result['summary'])
        ) {
            $this->newLine();

            $this->table(
                [
                    'Summary',
                    'Total',
                ],
                [
                    [
                        'Successful',

                        $result['summary']['successful']
                        ?? 0,
                    ],

                    [
                        'Failed',

                        $result['summary']['failed']
                        ?? 0,
                    ],

                    [
                        'Total Services',

                        $result['summary']['total']
                        ?? 7,
                    ],
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Final Country Status
            |--------------------------------------------------------------------------
            */

            if (
                (bool) (
                    $result['summary']['success']
                    ?? false
                )
            ) {
                $this->info(
                    ($result['country'] ?? 'Country')
                    . ' synchronization completed successfully.'
                );
            } else {
                $this->warn(
                    ($result['country'] ?? 'Country')
                    . ' synchronization completed with failure(s).'
                );
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Format Service Status
    |--------------------------------------------------------------------------
    */

    private function status(
        bool $success
    ): string {
        return $success
            ? '<fg=green>SUCCESS</>'
            : '<fg=red>FAILED</>';
    }
}