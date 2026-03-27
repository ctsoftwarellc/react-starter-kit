<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\CreateProject;
use App\Modules\AppPlatform\DTOs\CreateProjectData;
use App\Modules\AppPlatform\Events\ProjectCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_project_with_correct_attributes(): void
    {
        Event::fake();

        $data = new CreateProjectData(name: 'My Project', description: 'A test project');

        $project = (new CreateProject)->execute($data);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'My Project',
            'description' => 'A test project',
        ]);
    }

    public function test_it_generates_slug_from_name(): void
    {
        Event::fake();

        $data = new CreateProjectData(name: 'My Awesome Project');

        $project = (new CreateProject)->execute($data);

        $this->assertEquals('my-awesome-project', $project->slug);
    }

    public function test_it_handles_duplicate_slug_by_appending_suffix(): void
    {
        Event::fake();

        $first = (new CreateProject)->execute(new CreateProjectData(name: 'My Project'));
        $second = (new CreateProject)->execute(new CreateProjectData(name: 'My Project'));

        $this->assertEquals('my-project', $first->slug);
        $this->assertEquals('my-project-2', $second->slug);
    }

    public function test_it_dispatches_project_created_event(): void
    {
        Event::fake();

        $data = new CreateProjectData(name: 'My Project');

        $project = (new CreateProject)->execute($data);

        Event::assertDispatched(ProjectCreated::class, function (ProjectCreated $event) use ($project) {
            return $event->project->id === $project->id;
        });
    }
}
