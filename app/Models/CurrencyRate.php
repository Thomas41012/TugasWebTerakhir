<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrencyRate extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'country_id',
        'base_currency',
        'target_currency',
        'exchange_rate',
        'previous_rate',
        'percentage_change',
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

            'base_currency' => 'string',

            'target_currency' => 'string',

            'exchange_rate' => 'decimal:6',

            'previous_rate' => 'decimal:6',

            'percentage_change' => 'decimal:4',

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
    | Currency Increased Helper
    |--------------------------------------------------------------------------
    */

    public function hasIncreased(): bool
    {
        return (float) $this->percentage_change > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Currency Decreased Helper
    |--------------------------------------------------------------------------
    */

    public function hasDecreased(): bool
    {
        return (float) $this->percentage_change < 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Currency Stable Helper
    |--------------------------------------------------------------------------
    */

    public function isStable(): bool
    {
        return (float) $this->percentage_change === 0.0;
    }

    /*
    |--------------------------------------------------------------------------
    | Absolute Percentage Change
    |--------------------------------------------------------------------------
    */

    public function getAbsolutePercentageChangeAttribute(): float
    {
        return abs(
            (float) $this->percentage_change
        );
    }
}