<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prepare the database for the v0.3 schema reset.
     *
     * This migration also clears obsolete scaffold schema from partial local
     * states so v0.3 can reuse canonical table names such as workplaces,
     * industries, occupations, and audit_events.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto');

        $this->dropLegacyUserForeignId('tenant_id');
        $this->dropLegacyUserForeignId('business_id');

        foreach ([
            'generated_documents',
            'file_uploads',
            'audit_events',
            'submissions',
            'activities',
            'task_pack_industries',
            'task_pack_occupations',
            'task_packs',
            'workplace_user_assignments',
            'user_occupations',
            'user_roles',
            'roles',
            'occupations',
            'industries',
            'workplaces',
            'businesses',
            'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Intentionally do not recreate the obsolete demo schema.
    }

    private function dropLegacyUserForeignId(string $column): void
    {
        if (! Schema::hasColumn('users', $column)) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($column) {
            $table->dropConstrainedForeignId($column);
        });
    }
};
