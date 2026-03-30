<?php

namespace App\Http\Controllers\Api\Pipeline;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pipeline\ReadPipelineJobLogRequest;
use App\Http\Resources\Pipeline\PipelineJobResource;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Services\LogStreamer;
use Illuminate\Http\JsonResponse;

class PipelineJobController extends Controller
{
    public function show(PipelineJob $pipelineJob): PipelineJobResource
    {
        return new PipelineJobResource($pipelineJob->load(['pipelineRun.pipeline.application', 'runner']));
    }

    public function log(ReadPipelineJobLogRequest $request, PipelineJob $pipelineJob, LogStreamer $streamer): JsonResponse
    {
        $payload = $streamer->read(
            $pipelineJob,
            $request->validated('offset'),
            $request->validated('length'),
        );

        if (is_string($payload)) {
            $payload = [
                'content' => $payload,
                'offset' => 0,
                'length' => strlen($payload),
            ];
        }

        return response()->json($payload);
    }
}
