<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\CreateBackupRequest;
use App\Http\Requests\Operations\RestoreBackupRequest;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Operations\Actions\CreateBackup;
use App\Modules\Operations\Actions\RestoreBackup;
use App\Modules\Operations\Models\Backup;
use Illuminate\Http\RedirectResponse;

class BackupWebController extends Controller
{
    public function store(CreateBackupRequest $request, Server $server): RedirectResponse
    {
        (new CreateBackup)->execute($server, $request->validated());

        return back()->with('success', 'Backup queued.');
    }

    public function restore(RestoreBackupRequest $request, Backup $backup): RedirectResponse
    {
        $targetServer = Server::query()->findOrFail($request->validated('server_id'));

        (new RestoreBackup)->execute($backup, $targetServer);

        return back()->with('success', 'Backup restore started.');
    }
}
