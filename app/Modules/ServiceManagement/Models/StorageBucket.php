<?php

namespace App\Modules\ServiceManagement\Models;

use App\Support\Concerns\HasUlid;
use Database\Factories\StorageBucketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageBucket extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'access_key' => 'encrypted',
            'secret_key' => 'encrypted',
        ];
    }

    public function serviceBindings(): HasMany
    {
        return $this->hasMany(ServiceBinding::class);
    }

    protected static function newFactory(): StorageBucketFactory
    {
        return StorageBucketFactory::new();
    }
}
