<?php

namespace App\Support\Services\ObjectStorage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class ObjectStorageService
{
    public function disk(): Filesystem
    {
        return Storage::disk(config('helm.storage_disk'));
    }

    public function putArtifact(string $path, mixed $contents): bool
    {
        return $this->disk()->put("artifacts/{$path}", $contents);
    }

    public function getArtifact(string $path): ?string
    {
        return $this->disk()->get("artifacts/{$path}");
    }

    public function artifactUrl(string $path): string
    {
        return $this->disk()->temporaryUrl("artifacts/{$path}", now()->addHour());
    }

    public function putLog(string $path, string $contents): bool
    {
        return $this->disk()->put("logs/{$path}", $contents);
    }

    public function appendLog(string $path, string $chunk): bool
    {
        return $this->disk()->append("logs/{$path}", $chunk);
    }

    public function getLog(string $path): ?string
    {
        return $this->disk()->get("logs/{$path}");
    }

    public function delete(string $path): bool
    {
        return $this->disk()->delete($path);
    }

    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    public function size(string $path): int
    {
        return $this->disk()->size($path);
    }
}
