<?php

declare(strict_types=1);

namespace App\Domain\Shared\Tenancy;

use App\Domain\OpsFortress\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Apply this trait to every model whose table has a `tenant_id` column.
 *
 * Behavior:
 *   - Auto-applies the TenantScope on every query (filters by current tenant).
 *   - On model create, auto-stamps tenant_id from the active TenantContext.
 *   - Throws if a model is being created without a tenant context — this is
 *     a deliberate guard against accidental cross-tenant writes.
 *
 * Usage:
 *   class Business extends Model {
 *       use BelongsToTenant;
 *   }
 *
 * Escape hatch: TenantContext::runAs($tenantId, fn() => ...) for legitimate
 * cross-tenant administrative operations (seeders, console commands, tests).
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model): void {
            if ($model->getAttribute('tenant_id') !== null) {
                return;
            }

            $context = app(TenantContext::class);

            if (! $context->isSet()) {
                throw new RuntimeException(sprintf(
                    'Cannot create %s without a tenant context. '.
                    'Either set TenantContext::set() or wrap in TenantContext::runAs().',
                    static::class,
                ));
            }

            $model->setAttribute('tenant_id', $context->id());
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
