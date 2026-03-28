<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\DTOs\CreateProviderData;
use App\Modules\Infrastructure\Events\ProviderCreated;
use App\Modules\Infrastructure\Models\Provider;
use Illuminate\Support\Facades\DB;

class CreateProvider
{
    public function execute(CreateProviderData $data): Provider
    {
        return DB::transaction(function () use ($data) {
            $provider = Provider::create([
                'name' => $data->name,
                'type' => $data->type,
                'credentials' => $data->credentials,
            ]);

            event(new ProviderCreated($provider));

            return $provider;
        });
    }
}
