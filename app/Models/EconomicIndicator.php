<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EconomicIndicator extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'country_id',
        'year',
        'gdp',
        'gdp_growth',
        'inflation_rate',
        'exports',
        'imports',
        'population',
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

            'year' => 'integer',

            'gdp' => 'decimal:2',

            'gdp_growth' => 'decimal:3',

            'inflation_rate' => 'decimal:3',

            'exports' => 'decimal:2',

            'imports' => 'decimal:2',

            'population' => 'integer',
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
    | GDP Growth Helpers
    |--------------------------------------------------------------------------
    */

    public function hasPositiveGrowth(): bool
    {
        return (float) $this->gdp_growth > 0;
    }

    public function hasNegativeGrowth(): bool
    {
        return (float) $this->gdp_growth < 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Inflation Helpers
    |--------------------------------------------------------------------------
    */

    public function hasHighInflation(): bool
    {
        return (float) $this->inflation_rate >= 10;
    }

    /*
    |--------------------------------------------------------------------------
    | Trade Balance
    |--------------------------------------------------------------------------
    */

    public function getTradeBalanceAttribute(): float
    {
        return (float) $this->exports
            - (float) $this->imports;
    }

    /*
    |--------------------------------------------------------------------------
    | Trade Surplus / Deficit Helpers
    |--------------------------------------------------------------------------
    */

    public function hasTradeSurplus(): bool
    {
        return $this->trade_balance > 0;
    }

    public function hasTradeDeficit(): bool
    {
        return $this->trade_balance < 0;
    }
}