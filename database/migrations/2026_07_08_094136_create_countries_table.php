<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('official_name')->nullable();

            $table->string('iso2', 2)->unique();
            $table->string('iso3', 3)->unique();

            $table->string('capital')->nullable();
            $table->string('region')->nullable();
            $table->string('subregion')->nullable();

            $table->string('currency_code', 3)->nullable();
            $table->string('currency_name')->nullable();
            $table->string('currency_symbol', 10)->nullable();

            $table->unsignedBigInteger('population')->default(0);

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('timezone')->default('UTC');

            $table->string('flag_url')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->index('name');
            $table->index('region');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};