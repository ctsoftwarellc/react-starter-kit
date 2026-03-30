<?php

namespace Tests\Feature\Api\Pipeline;

use App\Models\User;
use App\Modules\Pipeline\Models\Runner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunnerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_register_show_and_delete_runners(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $runner = Runner::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/runners')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $response = $this->actingAs($user)
            ->postJson('/api/v1/runners', [
                'name' => 'runner-new',
                'platform' => 'linux/amd64',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'runner-new');

        $createdId = $response->json('data.id');

        $this->actingAs($user)
            ->getJson('/api/v1/runners/'.$runner->id)
            ->assertOk()
            ->assertJsonPath('data.id', $runner->id);

        $this->actingAs($user)
            ->deleteJson('/api/v1/runners/'.$createdId)
            ->assertNoContent();
    }
}
