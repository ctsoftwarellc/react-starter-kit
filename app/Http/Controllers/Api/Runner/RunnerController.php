<?php

namespace App\Http\Controllers\Api\Runner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pipeline\RunnerHeartbeatRequest;
use App\Http\Requests\Pipeline\RunnerJobStatusRequest;
use App\Http\Resources\Pipeline\ArtifactResource;
use App\Http\Resources\Pipeline\PipelineJobResource;
use App\Http\Resources\Pipeline\RunnerJobAssignmentResource;
use App\Http\Resources\Pipeline\RunnerResource;
use App\Modules\Pipeline\Actions\AppendPipelineJobLog;
use App\Modules\Pipeline\Actions\CreateArtifact;
use App\Modules\Pipeline\Actions\RecordRunnerHeartbeat;
use App\Modules\Pipeline\Actions\RequestRunnerJob;
use App\Modules\Pipeline\Actions\UpdateRunnerJobStatus;
use App\Modules\Pipeline\DTOs\RunnerHeartbeatData;
use App\Modules\Pipeline\DTOs\UpdateRunnerJobStatusData;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Models\Runner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RunnerController extends Controller
{
    public function nextJob(Request $request): Response|RunnerJobAssignmentResource
    {
        $job = (new RequestRunnerJob)->execute($this->runner($request));

        if ($job === null) {
            return response()->noContent();
        }

        return new RunnerJobAssignmentResource($job->load(['pipelineRun.pipeline.application']));
    }

    public function updateStatus(RunnerJobStatusRequest $request, PipelineJob $pipelineJob): PipelineJobResource
    {
        $pipelineJob = (new UpdateRunnerJobStatus)->execute(
            $this->runner($request),
            $pipelineJob,
            UpdateRunnerJobStatusData::from($request->validated()),
        );

        return new PipelineJobResource($pipelineJob);
    }

    public function appendLog(Request $request, PipelineJob $pipelineJob): JsonResponse
    {
        $runner = $this->runner($request);
        $this->ensureRunnerOwnsJob($runner, $pipelineJob);

        (new AppendPipelineJobLog)->execute($pipelineJob, $request->getContent());

        return response()->json(status: SymfonyResponse::HTTP_ACCEPTED);
    }

    public function uploadArtifact(Request $request, PipelineJob $pipelineJob): ArtifactResource
    {
        $runner = $this->runner($request);
        $this->ensureRunnerOwnsJob($runner, $pipelineJob);

        $artifact = (new CreateArtifact)->execute($pipelineJob, $request->getContent());

        return new ArtifactResource($artifact);
    }

    public function heartbeat(RunnerHeartbeatRequest $request): RunnerResource
    {
        $runner = (new RecordRunnerHeartbeat)->execute(
            $this->runner($request),
            RunnerHeartbeatData::from($request->validated()),
        );

        return new RunnerResource($runner);
    }

    private function runner(Request $request): Runner
    {
        /** @var Runner $runner */
        $runner = $request->attributes->get('runner');

        return $runner;
    }

    private function ensureRunnerOwnsJob(Runner $runner, PipelineJob $pipelineJob): void
    {
        abort_if($pipelineJob->runner_id !== $runner->id, 403, 'This job is not assigned to the authenticated runner.');
    }
}
