<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_checks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('environment_id');
            $table->string('type', 20)->default('http');
            $table->string('target', 500);
            $table->unsignedInteger('interval_seconds')->default(30);
            $table->unsignedInteger('timeout_seconds')->default(5);
            $table->unsignedInteger('healthy_threshold')->default(3);
            $table->unsignedInteger('unhealthy_threshold')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('environment_id')->references('id')->on('environments')->cascadeOnDelete();
            $table->index('environment_id');
            $table->index(['environment_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_checks');
    }
};
