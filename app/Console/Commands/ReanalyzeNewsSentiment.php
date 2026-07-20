<?php

namespace App\Console\Commands;

use App\Models\News;
use App\Services\SentimentService;
use Illuminate\Console\Command;
use Throwable;

class ReanalyzeNewsSentiment extends Command
{
    /*
    |--------------------------------------------------------------------------
    | Command Signature
    |--------------------------------------------------------------------------
    */

    protected $signature = 'news:reanalyze-sentiment
                            {--country= : Country ID}
                            {--chunk=100 : Number of news processed per chunk}';

    /*
    |--------------------------------------------------------------------------
    | Command Description
    |--------------------------------------------------------------------------
    */

    protected $description =
        'Reanalyze sentiment for all existing supply chain news';

    /*
    |--------------------------------------------------------------------------
    | Execute Command
    |--------------------------------------------------------------------------
    */

    public function handle(
        SentimentService $sentimentService
    ): int {
        $countryId = $this->option('country');

        $chunkSize = max(
            1,
            (int) $this->option('chunk')
        );

        /*
        |--------------------------------------------------------------------------
        | Build News Query
        |--------------------------------------------------------------------------
        */

        $query = News::query()
            ->orderBy('id');

        if (
            $countryId !== null
            && $countryId !== ''
        ) {
            $query->where(
                'country_id',
                (int) $countryId
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Count News
        |--------------------------------------------------------------------------
        */

        $totalNews = (clone $query)->count();

        if ($totalNews === 0) {
            $this->warn(
                'No news available for sentiment analysis.'
            );

            return self::SUCCESS;
        }

        /*
        |--------------------------------------------------------------------------
        | Command Information
        |--------------------------------------------------------------------------
        */

        $this->info(
            'Starting news sentiment reanalysis...'
        );

        $this->newLine();

        $this->line(
            "Total News: {$totalNews}"
        );

        $this->line(
            "Chunk Size: {$chunkSize}"
        );

        if (
            $countryId !== null
            && $countryId !== ''
        ) {
            $this->line(
                "Country ID: {$countryId}"
            );
        }

        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | Progress Bar
        |--------------------------------------------------------------------------
        */

        $progressBar = $this
            ->output
            ->createProgressBar($totalNews);

        $progressBar->start();

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $processed = 0;

        $updated = 0;

        $failed = 0;

        $positive = 0;

        $neutral = 0;

        $negative = 0;

        /*
        |--------------------------------------------------------------------------
        | Process News
        |--------------------------------------------------------------------------
        */

        $query->chunkById(
            $chunkSize,
            function ($articles) use (
                $sentimentService,
                $progressBar,
                &$processed,
                &$updated,
                &$failed,
                &$positive,
                &$neutral,
                &$negative
            ): void {
                foreach ($articles as $article) {
                    try {
                        /*
                        |--------------------------------------------------------------------------
                        | Combine News Text
                        |--------------------------------------------------------------------------
                        */

                        $text = implode(
                            ' ',
                            array_filter([
                                $article->title,
                                $article->description,
                                $article->content,
                            ])
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Analyze Sentiment
                        |--------------------------------------------------------------------------
                        */

                        $result = $sentimentService
                            ->analyze($text);

                        /*
                        |--------------------------------------------------------------------------
                        | Update News
                        |--------------------------------------------------------------------------
                        */

                        $article->update([
                            'sentiment' =>
                                $result['sentiment'],

                            'positive_score' =>
                                $result['positive_score'],

                            'negative_score' =>
                                $result['negative_score'],

                            'sentiment_score' =>
                                $result['sentiment_score'],
                        ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Sentiment Statistics
                        |--------------------------------------------------------------------------
                        */

                        match ($result['sentiment']) {
                            'positive' =>
                                $positive++,

                            'negative' =>
                                $negative++,

                            default =>
                                $neutral++,
                        };

                        $updated++;
                    } catch (Throwable $exception) {
                        $failed++;

                        report($exception);
                    }

                    $processed++;

                    $progressBar->advance();
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Finish Progress Bar
        |--------------------------------------------------------------------------
        */

        $progressBar->finish();

        $this->newLine(2);

        /*
        |--------------------------------------------------------------------------
        | Display Results
        |--------------------------------------------------------------------------
        */

        $this->table(
            [
                'Result',
                'Total',
            ],
            [
                [
                    'Processed',
                    $processed,
                ],
                [
                    'Updated',
                    $updated,
                ],
                [
                    'Failed',
                    $failed,
                ],
                [
                    'Positive',
                    $positive,
                ],
                [
                    'Neutral',
                    $neutral,
                ],
                [
                    'Negative',
                    $negative,
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Final Status
        |--------------------------------------------------------------------------
        */

        if ($failed > 0) {
            $this->warn(
                "Sentiment reanalysis completed with {$failed} failure(s)."
            );

            return self::FAILURE;
        }

        $this->info(
            'News sentiment reanalysis completed successfully.'
        );

        return self::SUCCESS;
    }
}