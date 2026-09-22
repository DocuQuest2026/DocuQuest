<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function __construct(private Request $request) {}

    /**
     * Record an entry in the append-only audit log.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function log(?User $actor, string $action, ?Model $subject = null, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata === [] ? null : $metadata,
            'ip' => $this->request->ip(),
        ]);
    }
}
