<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\RemoteCommandStatus;
use App\Modules\Deployment\Enums\RemoteCommandType;
use App\Modules\Deployment\Models\RemoteCommand;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RemoteCommand> */
class RemoteCommandFactory extends Factory
{
    protected $model = RemoteCommand::class;

    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'server_id' => Server::factory(),
            'type' => RemoteCommandType::RunMigrations,
            'command' => 'php artisan migrate --force',
            'status' => RemoteCommandStatus::Pending,
        ];
    }
}
