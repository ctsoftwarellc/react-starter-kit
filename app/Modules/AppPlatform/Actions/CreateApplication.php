<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\DTOs\CreateApplicationData;
use App\Modules\AppPlatform\Events\ApplicationCreated;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateApplication
{
    public function execute(Project $project, CreateApplicationData $data): Application
    {
        return DB::transaction(function () use ($project, $data) {
            $application = Application::create([
                'project_id' => $project->id,
                'name' => $data->name,
                'slug' => $this->generateUniqueSlug($data->name),
                'runtime' => $data->runtime,
                'repository_url' => $data->repositoryUrl,
                'repository_branch' => $data->repositoryBranch,
                'git_connection_id' => $data->gitConnectionId,
                'settings' => $data->settings,
            ]);

            event(new ApplicationCreated($application, $data->defaultClusterId));

            return $application;
        });
    }

    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 2;

        while (Application::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
