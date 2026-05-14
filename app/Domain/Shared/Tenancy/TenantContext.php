<?php

declare(strict_types=1);

namespace App\Domain\Shared\Tenancy;

/**
 * Holds the current request's tenant id for the lifetime of a request or job.
 *
 * Bound as a singleton in the service container so that:
 *   - SetTenantContext middleware sets it once per HTTP request
 *   - Queue jobs explicitly restore it via TenantContext::set() before running
 *   - Eloquent global scopes and event listeners read it implicitly
 *
 * Never read tenant_id from auth()->user() inside a query — read it from here.
 * That way background jobs (which have no auth user) still scope correctly.
 */
final class TenantContext
{
    private ?int $tenantId = null;

    public function set(?int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function id(): ?int
    {
        return $this->tenantId;
    }

    public function isSet(): bool
    {
        return $this->tenantId !== null;
    }

    /**
     * Run a callback with a temporary tenant context, then restore the previous one.
     * Used for cross-tenant administrative operations and tests.
     */
    public function runAs(?int $tenantId, callable $callback): mixed
    {
        $previous = $this->tenantId;
        $this->tenantId = $tenantId;

        try {
            return $callback();
        } finally {
            $this->tenantId = $previous;
        }
    }

    public function clear(): void
    {
        $this->tenantId = null;
    }
}
