<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\DeleteEnvironmentVariable;
use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\EnvironmentVariable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteEnvironmentVariableTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_variable(): void
    {
        Event::fake();

        $variable = EnvironmentVariable::factory()->create();

        (new DeleteEnvironmentVariable)->execute($variable);

        $this->assertDatabaseMissing('environment_variables', ['id' => $variable->id]);
        Event::assertDispatched(EnvironmentConfigChanged::class);
    }
}
