<?php

namespace Tests\Unit\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Actions\TestProviderConnection;
use App\Modules\Infrastructure\Enums\ProviderType;
use App\Modules\Infrastructure\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestProviderConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_true_for_manual_provider(): void
    {
        $provider = Provider::factory()->create([
            'type' => ProviderType::Manual,
        ]);

        $result = (new TestProviderConnection)->execute($provider);

        $this->assertTrue($result);
    }
}
