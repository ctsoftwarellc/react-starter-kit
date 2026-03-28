<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('provider_id')->nullable();
            $table->string('name', 255);
            $table->string('hostname', 255);
            $table->string('public_ip', 45);
            $table->string('private_ip', 45)->nullable();
            $table->integer('ssh_port')->default(22);
            $table->string('ssh_user', 50)->default('root');
            $table->string('os', 100)->nullable();
            $table->integer('cpu_cores')->nullable();
            $table->integer('memory_mb')->nullable();
            $table->integer('disk_gb')->nullable();
            $table->string('region', 100)->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('agent_token')->nullable();
            $table->string('agent_token_hash', 64)->nullable()->index();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->json('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('provider_id')->references('id')->on('providers')->nullOnDelete();
            $table->index('status');
            $table->index('public_ip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
