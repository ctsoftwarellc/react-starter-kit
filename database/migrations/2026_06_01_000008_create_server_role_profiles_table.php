<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_role_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('environment_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->string('name', 100);
            $table->jsonb('config')->default('{}');
            $table->timestamps();

            $table->unique(['environment_id', 'role']);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_role_profiles');
    }
};
