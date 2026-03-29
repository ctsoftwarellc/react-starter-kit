<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Enums\Runtime;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\GitConnection;
use App\Modules\AppPlatform\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Application> */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'project_id' => Project::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'runtime' => Runtime::Php,
            'repository_url' => 'https://github.com/'.fake()->userName().'/'.Str::slug($name),
            'repository_branch' => 'main',
            'git_connection_id' => GitConnection::factory(),
            'settings' => [],
        ];
    }
}
