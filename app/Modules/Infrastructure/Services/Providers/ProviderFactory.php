<?php

namespace App\Modules\Infrastructure\Services\Providers;

use App\Modules\Infrastructure\Enums\ProviderType;
use App\Modules\Infrastructure\Models\Provider;
use InvalidArgumentException;

class ProviderFactory
{
    public static function make(Provider $provider): ProviderInterface
    {
        return match ($provider->type) {
            ProviderType::DigitalOcean => new DigitalOceanProvider($provider->credentials),
            ProviderType::Manual => new ManualProvider($provider->credentials),
            default => throw new InvalidArgumentException(
                "Provider type [{$provider->type->value}] is not yet supported."
            ),
        };
    }
}
