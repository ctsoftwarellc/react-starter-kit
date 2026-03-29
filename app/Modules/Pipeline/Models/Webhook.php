<?php

namespace App\Modules\Pipeline\Models;

use App\Modules\AppPlatform\Enums\GitProvider;
use App\Modules\AppPlatform\Models\Application;
use App\Support\Concerns\HasUlid;
use Database\Factories\WebhookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'provider' => GitProvider::class,
            'secret' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    protected static function newFactory(): WebhookFactory
    {
        return WebhookFactory::new();
    }
}
