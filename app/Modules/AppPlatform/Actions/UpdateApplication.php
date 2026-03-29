<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\DTOs\UpdateApplicationData;
use App\Modules\AppPlatform\Models\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateApplication
{
    public function execute(Application $application, UpdateApplicationData $data): Application
    {
        return DB::transaction(function () use ($application, $data) {
            $attributes = [];

            if ($data->hasName) {
                $attributes['name'] = $data->name;
                $attributes['slug'] = $this->generateUniqueSlug($application, (string) $data->name);
            }

            if ($data->hasRuntime) {
                $attributes['runtime'] = $data->runtime;
            }

            if ($data->hasRepositoryUrl) {
                $attributes['repository_url'] = $data->repositoryUrl;
            }

            if ($data->hasRepositoryBranch) {
                $attributes['repository_branch'] = $data->repositoryBranch;
            }

            if ($data->hasGitConnectionId) {
                $attributes['git_connection_id'] = $data->gitConnectionId;
            }

            if ($data->hasSettings) {
                $attributes['settings'] = $data->settings ?? [];
            }

            $application->update($attributes);

            return $application->fresh();
        });
    }

    private function generateUniqueSlug(Application $application, string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 2;

        while (Application::withTrashed()
            ->where('slug', $slug)
            ->whereKeyNot($application->getKey())
            ->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
