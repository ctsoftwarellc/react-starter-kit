<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipelines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('application_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->jsonb('definition');
            $table->boolean('is_active')->default(true);
            $table->jsonb('trigger_branches')->default('["main"]');
            $table->jsonb('trigger_events')->default('["push"]');
            $table->timestamps();

            $table->index('application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipelines');
    }
};
