<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clusters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('status', 30)->default('pending');
            $table->json('settings')->default('{}');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clusters');
    }
};
