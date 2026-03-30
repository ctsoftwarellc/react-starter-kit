<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\CreateBackupRequest;
use App\Http\Requests\Operations\RestoreBackupRequest;
use App\Http\Resources\Operations\BackupResource;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Operations\Actions\CreateBackup;
use App\Modules\Operations\Actions\RestoreBackup;
use App\Modules\Operations\Models\Backup;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BackupController extends Controller
{
    public function index(Server $server): AnonymousResourceCollection
    {
        return BackupResource::collection(
            $server->backups()->with('server')->latest()->get(),
        );
    }

    public function store(CreateBackupRequest $request, Server $server): BackupResource
    {
        $backup = (new CreateBackup)->execute($server, $request->validated());

        return new BackupResource($backup->load('server'));
    }

    public function restore(RestoreBackupRequest $request, Backup $backup): BackupResource
    {
        $targetServer = Server::query()->findOrFail($request->validated('server_id'));

        $backup = (new RestoreBackup)->execute($backup, $targetServer);

        return new BackupResource($backup->load('server'));
    }
}
