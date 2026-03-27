<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\UpdateProject;
use App\Modules\AppPlatform\DTOs\UpdateProjectData;
use App\Modules\AppPlatform\Events\ProjectUpdated;
use App\Modules\AppPlatform\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UpdateProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_name_and_description(): void
    {
        Event::fake();

        $project = Project::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
            'description' => 'Old description',
        ]);

        $data = new UpdateProjectData(name: 'Old Name', description: 'New description');

        $updated = (new UpdateProject)->execute($project, $data);

        $this->assertEquals('Old Name', $updated->name);
        $this->assertEquals('New description', $updated->description);
    }

    public function test_it_regenerates_slug_when_name_changes(): void
    {
        Event::fake();

        $project = Project::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
        ]);

        $data = new UpdateProjectData(name: 'New Name');

        $updated = (new UpdateProject)->execute($project, $data);

        $this->assertEquals('new-name', $updated->slug);
    }

    public function test_it_dispatches_project_updated_event_with_old_and_new_values(): void
    {
        Event::fake();

        $project = Project::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
            'description' => 'Old desc',
        ]);

        $data = new UpdateProjectData(name: 'New Name', description: 'New desc');

        (new UpdateProject)->execute($project, $data);

        Event::assertDispatched(ProjectUpdated::class, function (ProjectUpdated $event) {
            return $event->oldValues['name'] === 'Old Name'
                && $event->newValues['name'] === 'New Name'
                && $event->oldValues['description'] === 'Old desc'
                && $event->newValues['description'] === 'New desc';
        });
    }
}
