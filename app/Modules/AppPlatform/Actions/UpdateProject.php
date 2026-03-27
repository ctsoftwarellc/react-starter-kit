<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\DTOs\UpdateProjectData;
use App\Modules\AppPlatform\Events\ProjectUpdated;
use App\Modules\AppPlatform\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateProject
{
    public function execute(Project $project, UpdateProjectData $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            $oldValues = [
                'name' => $project->name,
                'slug' => $project->slug,
                'description' => $project->description,
            ];

            $attributes = [
                'name' => $data->name,
                'description' => $data->description,
            ];

            if ($data->name !== $project->name) {
                $attributes['slug'] = $this->generateUniqueSlug($data->name, $project->id);
            }

            $project->update($attributes);

            $newValues = [
                'name' => $project->name,
                'slug' => $project->slug,
                'description' => $project->description,
            ];

            event(new ProjectUpdated($project, $oldValues, $newValues));

            return $project;
        });
    }

    private function generateUniqueSlug(string $name, string $excludeId): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 2;

        while (Project::withTrashed()->where('slug', $slug)->where('id', '!=', $excludeId)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
