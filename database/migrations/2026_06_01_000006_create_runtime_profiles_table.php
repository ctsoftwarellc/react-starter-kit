<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('runtime_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('application_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('stack', 50);
            $table->jsonb('config')->default('{}');
            $table->timestamps();

            $table->unique(['application_id', 'name']);
            $table->index('stack');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runtime_profiles');
    }
};
