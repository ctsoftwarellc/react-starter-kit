<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->foreignUlid('runtime_profile_id')->nullable()->after('active_release_id')->constrained('runtime_profiles')->nullOnDelete();
            $table->index('runtime_profile_id');
        });
    }

    public function down(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('runtime_profile_id');
        });
    }
};
