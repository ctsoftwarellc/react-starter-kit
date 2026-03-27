<?php

namespace App\Modules\AppPlatform\Events;

use App\Modules\AppPlatform\Models\Project;

class ProjectDeleted
{
    public function __construct(public Project $project) {}
}
