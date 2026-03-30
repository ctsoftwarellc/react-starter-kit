<?php

namespace Tests\Unit\Modules\Pipeline\Services;

use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Services\LogStreamer;
use App\Support\Services\ObjectStorage\ObjectStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LogStreamerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_appends_log_chunks_to_object_storage(): void
    {
        Storage::fake('artifacts');

        $job = PipelineJob::factory()->create();
        $streamer = new LogStreamer(new ObjectStorageService);

        $path = $streamer->append($job, 'first line');
        $streamer->append($job->fresh(), 'second line');

        $this->assertSame($path, $job->fresh()->log_path);
        $this->assertTrue(Storage::disk('artifacts')->exists('logs/'.$path));
        $this->assertStringContainsString('first line', Storage::disk('artifacts')->get('logs/'.$path));
    }

    public function test_it_reads_logs_by_offset_or_range(): void
    {
        Storage::fake('artifacts');

        $job = PipelineJob::factory()->create(['log_path' => 'run/job.log']);
        Storage::disk('artifacts')->put('logs/run/job.log', 'abcdef');

        $payload = (new LogStreamer(new ObjectStorageService))->read($job, 2, 3);

        $this->assertSame('cde', $payload['content']);
        $this->assertSame(2, $payload['offset']);
        $this->assertSame(3, $payload['length']);
    }
}
