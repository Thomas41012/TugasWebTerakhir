<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Country extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'name',
        'official_name',
        'iso2',
        'iso3',
        'capital',
        'region',
        'subregion',
        'currency_code',
        'currency_name',
        'currency_symbol',
        'population',
        'latitude',
        'longitude',
        'timezone',
        'flag_url',
        'is_active',
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
            'population' => 'integer',

            'latitude' => 'decimal:7',

            'longitude' => 'decimal:7',

            'is_active' => 'boolean',

            'last_synced_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Ports
    |--------------------------------------------------------------------------
    */

    public function ports(): HasMany
    {
        return $this->hasMany(
            Port::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Market Trends
    |--------------------------------------------------------------------------
    */

    public function marketTrends(): HasMany
    {
        return $this->hasMany(
            MarketTrend::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Weather Records
    |--------------------------------------------------------------------------
    */

    public function weatherRecords(): HasMany
    {
        return $this->hasMany(
            WeatherRecord::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Currency Rates
    |--------------------------------------------------------------------------
    */

    public function currencyRates(): HasMany
    {
        return $this->hasMany(
            CurrencyRate::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Economic Indicators
    |--------------------------------------------------------------------------
    */

    public function economicIndicators(): HasMany
    {
        return $this->hasMany(
            EconomicIndicator::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Risk Scores
    |--------------------------------------------------------------------------
    */

    public function riskScores(): HasMany
    {
        return $this->hasMany(
            RiskScore::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | News
    |--------------------------------------------------------------------------
    */

    public function news(): HasMany
    {
        return $this->hasMany(
            News::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Watchlists
    |--------------------------------------------------------------------------
    */

    public function watchlists(): HasMany
    {
        return $this->hasMany(
            Watchlist::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | User Favorites
    |--------------------------------------------------------------------------
    */

    public function favorites(): HasMany
    {
        return $this->hasMany(
            UserFavorite::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Articles
    |--------------------------------------------------------------------------
    */

    public function articles(): HasMany
    {
        return $this->hasMany(
            Article::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Latest Risk Score
    |--------------------------------------------------------------------------
    */

    public function latestRiskScore(): HasOne
    {
        return $this
            ->hasOne(RiskScore::class)
            ->latestOfMany(
                'calculated_at'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Latest Market Trend
    |--------------------------------------------------------------------------
    */

    public function latestMarketTrend(): HasOne
    {
        return $this
            ->hasOne(MarketTrend::class)
            ->latestOfMany(
                'recorded_at'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Latest Weather Record
    |--------------------------------------------------------------------------
    */

    public function latestWeatherRecord(): HasOne
    {
        return $this
            ->hasOne(WeatherRecord::class)
            ->latestOfMany(
                'recorded_at'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Latest Currency Rate
    |--------------------------------------------------------------------------
    */

    public function latestCurrencyRate(): HasOne
    {
        return $this
            ->hasOne(CurrencyRate::class)
            ->latestOfMany(
                'recorded_at'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Latest Economic Indicator
    |--------------------------------------------------------------------------
    |
    | Digunakan untuk mengambil data ekonomi World Bank terbaru.
    |
    */

    public function latestEconomicIndicator(): HasOne
    {
        return $this
            ->hasOne(EconomicIndicator::class)
            ->ofMany(
                'year',
                'max'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Latest News
    |--------------------------------------------------------------------------
    */

    public function latestNews(): HasOne
    {
        return $this
            ->hasOne(News::class)
            ->latestOfMany(
                'published_at'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Active Ports
    |--------------------------------------------------------------------------
    */

    public function activePorts(): HasMany
    {
        return $this
            ->hasMany(Port::class)
            ->where(
                'status',
                'active'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | High Risk Ports
    |--------------------------------------------------------------------------
    */

    public function highRiskPorts(): HasMany
    {
        return $this
            ->hasMany(Port::class)
            ->where(
                'risk_score',
                '>=',
                70
            );
    }
}