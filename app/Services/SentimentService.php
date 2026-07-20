<?php

namespace App\Services;

use App\Models\NegativeWord;
use App\Models\PositiveWord;

class SentimentService
{
    /*
    |--------------------------------------------------------------------------
    | Sentiment Dictionaries
    |--------------------------------------------------------------------------
    */

    protected array $positiveWords = [];

    protected array $negativeWords = [];

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct()
    {
        $this->loadDictionaries();
    }

    /*
    |--------------------------------------------------------------------------
    | Load Sentiment Dictionaries
    |--------------------------------------------------------------------------
    */

    private function loadDictionaries(): void
    {
        $this->positiveWords = PositiveWord::query()
            ->get([
                'word',
                'weight',
            ])
            ->mapWithKeys(
                fn (PositiveWord $positiveWord): array => [
                    mb_strtolower(
                        trim($positiveWord->word)
                    ) => (float) $positiveWord->weight,
                ]
            )
            ->all();

        $this->negativeWords = NegativeWord::query()
            ->get([
                'word',
                'weight',
            ])
            ->mapWithKeys(
                fn (NegativeWord $negativeWord): array => [
                    mb_strtolower(
                        trim($negativeWord->word)
                    ) => (float) $negativeWord->weight,
                ]
            )
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Analyze Sentiment
    |--------------------------------------------------------------------------
    */

    public function analyze(string $text): array
    {
        /*
        |--------------------------------------------------------------------------
        | Tokenize Text
        |--------------------------------------------------------------------------
        */

        $words = $this->tokenize($text);

        /*
        |--------------------------------------------------------------------------
        | Initial Scores
        |--------------------------------------------------------------------------
        */

        $positiveScore = 0.0;

        $negativeScore = 0.0;

        $positiveMatches = [];

        $negativeMatches = [];

        /*
        |--------------------------------------------------------------------------
        | Analyze Every Word
        |--------------------------------------------------------------------------
        */

        foreach ($words as $word) {
            /*
            |--------------------------------------------------------------------------
            | Positive Word
            |--------------------------------------------------------------------------
            */

            if (
                array_key_exists(
                    $word,
                    $this->positiveWords
                )
            ) {
                $positiveScore +=
                    $this->positiveWords[$word];

                $positiveMatches[] = $word;
            }

            /*
            |--------------------------------------------------------------------------
            | Negative Word
            |--------------------------------------------------------------------------
            */

            if (
                array_key_exists(
                    $word,
                    $this->negativeWords
                )
            ) {
                $negativeScore +=
                    $this->negativeWords[$word];

                $negativeMatches[] = $word;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Total Sentiment Weight
        |--------------------------------------------------------------------------
        */

        $totalScore =
            $positiveScore
            + $negativeScore;

        /*
        |--------------------------------------------------------------------------
        | Normalized Sentiment Score
        |--------------------------------------------------------------------------
        |
        | Hasil berada pada rentang:
        |
        | -1.0 = Sangat Negatif
        |  0.0 = Netral
        |  1.0 = Sangat Positif
        |
        */

        $normalizedScore =
            $totalScore > 0
                ? (
                    $positiveScore
                    - $negativeScore
                ) / $totalScore
                : 0.0;

        /*
        |--------------------------------------------------------------------------
        | Determine Sentiment
        |--------------------------------------------------------------------------
        */

        $sentiment = $this->determineSentiment(
            $normalizedScore
        );

        /*
        |--------------------------------------------------------------------------
        | Analysis Result
        |--------------------------------------------------------------------------
        */

        return [
            'sentiment' => $sentiment,

            /*
             * Tetap integer karena migration tabel news
             * menggunakan tipe integer.
             */

            'positive_score' => (int) round(
                $positiveScore
            ),

            'negative_score' => (int) round(
                $negativeScore
            ),

            'sentiment_score' => round(
                $normalizedScore,
                4
            ),

            'positive_matches' => array_values(
                array_unique(
                    $positiveMatches
                )
            ),

            'negative_matches' => array_values(
                array_unique(
                    $negativeMatches
                )
            ),

            'positive_match_count' => count(
                $positiveMatches
            ),

            'negative_match_count' => count(
                $negativeMatches
            ),

            'total_words' => count(
                $words
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Determine Sentiment
    |--------------------------------------------------------------------------
    */

    private function determineSentiment(
        float $normalizedScore
    ): string {
        return match (true) {
            $normalizedScore > 0.15 =>
                'positive',

            $normalizedScore < -0.15 =>
                'negative',

            default =>
                'neutral',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Tokenize Text
    |--------------------------------------------------------------------------
    */

    private function tokenize(string $text): array
    {
        /*
        |--------------------------------------------------------------------------
        | Convert Text To Lowercase
        |--------------------------------------------------------------------------
        */

        $text = mb_strtolower(
            trim($text)
        );

        /*
        |--------------------------------------------------------------------------
        | Remove URL
        |--------------------------------------------------------------------------
        */

        $text = preg_replace(
            '/https?:\/\/\S+/u',
            ' ',
            $text
        ) ?? $text;

        /*
        |--------------------------------------------------------------------------
        | Remove Special Characters
        |--------------------------------------------------------------------------
        */

        $text = preg_replace(
            '/[^\p{L}\p{N}\s]/u',
            ' ',
            $text
        ) ?? $text;

        /*
        |--------------------------------------------------------------------------
        | Split Text Into Words
        |--------------------------------------------------------------------------
        */

        $words = preg_split(
            '/\s+/u',
            $text
        ) ?: [];

        /*
        |--------------------------------------------------------------------------
        | Remove Empty Words
        |--------------------------------------------------------------------------
        */

        return array_values(
            array_filter(
                $words,
                fn (string $word): bool =>
                    trim($word) !== ''
            )
        );
    }
}