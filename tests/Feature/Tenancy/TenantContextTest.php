<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Domain\Shared\Tenancy\TenantContext;
use Tests\TestCase;

final class TenantContextTest extends TestCase
{
    public function test_set_and_id_round_trip(): void
    {
        $ctx = new TenantContext;
        $this->assertFalse($ctx->isSet());
        $this->assertNull($ctx->id());

        $ctx->set(42);
        $this->assertTrue($ctx->isSet());
        $this->assertSame(42, $ctx->id());

        $ctx->clear();
        $this->assertFalse($ctx->isSet());
    }

    public function test_run_as_restores_previous_context(): void
    {
        $ctx = new TenantContext;
        $ctx->set(10);

        $result = $ctx->runAs(99, function () use ($ctx) {
            $this->assertSame(99, $ctx->id());

            return 'ok';
        });

        $this->assertSame('ok', $result);
        $this->assertSame(10, $ctx->id(), 'context must be restored after runAs');
    }

    public function test_run_as_restores_even_when_callback_throws(): void
    {
        $ctx = new TenantContext;
        $ctx->set(10);

        try {
            $ctx->runAs(99, fn () => throw new \RuntimeException('boom'));
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertSame(10, $ctx->id(), 'context must be restored even on exception');
    }

    public function test_singleton_binding_persists_across_resolves(): void
    {
        app(TenantContext::class)->set(7);

        $this->assertSame(7, app(TenantContext::class)->id());
    }
}
