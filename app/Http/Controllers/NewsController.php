<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\News;
use App\Services\NewsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class NewsController extends Controller
{
    public function __construct(
        protected NewsService $newsService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | News Index
    |--------------------------------------------------------------------------
    |
    | GET /api/v1/news
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

            'sentiment' => [
                'nullable',
                'string',
                'in:positive,neutral,negative',
            ],

            'category' => [
                'nullable',
                'string',
                'max:100',
            ],

            'search' => [
                'nullable',
                'string',
                'max:255',
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
            ?? 20
        );

        $news = News::query()

            /*
            |--------------------------------------------------------------------------
            | Country Relationship
            |--------------------------------------------------------------------------
            */

            ->with([
                'country:id,name,iso2,iso3',
            ])

            /*
            |--------------------------------------------------------------------------
            | Country Filter
            |--------------------------------------------------------------------------
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
            |--------------------------------------------------------------------------
            | Sentiment Filter
            |--------------------------------------------------------------------------
            */

            ->when(
                isset($validated['sentiment']),
                fn (Builder $query) =>
                    $query->where(
                        'sentiment',
                        $validated['sentiment']
                    )
            )

            /*
            |--------------------------------------------------------------------------
            | Category Filter
            |--------------------------------------------------------------------------
            */

            ->when(
                isset($validated['category']),
                fn (Builder $query) =>
                    $query->where(
                        'category',
                        $validated['category']
                    )
            )

            /*
            |--------------------------------------------------------------------------
            | Search Filter
            |--------------------------------------------------------------------------
            */

            ->when(
                isset($validated['search']),
                function (Builder $query) use ($validated): void {
                    $search =
                        trim($validated['search']);

                    $query->where(
                        function (Builder $subQuery) use ($search): void {
                            $subQuery
                                ->where(
                                    'title',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'description',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'content',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'source',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Start Date Filter
            |--------------------------------------------------------------------------
            */

            ->when(
                isset($validated['date_from']),
                fn (Builder $query) =>
                    $query->whereDate(
                        'published_at',
                        '>=',
                        $validated['date_from']
                    )
            )

            /*
            |--------------------------------------------------------------------------
            | End Date Filter
            |--------------------------------------------------------------------------
            */

            ->when(
                isset($validated['date_to']),
                fn (Builder $query) =>
                    $query->whereDate(
                        'published_at',
                        '<=',
                        $validated['date_to']
                    )
            )

            /*
            |--------------------------------------------------------------------------
            | Latest News First
            |--------------------------------------------------------------------------
            */

            ->orderByDesc('published_at')
            ->orderByDesc('id')

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            ->paginate($perPage);

        return response()->json([
            'success' => true,

            'message' =>
                'News retrieved successfully.',

            'data' =>
                $news,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show News
    |--------------------------------------------------------------------------
    |
    | GET /api/v1/news/{news}
    |
    */

    public function show(News $news): JsonResponse
    {
        $news->load([
            'country:id,name,iso2,iso3',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'News article retrieved successfully.',

            'data' =>
                $news,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Synchronize Country News
    |--------------------------------------------------------------------------
    |
    | POST /api/v1/news/{country}/sync
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
                        'News synchronization is not available for inactive countries.',
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
            | Synchronize News
            |--------------------------------------------------------------------------
            */

            $total =
                $this
                    ->newsService
                    ->sync($country);

            /*
            |--------------------------------------------------------------------------
            | Normalize Result
            |--------------------------------------------------------------------------
            */

            $total = (int) $total;

            /*
            |--------------------------------------------------------------------------
            | Success Response
            |--------------------------------------------------------------------------
            |
            | Nilai 0 tetap dianggap berhasil karena request API
            | dapat berhasil meskipun tidak ada artikel baru.
            |
            */

            return response()->json([
                'success' => true,

                'message' =>
                    "{$total} news articles processed successfully.",

                'data' => [
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

                    'total' =>
                        $total,
                ],
            ]);
        } catch (Throwable $exception) {
            /*
            |--------------------------------------------------------------------------
            | Application Log
            |--------------------------------------------------------------------------
            */

            Log::error(
                'Country news synchronization failed.',
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
                        'News synchronization failed.',

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