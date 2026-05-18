<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_questions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignUuid('swms_version_id')->nullable()->constrained('swms_versions')->nullOnDelete();
            $table->unsignedSmallInteger('question_number');
            $table->text('prompt');
            $table->string('question_type', 32)->default('multiple_choice');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_critical_failure')->default(false);
            $table->jsonb('options')->nullable();
            $table->jsonb('correct_answer')->nullable();
            $table->jsonb('scoring_rules')->nullable();
            $table->string('source_sheet_name')->nullable();
            $table->unsignedInteger('source_row_number')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['task_id', 'swms_version_id']);
            $table->index('is_critical_failure');
        });

        DB::statement(
            'CREATE UNIQUE INDEX training_questions_number_unique '.
            'ON training_questions (task_id, question_number) '.
            'WHERE deleted_at IS NULL',
        );

        Schema::create('training_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->foreignUuid('workplace_id')->nullable()->constrained('workplaces')->nullOnDelete();
            $table->foreignUuid('task_id')->constrained('tasks')->restrictOnDelete();
            $table->foreignUuid('swms_version_id')->nullable()->constrained('swms_versions')->nullOnDelete();
            $table->foreignUuid('worker_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->string('status', 32)->default('in_progress');
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->boolean('passed')->nullable();
            $table->unsignedSmallInteger('critical_failure_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->uuid('supersedes_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'status']);
            $table->index(['worker_user_id', 'task_id']);
            $table->index('supersedes_id');
        });

        Schema::create('training_responses', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('training_attempt_id')->constrained('training_attempts')->cascadeOnDelete();
            $table->foreignUuid('training_question_id')->constrained('training_questions')->restrictOnDelete();
            $table->foreignUuid('worker_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->boolean('answer_boolean')->nullable();
            $table->decimal('answer_number', 12, 4)->nullable();
            $table->jsonb('answer_json')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->boolean('is_critical_failure')->default(false);
            $table->decimal('score_awarded', 8, 2)->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->uuid('supersedes_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['training_attempt_id', 'training_question_id'], 'training_responses_question_idx');
            $table->index(['worker_user_id', 'answered_at']);
            $table->index('supersedes_id');
        });

        Schema::create('worker_training_completions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->foreignUuid('workplace_id')->nullable()->constrained('workplaces')->nullOnDelete();
            $table->foreignUuid('task_id')->constrained('tasks')->restrictOnDelete();
            $table->foreignUuid('worker_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('latest_training_attempt_id')->nullable()->constrained('training_attempts')->nullOnDelete();
            $table->string('status', 32)->default('current');
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->unsignedSmallInteger('critical_failure_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'status']);
            $table->index(['worker_user_id', 'expires_at']);
            $table->index('latest_training_attempt_id', 'wtc_latest_attempt_idx');
        });

        DB::statement(
            'CREATE UNIQUE INDEX wtc_workplace_current_unique '.
            'ON worker_training_completions (worker_user_id, task_id, workplace_id) '.
            'WHERE workplace_id IS NOT NULL',
        );

        DB::statement(
            'CREATE UNIQUE INDEX wtc_business_current_unique '.
            'ON worker_training_completions (worker_user_id, task_id, business_entity_id) '.
            'WHERE workplace_id IS NULL',
        );

        foreach ([
            'training_attempts',
            'training_responses',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreign('supersedes_id')->references('id')->on($tableName)->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_training_completions');
        Schema::dropIfExists('training_responses');
        Schema::dropIfExists('training_attempts');
        Schema::dropIfExists('training_questions');
    }
};
