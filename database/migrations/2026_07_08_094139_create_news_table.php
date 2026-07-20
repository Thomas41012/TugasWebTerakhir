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
        Schema::create('news', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Country Relationship
            |--------------------------------------------------------------------------
            */

            $table
                ->foreignId('country_id')
                ->nullable()
                ->constrained('countries')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | News Information
            |--------------------------------------------------------------------------
            */

            $table->string('title');

            $table
                ->text('description')
                ->nullable();

            $table
                ->longText('content')
                ->nullable();

            $table
                ->string('source')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | News URL
            |--------------------------------------------------------------------------
            |
            | Digunakan NewsService sebagai identitas unik artikel.
            |
            */

            $table
                ->string('url', 2048)
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Image
            |--------------------------------------------------------------------------
            */

            $table
                ->text('image_url')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Category
            |--------------------------------------------------------------------------
            */

            $table
                ->string('category', 100)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Sentiment Analysis
            |--------------------------------------------------------------------------
            */

            $table
                ->string('sentiment', 20)
                ->default('neutral');

            $table
                ->integer('positive_score')
                ->default(0);

            $table
                ->integer('negative_score')
                ->default(0);

            $table
                ->decimal('sentiment_score', 8, 4)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Publication Time
            |--------------------------------------------------------------------------
            */

            $table
                ->dateTime('published_at')
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
                'published_at',
            ]);

            $table->index([
                'country_id',
                'sentiment',
            ]);

            $table->index(
                'published_at'
            );

            $table->index(
                'sentiment'
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
        Schema::dropIfExists('news');
    }
};