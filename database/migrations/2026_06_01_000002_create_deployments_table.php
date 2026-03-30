<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('release_id');
            $table->ulid('environment_id');
            $table->string('status', 20)->default('pending');
            $table->string('strategy', 20)->default('rolling');
            $table->unsignedInteger('total_nodes')->default(0);
            $table->unsignedInteger('completed_nodes')->default(0);
            $table->unsignedInteger('failed_nodes')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->ulid('initiated_by')->nullable();
            $table->timestamps();

            $table->foreign('release_id')->references('id')->on('releases')->cascadeOnDelete();
            $table->foreign('environment_id')->references('id')->on('environments')->cascadeOnDelete();
            $table->foreign('initiated_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['environment_id', 'created_at']);
            $table->index('status');
            $table->index('release_id');
            $table->index('initiated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
