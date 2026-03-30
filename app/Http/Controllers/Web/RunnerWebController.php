<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Pipeline\RunnerResource;
use App\Modules\Pipeline\Models\Runner;
use Inertia\Inertia;
use Inertia\Response;

class RunnerWebController extends Controller
{
    public function index(): Response
    {
        $runners = Runner::query()
            ->with(['pipelineJobs' => fn ($query) => $query
                ->whereIn('status', ['assigned', 'running'])
                ->with('pipelineRun.pipeline')
                ->latest()
                ->limit(1)])
            ->latest()
            ->paginate(15);

        return Inertia::render('runners/index', [
            'runners' => RunnerResource::collection($runners),
        ]);
    }
}
