<?php

namespace App\Modules\AppPlatform\Events;

use App\Modules\AppPlatform\Models\Project;

class ProjectUpdated
{
    public function __construct(
        public Project $project,
        public array $oldValues,
        public array $newValues,
    ) {}
}
