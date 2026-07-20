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
        Schema::create('positive_words', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Positive Word
            |--------------------------------------------------------------------------
            */

            $table
                ->string('word', 100)
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Sentiment Weight
            |--------------------------------------------------------------------------
            */

            $table
                ->decimal('weight', 5, 2)
                ->default(1.00);

            /*
            |--------------------------------------------------------------------------
            | Laravel Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();
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
            'positive_words'
        );
    }
};