<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\DTOs\CreateProjectData;
use App\Modules\AppPlatform\Events\ProjectCreated;
use App\Modules\AppPlatform\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateProject
{
    public function execute(CreateProjectData $data): Project
    {
        return DB::transaction(function () use ($data) {
            $slug = $this->generateUniqueSlug($data->name);

            $project = Project::create([
                'name' => $data->name,
                'slug' => $slug,
                'description' => $data->description,
            ]);

            event(new ProjectCreated($project));

            return $project;
        });
    }

    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 2;

        while (Project::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
