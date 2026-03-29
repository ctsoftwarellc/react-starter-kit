<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\CreateApplication;
use App\Modules\AppPlatform\DTOs\CreateApplicationData;
use App\Modules\AppPlatform\Enums\Runtime;
use App\Modules\AppPlatform\Events\ApplicationCreated;
use App\Modules\AppPlatform\Models\GitConnection;
use App\Modules\AppPlatform\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_application_for_project(): void
    {
        Event::fake();

        $project = Project::factory()->create();
        $connection = GitConnection::factory()->create();

        $application = (new CreateApplication)->execute($project, new CreateApplicationData(
            name: 'Helm Web',
            runtime: Runtime::Php,
            repositoryUrl: 'https://github.com/caleb/helm',
            repositoryBranch: 'main',
            gitConnectionId: $connection->id,
            settings: ['framework' => 'laravel'],
        ));

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'project_id' => $project->id,
            'git_connection_id' => $connection->id,
            'name' => 'Helm Web',
        ]);
    }

    public function test_it_generates_slug_from_name(): void
    {
        Event::fake();

        $application = (new CreateApplication)->execute(Project::factory()->create(), new CreateApplicationData(
            name: 'Helm Web App',
            runtime: Runtime::Php,
        ));

        $this->assertSame('helm-web-app', $application->slug);
    }

    public function test_it_dispatches_application_created_event(): void
    {
        Event::fake();

        $application = (new CreateApplication)->execute(Project::factory()->create(), new CreateApplicationData(
            name: 'Helm Web',
            runtime: Runtime::Php,
            defaultClusterId: '01TESTCLUSTERID',
        ));

        Event::assertDispatched(ApplicationCreated::class, function (ApplicationCreated $event) use ($application) {
            return $event->application->is($application)
                && $event->defaultClusterId === '01TESTCLUSTERID';
        });
    }
}
