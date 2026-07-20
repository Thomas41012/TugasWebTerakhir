<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherRecord extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'country_id',
        'temperature',
        'feels_like',
        'humidity',
        'precipitation',
        'rain',
        'cloud_cover',
        'pressure',
        'wind_speed',
        'wind_direction',
        'weather_code',
        'weather_condition',
        'weather_risk_score',
        'extreme_weather',
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

            'temperature' => 'float',

            'feels_like' => 'float',

            'humidity' => 'float',

            'precipitation' => 'float',

            'rain' => 'float',

            'cloud_cover' => 'float',

            'pressure' => 'float',

            'wind_speed' => 'float',

            'wind_direction' => 'float',

            'weather_code' => 'integer',

            'weather_condition' => 'string',

            'weather_risk_score' => 'float',

            'extreme_weather' => 'boolean',

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
    | Extreme Weather Helper
    |--------------------------------------------------------------------------
    */

    public function isExtremeWeather(): bool
    {
        return (bool) $this->extreme_weather;
    }

    /*
    |--------------------------------------------------------------------------
    | Weather Risk Helper
    |--------------------------------------------------------------------------
    */

    public function hasHighWeatherRisk(): bool
    {
        return (float) $this->weather_risk_score >= 70;
    }
}