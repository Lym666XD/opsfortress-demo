<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Top-level seeder. Order matters:
 *
 *   1. PlatformCatalogSeeder — industries, occupations, and the four baseline
 *      permission roles. NOT tenant-scoped. Always runs first because tenant
 *      seeders reference these by code.
 *
 *   2. DemoTenantSeeder — one realistic tenant (Acme Construction) with
 *      business, workplace, three users, role/occupation assignments, and
 *      a sample task pack. Wraps everything in TenantContext::runAs() so
 *      the BelongsToTenant trait stamps tenant_id correctly.
 *
 * Both child seeders are idempotent (firstOrCreate on stable keys), so
 * `php artisan db:seed` can be re-run safely without duplicating rows.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlatformCatalogSeeder::class,
            DemoTenantSeeder::class,
        ]);
    }
}
