<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\RiskScore;
use App\Services\RiskScoringService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RiskController extends Controller
{
    public function __construct(
        protected RiskScoringService $riskScoringService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Risk Score Index
    |--------------------------------------------------------------------------
    |
    | GET /api/v1/risk
    |
    */

    public function index(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Request Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'country_id' => [
                'nullable',
                'integer',
                'exists:countries,id',
            ],

            'risk_level' => [
                'nullable',
                'string',
                'in:low,medium,high,critical',
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

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = (int) (
            $validated['per_page']
            ?? 20
        );

        /*
        |--------------------------------------------------------------------------
        | Risk Query
        |--------------------------------------------------------------------------
        */

        $risks = RiskScore::query()

            /*
             * Load Country Relationship
             */

            ->with([
                'country:id,name,iso2,iso3',
            ])

            /*
             * Country Filter
             */

            ->when(
                isset($validated['country_id']),
                fn (Builder $query) =>
                    $query->where(
                        'country_id',
                        (int) $validated['country_id']
                    )
            )

            /*
             * Risk Level Filter
             */

            ->when(
                isset($validated['risk_level']),
                fn (Builder $query) =>
                    $query->where(
                        'risk_level',
                        $validated['risk_level']
                    )
            )

            /*
             * Start Date Filter
             */

            ->when(
                isset($validated['date_from']),
                fn (Builder $query) =>
                    $query->whereDate(
                        'calculated_at',
                        '>=',
                        $validated['date_from']
                    )
            )

            /*
             * End Date Filter
             */

            ->when(
                isset($validated['date_to']),
                fn (Builder $query) =>
                    $query->whereDate(
                        'calculated_at',
                        '<=',
                        $validated['date_to']
                    )
            )

            /*
             * Latest Risk First
             */

            ->orderByDesc('calculated_at')
            ->orderByDesc('id')

            /*
             * Pagination
             */

            ->paginate($perPage);

        /*
        |--------------------------------------------------------------------------
        | JSON Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' =>
                'Risk scores retrieved successfully.',

            'data' =>
                $risks,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Latest Risk Score
    |--------------------------------------------------------------------------
    |
    | GET /api/v1/risk/latest
    |
    */

    public function latest(): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Active Countries
        |--------------------------------------------------------------------------
        */

        $countries = Country::query()
            ->where('is_active', true)

            /*
             * Load Latest Risk
             */

            ->with([
                'latestRiskScore',
            ])

            /*
             * Country Order
             */

            ->orderBy('name')

            /*
             * Execute Query
             */

            ->get();

        /*
        |--------------------------------------------------------------------------
        | Transform Response
        |--------------------------------------------------------------------------
        */

        $risks = $countries
            ->map(
                function (Country $country): array {
                    $risk =
                        $country->latestRiskScore;

                    return [
                        /*
                        |--------------------------------------------------------------------------
                        | Country
                        |--------------------------------------------------------------------------
                        */

                        'country' => [
                            'id' =>
                                $country->id,

                            'name' =>
                                $country->name,

                            'iso2' =>
                                $country->iso2,

                            'iso3' =>
                                $country->iso3,
                        ],

                        /*
                        |--------------------------------------------------------------------------
                        | Risk Score
                        |--------------------------------------------------------------------------
                        */

                        'risk' => $risk
                            ? [
                                'id' =>
                                    $risk->id,

                                'weather_score' =>
                                    (float) $risk->weather_score,

                                'inflation_score' =>
                                    (float) $risk->inflation_score,

                                'currency_score' =>
                                    (float) $risk->currency_score,

                                'political_score' =>
                                    (float) $risk->political_score,

                                'port_score' =>
                                    (float) $risk->port_score,

                                'total_score' =>
                                    (float) $risk->total_score,

                                'risk_level' =>
                                    $risk->risk_level,

                                'calculation_details' =>
                                    $risk->calculation_details,

                                'calculated_at' =>
                                    optional(
                                        $risk->calculated_at
                                    )->toISOString(),
                            ]
                            : null,
                    ];
                }
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | JSON Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' =>
                'Latest risk scores retrieved successfully.',

            'data' =>
                $risks,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Country Risk
    |--------------------------------------------------------------------------
    |
    | POST /api/v1/risk/{country}/calculate
    |
    */

    public function calculate(
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
                        'Risk calculation is not available for inactive countries.',
                ],
                422
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate Risk
        |--------------------------------------------------------------------------
        */

        try {
            /*
             * Refresh country model before
             * performing risk calculation.
             */

            $country->refresh();

            /*
             * Execute Risk Scoring Service
             */

            $risk =
                $this
                    ->riskScoringService
                    ->calculate($country);

            /*
             * Handle Empty Result
             */

            if (! $risk) {
                return response()->json(
                    [
                        'success' => false,

                        'message' =>
                            'Risk calculation returned no data.',
                    ],
                    422
                );
            }

            /*
             * Load Country Relationship
             */

            $risk->load([
                'country:id,name,iso2,iso3',
            ]);

            /*
            |--------------------------------------------------------------------------
            | JSON Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,

                'message' =>
                    'Risk score calculated successfully.',

                'data' =>
                    $risk,
            ]);
        } catch (Throwable $exception) {
            /*
            |--------------------------------------------------------------------------
            | Application Log
            |--------------------------------------------------------------------------
            */

            Log::error(
                'Country risk calculation failed.',
                [
                    'country_id' =>
                        $country->id,

                    'country' =>
                        $country->name,

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
                        'Risk score calculation failed.',

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