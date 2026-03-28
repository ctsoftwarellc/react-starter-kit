<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_commands', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('server_id');
            $table->string('type', 50);
            $table->json('payload')->default('{}');
            $table->string('status', 20)->default('pending');
            $table->json('result')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
            $table->index(['server_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_commands');
    }
};
