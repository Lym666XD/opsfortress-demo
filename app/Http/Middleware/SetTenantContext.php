<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active tenant for the current HTTP request from the authenticated
 * user's tenant_id and binds it into the request-scoped TenantContext.
 *
 * Runs in the 'web' middleware group AFTER authentication. Unauthenticated
 * requests pass through without setting a tenant — pages that need data
 * are protected by 'auth' middleware and will never reach a query.
 */
final class SetTenantContext
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && isset($user->tenant_id)) {
            $this->context->set($user->tenant_id);
        }

        return $next($request);
    }
}
