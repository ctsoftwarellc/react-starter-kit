<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Enums\GitProvider;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\Pipeline\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Webhook> */
class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'provider' => GitProvider::Github,
            'secret' => Str::random(40),
            'is_active' => true,
        ];
    }
}
