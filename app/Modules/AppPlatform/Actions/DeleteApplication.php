<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Models\Application;
use Illuminate\Support\Facades\DB;

class DeleteApplication
{
    public function execute(Application $application): void
    {
        DB::transaction(function () use ($application) {
            $application->delete();
        });
    }
}
