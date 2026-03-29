<?php

namespace App\Modules\AppPlatform\Models;

use App\Support\Concerns\HasUlid;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, HasUlid, SoftDeletes;

    protected $guarded = [];

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }
}
