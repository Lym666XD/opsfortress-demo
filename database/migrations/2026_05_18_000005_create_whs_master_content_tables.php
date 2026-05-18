<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industries', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('external_industry_id')->nullable();
            $table->string('industry_group')->nullable();
            $table->string('industry_sub_group')->nullable();
            $table->string('industry_leaf')->nullable();
            $table->string('industry_candidate_key')->nullable();
            $table->boolean('active_status')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('active_status');
            $table->index('industry_group');
        });

        DB::statement(
            'CREATE UNIQUE INDEX industries_external_id_active_unique '.
            'ON industries (external_industry_id) '.
            'WHERE deleted_at IS NULL AND external_industry_id IS NOT NULL',
        );

        DB::statement(
            'CREATE UNIQUE INDEX industries_candidate_key_active_unique '.
            'ON industries (industry_candidate_key) '.
            'WHERE deleted_at IS NULL AND industry_candidate_key IS NOT NULL',
        );

        Schema::create('occupations', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('external_occupation_id')->nullable();
            $table->string('occupation_group')->nullable();
            $table->string('occupation_sub_group')->nullable();
            $table->string('occupation_leaf')->nullable();
            $table->string('occupation_candidate_key')->nullable();
            $table->boolean('active_status')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('active_status');
            $table->index('occupation_group');
        });

        DB::statement(
            'CREATE UNIQUE INDEX occupations_external_id_active_unique '.
            'ON occupations (external_occupation_id) '.
            'WHERE deleted_at IS NULL AND external_occupation_id IS NOT NULL',
        );

        DB::statement(
            'CREATE UNIQUE INDEX occupations_candidate_key_active_unique '.
            'ON occupations (occupation_candidate_key) '.
            'WHERE deleted_at IS NULL AND occupation_candidate_key IS NOT NULL',
        );

        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('external_task_id');
            $table->string('task_name');
            $table->string('task_title')->nullable();
            $table->string('document_type', 64)->nullable();
            $table->string('trade_industry')->nullable();
            $table->string('task_group')->nullable();
            $table->string('task_sub_group')->nullable();
            $table->string('task_leaf')->nullable();
            $table->string('task_candidate_key')->nullable();
            $table->boolean('active_status')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('active_status');
            $table->index(['document_type', 'active_status']);
            $table->index('task_group');
        });

        DB::statement(
            'CREATE UNIQUE INDEX tasks_external_id_active_unique '.
            'ON tasks (external_task_id) '.
            'WHERE deleted_at IS NULL',
        );

        DB::statement(
            'CREATE UNIQUE INDEX tasks_candidate_key_active_unique '.
            'ON tasks (task_candidate_key) '.
            'WHERE deleted_at IS NULL AND task_candidate_key IS NOT NULL',
        );

        Schema::create('business_industries', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('account_id')->nullable()->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->foreignUuid('industry_id')->constrained('industries')->restrictOnDelete();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('account_id');
            $table->index('industry_id');
        });

        DB::statement(
            'CREATE UNIQUE INDEX business_industries_active_unique '.
            'ON business_industries (business_entity_id, industry_id) '.
            'WHERE deleted_at IS NULL',
        );

        Schema::create('task_occupation_access', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignUuid('occupation_id')->constrained('occupations')->cascadeOnDelete();
            $table->string('swms_view_access', 16)->default('none');
            $table->string('pre_start_access', 16)->default('none');
            $table->string('post_task_access', 16)->default('none');
            $table->string('training_access', 16)->default('none');
            $table->string('menu_visibility', 16)->default('none');
            $table->boolean('active_status')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('occupation_id');
            $table->index('swms_view_access');
        });

        DB::statement(
            'ALTER TABLE task_occupation_access ADD CONSTRAINT toa_access_values_check '.
            "CHECK (swms_view_access IN ('full','conditional','supervised','none') ".
            "AND pre_start_access IN ('full','conditional','supervised','none') ".
            "AND post_task_access IN ('full','conditional','supervised','none') ".
            "AND training_access IN ('full','conditional','supervised','none') ".
            "AND menu_visibility IN ('full','conditional','supervised','none'))"
        );

        DB::statement(
            'CREATE UNIQUE INDEX task_occupation_access_active_unique '.
            'ON task_occupation_access (task_id, occupation_id) '.
            'WHERE deleted_at IS NULL',
        );

        Schema::create('task_industry_access', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignUuid('industry_id')->constrained('industries')->cascadeOnDelete();
            $table->string('swms_view_access', 16)->default('none');
            $table->string('pre_start_access', 16)->default('none');
            $table->string('post_task_access', 16)->default('none');
            $table->string('training_access', 16)->default('none');
            $table->string('menu_visibility', 16)->default('none');
            $table->boolean('active_status')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('industry_id');
            $table->index('swms_view_access');
        });

        DB::statement(
            'ALTER TABLE task_industry_access ADD CONSTRAINT tia_access_values_check '.
            "CHECK (swms_view_access IN ('full','conditional','supervised','none') ".
            "AND pre_start_access IN ('full','conditional','supervised','none') ".
            "AND post_task_access IN ('full','conditional','supervised','none') ".
            "AND training_access IN ('full','conditional','supervised','none') ".
            "AND menu_visibility IN ('full','conditional','supervised','none'))"
        );

        DB::statement(
            'CREATE UNIQUE INDEX task_industry_access_active_unique '.
            'ON task_industry_access (task_id, industry_id) '.
            'WHERE deleted_at IS NULL',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('task_industry_access');
        Schema::dropIfExists('task_occupation_access');
        Schema::dropIfExists('business_industries');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('occupations');
        Schema::dropIfExists('industries');
    }
};
