<?php

namespace App\Modules\AppPlatform\Models;

use App\Support\Concerns\HasUlid;
use Database\Factories\EnvironmentVariableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentVariable extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_build_arg' => 'boolean',
        ];
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    protected static function newFactory(): EnvironmentVariableFactory
    {
        return EnvironmentVariableFactory::new();
    }
}
