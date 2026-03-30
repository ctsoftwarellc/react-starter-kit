<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_runs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('pipeline_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('environment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('trigger_type', 20);
            $table->string('trigger_ref', 255)->nullable();
            $table->string('trigger_sha', 40)->nullable();
            $table->string('trigger_actor', 255)->nullable();
            $table->jsonb('definition_snapshot');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['pipeline_id', 'status']);
            $table->index('created_at');
            $table->index('environment_id');
            $table->index('trigger_sha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_runs');
    }
};
