<?php

namespace App\Services;

use App\Models\Country;
use App\Models\News;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class NewsService
{
    public function __construct(
        protected ApiLogService $apiLogService,
        protected SentimentService $sentimentService
    ) {
    }

    /**
     * Sinkronisasi berita satu negara.
     */
    public function sync(Country $country): int
    {
        /*
        |--------------------------------------------------------------------------
        | API Configuration
        |--------------------------------------------------------------------------
        */

        $apiKey = trim(
            (string) config('services.gnews.key')
        );

        if ($apiKey === '') {
            throw new RuntimeException(
                'GNEWS_API_KEY belum diisi di file .env.'
            );
        }

        $baseUrl = rtrim(
            (string) config(
                'services.gnews.url',
                'https://gnews.io/api/v4'
            ),
            '/'
        );

        $endpoint = "{$baseUrl}/search";

        /*
        |--------------------------------------------------------------------------
        | Request Parameters
        |--------------------------------------------------------------------------
        */

        $parameters = [
            'q' => "\"{$country->name}\" "
                . '(trade OR logistics OR shipping OR economy)',

            'lang' => 'en',

            'max' => 10,

            'apikey' => $apiKey,
        ];

        /*
        |--------------------------------------------------------------------------
        | Safe Request Data For API Log
        |--------------------------------------------------------------------------
        |
        | API key sengaja tidak disimpan ke database api_logs.
        |
        */

        $requestData = [
            'country_id' => $country->id,

            'country' => $country->name,

            'iso2' => $country->iso2,

            'iso3' => $country->iso3,

            'query' => $parameters['q'],

            'language' => $parameters['lang'],

            'max_articles' => $parameters['max'],
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
            | Request GNews API
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
                    "GNews request gagal dengan HTTP {$response->status()}.";

                $this->apiLogService->failure(
                    service: 'GNews',

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

            if (! is_array($responseData)) {
                $failureLogged = true;

                $errorMessage =
                    "Response GNews tidak valid untuk {$country->name}.";

                $this->apiLogService->failure(
                    service: 'GNews',

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
            | Get Articles
            |--------------------------------------------------------------------------
            */

            $articles = $responseData['articles'] ?? [];

            if (! is_array($articles)) {
                $failureLogged = true;

                $errorMessage =
                    "Data artikel GNews tidak valid untuk {$country->name}.";

                $this->apiLogService->failure(
                    service: 'GNews',

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
            | Save Articles
            |--------------------------------------------------------------------------
            */

            $saved = 0;

            foreach ($articles as $article) {
                if (! is_array($article)) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Article URL
                |--------------------------------------------------------------------------
                */

                $url = trim(
                    (string) (
                        $article['url']
                        ?? ''
                    )
                );

                if ($url === '') {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Sentiment Text
                |--------------------------------------------------------------------------
                */

                $text = implode(
                    ' ',
                    [
                        $article['title'] ?? '',

                        $article['description'] ?? '',

                        $article['content'] ?? '',
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | Sentiment Analysis
                |--------------------------------------------------------------------------
                */

                $sentiment = $this
                    ->sentimentService
                    ->analyze($text);

                /*
                |--------------------------------------------------------------------------
                | Published Date
                |--------------------------------------------------------------------------
                */

                $publishedAt = now();

                if (
                    ! empty(
                        $article['publishedAt']
                    )
                ) {
                    try {
                        $publishedAt = Carbon::parse(
                            $article['publishedAt']
                        );
                    } catch (Throwable) {
                        $publishedAt = now();
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Save News
                |--------------------------------------------------------------------------
                */

                News::query()->updateOrCreate(
                    [
                        'url' => $url,
                    ],
                    [
                        'country_id' => $country->id,

                        'title' => (
                            $article['title']
                            ?? 'Untitled'
                        ),

                        'description' => (
                            $article['description']
                            ?? null
                        ),

                        'content' => (
                            $article['content']
                            ?? null
                        ),

                        'source' => data_get(
                            $article,
                            'source.name'
                        ),

                        'image_url' => (
                            $article['image']
                            ?? null
                        ),

                        'category' => 'supply-chain',

                        'sentiment' => (
                            $sentiment['sentiment']
                            ?? 'neutral'
                        ),

                        'positive_score' => (
                            $sentiment['positive_score']
                            ?? 0
                        ),

                        'negative_score' => (
                            $sentiment['negative_score']
                            ?? 0
                        ),

                        'sentiment_score' => (
                            $sentiment['sentiment_score']
                            ?? 0
                        ),

                        'published_at' => $publishedAt,
                    ]
                );

                $saved++;
            }

            /*
            |--------------------------------------------------------------------------
            | Success API Log
            |--------------------------------------------------------------------------
            */

            $this->apiLogService->success(
                service: 'GNews',

                endpoint: $endpoint,

                statusCode: $response->status(),

                responseTimeMs: $responseTimeMs,

                requestData: array_merge(
                    $requestData,
                    [
                        'received_articles' =>
                            count($articles),

                        'processed_articles' =>
                            $saved,
                    ]
                ),

                method: 'GET',
            );

            return $saved;
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
            */

            if (! $failureLogged) {
                $this->apiLogService->failure(
                    service: 'GNews',

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
            | Throw Service Exception
            |--------------------------------------------------------------------------
            */

            throw new RuntimeException(
                "Gagal sinkronisasi berita {$country->name}: "
                . $exception->getMessage(),
                previous: $exception
            );
        }
    }

    /**
     * Alias untuk kompatibilitas kode lama.
     */
    public function syncCountryNews(
        Country $country
    ): int {
        try {
            return $this->sync($country);
        } catch (Throwable $exception) {
            report($exception);

            return 0;
        }
    }

    /**
     * Sinkronisasi berita seluruh negara aktif.
     */
    public function syncAllCountries(): int
    {
        $total = 0;

        Country::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->each(
                function (
                    Country $country
                ) use (&$total): void {
                    try {
                        $total += $this->sync(
                            $country
                        );
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                }
            );

        return $total;
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