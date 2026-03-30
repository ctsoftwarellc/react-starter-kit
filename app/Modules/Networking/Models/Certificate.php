<?php

namespace App\Modules\Networking\Models;

use App\Modules\Networking\Enums\CertificateStatus;
use App\Support\Concerns\HasUlid;
use Carbon\CarbonInterface;
use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory, HasUlid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => CertificateStatus::class,
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function scopeExpiring(Builder $query, CarbonInterface $before): Builder
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<=', $before);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CertificateStatus::Active);
    }

    protected static function newFactory(): CertificateFactory
    {
        return CertificateFactory::new();
    }
}
