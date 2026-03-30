<?php

namespace App\Modules\Deployment\Actions;

use App\Models\User;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Models\Release;
use Illuminate\Support\Facades\DB;

class CloneReleaseForRollback
{
    public function execute(Release $sourceRelease, ?User $deployedBy = null): Release
    {
        return DB::transaction(function () use ($sourceRelease, $deployedBy) {
            $sourceRelease->loadMissing('environment');

            $nextVersion = (int) $sourceRelease->environment->releases()->max('version') + 1;

            return Release::create([
                'environment_id' => $sourceRelease->environment_id,
                'artifact_id' => $sourceRelease->artifact_id,
                'version' => $nextVersion,
                'status' => ReleaseStatus::Pending,
                'config_snapshot' => $sourceRelease->config_snapshot,
                'deployed_by' => $deployedBy?->id,
            ]);
        });
    }
}
