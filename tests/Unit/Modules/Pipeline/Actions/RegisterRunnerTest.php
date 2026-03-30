<?php

namespace Tests\Unit\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Actions\RegisterRunner;
use App\Modules\Pipeline\DTOs\RegisterRunnerData;
use App\Modules\Pipeline\Enums\RunnerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterRunnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_runner_and_returns_the_plaintext_token_once(): void
    {
        $registered = (new RegisterRunner)->execute(new RegisterRunnerData(
            name: 'runner-eu-1',
            platform: 'linux/amd64',
            metadata: ['region' => 'eu-west'],
        ));

        $this->assertSame('runner-eu-1', $registered->runner->name);
        $this->assertSame('linux/amd64', $registered->runner->platform);
        $this->assertEquals(RunnerStatus::Offline, $registered->runner->status);
        $this->assertNotEmpty($registered->plainTextToken);
        $this->assertSame(hash('sha256', $registered->plainTextToken), $registered->runner->token_hash);
    }
}
