<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posttask_questions', function (Blueprint $table) {
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
            'CREATE UNIQUE INDEX posttask_questions_number_unique '.
            'ON posttask_questions (task_id, question_number) '.
            'WHERE deleted_at IS NULL',
        );

        Schema::create('posttask_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('worker_task_session_id')->constrained('worker_task_sessions')->cascadeOnDelete();
            $table->foreignUuid('account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->foreignUuid('workplace_id')->constrained('workplaces')->cascadeOnDelete();
            $table->foreignUuid('task_id')->constrained('tasks')->restrictOnDelete();
            $table->foreignUuid('worker_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('draft');
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->unsignedSmallInteger('critical_failure_count')->default(0);
            $table->boolean('has_critical_failure')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->uuid('supersedes_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'status']);
            $table->index(['worker_user_id', 'submitted_at']);
            $table->index(['workplace_id', 'task_id']);
            $table->index('supersedes_id');
        });

        Schema::create('posttask_responses', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('posttask_submission_id')->constrained('posttask_submissions')->cascadeOnDelete();
            $table->foreignUuid('posttask_question_id')->constrained('posttask_questions')->restrictOnDelete();
            $table->foreignUuid('worker_task_session_id')->nullable()->constrained('worker_task_sessions')->cascadeOnDelete();
            $table->foreignUuid('worker_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->boolean('answer_boolean')->nullable();
            $table->decimal('answer_number', 12, 4)->nullable();
            $table->jsonb('answer_json')->nullable();
            $table->boolean('is_critical_failure')->default(false);
            $table->decimal('score_awarded', 8, 2)->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->uuid('supersedes_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['posttask_submission_id', 'posttask_question_id'], 'posttask_responses_question_idx');
            $table->index(['worker_user_id', 'answered_at']);
            $table->index('supersedes_id');
        });

        foreach ([
            'posttask_submissions',
            'posttask_responses',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreign('supersedes_id')->references('id')->on($tableName)->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('posttask_responses');
        Schema::dropIfExists('posttask_submissions');
        Schema::dropIfExists('posttask_questions');
    }
};
