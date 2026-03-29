<?php

namespace App\Modules\AppPlatform\Models;

use App\Modules\AppPlatform\Enums\GitProvider;
use App\Support\Concerns\HasUlid;
use Database\Factories\GitConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GitConnection extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'provider' => GitProvider::class,
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    protected static function newFactory(): GitConnectionFactory
    {
        return GitConnectionFactory::new();
    }
}
