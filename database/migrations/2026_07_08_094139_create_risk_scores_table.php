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
        Schema::create('risk_scores', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Country
            |--------------------------------------------------------------------------
            */

            $table
                ->foreignId('country_id')
                ->constrained('countries')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Risk Scores
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('weather_score', 5, 2)
                ->default(0);

            $table
                ->decimal('inflation_score', 5, 2)
                ->default(0);

            $table
                ->decimal('currency_score', 5, 2)
                ->default(0);

            $table
                ->decimal('political_score', 5, 2)
                ->default(0);

            $table
                ->decimal('port_score', 5, 2)
                ->default(0);

            $table
                ->decimal('total_score', 5, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Risk Level
            |--------------------------------------------------------------------------
            */

            $table
                ->string('risk_level', 20)
                ->default('low');

            /*
            |--------------------------------------------------------------------------
            | Calculation Details
            |--------------------------------------------------------------------------
            */

            $table
                ->json('calculation_details')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Calculation Time
            |--------------------------------------------------------------------------
            */

            $table
                ->dateTime('calculated_at');

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index([
                'country_id',
                'calculated_at',
            ]);

            $table->index(
                'risk_level'
            );

            $table->index(
                'calculated_at'
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
            'risk_scores'
        );
    }
};