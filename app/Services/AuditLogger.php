<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\PrintShop;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Record a governance or operational audit log entry.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        string $action,
        ?Model $auditable = null,
        ?int $printShopId = null,
        ?User $actor = null,
        array $metadata = [],
        ?string $ipAddress = null,
    ): AuditLog {
        $resolvedActor = $actor;
        if ($resolvedActor === null) {
            $authUser = Auth::user();
            if ($authUser instanceof User) {
                $resolvedActor = $authUser;
            }
        }

        $resolvedShopId = $printShopId;
        if ($resolvedShopId === null && $auditable instanceof PrintShop) {
            $resolvedShopId = $auditable->id;
        }

        $resolvedIp = $ipAddress;
        if ($resolvedIp === null && app()->bound('request')) {
            $resolvedIp = request()->ip();
        }

        $auditableKey = $auditable?->getKey();
        $resolvedAuditableId = is_numeric($auditableKey) ? (int) $auditableKey : null;

        return AuditLog::create([
            'print_shop_id' => $resolvedShopId,
            'actor_id' => $resolvedActor?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $resolvedAuditableId,
            'metadata' => $metadata,
            'ip_address' => $resolvedIp,
        ]);
    }
}
