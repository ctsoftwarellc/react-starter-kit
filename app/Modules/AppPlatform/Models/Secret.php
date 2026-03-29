<?php

namespace App\Modules\AppPlatform\Models;

use App\Support\Concerns\HasUlid;
use Database\Factories\SecretFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Secret extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'encrypted_value' => 'encrypted',
            'version' => 'integer',
        ];
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    protected static function newFactory(): SecretFactory
    {
        return SecretFactory::new();
    }
}
