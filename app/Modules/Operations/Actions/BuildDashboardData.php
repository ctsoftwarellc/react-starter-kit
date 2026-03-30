<?php

namespace App\Modules\Operations\Actions;

use App\Modules\Operations\Services\DashboardDataBuilder;

class BuildDashboardData
{
    public function __construct(
        private readonly DashboardDataBuilder $builder = new DashboardDataBuilder,
    ) {}

    public function execute(): array
    {
        return $this->builder->build();
    }
}
