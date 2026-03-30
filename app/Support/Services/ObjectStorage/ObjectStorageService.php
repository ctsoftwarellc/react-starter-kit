<?php

namespace App\Support\Services\ObjectStorage;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class ObjectStorageService
{
    public function disk(): FilesystemAdapter
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
        $artifactPath = "artifacts/{$path}";

        try {
            return $this->disk()->temporaryUrl($artifactPath, now()->addHour());
        } catch (\RuntimeException) {
            return $this->disk()->url($artifactPath);
        }
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
