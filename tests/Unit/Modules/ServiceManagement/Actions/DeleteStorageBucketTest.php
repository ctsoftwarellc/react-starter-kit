<?php

namespace Tests\Unit\Modules\ServiceManagement\Actions;

use App\Modules\ServiceManagement\Actions\DeleteStorageBucket;
use App\Modules\ServiceManagement\Enums\ServiceType;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use App\Modules\ServiceManagement\Models\StorageBucket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DeleteStorageBucketTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_an_unbound_storage_bucket(): void
    {
        $bucket = StorageBucket::factory()->create();

        (new DeleteStorageBucket)->execute($bucket);

        $this->assertDatabaseMissing('storage_buckets', ['id' => $bucket->id]);
    }

    public function test_it_refuses_to_delete_a_bound_storage_bucket(): void
    {
        $bucket = StorageBucket::factory()->create();
        ServiceBinding::factory()->create([
            'service_type' => ServiceType::Storage,
            'database_instance_id' => null,
            'storage_bucket_id' => $bucket->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete a storage bucket while it is bound to an environment.');

        (new DeleteStorageBucket)->execute($bucket);
    }
}
