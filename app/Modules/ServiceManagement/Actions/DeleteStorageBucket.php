<?php

namespace App\Modules\ServiceManagement\Actions;

use App\Modules\ServiceManagement\Models\StorageBucket;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeleteStorageBucket
{
    public function execute(StorageBucket $storageBucket): void
    {
        if ($storageBucket->serviceBindings()->exists()) {
            throw new InvalidArgumentException('Cannot delete a storage bucket while it is bound to an environment.');
        }

        DB::transaction(function () use ($storageBucket) {
            $storageBucket->delete();
        });
    }
}
