<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * K1 + K2 from MILESTONE — Kevin-driven business identity hardening.
 *
 * K1: widen all `blockchain_id` columns from varchar(16) to varchar(26) so
 *     they can hold a ULID (26 chars). Per Kevin's spec (2026-05-13) the
 *     blockchain_id is internal-only, system-generated, and immutable. Auto-
 *     generation and immutability are enforced at the model layer, not in
 *     this migration.
 *
 * K2: businesses.abn becomes globally unique (across all tenants) via a
 *     partial unique index. Per Kevin: "An ABN belongs to a specific legal
 *     entity. The same ABN should not be registered under two different
 *     customer accounts or duplicated within the same account." NULL abn
 *     is still allowed (sole traders during onboarding may not have one
 *     yet).
 *
 * Existing data: the seeded business has blockchain_id 'acme0001' (8 chars).
 * It fits in varchar(26) so no backfill is needed in dev. New businesses
 * created via the controller (slice 2 onwards) will get a ULID via the
 * Business model's `creating` observer.
 */
return new class extends Migration
{
    public function up(): void
    {
        // K1 — widen blockchain_id columns
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('blockchain_id', 26)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('blockchain_id', 26)->nullable()->change();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->string('blockchain_id', 26)->nullable()->change();
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->string('blockchain_id', 26)->nullable()->change();
        });

        // K2 — global unique on businesses.abn (where not null)
        DB::statement(
            'CREATE UNIQUE INDEX businesses_abn_unique '.
            'ON businesses (abn) '.
            'WHERE abn IS NOT NULL',
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS businesses_abn_unique');

        Schema::table('submissions', function (Blueprint $table) {
            $table->string('blockchain_id', 16)->nullable()->change();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->string('blockchain_id', 16)->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('blockchain_id', 16)->nullable()->change();
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->string('blockchain_id', 16)->change();
        });
    }
};
