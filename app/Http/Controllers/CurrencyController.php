<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\CurrencyRate;
use App\Services\CurrencyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CurrencyController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Currency Rate Index
    |--------------------------------------------------------------------------
    |
    | GET /api/v1/currency
    |
    */

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_id' => [
                'nullable',
                'integer',
                'exists:countries,id',
            ],

            'base_currency' => [
                'nullable',
                'string',
                'size:3',
            ],

            'target_currency' => [
                'nullable',
                'string',
                'size:3',
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $perPage = (int) (
            $validated['per_page']
            ?? 30
        );

        $rates = CurrencyRate::query()
            ->with([
                'country:id,name,iso2,iso3,currency_code',
            ])

            ->when(
                isset($validated['country_id']),
                fn (Builder $query) =>
                    $query->where(
                        'country_id',
                        (int) $validated['country_id']
                    )
            )

            ->when(
                isset($validated['base_currency']),
                fn (Builder $query) =>
                    $query->where(
                        'base_currency',
                        strtoupper(
                            $validated['base_currency']
                        )
                    )
            )

            ->when(
                isset($validated['target_currency']),
                fn (Builder $query) =>
                    $query->where(
                        'target_currency',
                        strtoupper(
                            $validated['target_currency']
                        )
                    )
            )

            ->when(
                isset($validated['date_from']),
                fn (Builder $query) =>
                    $query->whereDate(
                        'recorded_at',
                        '>=',
                        $validated['date_from']
                    )
            )

            ->when(
                isset($validated['date_to']),
                fn (Builder $query) =>
                    $query->whereDate(
                        'recorded_at',
                        '<=',
                        $validated['date_to']
                    )
            )

            ->orderByDesc('recorded_at')
            ->orderByDesc('id')

            ->paginate($perPage);

        return response()->json([
            'success' => true,

            'message' =>
                'Currency rates retrieved successfully.',

            'data' =>
                $rates,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Latest Currency Rates
    |--------------------------------------------------------------------------
    |
    | GET /api/v1/currency/latest
    |
    */

    public function latest(): JsonResponse
    {
        $countries = Country::query()
            ->where('is_active', true)

            ->with([
                'latestCurrencyRate',
            ])

            ->orderBy('name')

            ->get()

            ->map(
                function (Country $country): array {
                    $rate =
                        $country->latestCurrencyRate;

                    return [
                        'country' => [
                            'id' =>
                                $country->id,

                            'name' =>
                                $country->name,

                            'iso2' =>
                                $country->iso2,

                            'iso3' =>
                                $country->iso3,

                            'currency_code' =>
                                $country->currency_code,

                            'currency_name' =>
                                $country->currency_name,

                            'currency_symbol' =>
                                $country->currency_symbol,
                        ],

                        'currency' => $rate
                            ? [
                                'id' =>
                                    $rate->id,

                                'base_currency' =>
                                    $rate->base_currency,

                                'target_currency' =>
                                    $rate->target_currency,

                                'exchange_rate' =>
                                    (float) $rate->exchange_rate,

                                'previous_rate' =>
                                    $rate->previous_rate !== null
                                        ? (float) $rate->previous_rate
                                        : null,

                                'percentage_change' =>
                                    (float) $rate->percentage_change,

                                'recorded_at' =>
                                    optional(
                                        $rate->recorded_at
                                    )->toISOString(),
                            ]
                            : null,
                    ];
                }
            )
            ->values();

        return response()->json([
            'success' => true,

            'message' =>
                'Latest currency rates retrieved successfully.',

            'data' =>
                $countries,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Synchronize Country Currency
    |--------------------------------------------------------------------------
    |
    | POST /api/v1/currency/{country}/sync
    |
    */

    public function sync(
        Country $country
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Check Active Country
        |--------------------------------------------------------------------------
        */

        if (! $country->is_active) {
            return response()->json(
                [
                    'success' => false,

                    'message' =>
                        'Currency synchronization is not available for inactive countries.',
                ],
                422
            );
        }

        try {
            /*
            |--------------------------------------------------------------------------
            | Refresh Country
            |--------------------------------------------------------------------------
            */

            $country->refresh();

            /*
            |--------------------------------------------------------------------------
            | Execute Currency Service
            |--------------------------------------------------------------------------
            */

            $rate =
                $this
                    ->currencyService
                    ->sync($country);

            /*
            |--------------------------------------------------------------------------
            | Handle Empty Result
            |--------------------------------------------------------------------------
            */

            if (! $rate) {
                return response()->json(
                    [
                        'success' => false,

                        'message' =>
                            'Currency synchronization returned no data.',

                        'data' =>
                            null,
                    ],
                    422
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Load Country Relationship
            |--------------------------------------------------------------------------
            */

            $rate->load([
                'country:id,name,iso2,iso3,currency_code',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Success Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,

                'message' =>
                    'Currency synchronized successfully.',

                'data' =>
                    $rate,
            ]);
        } catch (Throwable $exception) {
            /*
            |--------------------------------------------------------------------------
            | Application Log
            |--------------------------------------------------------------------------
            */

            Log::error(
                'Country currency synchronization failed.',
                [
                    'country_id' =>
                        $country->id,

                    'country' =>
                        $country->name,

                    'currency_code' =>
                        $country->currency_code,

                    'message' =>
                        $exception->getMessage(),
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Laravel Exception Reporting
            |--------------------------------------------------------------------------
            */

            report($exception);

            /*
            |--------------------------------------------------------------------------
            | Error Response
            |--------------------------------------------------------------------------
            */

            return response()->json(
                [
                    'success' => false,

                    'message' =>
                        'Currency synchronization failed.',

                    'error' =>
                        config('app.debug')
                            ? $exception->getMessage()
                            : null,
                ],
                500
            );
        }
    }
}