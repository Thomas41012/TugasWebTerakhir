<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class News extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Table Name
    |--------------------------------------------------------------------------
    */

    protected $table = 'news';

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'country_id',
        'title',
        'description',
        'content',
        'source',
        'url',
        'image_url',
        'category',
        'sentiment',
        'positive_score',
        'negative_score',
        'sentiment_score',
        'published_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'country_id' => 'integer',

            'positive_score' => 'integer',

            'negative_score' => 'integer',

            'sentiment_score' => 'decimal:4',

            'published_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Country Relationship
    |--------------------------------------------------------------------------
    */

    public function country(): BelongsTo
    {
        return $this->belongsTo(
            Country::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sentiment Helpers
    |--------------------------------------------------------------------------
    */

    public function isPositive(): bool
    {
        return strtolower(
            (string) $this->sentiment
        ) === 'positive';
    }

    public function isNegative(): bool
    {
        return strtolower(
            (string) $this->sentiment
        ) === 'negative';
    }

    public function isNeutral(): bool
    {
        return strtolower(
            (string) $this->sentiment
        ) === 'neutral';
    }

    /*
    |--------------------------------------------------------------------------
    | Sentiment Label
    |--------------------------------------------------------------------------
    */

    public function getSentimentLabelAttribute(): string
    {
        return match (
            strtolower(
                (string) $this->sentiment
            )
        ) {
            'positive' => 'Positive',

            'negative' => 'Negative',

            default => 'Neutral',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Has Image
    |--------------------------------------------------------------------------
    */

    public function hasImage(): bool
    {
        return ! empty(
            $this->image_url
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Has Description
    |--------------------------------------------------------------------------
    */

    public function hasDescription(): bool
    {
        return ! empty(
            $this->description
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Latest Published
    |--------------------------------------------------------------------------
    */

    public function scopeLatestPublished($query)
    {
        return $query->latest(
            'published_at'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Positive Sentiment
    |--------------------------------------------------------------------------
    */

    public function scopePositive($query)
    {
        return $query->where(
            'sentiment',
            'positive'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Negative Sentiment
    |--------------------------------------------------------------------------
    */

    public function scopeNegative($query)
    {
        return $query->where(
            'sentiment',
            'negative'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Neutral Sentiment
    |--------------------------------------------------------------------------
    */

    public function scopeNeutral($query)
    {
        return $query->where(
            'sentiment',
            'neutral'
        );
    }
}