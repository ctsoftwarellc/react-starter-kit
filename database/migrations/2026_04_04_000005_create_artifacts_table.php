<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('pipeline_run_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('application_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('building');
            $table->string('storage_path', 500)->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();

            $table->index('pipeline_run_id');
            $table->index('application_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifacts');
    }
};
