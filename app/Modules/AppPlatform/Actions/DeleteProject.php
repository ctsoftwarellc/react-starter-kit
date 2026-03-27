<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Events\ProjectDeleted;
use App\Modules\AppPlatform\Models\Project;

class DeleteProject
{
    public function execute(Project $project): void
    {
        $project->delete();

        event(new ProjectDeleted($project));
    }
}
