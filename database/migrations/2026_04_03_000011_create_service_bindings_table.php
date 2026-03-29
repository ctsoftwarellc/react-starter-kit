<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_bindings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('environment_id');
            $table->string('service_type', 20);
            $table->ulid('database_instance_id')->nullable();
            $table->ulid('cache_instance_id')->nullable();
            $table->ulid('storage_bucket_id')->nullable();
            $table->string('binding_name', 100);
            $table->jsonb('config')->default('{}');
            $table->timestamps();

            $table->foreign('environment_id')->references('id')->on('environments')->cascadeOnDelete();
            $table->foreign('database_instance_id')->references('id')->on('database_instances');
            $table->foreign('cache_instance_id')->references('id')->on('cache_instances');
            $table->foreign('storage_bucket_id')->references('id')->on('storage_buckets');
            $table->index('environment_id');
            $table->index('service_type');
            $table->unique(['environment_id', 'binding_name']);
            $table->unique(['environment_id', 'database_instance_id']);
            $table->unique(['environment_id', 'cache_instance_id']);
            $table->unique(['environment_id', 'storage_bucket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_bindings');
    }
};
