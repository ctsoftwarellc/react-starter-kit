<?php

namespace App\Modules\Pipeline\Models;

use App\Modules\Pipeline\Enums\RunnerStatus;
use App\Support\Concerns\HasUlid;
use Database\Factories\RunnerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Runner extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'status' => RunnerStatus::class,
            'last_heartbeat_at' => 'datetime',
            'metadata' => 'json',
        ];
    }

    public function pipelineJobs(): HasMany
    {
        return $this->hasMany(PipelineJob::class);
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('status', RunnerStatus::Online->value);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', RunnerStatus::Online->value);
    }

    protected static function newFactory(): RunnerFactory
    {
        return RunnerFactory::new();
    }
}
