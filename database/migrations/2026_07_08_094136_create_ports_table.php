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
        Schema::create('ports', function (Blueprint $table) {
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
            | Port Information
            |--------------------------------------------------------------------------
            */

            $table->string('name');

            $table
                ->string('unlocode', 10)
                ->nullable()
                ->unique();

            $table
                ->string('city')
                ->nullable();

            $table
                ->string('port_type', 100)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Port Coordinates
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('latitude', 10, 7);

            $table
                ->decimal('longitude', 10, 7);

            /*
            |--------------------------------------------------------------------------
            | Port Status
            |--------------------------------------------------------------------------
            */

            $table
                ->string('status', 50)
                ->default('active');

            /*
            |--------------------------------------------------------------------------
            | Congestion Level
            |--------------------------------------------------------------------------
            */

            $table
                ->unsignedInteger('congestion_level')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Port Risk Score
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('risk_score', 5, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Additional Port Metadata
            |--------------------------------------------------------------------------
            */

            $table
                ->json('metadata')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Last Synchronization
            |--------------------------------------------------------------------------
            */

            $table
                ->timestamp('last_synced_at')
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
                'status',
            ]);

            $table->index([
                'latitude',
                'longitude',
            ]);

            $table->index(
                'risk_score'
            );

            $table->index(
                'congestion_level'
            );

            $table->index(
                'last_synced_at'
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
        Schema::dropIfExists('ports');
    }
};