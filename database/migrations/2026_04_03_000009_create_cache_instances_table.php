<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache_instances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('cluster_id');
            $table->string('name');
            $table->string('engine', 20);
            $table->string('version', 50)->nullable();
            $table->string('host');
            $table->unsignedInteger('port');
            $table->text('password')->nullable();
            $table->timestamps();

            $table->foreign('cluster_id')->references('id')->on('clusters');
            $table->index('cluster_id');
            $table->index('engine');
            $table->unique(['cluster_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_instances');
    }
};
