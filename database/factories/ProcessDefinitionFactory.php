<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Enums\ProcessType;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\ProcessDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProcessDefinition> */
class ProcessDefinitionFactory extends Factory
{
    protected $model = ProcessDefinition::class;

    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'type' => ProcessType::Web,
            'command' => 'php artisan serve --host=0.0.0.0 --port=8000',
            'instances' => 1,
        ];
    }
}
