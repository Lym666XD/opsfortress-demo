<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\OpsFortress\Businesses\Models\Business;
use App\Domain\OpsFortress\Permissions\Models\Role;
use App\Domain\OpsFortress\Permissions\Models\UserRole;
use App\Domain\OpsFortress\Tenancy\Models\Tenant;
use App\Domain\OpsFortress\Workplaces\Models\Workplace;
use App\Domain\Shared\Audit\Models\AuditEvent;
use App\Domain\Shared\Audit\Services\AuditService;
use App\Domain\Shared\Tenancy\TenantContext;
use App\Models\User;
use Database\Seeders\PlatformCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * M10 Slice 1 — end-to-end coverage for "Add Workplace".
 *
 * Verifies the full stack:
 *   - Policy: admin/manager can; worker cannot
 *   - Tenant isolation: admin in tenant A cannot see tenant B's workplaces
 *   - Validation: FormRequest rejects business_id from another tenant
 *   - Audit: one ADMIN-anchored audit event per successful create
 *   - Persistence: workplace shows up on list after creation
 */
final class WorkplaceManagementTest extends TestCase
{
    use RefreshDatabase;

    private TenantContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        // Decouple from the Vite manifest. Without this, GETs that render an
        // Inertia page would fail with "Unable to locate file in Vite manifest"
        // whenever a newly-added .tsx page hasn't been built via `bun run build`.
        // This is a backend test — it must not depend on frontend bundling.
        $this->withoutVite();

        // Seed platform-level catalog (industries, occupations, baseline roles).
        $this->seed(PlatformCatalogSeeder::class);

        $this->context = app(TenantContext::class);
        $this->context->clear();
    }

    public function test_admin_can_view_workplaces_index_for_their_tenant(): void
    {
        [$tenant, $business, $admin] = $this->makeTenantWithAdmin('a');
        $workplace = $this->makeWorkplace($tenant, $business, 'A Site');

        $response = $this->actingAs($admin)->get('/admin/workplaces');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/workplaces/index')
            ->has('workplaces', 1)
            ->where('workplaces.0.id', $workplace->id)
            ->where('workplaces.0.name', 'A Site'),
        );
    }

    public function test_admin_cannot_see_workplaces_from_another_tenant(): void
    {
        [$tenantA, $businessA, $adminA] = $this->makeTenantWithAdmin('a');
        [$tenantB, $businessB] = $this->makeTenantWithAdmin('b');

        $this->makeWorkplace($tenantA, $businessA, 'A Site');
        $workplaceB = $this->makeWorkplace($tenantB, $businessB, 'B Site');

        $response = $this->actingAs($adminA)->get('/admin/workplaces');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('workplaces', 1)
            ->where('workplaces.0.name', 'A Site'),
        );

        // Cross-check: directly confirm tenant B's row exists but is hidden.
        $this->context->runAs($tenantB->id, function () use ($workplaceB) {
            $this->assertTrue(Workplace::query()->whereKey($workplaceB->id)->exists());
        });
    }

    public function test_worker_cannot_view_workplaces_index(): void
    {
        [$tenant, $business] = $this->makeTenantWithAdmin('a');
        $worker = $this->makeUserWithRole($tenant, $business, 'worker', 'worker-a@test');

        $response = $this->actingAs($worker)->get('/admin/workplaces');

        $response->assertForbidden();
    }

    public function test_worker_cannot_view_create_form(): void
    {
        [$tenant, $business] = $this->makeTenantWithAdmin('a');
        $worker = $this->makeUserWithRole($tenant, $business, 'worker', 'worker-a@test');

        $response = $this->actingAs($worker)->get('/admin/workplaces/create');

        $response->assertForbidden();
    }

    public function test_admin_can_create_a_workplace_via_post(): void
    {
        [$tenant, $business, $admin] = $this->makeTenantWithAdmin('a');

        $payload = [
            'business_id' => $business->id,
            'name' => 'Newly Created Site',
            'code' => 'NCS-01',
            'suburb' => 'Fortitude Valley',
            'state' => 'QLD',
            'postcode' => '4006',
            'country' => 'AU',
            'latitude' => -27.4560,
            'longitude' => 153.0341,
            'geofence_radius_meters' => 75,
        ];

        $response = $this->actingAs($admin)->post('/admin/workplaces', $payload);

        $response->assertRedirect('/admin/workplaces');

        $this->context->runAs($tenant->id, function () use ($business) {
            $created = Workplace::query()->where('name', 'Newly Created Site')->first();
            $this->assertNotNull($created);
            $this->assertSame($business->id, $created->business_id);
            $this->assertSame(75, $created->geofence_radius_meters);
        });
    }

    public function test_creating_a_workplace_writes_an_audit_event(): void
    {
        [$tenant, $business, $admin] = $this->makeTenantWithAdmin('a');

        $this->actingAs($admin)->post('/admin/workplaces', [
            'business_id' => $business->id,
            'name' => 'Audited Site',
        ]);

        $this->context->runAs($tenant->id, function () use ($admin) {
            $event = AuditEvent::query()
                ->where('event_name', 'workplace.created')
                ->latest('id')
                ->first();

            $this->assertNotNull($event);
            $this->assertSame(AuditService::ANCHOR_ADMIN_CONFIG, $event->anchor);
            $this->assertNull($event->previous_hash, 'first event for a subject must have null previous_hash');
            $this->assertSame(64, strlen($event->hash));
            $this->assertSame($admin->id, $event->user_id);
        });
    }

    public function test_cannot_create_workplace_with_business_id_from_another_tenant(): void
    {
        [, , $adminA] = $this->makeTenantWithAdmin('a');
        [, $businessB] = $this->makeTenantWithAdmin('b');

        $response = $this->actingAs($adminA)->post('/admin/workplaces', [
            'business_id' => $businessB->id,
            'name' => 'Cross-Tenant Attempt',
        ]);

        $response->assertSessionHasErrors('business_id');
    }

    /**
     * @return array{0:Tenant, 1:Business, 2:User}
     */
    private function makeTenantWithAdmin(string $suffix): array
    {
        $tenant = $this->context->runAs(null, fn () => Tenant::create([
            'slug' => 'tenant-'.$suffix,
            'name' => 'Tenant '.strtoupper($suffix),
        ]));

        $business = $this->context->runAs($tenant->id, fn () => Business::create([
            'legal_name' => 'Biz '.strtoupper($suffix).' Pty Ltd',
            'trading_name' => 'Biz '.strtoupper($suffix),
            'blockchain_id' => substr(md5('biz-'.$suffix), 0, 8),
        ]));

        $admin = $this->makeUserWithRole($tenant, $business, 'admin', 'admin-'.$suffix.'@test');

        return [$tenant, $business, $admin];
    }

    private function makeUserWithRole(Tenant $tenant, Business $business, string $roleCode, string $email): User
    {
        $user = User::create([
            'tenant_id' => $tenant->id,
            'business_id' => $business->id,
            'name' => ucfirst($roleCode).' '.$email,
            'email' => $email,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'status' => 'active',
            'person_type' => 'employee',
        ]);

        $role = Role::query()->where(['tenant_id' => null, 'code' => $roleCode])->firstOrFail();

        $this->context->runAs($tenant->id, fn () => UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'business_id' => $business->id,
        ]));

        return $user;
    }

    private function makeWorkplace(Tenant $tenant, Business $business, string $name): Workplace
    {
        return $this->context->runAs($tenant->id, fn () => Workplace::create([
            'business_id' => $business->id,
            'name' => $name,
            'code' => substr(md5($name), 0, 6),
        ]));
    }
}
