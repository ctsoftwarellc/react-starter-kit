<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_definitions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('environment_id');
            $table->string('type', 20);
            $table->text('command');
            $table->integer('instances')->default(1);
            $table->timestamps();

            $table->foreign('environment_id')->references('id')->on('environments')->cascadeOnDelete();
            $table->index('environment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_definitions');
    }
};
