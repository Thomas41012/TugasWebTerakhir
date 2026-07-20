<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'service',
        'endpoint',
        'method',
        'status_code',
        'response_time_ms',
        'success',
        'error_message',
        'request_data',
        'requested_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',

            'response_time_ms' => 'integer',

            'success' => 'boolean',

            'request_data' => 'array',

            'requested_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Success
    |--------------------------------------------------------------------------
    */

    public function scopeSuccessful($query)
    {
        return $query->where(
            'success',
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Failed
    |--------------------------------------------------------------------------
    */

    public function scopeFailed($query)
    {
        return $query->where(
            'success',
            false
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Service
    |--------------------------------------------------------------------------
    */

    public function scopeForService(
        $query,
        string $service
    ) {
        return $query->where(
            'service',
            $service
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Latest Request
    |--------------------------------------------------------------------------
    */

    public function scopeLatestRequest($query)
    {
        return $query->orderByDesc(
            'requested_at'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Status Label
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return $this->success
            ? 'success'
            : 'failed';
    }

    /*
    |--------------------------------------------------------------------------
    | Response Time
    |--------------------------------------------------------------------------
    */

    public function getFormattedResponseTimeAttribute(): string
    {
        if (
            $this->response_time_ms === null
        ) {
            return '-';
        }

        return number_format(
            $this->response_time_ms
        ) . ' ms';
    }

    /*
    |--------------------------------------------------------------------------
    | Service Name
    |--------------------------------------------------------------------------
    */

    public function getFormattedServiceAttribute(): string
    {
        return match (
            strtolower(
                (string) $this->service
            )
        ) {
            'open-meteo',
            'open_meteo',
            'weather'
                => 'Open-Meteo',

            'exchange-rate',
            'exchange_rate',
            'currency'
                => 'Exchange Rate',

            'world-bank',
            'world_bank',
            'market'
                => 'World Bank',

            'gnews',
            'news'
                => 'GNews',

            'rest-countries',
            'rest_countries',
            'country',
            'profile'
                => 'REST Countries',

            default
                => (string) $this->service,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Is Slow Response
    |--------------------------------------------------------------------------
    */

    public function getIsSlowAttribute(): bool
    {
        return (
            (int) $this->response_time_ms
            > 3000
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Is Online
    |--------------------------------------------------------------------------
    */

    public function getIsOnlineAttribute(): bool
    {
        return (
            $this->success === true
        );
    }
}