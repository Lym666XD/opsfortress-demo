<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Domain\OpsFortress\Permissions\Models\Role;
use App\Domain\OpsFortress\Tenancy\Models\Tenant;
use App\Domain\Shared\Tenancy\TenantContext;
use App\Models\User;
use Database\Seeders\PlatformCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Audit hardening A4: User::hasRole() and ::roleCodes() must filter
 * user_roles.tenant_id by the user's own tenant_id, so a corrupted
 * cross-tenant user_roles row does NOT grant permissions.
 */
final class HasRoleTenantBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformCatalogSeeder::class);
    }

    public function test_legitimate_role_within_same_tenant_returns_true(): void
    {
        $context = app(TenantContext::class);
        $context->clear();

        $tenant = $context->runAs(null, fn () => Tenant::create([
            'slug' => 'tenant-legit',
            'name' => 'Legit',
        ]));

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Legit Admin',
            'email' => 'legit@test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $adminRole = Role::query()->where(['tenant_id' => null, 'code' => 'admin'])->firstOrFail();

        // Insert a user_roles row stamped with the user's own tenant.
        DB::table('user_roles')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role_id' => $adminRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertContains('admin', $user->roleCodes());
    }

    public function test_cross_tenant_user_roles_row_does_not_grant_permission(): void
    {
        $context = app(TenantContext::class);
        $context->clear();

        $tenantA = $context->runAs(null, fn () => Tenant::create(['slug' => 'a', 'name' => 'A']));
        $tenantB = $context->runAs(null, fn () => Tenant::create(['slug' => 'b', 'name' => 'B']));

        $user = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'A User',
            'email' => 'a@test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $adminRole = Role::query()->where(['tenant_id' => null, 'code' => 'admin'])->firstOrFail();

        // Simulate corruption: a user in tenant A has a user_roles row
        // stamped with tenant B. Without A4's explicit filter this would
        // silently grant the user the admin role.
        DB::table('user_roles')->insert([
            'tenant_id' => $tenantB->id,   // wrong tenant
            'user_id' => $user->id,
            'role_id' => $adminRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFalse(
            $user->hasRole('admin'),
            'Cross-tenant user_roles row must not grant role; A4 guard failed.',
        );

        $this->assertNotContains(
            'admin',
            $user->roleCodes(),
            'roleCodes() must also honor the tenant boundary.',
        );
    }

    public function test_user_with_null_tenant_id_holds_no_roles(): void
    {
        $user = User::create([
            'tenant_id' => null,
            'name' => 'Platform Phantom',
            'email' => 'phantom@test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $this->assertFalse($user->hasRole('admin'));
        $this->assertSame([], $user->roleCodes());
    }
}
