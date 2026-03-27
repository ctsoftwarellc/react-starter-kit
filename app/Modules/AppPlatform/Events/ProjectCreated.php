<?php

namespace App\Modules\AppPlatform\Events;

use App\Modules\AppPlatform\Models\Project;

class ProjectCreated
{
    public function __construct(public Project $project) {}
}
