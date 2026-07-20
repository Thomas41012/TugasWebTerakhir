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
        Schema::create('market_trends', function (Blueprint $table) {
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
            | Currency Data
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('exchange_rate', 18, 6)
                ->nullable();

            $table
                ->decimal('exchange_rate_change', 10, 4)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Inflation Data
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('inflation_rate', 8, 3)
                ->nullable();

            $table
                ->decimal('inflation_change', 8, 3)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Market Impact Analysis
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('market_impact_score', 5, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Market Trend Status
            |--------------------------------------------------------------------------
            |
            | Available values:
            |
            | positive
            | neutral
            | negative
            |
            */

            $table
                ->string('trend_status', 50)
                ->default('neutral');

            /*
            |--------------------------------------------------------------------------
            | Record Time
            |--------------------------------------------------------------------------
            */

            $table
                ->dateTime('recorded_at');

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

            $table->index([
                'country_id',
                'trend_status',
            ]);

            $table->index(
                'trend_status'
            );

            $table->index(
                'market_impact_score'
            );

            $table->index(
                'recorded_at'
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
            'market_trends'
        );
    }
};