<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watchlists', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('country_id')
                ->constrained('countries')
                ->cascadeOnDelete();

            $table->boolean('risk_alerts')
                ->default(true);

            $table->boolean('weather_alerts')
                ->default(true);

            $table->boolean('currency_alerts')
                ->default(true);

            $table->timestamps();

            $table->unique([
                'user_id',
                'country_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watchlists');
    }
};