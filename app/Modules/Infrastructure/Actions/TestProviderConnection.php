<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Models\Provider;
use App\Modules\Infrastructure\Services\Providers\ProviderFactory;

class TestProviderConnection
{
    public function execute(Provider $provider): bool
    {
        $service = ProviderFactory::make($provider);

        return $service->testConnection();
    }
}
