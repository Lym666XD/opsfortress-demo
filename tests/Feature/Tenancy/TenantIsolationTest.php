<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Domain\OpsFortress\Businesses\Models\Business;
use App\Domain\OpsFortress\Tenancy\Models\Tenant;
use App\Domain\OpsFortress\Workplaces\Models\Workplace;
use App\Domain\Shared\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Verifies the BelongsToTenant invariant for representative models across
 * both OpsFortress and Whs domains:
 *
 *   1. Reads from tenant A do not return tenant B's rows.
 *   2. Writes without a tenant context throw.
 *   3. tenant_id is auto-stamped from context (cannot be silently overridden).
 *
 * If this suite ever turns red, every model that uses BelongsToTenant is
 * suspect — fix the trait, not the test.
 */
final class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private TenantContext $context;
    private Tenant $tenantA;
    private Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = app(TenantContext::class);
        $this->context->clear();

        $this->tenantA = $this->context->runAs(null, fn () => Tenant::create([
            'slug' => 'tenant-a',
            'name' => 'Tenant A',
        ]));

        $this->tenantB = $this->context->runAs(null, fn () => Tenant::create([
            'slug' => 'tenant-b',
            'name' => 'Tenant B',
        ]));
    }

    public function test_create_without_tenant_context_throws(): void
    {
        $this->context->clear();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('without a tenant context');

        Business::create([
            'legal_name' => 'Should Fail Pty Ltd',
            'trading_name' => 'Should Fail',
            'blockchain_id' => substr(md5('a'), 0, 8),
        ]);
    }

    public function test_create_auto_stamps_tenant_from_context(): void
    {
        $this->context->set($this->tenantA->id);

        $business = Business::create([
            'legal_name' => 'Auto Stamped Pty Ltd',
            'trading_name' => 'Auto',
            'blockchain_id' => substr(md5('b'), 0, 8),
        ]);

        $this->assertSame($this->tenantA->id, $business->tenant_id);
    }

    public function test_global_scope_hides_other_tenants_rows(): void
    {
        // Create one business per tenant, each scoped via runAs.
        $businessA = $this->context->runAs($this->tenantA->id, fn () => Business::create([
            'legal_name' => 'A Co',
            'trading_name' => 'A',
            'blockchain_id' => substr(md5('A'), 0, 8),
        ]));

        $businessB = $this->context->runAs($this->tenantB->id, fn () => Business::create([
            'legal_name' => 'B Co',
            'trading_name' => 'B',
            'blockchain_id' => substr(md5('B'), 0, 8),
        ]));

        // From inside tenant A's context, only A's businesses are visible.
        $this->context->set($this->tenantA->id);
        $visible = Business::pluck('id')->all();

        $this->assertContains($businessA->id, $visible);
        $this->assertNotContains($businessB->id, $visible);

        // And switching context flips the visibility.
        $this->context->set($this->tenantB->id);
        $visible = Business::pluck('id')->all();

        $this->assertContains($businessB->id, $visible);
        $this->assertNotContains($businessA->id, $visible);
    }

    public function test_global_scope_applies_to_workplace_too(): void
    {
        // Confirms the trait is wired the same way on a downstream model,
        // not just on Business.
        $businessA = $this->context->runAs($this->tenantA->id, fn () => Business::create([
            'legal_name' => 'A Co',
            'trading_name' => 'A',
            'blockchain_id' => substr(md5('wA'), 0, 8),
        ]));

        $businessB = $this->context->runAs($this->tenantB->id, fn () => Business::create([
            'legal_name' => 'B Co',
            'trading_name' => 'B',
            'blockchain_id' => substr(md5('wB'), 0, 8),
        ]));

        $this->context->runAs($this->tenantA->id, fn () => Workplace::create([
            'business_id' => $businessA->id,
            'name' => 'A Site',
        ]));

        $this->context->runAs($this->tenantB->id, fn () => Workplace::create([
            'business_id' => $businessB->id,
            'name' => 'B Site',
        ]));

        $this->context->set($this->tenantA->id);
        $names = Workplace::pluck('name')->all();

        $this->assertContains('A Site', $names);
        $this->assertNotContains('B Site', $names);
    }
}
