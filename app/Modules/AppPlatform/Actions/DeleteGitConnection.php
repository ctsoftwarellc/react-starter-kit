<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Models\GitConnection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeleteGitConnection
{
    public function execute(GitConnection $connection): void
    {
        if ($connection->applications()->exists()) {
            throw new InvalidArgumentException('Cannot delete a git connection that is still attached to applications.');
        }

        DB::transaction(function () use ($connection) {
            $connection->delete();
        });
    }
}
