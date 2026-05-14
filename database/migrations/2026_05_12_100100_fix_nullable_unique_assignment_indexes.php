<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix the nullable-unique flaw on user_roles, user_occupations, and (defensively)
 * any other assignment table that uses `unique([..., business_id])` where
 * business_id is nullable.
 *
 * Problem: in PostgreSQL, multiple NULLs in a unique index are NOT considered
 * equal, so platform-scoped rows (business_id IS NULL) can be duplicated
 * silently. This is flagged in MILESTONE.md "Review Findings To Address" #1.
 *
 * Fix: replace the standard unique constraint with two partial unique indexes:
 *   1. WHERE business_id IS NOT NULL — uniqueness across all three columns
 *   2. WHERE business_id IS NULL — uniqueness across the other two only
 *
 * This preserves PostgreSQL semantics rather than fighting them.
 */
return new class extends Migration
{
    public function up(): void
    {
        // user_roles
        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'role_id', 'business_id']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX user_roles_business_unique '.
            'ON user_roles (user_id, role_id, business_id) '.
            'WHERE business_id IS NOT NULL',
        );

        DB::statement(
            'CREATE UNIQUE INDEX user_roles_platform_unique '.
            'ON user_roles (user_id, role_id) '.
            'WHERE business_id IS NULL',
        );

        // user_occupations
        Schema::table('user_occupations', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'occupation_id', 'business_id']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX user_occupations_business_unique '.
            'ON user_occupations (user_id, occupation_id, business_id) '.
            'WHERE business_id IS NOT NULL',
        );

        DB::statement(
            'CREATE UNIQUE INDEX user_occupations_platform_unique '.
            'ON user_occupations (user_id, occupation_id) '.
            'WHERE business_id IS NULL',
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS user_roles_business_unique');
        DB::statement('DROP INDEX IF EXISTS user_roles_platform_unique');
        DB::statement('DROP INDEX IF EXISTS user_occupations_business_unique');
        DB::statement('DROP INDEX IF EXISTS user_occupations_platform_unique');

        Schema::table('user_roles', function (Blueprint $table) {
            $table->unique(['user_id', 'role_id', 'business_id']);
        });

        Schema::table('user_occupations', function (Blueprint $table) {
            $table->unique(['user_id', 'occupation_id', 'business_id']);
        });
    }
};
