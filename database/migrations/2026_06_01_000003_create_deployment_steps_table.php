<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployment_steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('deployment_id');
            $table->ulid('server_id');
            $table->string('status', 20)->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('output')->nullable();
            $table->timestamps();

            $table->foreign('deployment_id')->references('id')->on('deployments')->cascadeOnDelete();
            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
            $table->index('deployment_id');
            $table->index('server_id');
            $table->index(['deployment_id', 'status']);
            $table->unique(['deployment_id', 'server_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_steps');
    }
};
