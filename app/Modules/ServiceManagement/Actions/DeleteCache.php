<?php

namespace App\Modules\ServiceManagement\Actions;

use App\Modules\ServiceManagement\Models\CacheInstance;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeleteCache
{
    public function execute(CacheInstance $cacheInstance): void
    {
        if ($cacheInstance->serviceBindings()->exists()) {
            throw new InvalidArgumentException('Cannot delete a cache instance while it is bound to an environment.');
        }

        DB::transaction(function () use ($cacheInstance) {
            $cacheInstance->delete();
        });
    }
}
