<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Services\PortService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PortController extends Controller
{
    public function __construct(
        protected PortService $portService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Port Index
    |--------------------------------------------------------------------------
    |
    | GET /api/v1/ports
    |
    */

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'country_id' => [
                'nullable',
                'integer',
                'exists:countries,id',
            ],

            'limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:10000',
            ],
        ]);

        try {
            $search = isset($validated['search'])
                ? trim($validated['search'])
                : null;

            $countryId = isset($validated['country_id'])
                ? (int) $validated['country_id']
                : null;

            $limit = (int) (
                $validated['limit']
                ?? 5000
            );

            $ports = $this
                ->portService
                ->search(
                    search: $search,
                    countryId: $countryId,
                    limit: $limit
                );

            return response()->json([
                'success' => true,

                'message' =>
                    'Ports retrieved successfully.',

                'data' =>
                    $ports,

                'meta' => [
                    'total' =>
                        $ports->count(),

                    'limit' =>
                        $limit,

                    'filters' => [
                        'search' =>
                            $search,

                        'country_id' =>
                            $countryId,
                    ],
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error(
                'Port retrieval failed.',
                [
                    'message' =>
                        $exception->getMessage(),
                ]
            );

            report($exception);

            return response()->json(
                [
                    'success' => false,

                    'message' =>
                        'Failed to retrieve ports.',

                    'error' =>
                        config('app.debug')
                            ? $exception->getMessage()
                            : null,
                ],
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Port
    |--------------------------------------------------------------------------
    |
    | GET /api/v1/ports/{port}
    |
    */

    public function show(Port $port): JsonResponse
    {
        try {
            $port->load([
                'country:id,name,iso2,iso3',
            ]);

            return response()->json([
                'success' => true,

                'message' =>
                    'Port retrieved successfully.',

                'data' =>
                    $port,
            ]);
        } catch (Throwable $exception) {
            Log::error(
                'Port detail retrieval failed.',
                [
                    'port_id' =>
                        $port->id,

                    'message' =>
                        $exception->getMessage(),
                ]
            );

            report($exception);

            return response()->json(
                [
                    'success' => false,

                    'message' =>
                        'Failed to retrieve port.',

                    'error' =>
                        config('app.debug')
                            ? $exception->getMessage()
                            : null,
                ],
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Port GeoJSON
    |--------------------------------------------------------------------------
    |
    | GET /api/v1/ports/geojson
    |
    */

    public function geoJson(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'country_id' => [
                'nullable',
                'integer',
                'exists:countries,id',
            ],
        ]);

        try {
            $search = isset($validated['search'])
                ? trim($validated['search'])
                : null;

            $countryId = isset($validated['country_id'])
                ? (int) $validated['country_id']
                : null;

            $data = $this
                ->portService
                ->getGeoJson(
                    search: $search,
                    countryId: $countryId
                );

            return response()->json($data);
        } catch (Throwable $exception) {
            Log::error(
                'Port GeoJSON retrieval failed.',
                [
                    'message' =>
                        $exception->getMessage(),
                ]
            );

            report($exception);

            return response()->json(
                [
                    'type' =>
                        'FeatureCollection',

                    'features' =>
                        [],

                    'error' =>
                        config('app.debug')
                            ? $exception->getMessage()
                            : 'Failed to retrieve port GeoJSON data.',
                ],
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Port Statistics
    |--------------------------------------------------------------------------
    |
    | GET /api/v1/ports/statistics
    |
    */

    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this
                ->portService
                ->getStatistics();

            return response()->json([
                'success' => true,

                'message' =>
                    'Port statistics retrieved successfully.',

                'data' =>
                    $statistics,
            ]);
        } catch (Throwable $exception) {
            Log::error(
                'Port statistics retrieval failed.',
                [
                    'message' =>
                        $exception->getMessage(),
                ]
            );

            report($exception);

            return response()->json(
                [
                    'success' => false,

                    'message' =>
                        'Failed to retrieve port statistics.',

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