<?php

namespace Database\Factories;

use App\Modules\Networking\Enums\CertificateStatus;
use App\Modules\Networking\Models\Certificate;
use App\Modules\Networking\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Certificate> */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'domain_id' => Domain::factory(),
            'type' => 'auto',
            'status' => CertificateStatus::Pending,
            'issued_at' => null,
            'expires_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => CertificateStatus::Active,
            'issued_at' => now()->subDay(),
            'expires_at' => now()->addDays(60),
        ]);
    }
}
