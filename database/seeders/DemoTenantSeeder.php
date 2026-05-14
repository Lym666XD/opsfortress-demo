<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\OpsFortress\Businesses\Models\Business;
use App\Domain\OpsFortress\Occupations\Models\Occupation;
use App\Domain\OpsFortress\People\Models\UserOccupation;
use App\Domain\OpsFortress\Permissions\Models\Role;
use App\Domain\OpsFortress\Permissions\Models\UserRole;
use App\Domain\OpsFortress\Tenancy\Models\Tenant;
use App\Domain\OpsFortress\Workplaces\Models\Workplace;
use App\Domain\OpsFortress\Workplaces\Models\WorkplaceUserAssignment;
use App\Domain\Shared\Tenancy\TenantContext;
use App\Domain\Whs\TaskPacks\Models\TaskPack;
use App\Domain\Whs\TaskPacks\Models\TaskPackOccupation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds one realistic demo tenant end-to-end:
 *
 *   Demo Tenant
 *     └─ Acme Construction Pty Ltd
 *          └─ Brisbane Site 1
 *               ├─ admin@demo.test       (Admin, no occupation)
 *               ├─ supervisor@demo.test  (Supervisor, Site Supervisor)
 *               └─ worker@demo.test      (Worker, Carpenter)
 *
 *     One sample TaskPack: "Lay Concrete Blocks" (matches the architecture
 *     record's reference template, §3.20). Eligible to Carpenters and
 *     General Labourers.
 *
 * Idempotent: every create is firstOrCreate keyed by a stable unique field.
 *
 * Tenant context handling: every BelongsToTenant model creation runs inside
 * TenantContext::runAs() so the global scope and creating-listener fire
 * correctly. The User model is NOT BelongsToTenant (see User.php docblock)
 * so tenant_id is set explicitly there.
 *
 * Default password for all 3 demo users is "password" (Fortify default,
 * acceptable for local dev only — never in production).
 */
final class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $context = app(TenantContext::class);

        // Tenant doesn't use BelongsToTenant (it's the root) — create directly.
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo'],
            ['name' => 'Demo Tenant', 'status' => 'active'],
        );

        $context->runAs($tenant->id, function () use ($tenant) {
            $business = $this->seedBusiness();
            $workplace = $this->seedWorkplace($business);
            $users = $this->seedUsers($tenant, $business);
            $this->assignRoles($tenant, $business, $users);
            $this->assignOccupations($tenant, $business, $users);
            $this->assignToWorkplace($tenant, $business, $workplace, $users);
            $this->seedTaskPack($tenant, $business);
        });
    }

    private function seedBusiness(): Business
    {
        // Look up by legal_name (tenant-scoped via global scope), not by
        // blockchain_id. The Business model's `creating` observer now
        // auto-generates a ULID; passing one explicitly is no longer the
        // identity. This keeps re-runs idempotent and lets fresh seeds use
        // ULIDs (rather than the legacy 'acme0001' literal).
        return Business::firstOrCreate(
            ['legal_name' => 'Acme Construction Pty Ltd'],
            [
                'trading_name' => 'Acme Construction',
                'abn' => '12 345 678 901',
                'business_type' => 'company',
                'primary_email' => 'admin@demo.test',
                'primary_phone' => '+61 7 0000 0000',
                'status' => 'active',
            ],
        );
    }

    private function seedWorkplace(Business $business): Workplace
    {
        return Workplace::firstOrCreate(
            ['code' => 'BNE-01'],
            [
                'business_id' => $business->id,
                'name' => 'Brisbane Site 1',
                'classification' => 'standard',
                'street_address' => '100 Demo Street',
                'suburb' => 'South Brisbane',
                'state' => 'QLD',
                'postcode' => '4101',
                'country' => 'AU',
                'latitude' => -27.4810,
                'longitude' => 153.0244,
                'geofence_radius_meters' => 100,
                'active' => true,
            ],
        );
    }

    /**
     * @return array{admin:User, supervisor:User, worker:User}
     */
    private function seedUsers(Tenant $tenant, Business $business): array
    {
        $defaults = [
            'tenant_id' => $tenant->id,
            'business_id' => $business->id,
            'status' => 'active',
            'person_type' => 'employee',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ];

        $admin = User::firstOrCreate(
            ['email' => 'admin@demo.test'],
            [...$defaults, 'name' => 'Demo Admin', 'first_name' => 'Demo', 'last_name' => 'Admin'],
        );

        $supervisor = User::firstOrCreate(
            ['email' => 'supervisor@demo.test'],
            [...$defaults, 'name' => 'Demo Supervisor', 'first_name' => 'Demo', 'last_name' => 'Supervisor'],
        );

        $worker = User::firstOrCreate(
            ['email' => 'worker@demo.test'],
            [...$defaults, 'name' => 'Demo Worker', 'first_name' => 'Demo', 'last_name' => 'Worker'],
        );

        return ['admin' => $admin, 'supervisor' => $supervisor, 'worker' => $worker];
    }

    /**
     * @param  array{admin:User, supervisor:User, worker:User}  $users
     */
    private function assignRoles(Tenant $tenant, Business $business, array $users): void
    {
        $roleMap = [
            'admin' => Role::where(['tenant_id' => null, 'code' => 'admin'])->firstOrFail(),
            'supervisor' => Role::where(['tenant_id' => null, 'code' => 'supervisor'])->firstOrFail(),
            'worker' => Role::where(['tenant_id' => null, 'code' => 'worker'])->firstOrFail(),
        ];

        foreach ($users as $key => $user) {
            UserRole::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'role_id' => $roleMap[$key]->id,
                    'business_id' => $business->id,
                ],
                ['tenant_id' => $tenant->id],
            );
        }
    }

    /**
     * @param  array{admin:User, supervisor:User, worker:User}  $users
     */
    private function assignOccupations(Tenant $tenant, Business $business, array $users): void
    {
        $supervisorOccupation = Occupation::where('code', 'site-supervisor')->firstOrFail();
        $workerOccupation = Occupation::where('code', 'carpenter')->firstOrFail();

        UserOccupation::firstOrCreate(
            [
                'user_id' => $users['supervisor']->id,
                'occupation_id' => $supervisorOccupation->id,
                'business_id' => $business->id,
            ],
            ['tenant_id' => $tenant->id, 'is_primary' => true],
        );

        UserOccupation::firstOrCreate(
            [
                'user_id' => $users['worker']->id,
                'occupation_id' => $workerOccupation->id,
                'business_id' => $business->id,
            ],
            ['tenant_id' => $tenant->id, 'is_primary' => true],
        );
    }

    /**
     * @param  array{admin:User, supervisor:User, worker:User}  $users
     */
    private function assignToWorkplace(Tenant $tenant, Business $business, Workplace $workplace, array $users): void
    {
        foreach ($users as $key => $user) {
            WorkplaceUserAssignment::firstOrCreate(
                ['workplace_id' => $workplace->id, 'user_id' => $user->id],
                [
                    'tenant_id' => $tenant->id,
                    'business_id' => $business->id,
                    'role_context' => $key,
                    'active_from' => now(),
                ],
            );
        }
    }

    private function seedTaskPack(Tenant $tenant, Business $business): TaskPack
    {
        $pack = TaskPack::firstOrCreate(
            ['code' => 'lay-concrete-blocks'],
            [
                'tenant_id' => $tenant->id,
                'business_id' => $business->id,
                'title' => 'Lay Concrete Blocks',
                'category' => 'swms',
                'status' => 'active',
                'version' => '7.0.0',
                'summary' => 'SWMS for masonry block laying — derived from the v7 reference workbook in WHS_Architecture_Record §3.20.',
                'requires_swms_ack' => true,
                'requires_prestart' => true,
                'requires_posttask' => false,
                'requires_training' => false,
            ],
        );

        // Eligible occupations.
        foreach (['carpenter', 'general-labourer'] as $code) {
            $occupation = Occupation::where('code', $code)->firstOrFail();

            TaskPackOccupation::firstOrCreate(
                ['task_pack_id' => $pack->id, 'occupation_id' => $occupation->id],
                ['access_level' => 'full'],
            );
        }

        return $pack;
    }
}
