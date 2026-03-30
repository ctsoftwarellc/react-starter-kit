<?php

namespace App\Http\Controllers\Api\Pipeline;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pipeline\RegisterRunnerRequest;
use App\Http\Resources\Pipeline\RunnerResource;
use App\Modules\Pipeline\Actions\DeleteRunner;
use App\Modules\Pipeline\Actions\RegisterRunner;
use App\Modules\Pipeline\DTOs\RegisterRunnerData;
use App\Modules\Pipeline\Models\Runner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RunnerController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return RunnerResource::collection(
            Runner::query()
                ->with(['pipelineJobs' => fn ($query) => $query
                    ->whereIn('status', ['assigned', 'running'])
                    ->with('pipelineRun.pipeline')
                    ->latest()
                    ->limit(1)])
                ->latest()
                ->paginate(15),
        );
    }

    public function store(RegisterRunnerRequest $request): JsonResponse
    {
        $registeredRunner = (new RegisterRunner)->execute(
            RegisterRunnerData::from($request->validated()),
        );

        return (new RunnerResource($registeredRunner->runner))
            ->additional(['plain_text_token' => $registeredRunner->plainTextToken])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Runner $runner): RunnerResource
    {
        return new RunnerResource($runner->load([
            'pipelineJobs' => fn ($query) => $query
                ->whereIn('status', ['assigned', 'running'])
                ->with('pipelineRun.pipeline')
                ->latest()
                ->limit(1),
        ]));
    }

    public function destroy(Runner $runner): Response
    {
        (new DeleteRunner)->execute($runner);

        return response()->noContent();
    }
}
