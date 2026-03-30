<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('environment_id');
            $table->ulid('artifact_id');
            $table->unsignedInteger('version');
            $table->string('status', 20)->default('pending');
            $table->jsonb('config_snapshot');
            $table->ulid('deployed_by')->nullable();
            $table->timestamps();

            $table->foreign('environment_id')->references('id')->on('environments')->cascadeOnDelete();
            $table->foreign('artifact_id')->references('id')->on('artifacts')->cascadeOnDelete();
            $table->foreign('deployed_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['environment_id', 'version']);
            $table->index('environment_id');
            $table->index('artifact_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
