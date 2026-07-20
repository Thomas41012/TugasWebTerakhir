<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Port extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'country_id',
        'name',
        'unlocode',
        'city',
        'port_type',
        'latitude',
        'longitude',
        'status',
        'congestion_level',
        'risk_score',
        'metadata',
        'last_synced_at',
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

            'latitude' => 'decimal:7',

            'longitude' => 'decimal:7',

            'congestion_level' => 'integer',

            'risk_score' => 'decimal:2',

            'metadata' => 'array',

            'last_synced_at' => 'datetime',
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
    | Coordinate Helper
    |--------------------------------------------------------------------------
    */

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null
            && $this->longitude !== null;
    }

    /*
    |--------------------------------------------------------------------------
    | Active Port Helper
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return strtolower(
            (string) $this->status
        ) === 'active';
    }

    /*
    |--------------------------------------------------------------------------
    | High Congestion Helper
    |--------------------------------------------------------------------------
    */

    public function hasHighCongestion(): bool
    {
        return (int) $this->congestion_level >= 70;
    }

    /*
    |--------------------------------------------------------------------------
    | High Risk Helper
    |--------------------------------------------------------------------------
    */

    public function hasHighRisk(): bool
    {
        return (float) $this->risk_score >= 70;
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Active Ports
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where(
            'status',
            'active'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope With Coordinates
    |--------------------------------------------------------------------------
    */

    public function scopeWithCoordinates($query)
    {
        return $query
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }
}