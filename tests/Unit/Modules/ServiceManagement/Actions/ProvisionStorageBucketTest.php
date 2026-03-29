<?php

namespace Tests\Unit\Modules\ServiceManagement\Actions;

use App\Modules\ServiceManagement\Actions\ProvisionStorageBucket;
use App\Modules\ServiceManagement\DTOs\ProvisionStorageBucketData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProvisionStorageBucketTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_provisions_a_storage_bucket_with_generated_credentials(): void
    {
        $bucket = (new ProvisionStorageBucket)->execute(new ProvisionStorageBucketData(
            name: 'Assets',
            provider: 's3',
            region: 'us-east-1',
            bucketName: 'assets-bucket',
        ));

        $this->assertDatabaseHas('storage_buckets', [
            'id' => $bucket->id,
            'name' => 'Assets',
            'provider' => 's3',
            'region' => 'us-east-1',
            'bucket_name' => 'assets-bucket',
        ]);
        $this->assertNotEmpty($bucket->access_key);
        $this->assertNotEmpty($bucket->secret_key);
    }
}
