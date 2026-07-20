<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
use App\Models\Article;
use App\Models\Country;
use App\Models\News;
use App\Models\Port;
use App\Models\RiskScore;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function statistics(): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | General Statistics
        |--------------------------------------------------------------------------
        */

        $totalUsers = User::count();

        $totalCountries = Country::count();

        $activeCountries = Country::where(
            'is_active',
            true
        )->count();

        $totalPorts = Port::count();

        $totalNews = News::count();

        $totalArticles = Article::count();

        /*
        |--------------------------------------------------------------------------
        | Port Statistics
        |--------------------------------------------------------------------------
        */

        $activePorts = Port::where(
            'status',
            'active'
        )->count();

        $highCongestionPorts = Port::where(
            'congestion_level',
            '>=',
            70
        )->count();

        $highRiskPorts = Port::where(
            'risk_score',
            '>=',
            70
        )->count();

        $averagePortRisk = Port::avg(
            'risk_score'
        );

        /*
        |--------------------------------------------------------------------------
        | News Statistics
        |--------------------------------------------------------------------------
        */

        $positiveNews = News::where(
            'sentiment',
            'positive'
        )->count();

        $neutralNews = News::where(
            'sentiment',
            'neutral'
        )->count();

        $negativeNews = News::where(
            'sentiment',
            'negative'
        )->count();

        /*
        |--------------------------------------------------------------------------
        | Risk Statistics
        |--------------------------------------------------------------------------
        */

        $totalRiskRecords = RiskScore::count();

        $lowRiskRecords = RiskScore::where(
            'risk_level',
            'low'
        )->count();

        $mediumRiskRecords = RiskScore::where(
            'risk_level',
            'medium'
        )->count();

        $highRiskRecords = RiskScore::where(
            'risk_level',
            'high'
        )->count();

        $criticalRiskRecords = RiskScore::where(
            'risk_level',
            'critical'
        )->count();

        $averageRiskScore = RiskScore::avg(
            'total_score'
        );

        /*
        |--------------------------------------------------------------------------
        | Latest Risk Per Country
        |--------------------------------------------------------------------------
        */

        $countries = Country::where(
            'is_active',
            true
        )
            ->with('latestRiskScore')
            ->get();

        $latestCountryRisks = $countries
            ->pluck('latestRiskScore')
            ->filter();

        $latestLowRiskCountries = $latestCountryRisks
            ->where(
                'risk_level',
                'low'
            )
            ->count();

        $latestMediumRiskCountries = $latestCountryRisks
            ->where(
                'risk_level',
                'medium'
            )
            ->count();

        $latestHighRiskCountries = $latestCountryRisks
            ->where(
                'risk_level',
                'high'
            )
            ->count();

        $latestCriticalRiskCountries = $latestCountryRisks
            ->where(
                'risk_level',
                'critical'
            )
            ->count();

        $latestAverageRiskScore = $latestCountryRisks
            ->avg('total_score');

        /*
        |--------------------------------------------------------------------------
        | API Statistics
        |--------------------------------------------------------------------------
        */

        $totalApiRequests = ApiLog::count();

        $successfulApiRequests = ApiLog::where(
            'success',
            true
        )->count();

        $failedApiRequests = ApiLog::where(
            'success',
            false
        )->count();

        $averageApiResponseTime = ApiLog::avg(
            'response_time_ms'
        );

        /*
        |--------------------------------------------------------------------------
        | API Success Rate
        |--------------------------------------------------------------------------
        */

        $apiSuccessRate = $totalApiRequests > 0
            ? (
                $successfulApiRequests
                / $totalApiRequests
            ) * 100
            : 0;

        /*
        |--------------------------------------------------------------------------
        | JSON Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' =>
                'Admin statistics retrieved successfully.',

            'data' => [
                'general' => [
                    'users' =>
                        $totalUsers,

                    'countries' =>
                        $totalCountries,

                    'active_countries' =>
                        $activeCountries,

                    'ports' =>
                        $totalPorts,

                    'news' =>
                        $totalNews,

                    'articles' =>
                        $totalArticles,
                ],

                'ports' => [
                    'total' =>
                        $totalPorts,

                    'active' =>
                        $activePorts,

                    'high_congestion' =>
                        $highCongestionPorts,

                    'high_risk' =>
                        $highRiskPorts,

                    'average_risk' =>
                        round(
                            (float) ($averagePortRisk ?? 0),
                            2
                        ),
                ],

                'news' => [
                    'total' =>
                        $totalNews,

                    'positive' =>
                        $positiveNews,

                    'neutral' =>
                        $neutralNews,

                    'negative' =>
                        $negativeNews,
                ],

                'risk_records' => [
                    'total' =>
                        $totalRiskRecords,

                    'low' =>
                        $lowRiskRecords,

                    'medium' =>
                        $mediumRiskRecords,

                    'high' =>
                        $highRiskRecords,

                    'critical' =>
                        $criticalRiskRecords,

                    'average_score' =>
                        round(
                            (float) ($averageRiskScore ?? 0),
                            2
                        ),
                ],

                'latest_country_risk' => [
                    'total' =>
                        $latestCountryRisks->count(),

                    'low' =>
                        $latestLowRiskCountries,

                    'medium' =>
                        $latestMediumRiskCountries,

                    'high' =>
                        $latestHighRiskCountries,

                    'critical' =>
                        $latestCriticalRiskCountries,

                    'average_score' =>
                        round(
                            (float) ($latestAverageRiskScore ?? 0),
                            2
                        ),
                ],

                'api' => [
                    'requests' =>
                        $totalApiRequests,

                    'successful' =>
                        $successfulApiRequests,

                    'failed' =>
                        $failedApiRequests,

                    'success_rate' =>
                        round(
                            $apiSuccessRate,
                            2
                        ),

                    'average_response_time_ms' =>
                        round(
                            (float) ($averageApiResponseTime ?? 0),
                            2
                        ),
                ],
            ],
        ]);
    }

    public function apiLogs(
        Request $request
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'service' => [
                'nullable',
                'string',
                'max:100',
            ],

            'success' => [
                'nullable',
                'boolean',
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

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = (int) (
            $validated['per_page']
            ?? 50
        );

        /*
        |--------------------------------------------------------------------------
        | Query
        |--------------------------------------------------------------------------
        */

        $query = ApiLog::query();

        /*
        |--------------------------------------------------------------------------
        | Service Filter
        |--------------------------------------------------------------------------
        */

        if (isset($validated['service'])) {
            $query->where(
                'service',
                $validated['service']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Success Filter
        |--------------------------------------------------------------------------
        */

        if (
            array_key_exists(
                'success',
                $validated
            )
        ) {
            $query->where(
                'success',
                (bool) $validated['success']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Search Filter
        |--------------------------------------------------------------------------
        */

        if (isset($validated['search'])) {
            $search = trim(
                $validated['search']
            );

            $query->where(
                function ($subQuery) use ($search) {
                    $subQuery
                        ->where(
                            'service',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'endpoint',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'error_message',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Date From
        |--------------------------------------------------------------------------
        */

        if (isset($validated['date_from'])) {
            $query->whereDate(
                'requested_at',
                '>=',
                $validated['date_from']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Date To
        |--------------------------------------------------------------------------
        */

        if (isset($validated['date_to'])) {
            $query->whereDate(
                'requested_at',
                '<=',
                $validated['date_to']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Execute Query
        |--------------------------------------------------------------------------
        */

        $logs = $query
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        /*
        |--------------------------------------------------------------------------
        | JSON Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' =>
                'API logs retrieved successfully.',

            'data' =>
                $logs,
        ]);
    }
}