<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\Context\AccountContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetAccountContext
{
    public function __construct(private readonly AccountContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        return $this->context->runAs(
            accountId: $user?->account_id,
            businessEntityId: $user?->home_business_entity_id,
            callback: fn (): Response => $next($request),
        );
    }
}
