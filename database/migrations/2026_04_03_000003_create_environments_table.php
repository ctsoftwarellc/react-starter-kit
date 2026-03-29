<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('application_id');
            $table->ulid('cluster_id');
            $table->string('name', 100);
            $table->string('type', 20);
            $table->boolean('is_auto_deploy')->default(false);
            $table->string('branch', 255)->nullable();
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('applications')->cascadeOnDelete();
            $table->foreign('cluster_id')->references('id')->on('clusters')->cascadeOnDelete();
            $table->index('application_id');
            $table->index('cluster_id');
            $table->unique(['application_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environments');
    }
};
