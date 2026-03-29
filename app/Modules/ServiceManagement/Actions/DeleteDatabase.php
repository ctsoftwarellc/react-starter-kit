<?php

namespace App\Modules\ServiceManagement\Actions;

use App\Modules\ServiceManagement\Models\DatabaseInstance;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeleteDatabase
{
    public function execute(DatabaseInstance $databaseInstance): void
    {
        if ($databaseInstance->serviceBindings()->exists()) {
            throw new InvalidArgumentException('Cannot delete a database instance while it is bound to an environment.');
        }

        DB::transaction(function () use ($databaseInstance) {
            $databaseInstance->delete();
        });
    }
}
