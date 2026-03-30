<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_jobs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('pipeline_run_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 100);
            $table->string('name', 255);
            $table->string('status', 20)->default('pending');
            $table->foreignUlid('runner_id')->nullable()->constrained('runners')->nullOnDelete();
            $table->jsonb('commands');
            $table->jsonb('environment')->default('{}');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('log_path', 500)->nullable();
            $table->integer('exit_code')->nullable();
            $table->timestamps();

            $table->index(['pipeline_run_id', 'stage']);
            $table->index(['runner_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_jobs');
    }
};
