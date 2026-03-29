<?php

namespace App\Modules\ServiceManagement\Services;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\Secret;
use App\Modules\ServiceManagement\Enums\ServiceType;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ServiceSecretManager
{
    public function syncBindingSecrets(Environment $environment, ServiceBinding $binding): void
    {
        foreach ($this->buildSecrets($binding) as $key => $value) {
            $secret = Secret::firstOrNew([
                'environment_id' => $environment->id,
                'key' => $key,
            ]);

            $secret->encrypted_value = $value;
            $secret->version = $secret->exists ? $secret->version + 1 : 1;
            $secret->save();
        }
    }

    public function removeBindingSecrets(Environment $environment, ServiceBinding $binding): void
    {
        Secret::query()
            ->where('environment_id', $environment->id)
            ->whereIn('key', array_keys($this->buildSecrets($binding)))
            ->delete();
    }

    private function buildSecrets(ServiceBinding $binding): array
    {
        $prefix = $this->prefix($binding->binding_name);

        return match ($binding->service_type) {
            ServiceType::Database => [
                $prefix.'_CONNECTION' => $binding->databaseInstance->engine->value,
                $prefix.'_HOST' => $binding->databaseInstance->host,
                $prefix.'_PORT' => (string) $binding->databaseInstance->port,
                $prefix.'_DATABASE' => $binding->databaseInstance->database_name,
                $prefix.'_USERNAME' => $binding->databaseInstance->username,
                $prefix.'_PASSWORD' => $binding->databaseInstance->password,
            ],
            ServiceType::Cache => [
                $prefix.'_DRIVER' => $binding->cacheInstance->engine->value,
                $prefix.'_HOST' => $binding->cacheInstance->host,
                $prefix.'_PORT' => (string) $binding->cacheInstance->port,
                $prefix.'_PASSWORD' => $binding->cacheInstance->password ?? '',
            ],
            ServiceType::Storage => [
                $prefix.'_PROVIDER' => $binding->storageBucket->provider,
                $prefix.'_REGION' => $binding->storageBucket->region,
                $prefix.'_BUCKET' => $binding->storageBucket->bucket_name,
                $prefix.'_ACCESS_KEY_ID' => $binding->storageBucket->access_key,
                $prefix.'_SECRET_ACCESS_KEY' => $binding->storageBucket->secret_key,
            ],
            default => throw new InvalidArgumentException('Unsupported service binding type.'),
        };
    }

    private function prefix(string $bindingName): string
    {
        return Str::upper(Str::snake($bindingName));
    }
}
