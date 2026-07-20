<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Run The Migrations
    |--------------------------------------------------------------------------
    */

    public function up(): void
    {
        Schema::create('weather_records', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Country Relationship
            |--------------------------------------------------------------------------
            */

            $table
                ->foreignId('country_id')
                ->constrained('countries')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Weather Data
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('temperature', 8, 2)
                ->nullable();

            $table
                ->decimal('feels_like', 8, 2)
                ->nullable();

            $table
                ->decimal('humidity', 8, 2)
                ->nullable();

            $table
                ->decimal('precipitation', 10, 2)
                ->nullable();

            $table
                ->decimal('rain', 10, 2)
                ->nullable();

            $table
                ->decimal('cloud_cover', 8, 2)
                ->nullable();

            $table
                ->decimal('pressure', 10, 2)
                ->nullable();

            $table
                ->decimal('wind_speed', 10, 2)
                ->nullable();

            $table
                ->decimal('wind_direction', 8, 2)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Weather Condition
            |--------------------------------------------------------------------------
            */

            $table
                ->integer('weather_code')
                ->nullable();

            $table
                ->string('weather_condition', 100)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Supply Chain Weather Risk
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('weather_risk_score', 5, 2)
                ->default(0);

            $table
                ->boolean('extreme_weather')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Record Time
            |--------------------------------------------------------------------------
            */

            $table
                ->timestamp('recorded_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Laravel Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Database Indexes
            |--------------------------------------------------------------------------
            */

            $table->index([
                'country_id',
                'recorded_at',
            ]);

            $table->index(
                'weather_risk_score'
            );

            $table->index(
                'extreme_weather'
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Reverse The Migrations
    |--------------------------------------------------------------------------
    */

    public function down(): void
    {
        Schema::dropIfExists(
            'weather_records'
        );
    }
};