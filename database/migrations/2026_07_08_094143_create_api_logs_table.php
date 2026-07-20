<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();

            $table->string('service');

            $table->string('endpoint');

            $table->string('method', 10)
                ->default('GET');

            $table->unsignedSmallInteger('status_code')
                ->nullable();

            $table->unsignedInteger('response_time_ms')
                ->nullable();

            $table->boolean('success')
                ->default(false);

            $table->text('error_message')
                ->nullable();

            $table->json('request_data')
                ->nullable();

            $table->timestamp('requested_at');

            $table->timestamps();

            $table->index('service');
            $table->index('success');
            $table->index('requested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};