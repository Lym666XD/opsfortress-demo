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
            $table->uuid('parent_id')->nullable();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('status', 32)->default('active');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'status']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX industries_code_active_unique '.
            'ON industries (code) '.
            'WHERE deleted_at IS NULL',
        );

        Schema::table('industries', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('industries')->nullOnDelete();
        });

        Schema::create('occupations', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('parent_id')->nullable();
            $table->foreignUuid('primary_industry_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('status', 32)->default('active');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'status']);
            $table->index('primary_industry_id');
        });

        DB::statement(
            'CREATE UNIQUE INDEX occupations_code_active_unique '.
            'ON occupations (code) '.
            'WHERE deleted_at IS NULL',
        );

        Schema::table('occupations', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('occupations')->nullOnDelete();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('task_code')->nullable();
            $table->string('external_task_id')->nullable();
            $table->string('slug')->nullable();
            $table->string('title');
            $table->string('task_type', 64)->default('swms');
            $table->text('summary')->nullable();
            $table->string('status', 32)->default('draft');
            $table->string('source_file_name')->nullable();
            $table->string('source_sheet_name')->nullable();
            $table->unsignedInteger('source_row_number')->nullable();
            $table->string('source_hash', 128)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['task_type', 'status']);
            $table->index('external_task_id');
        });

        DB::statement(
            'CREATE UNIQUE INDEX tasks_task_code_active_unique '.
            'ON tasks (task_code) '.
            'WHERE deleted_at IS NULL AND task_code IS NOT NULL',
        );

        DB::statement(
            'CREATE UNIQUE INDEX tasks_external_id_active_unique '.
            'ON tasks (external_task_id) '.
            'WHERE deleted_at IS NULL AND external_task_id IS NOT NULL',
        );

        Schema::create('business_industries', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('customer_account_id')->nullable()->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->foreignUuid('industry_id')->constrained('industries')->restrictOnDelete();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_account_id');
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
            $table->string('access_level', 32)->default('eligible');
            $table->boolean('is_primary')->default(false);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('occupation_id');
        });

        DB::statement(
            'CREATE UNIQUE INDEX task_occupation_access_active_unique '.
            'ON task_occupation_access (task_id, occupation_id) '.
            'WHERE deleted_at IS NULL',
        );

        Schema::create('task_industry_access', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignUuid('industry_id')->constrained('industries')->cascadeOnDelete();
            $table->string('access_level', 32)->default('eligible');
            $table->boolean('is_primary')->default(false);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('industry_id');
        });

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
