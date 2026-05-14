<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\OpsFortress\Industries\Models\Industry;
use App\Domain\OpsFortress\Occupations\Models\Occupation;
use App\Domain\OpsFortress\Permissions\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Seeds platform-level catalog data: industries, occupations, and the four
 * baseline permission roles. This data is NOT tenant-scoped — every tenant
 * draws from the same catalog.
 *
 * In production these tables get loaded from the OpsFortress Central Source
 * Pack v4 (~1,000 rows of industries, occupations, etc.). For demo seed, a
 * small representative sample is enough to exercise the relationship model.
 *
 * All entries use firstOrCreate so the seeder is safe to re-run.
 */
final class PlatformCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedIndustries();
        $this->seedOccupations();
        $this->seedBaselineRoles();
    }

    private function seedIndustries(): void
    {
        $construction = Industry::firstOrCreate(
            ['code' => 'construction'],
            ['name' => 'Construction', 'level' => 1, 'active' => true],
        );

        Industry::firstOrCreate(
            ['code' => 'construction.building-services'],
            ['name' => 'Building Services', 'parent_id' => $construction->id, 'level' => 2, 'active' => true],
        );

        Industry::firstOrCreate(
            ['code' => 'construction.demolition'],
            ['name' => 'Demolition', 'parent_id' => $construction->id, 'level' => 2, 'active' => true],
        );

        Industry::firstOrCreate(
            ['code' => 'cleaning'],
            ['name' => 'Cleaning', 'level' => 1, 'active' => true],
        );
    }

    private function seedOccupations(): void
    {
        $construction = Industry::where('code', 'construction')->firstOrFail();
        $cleaning = Industry::where('code', 'cleaning')->firstOrFail();

        Occupation::firstOrCreate(
            ['code' => 'carpenter'],
            ['name' => 'Carpenter', 'industry_id' => $construction->id, 'level' => 1, 'active' => true],
        );

        Occupation::firstOrCreate(
            ['code' => 'general-labourer'],
            ['name' => 'General Labourer', 'industry_id' => $construction->id, 'level' => 1, 'active' => true],
        );

        Occupation::firstOrCreate(
            ['code' => 'site-supervisor'],
            ['name' => 'Site Supervisor', 'industry_id' => $construction->id, 'level' => 1, 'active' => true],
        );

        Occupation::firstOrCreate(
            ['code' => 'cleaner'],
            ['name' => 'Cleaner', 'industry_id' => $cleaning->id, 'level' => 1, 'active' => true],
        );
    }

    /**
     * The four MVP permission roles (per Role_Architecture_Notes.md).
     * Platform-scoped (tenant_id = NULL) so they apply to every tenant.
     */
    private function seedBaselineRoles(): void
    {
        $roles = [
            ['code' => 'admin', 'name' => 'Admin', 'description' => 'Full platform access, billing, user management, tenant settings.'],
            ['code' => 'manager', 'name' => 'Manager', 'description' => 'Configure task packs, view reports across teams.'],
            ['code' => 'supervisor', 'name' => 'Supervisor', 'description' => 'Assign tasks, review submissions, manage their team.'],
            ['code' => 'worker', 'name' => 'Worker', 'description' => 'Complete pre-starts, SWMS steps, post-task checks, training.'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['tenant_id' => null, 'code' => $role['code']],
                [
                    'name' => $role['name'],
                    'scope' => 'platform',
                    'description' => $role['description'],
                ],
            );
        }
    }
}
