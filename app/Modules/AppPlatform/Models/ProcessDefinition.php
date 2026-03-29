<?php

namespace App\Modules\AppPlatform\Models;

use App\Modules\AppPlatform\Enums\ProcessType;
use App\Support\Concerns\HasUlid;
use Database\Factories\ProcessDefinitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessDefinition extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => ProcessType::class,
            'instances' => 'integer',
        ];
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    protected static function newFactory(): ProcessDefinitionFactory
    {
        return ProcessDefinitionFactory::new();
    }
}
