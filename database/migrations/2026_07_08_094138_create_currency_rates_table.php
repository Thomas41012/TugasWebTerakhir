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
        Schema::create('currency_rates', function (Blueprint $table) {
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
            | Currency Information
            |--------------------------------------------------------------------------
            */

            $table
                ->string('base_currency', 3)
                ->default('USD');

            $table
                ->string('target_currency', 3);

            /*
            |--------------------------------------------------------------------------
            | Exchange Rate
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('exchange_rate', 18, 6);

            $table
                ->decimal('previous_rate', 18, 6)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Percentage Change
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('percentage_change', 10, 4)
                ->default(0);

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
                'base_currency',
                'target_currency',
            ]);

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
            'currency_rates'
        );
    }
};