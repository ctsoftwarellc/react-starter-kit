<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->ulid('active_release_id')->nullable()->after('branch');

            $table->foreign('active_release_id')->references('id')->on('releases')->nullOnDelete();
            $table->index('active_release_id');
        });
    }

    public function down(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->dropForeign(['active_release_id']);
            $table->dropIndex(['active_release_id']);
            $table->dropColumn('active_release_id');
        });
    }
};
