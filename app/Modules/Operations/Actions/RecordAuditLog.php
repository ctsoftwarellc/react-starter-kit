<?php

namespace App\Modules\Operations\Actions;

use App\Models\User;
use App\Modules\Operations\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class RecordAuditLog
{
    public function execute(
        string $action,
        Model $auditable,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null,
    ): AuditLog {
        $user = $user ?? auth()->user();
        $request = request();

        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
