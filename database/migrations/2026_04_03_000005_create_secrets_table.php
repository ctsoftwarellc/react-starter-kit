<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secrets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('environment_id');
            $table->string('key', 255);
            $table->text('encrypted_value');
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->foreign('environment_id')->references('id')->on('environments')->cascadeOnDelete();
            $table->index('environment_id');
            $table->unique(['environment_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secrets');
    }
};
