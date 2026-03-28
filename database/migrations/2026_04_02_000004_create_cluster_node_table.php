<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cluster_node', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('cluster_id');
            $table->ulid('server_id');
            $table->string('role', 20);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('cluster_id')->references('id')->on('clusters')->cascadeOnDelete();
            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
            $table->unique(['cluster_id', 'server_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cluster_node');
    }
};
