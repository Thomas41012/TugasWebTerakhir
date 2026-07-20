<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketTrend extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'country_id',
        'exchange_rate',
        'exchange_rate_change',
        'inflation_rate',
        'inflation_change',
        'market_impact_score',
        'trend_status',
        'recorded_at',
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

            'exchange_rate' => 'decimal:6',

            'exchange_rate_change' => 'decimal:4',

            'inflation_rate' => 'decimal:3',

            'inflation_change' => 'decimal:3',

            'market_impact_score' => 'decimal:2',

            'recorded_at' => 'datetime',
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
    | Positive Trend Helper
    |--------------------------------------------------------------------------
    */

    public function isPositive(): bool
    {
        return strtolower(
            (string) $this->trend_status
        ) === 'positive';
    }

    /*
    |--------------------------------------------------------------------------
    | Negative Trend Helper
    |--------------------------------------------------------------------------
    */

    public function isNegative(): bool
    {
        return strtolower(
            (string) $this->trend_status
        ) === 'negative';
    }

    /*
    |--------------------------------------------------------------------------
    | Neutral Trend Helper
    |--------------------------------------------------------------------------
    */

    public function isNeutral(): bool
    {
        return strtolower(
            (string) $this->trend_status
        ) === 'neutral';
    }

    /*
    |--------------------------------------------------------------------------
    | High Market Impact Helper
    |--------------------------------------------------------------------------
    */

    public function hasHighMarketImpact(): bool
    {
        return (float) $this->market_impact_score >= 70;
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Latest
    |--------------------------------------------------------------------------
    */

    public function scopeLatestRecorded($query)
    {
        return $query->orderByDesc(
            'recorded_at'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Positive
    |--------------------------------------------------------------------------
    */

    public function scopePositive($query)
    {
        return $query->where(
            'trend_status',
            'positive'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Negative
    |--------------------------------------------------------------------------
    */

    public function scopeNegative($query)
    {
        return $query->where(
            'trend_status',
            'negative'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Neutral
    |--------------------------------------------------------------------------
    */

    public function scopeNeutral($query)
    {
        return $query->where(
            'trend_status',
            'neutral'
        );
    }
}