<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\ServiceManagement\Enums\ServiceType;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceBinding> */
class ServiceBindingFactory extends Factory
{
    protected $model = ServiceBinding::class;

    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'service_type' => ServiceType::Database,
            'database_instance_id' => DatabaseInstance::factory(),
            'cache_instance_id' => null,
            'storage_bucket_id' => null,
            'binding_name' => 'DB',
            'config' => [],
        ];
    }
}
