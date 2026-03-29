<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\DeleteEnvironment;
use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\Environment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_environment(): void
    {
        Event::fake();

        $environment = Environment::factory()->create();

        (new DeleteEnvironment)->execute($environment);

        $this->assertDatabaseMissing('environments', ['id' => $environment->id]);
        Event::assertDispatched(EnvironmentConfigChanged::class);
    }
}
