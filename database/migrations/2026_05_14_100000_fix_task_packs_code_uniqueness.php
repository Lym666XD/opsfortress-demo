<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix task_packs.code uniqueness scope.
 *
 * Original schema declared `task_packs.code` as globally unique. That's wrong:
 * task_packs are tenant-scoped (or platform-shared via NULL tenant_id), and
 * two different tenants must each be able to have their own "lay-concrete-blocks"
 * code. The original constraint blocks legitimate customer setup.
 *
 * Replacement (same partial-index pattern as P0-6 nullable-unique fix):
 *   - tenant-scoped packs:    unique (tenant_id, code) where tenant_id IS NOT NULL
 *   - platform-shared packs:  unique (code)            where tenant_id IS NULL
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop the auto-generated unique constraint on task_packs.code.
        Schema::table('task_packs', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX task_packs_tenant_code_unique '.
            'ON task_packs (tenant_id, code) '.
            'WHERE tenant_id IS NOT NULL',
        );

        DB::statement(
            'CREATE UNIQUE INDEX task_packs_platform_code_unique '.
            'ON task_packs (code) '.
            'WHERE tenant_id IS NULL',
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS task_packs_tenant_code_unique');
        DB::statement('DROP INDEX IF EXISTS task_packs_platform_code_unique');

        Schema::table('task_packs', function (Blueprint $table) {
            $table->unique('code');
        });
    }
};
