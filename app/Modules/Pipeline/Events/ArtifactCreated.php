<?php

namespace App\Modules\Pipeline\Events;

use App\Modules\Pipeline\Models\Artifact;

class ArtifactCreated
{
    public function __construct(public Artifact $artifact) {}
}
