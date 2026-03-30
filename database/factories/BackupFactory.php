<?php

namespace Database\Factories;

use App\Modules\Infrastructure\Models\Server;
use App\Modules\Operations\Enums\BackupStatus;
use App\Modules\Operations\Models\Backup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Backup> */
class BackupFactory extends Factory
{
    protected $model = Backup::class;

    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'type' => fake()->randomElement(['database', 'files']),
            'status' => BackupStatus::Pending,
            'storage_path' => null,
            'size_bytes' => null,
            'started_at' => null,
            'finished_at' => null,
            'retention_days' => 30,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => BackupStatus::Completed,
            'storage_path' => 'backups/example.json',
            'size_bytes' => 1024,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now()->subMinutes(4),
        ]);
    }
}
