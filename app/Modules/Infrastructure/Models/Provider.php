<?php

namespace App\Modules\Infrastructure\Models;

use App\Modules\Infrastructure\Enums\ProviderType;
use App\Support\Concerns\HasUlid;
use Database\Factories\ProviderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => ProviderType::class,
            'credentials' => 'encrypted:json',
            'is_active' => 'boolean',
        ];
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    protected static function newFactory(): ProviderFactory
    {
        return ProviderFactory::new();
    }
}
