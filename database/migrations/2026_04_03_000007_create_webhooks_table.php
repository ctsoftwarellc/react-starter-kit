<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('application_id');
            $table->string('provider', 20);
            $table->text('secret');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('applications')->cascadeOnDelete();
            $table->index('application_id');
            $table->unique(['application_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
