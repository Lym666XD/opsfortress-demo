<?php

declare(strict_types=1);

namespace App\Domain\Shared\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Eloquent global scope that filters every query on a BelongsToTenant model
 * by the active TenantContext.
 *
 * If no tenant is active (e.g. console, test, or a deliberate cross-tenant op),
 * the scope is a no-op — the model is responsible for refusing writes via the
 * BelongsToTenant trait's `creating` listener.
 */
final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if (! $context->isSet()) {
            return;
        }

        $builder->where(
            $model->qualifyColumn('tenant_id'),
            $context->id(),
        );
    }
}
