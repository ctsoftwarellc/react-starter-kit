<?php

namespace Tests\Unit\Modules\ServiceManagement\Actions;

use App\Modules\ServiceManagement\Actions\DeleteCache;
use App\Modules\ServiceManagement\Models\CacheInstance;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DeleteCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_an_unbound_cache_instance(): void
    {
        $cache = CacheInstance::factory()->create();

        (new DeleteCache)->execute($cache);

        $this->assertDatabaseMissing('cache_instances', ['id' => $cache->id]);
    }

    public function test_it_refuses_to_delete_a_bound_cache_instance(): void
    {
        $cache = CacheInstance::factory()->create();
        ServiceBinding::factory()->create([
            'service_type' => 'cache',
            'database_instance_id' => null,
            'cache_instance_id' => $cache->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete a cache instance while it is bound to an environment.');

        (new DeleteCache)->execute($cache);
    }
}
