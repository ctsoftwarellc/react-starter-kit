<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_buckets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('provider');
            $table->string('region');
            $table->text('access_key');
            $table->text('secret_key');
            $table->string('bucket_name');
            $table->timestamps();

            $table->unique('name');
            $table->unique('bucket_name');
            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_buckets');
    }
};
