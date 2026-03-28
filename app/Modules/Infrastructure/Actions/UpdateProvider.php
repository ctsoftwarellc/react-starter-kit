<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\DTOs\UpdateProviderData;
use App\Modules\Infrastructure\Events\ProviderUpdated;
use App\Modules\Infrastructure\Models\Provider;
use Illuminate\Support\Facades\DB;

class UpdateProvider
{
    public function execute(Provider $provider, UpdateProviderData $data): Provider
    {
        return DB::transaction(function () use ($provider, $data) {
            $attributes = array_filter([
                'name' => $data->name,
                'credentials' => $data->credentials,
                'is_active' => $data->isActive,
            ], fn ($value) => $value !== null);

            $provider->update($attributes);

            event(new ProviderUpdated($provider));

            return $provider;
        });
    }
}
