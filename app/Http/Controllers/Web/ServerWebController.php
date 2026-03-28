<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Infrastructure\RegisterServerRequest;
use App\Modules\Infrastructure\Actions\RegisterServer;
use App\Modules\Infrastructure\DTOs\RegisterServerData;
use App\Modules\Infrastructure\Jobs\BootstrapServer;
use App\Modules\Infrastructure\Models\Provider;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServerWebController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('servers/index', [
            'servers' => Server::with(['provider', 'clusters'])->latest()->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('servers/create', [
            'providers' => Provider::where('is_active', true)->get(),
        ]);
    }

    public function store(RegisterServerRequest $request): RedirectResponse
    {
        $server = (new RegisterServer)->execute(
            RegisterServerData::from($request->validated()),
        );

        return redirect()->route('servers.show', $server);
    }

    public function show(Server $server): Response
    {
        $server->load(['provider', 'clusters', 'agentCommands' => function ($query) {
            $query->latest('created_at')->limit(20);
        }]);

        return Inertia::render('servers/show', [
            'server' => $server,
        ]);
    }

    public function bootstrap(Server $server): RedirectResponse
    {
        BootstrapServer::dispatch($server);

        return back()->with('success', 'Bootstrap job dispatched.');
    }
}
