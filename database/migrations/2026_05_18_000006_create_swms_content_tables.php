<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('swms_versions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('external_swms_version_id')->nullable();
            $table->string('version_label', 64)->nullable();
            $table->string('status', 32)->default('draft');
            $table->jsonb('full_swms_content');
            $table->string('source_file_name')->nullable();
            $table->string('source_sheet_name')->nullable();
            $table->unsignedInteger('source_row_number')->nullable();
            $table->string('source_hash', 128)->nullable();
            $table->foreignUuid('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->uuid('supersedes_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['task_id', 'status']);
            $table->index('supersedes_id');
        });

        DB::statement(
            'CREATE UNIQUE INDEX swms_versions_external_active_unique '.
            'ON swms_versions (task_id, external_swms_version_id) '.
            'WHERE deleted_at IS NULL AND external_swms_version_id IS NOT NULL',
        );

        Schema::table('swms_versions', function (Blueprint $table) {
            $table->foreign('supersedes_id')->references('id')->on('swms_versions')->nullOnDelete();
        });

        Schema::create('swms_activity_steps', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('swms_version_id')->constrained('swms_versions')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_number');
            $table->string('title')->nullable();
            $table->text('instruction');
            $table->jsonb('hazards')->nullable();
            $table->jsonb('controls')->nullable();
            $table->jsonb('required_ppe')->nullable();
            $table->unsignedSmallInteger('minimum_read_seconds')->nullable();
            $table->string('source_sheet_name')->nullable();
            $table->unsignedInteger('source_row_number')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('swms_version_id');
        });

        DB::statement(
            'CREATE UNIQUE INDEX swms_activity_steps_number_unique '.
            'ON swms_activity_steps (swms_version_id, step_number) '.
            'WHERE deleted_at IS NULL',
        );

        Schema::create('prestart_questions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->unsignedSmallInteger('question_number');
            $table->text('prompt');
            $table->string('question_type', 32)->default('yes_no');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_critical_failure')->default(false);
            $table->string('expected_answer')->nullable();
            $table->jsonb('options')->nullable();
            $table->jsonb('scoring_rules')->nullable();
            $table->string('source_sheet_name')->nullable();
            $table->unsignedInteger('source_row_number')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('task_id');
            $table->index('is_critical_failure');
        });

        DB::statement(
            'CREATE UNIQUE INDEX prestart_questions_number_unique '.
            'ON prestart_questions (task_id, question_number) '.
            'WHERE deleted_at IS NULL',
        );

        Schema::create('workplace_task_settings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->foreignUuid('workplace_id')->constrained('workplaces')->cascadeOnDelete();
            $table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignUuid('active_swms_version_id')->nullable()->constrained('swms_versions')->nullOnDelete();
            $table->string('prestart_frequency', 32)->default('daily');
            $table->string('posttask_frequency', 32)->default('off');
            $table->unsignedInteger('training_refresh_interval_days')->nullable();
            $table->unsignedInteger('minimum_read_seconds')->nullable();
            $table->foreignUuid('configured_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('configured_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'workplace_id'], 'wts_account_workplace_idx');
            $table->index(['business_entity_id', 'task_id'], 'wts_business_task_idx');
        });

        DB::statement(
            'CREATE UNIQUE INDEX workplace_task_settings_active_unique '.
            'ON workplace_task_settings (workplace_id, task_id) '.
            'WHERE deleted_at IS NULL',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('workplace_task_settings');
        Schema::dropIfExists('prestart_questions');
        Schema::dropIfExists('swms_activity_steps');
        Schema::dropIfExists('swms_versions');
    }
};
