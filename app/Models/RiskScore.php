<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskScore extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'country_id',
        'weather_score',
        'inflation_score',
        'currency_score',
        'political_score',
        'port_score',
        'total_score',
        'risk_level',
        'calculation_details',
        'calculated_at',
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

            'weather_score' => 'decimal:2',

            'inflation_score' => 'decimal:2',

            'currency_score' => 'decimal:2',

            'political_score' => 'decimal:2',

            'port_score' => 'decimal:2',

            'total_score' => 'decimal:2',

            'calculation_details' => 'array',

            'calculated_at' => 'datetime',
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
    | Risk Level Helpers
    |--------------------------------------------------------------------------
    */

    public function isLowRisk(): bool
    {
        return strtolower(
            (string) $this->risk_level
        ) === 'low';
    }

    public function isMediumRisk(): bool
    {
        return strtolower(
            (string) $this->risk_level
        ) === 'medium';
    }

    public function isHighRisk(): bool
    {
        return strtolower(
            (string) $this->risk_level
        ) === 'high';
    }

    public function isCriticalRisk(): bool
    {
        return strtolower(
            (string) $this->risk_level
        ) === 'critical';
    }

    /*
    |--------------------------------------------------------------------------
    | Risk Status
    |--------------------------------------------------------------------------
    */

    public function getRiskStatusAttribute(): string
    {
        return match (
            strtolower(
                (string) $this->risk_level
            )
        ) {
            'critical' =>
                'Critical Risk',

            'high' =>
                'High Risk',

            'medium' =>
                'Medium Risk',

            default =>
                'Low Risk',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Risk Percentage
    |--------------------------------------------------------------------------
    */

    public function getRiskPercentageAttribute(): float
    {
        return round(
            min(
                100,
                max(
                    0,
                    (float) $this->total_score
                )
            ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Calculation Weights
    |--------------------------------------------------------------------------
    */

    public function getWeightsAttribute(): array
    {
        return data_get(
            $this->calculation_details,
            'weights',
            []
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Raw Scores
    |--------------------------------------------------------------------------
    */

    public function getRawScoresAttribute(): array
    {
        return data_get(
            $this->calculation_details,
            'raw_scores',
            []
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Weighted Scores
    |--------------------------------------------------------------------------
    */

    public function getWeightedScoresAttribute(): array
    {
        return data_get(
            $this->calculation_details,
            'weighted_scores',
            []
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Data Information
    |--------------------------------------------------------------------------
    */

    public function getDataInformationAttribute(): array
    {
        return data_get(
            $this->calculation_details,
            'data_information',
            []
        );
    }
}