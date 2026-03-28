<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Infrastructure\AgentCommandResource;
use App\Modules\Infrastructure\Enums\AgentCommandStatus;
use App\Modules\Infrastructure\Models\AgentCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AgentController extends Controller
{
    public function heartbeat(Request $request): JsonResponse
    {
        $server = $request->input('server');

        $server->update([
            'last_heartbeat_at' => now(),
            'metadata' => array_merge($server->metadata ?? [], $request->only(['cpu', 'memory', 'disk', 'load'])),
        ]);

        return response()->json(['status' => 'ok']);
    }

    public function pendingCommands(Request $request): AnonymousResourceCollection
    {
        $server = $request->input('server');

        $commands = AgentCommand::where('server_id', $server->id)
            ->pending()
            ->orderBy('created_at')
            ->get();

        return AgentCommandResource::collection($commands);
    }

    public function reportResult(Request $request, AgentCommand $agentCommand): JsonResponse
    {
        $server = $request->input('server');

        if ($agentCommand->server_id !== $server->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'status' => ['required', 'string', 'in:completed,failed'],
            'result' => ['nullable', 'array'],
        ]);

        $agentCommand->update([
            'status' => AgentCommandStatus::from($request->input('status')),
            'result' => $request->input('result'),
            'completed_at' => now(),
        ]);

        return response()->json(['status' => 'ok']);
    }
}
