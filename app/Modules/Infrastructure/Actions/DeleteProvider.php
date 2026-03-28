<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Events\ProviderDeleted;
use App\Modules\Infrastructure\Models\Provider;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeleteProvider
{
    public function execute(Provider $provider): void
    {
        $activeStatuses = [
            ServerStatus::Active->value,
            ServerStatus::Bootstrapping->value,
            ServerStatus::Provisioning->value,
        ];

        $hasActiveServers = $provider->servers()
            ->whereIn('status', $activeStatuses)
            ->exists();

        if ($hasActiveServers) {
            throw new InvalidArgumentException(
                'Cannot delete provider with active servers.'
            );
        }

        DB::transaction(function () use ($provider) {
            $provider->delete();

            event(new ProviderDeleted($provider));
        });
    }
}
