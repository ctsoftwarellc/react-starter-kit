<?php

namespace App\Http\Controllers\Api\Pipeline;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pipeline\CreatePipelineRequest;
use App\Http\Requests\Pipeline\TriggerRunRequest;
use App\Http\Requests\Pipeline\UpdatePipelineRequest;
use App\Http\Resources\Pipeline\PipelineResource;
use App\Http\Resources\Pipeline\PipelineRunResource;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\Pipeline\Actions\CreatePipeline;
use App\Modules\Pipeline\Actions\DeletePipeline;
use App\Modules\Pipeline\Actions\TriggerPipelineRun;
use App\Modules\Pipeline\Actions\UpdatePipeline;
use App\Modules\Pipeline\DTOs\PipelineDefinitionData;
use App\Modules\Pipeline\DTOs\TriggerPipelineRunData;
use App\Modules\Pipeline\Models\Pipeline;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class PipelineController extends Controller
{
    public function index(Application $application): AnonymousResourceCollection
    {
        $pipelines = $application->pipelines()
            ->with(['runs' => fn ($query) => $query->latestFirst()->with(['environment', 'artifact'])->limit(5)])
            ->latest()
            ->get();

        return PipelineResource::collection($pipelines);
    }

    public function store(CreatePipelineRequest $request, Application $application): PipelineResource
    {
        try {
            $pipeline = (new CreatePipeline)->execute(
                $application,
                PipelineDefinitionData::from($request->validated()),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'definition' => $exception->getMessage(),
            ]);
        }

        return new PipelineResource($pipeline->load(['runs' => fn ($query) => $query->latestFirst()->limit(5)]));
    }

    public function show(Pipeline $pipeline): PipelineResource
    {
        return new PipelineResource($pipeline->load([
            'application',
            'runs' => fn ($query) => $query->latestFirst()->with(['environment', 'artifact'])->limit(10),
        ]));
    }

    public function update(UpdatePipelineRequest $request, Pipeline $pipeline): PipelineResource
    {
        try {
            $pipeline = (new UpdatePipeline)->execute(
                $pipeline,
                PipelineDefinitionData::from($request->validated()),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'definition' => $exception->getMessage(),
            ]);
        }

        return new PipelineResource($pipeline->load(['runs' => fn ($query) => $query->latestFirst()->limit(5)]));
    }

    public function destroy(Pipeline $pipeline): Response
    {
        try {
            (new DeletePipeline)->execute($pipeline);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'pipeline' => $exception->getMessage(),
            ]);
        }

        return response()->noContent();
    }

    public function trigger(TriggerRunRequest $request, Pipeline $pipeline): PipelineRunResource
    {
        $run = (new TriggerPipelineRun)->execute(
            $pipeline,
            TriggerPipelineRunData::from($request->validated()),
        );

        return new PipelineRunResource($run->load(['pipeline', 'environment', 'jobs', 'artifact']));
    }

    public function runs(Pipeline $pipeline): AnonymousResourceCollection
    {
        return PipelineRunResource::collection(
            $pipeline->runs()->latestFirst()->with(['pipeline', 'environment', 'artifact'])->paginate(15),
        );
    }
}
