<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\Deployment\Enums\RuntimeProfileStack;
use App\Modules\Deployment\Models\RuntimeProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RuntimeProfile> */
class RuntimeProfileFactory extends Factory
{
    protected $model = RuntimeProfile::class;

    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'name' => 'PHP Web',
            'stack' => RuntimeProfileStack::PhpFpm,
            'config' => [
                'web_server' => 'caddy',
                'php_version' => '8.3',
                'process_manager' => 'supervisor',
            ],
        ];
    }
}
