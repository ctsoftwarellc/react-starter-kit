<?php

namespace Tests\Feature\Web;

use App\Models\User;
use App\Modules\Pipeline\Models\Runner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RunnersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_runners_page_renders_for_authenticated_users(): void
    {
        $this->withoutVite();

        /** @var User $user */
        $user = User::factory()->create();
        Runner::factory()->count(2)->create();

        $this->actingAs($user)
            ->get('/runners')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('runners/index')
                ->has('runners.data', 2));
    }
}
