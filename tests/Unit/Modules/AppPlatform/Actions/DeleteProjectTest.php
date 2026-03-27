<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\DeleteProject;
use App\Modules\AppPlatform\Events\ProjectDeleted;
use App\Modules\AppPlatform\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_soft_deletes_the_project(): void
    {
        Event::fake();

        $project = Project::factory()->create();

        (new DeleteProject)->execute($project);

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_it_dispatches_project_deleted_event(): void
    {
        Event::fake();

        $project = Project::factory()->create();

        (new DeleteProject)->execute($project);

        Event::assertDispatched(ProjectDeleted::class, function (ProjectDeleted $event) use ($project) {
            return $event->project->id === $project->id;
        });
    }
}
