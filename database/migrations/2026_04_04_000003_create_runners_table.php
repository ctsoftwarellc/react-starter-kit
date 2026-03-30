<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('runners', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 255);
            $table->text('token');
            $table->string('token_hash', 64)->unique();
            $table->string('status', 20)->default('offline');
            $table->string('platform', 50)->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();

            $table->index('status');
            $table->index('last_heartbeat_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runners');
    }
};
