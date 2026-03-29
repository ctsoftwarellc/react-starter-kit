<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('project_id');
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->string('runtime', 20)->default('php');
            $table->string('repository_url', 500)->nullable();
            $table->string('repository_branch', 255)->default('main');
            $table->ulid('git_connection_id')->nullable();
            $table->jsonb('settings')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('git_connection_id')->references('id')->on('git_connections')->nullOnDelete();
            $table->index('project_id');
            $table->index('git_connection_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
