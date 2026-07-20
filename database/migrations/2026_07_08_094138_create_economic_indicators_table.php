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
        Schema::create('economic_indicators', function (Blueprint $table) {
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
            | Economic Data Year
            |--------------------------------------------------------------------------
            */

            $table->year('year');

            /*
            |--------------------------------------------------------------------------
            | Gross Domestic Product
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('gdp', 20, 2)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | GDP Growth
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('gdp_growth', 8, 3)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Inflation Rate
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('inflation_rate', 8, 3)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Export Data
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('exports', 20, 2)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Import Data
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('imports', 20, 2)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Population
            |--------------------------------------------------------------------------
            */

            $table
                ->unsignedBigInteger('population')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Laravel Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Database Constraints & Indexes
            |--------------------------------------------------------------------------
            |
            | WorldBankService menggunakan updateOrCreate berdasarkan
            | country_id dan year. Unique constraint ini mencegah
            | duplikasi data ekonomi negara pada tahun yang sama.
            |
            */

            $table->unique([
                'country_id',
                'year',
            ]);

            /*
             * Mempercepat query data ekonomi terbaru berdasarkan tahun.
             */

            $table->index([
                'country_id',
                'year',
            ]);

            /*
             * Mempercepat filter berdasarkan tahun.
             */

            $table->index('year');
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
            'economic_indicators'
        );
    }
};