<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_task_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('customer_account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->foreignUuid('workplace_id')->constrained('workplaces')->cascadeOnDelete();
            $table->foreignUuid('task_id')->constrained('tasks')->restrictOnDelete();
            $table->foreignUuid('swms_version_id')->nullable()->constrained('swms_versions')->restrictOnDelete();
            $table->foreignUuid('worker_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('not_started');
            $table->unsignedInteger('minimum_read_seconds_required')->nullable();
            $table->unsignedInteger('total_read_seconds')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->uuid('supersedes_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['customer_account_id', 'status']);
            $table->index(['worker_user_id', 'status']);
            $table->index(['workplace_id', 'task_id']);
            $table->index('supersedes_id');
        });

        Schema::create('swms_step_events', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('worker_task_session_id')->constrained('worker_task_sessions')->cascadeOnDelete();
            $table->foreignUuid('swms_activity_step_id')->constrained('swms_activity_steps')->restrictOnDelete();
            $table->foreignUuid('worker_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('event_type', 32)->default('viewed');
            $table->unsignedSmallInteger('step_number');
            $table->timestamp('read_started_at')->nullable();
            $table->timestamp('read_completed_at')->nullable();
            $table->unsignedInteger('read_seconds')->nullable();
            $table->boolean('met_minimum_read_time')->default(false);
            $table->jsonb('device_metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->uuid('supersedes_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['worker_task_session_id', 'step_number'], 'swms_step_events_session_step_idx');
            $table->index(['worker_user_id', 'occurred_at']);
            $table->index('supersedes_id');
        });

        Schema::create('prestart_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('worker_task_session_id')->constrained('worker_task_sessions')->cascadeOnDelete();
            $table->foreignUuid('customer_account_id')->constrained('customer_accounts')->cascadeOnDelete();
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

            $table->index(['customer_account_id', 'status']);
            $table->index(['worker_user_id', 'submitted_at']);
            $table->index(['workplace_id', 'task_id']);
            $table->index('supersedes_id');
        });

        Schema::create('prestart_responses', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('prestart_submission_id')->constrained('prestart_submissions')->cascadeOnDelete();
            $table->foreignUuid('prestart_question_id')->constrained('prestart_questions')->restrictOnDelete();
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

            $table->index(['prestart_submission_id', 'prestart_question_id'], 'prestart_responses_question_idx');
            $table->index(['worker_user_id', 'answered_at']);
            $table->index('supersedes_id');
        });

        foreach ([
            'worker_task_sessions',
            'swms_step_events',
            'prestart_submissions',
            'prestart_responses',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreign('supersedes_id')->references('id')->on($tableName)->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prestart_responses');
        Schema::dropIfExists('prestart_submissions');
        Schema::dropIfExists('swms_step_events');
        Schema::dropIfExists('worker_task_sessions');
    }
};
