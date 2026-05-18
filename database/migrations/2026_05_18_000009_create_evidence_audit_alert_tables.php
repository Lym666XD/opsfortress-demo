<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('customer_account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->nullable()->constrained('business_entities')->nullOnDelete();
            $table->foreignUuid('workplace_id')->nullable()->constrained('workplaces')->nullOnDelete();
            $table->foreignUuid('worker_task_session_id')->nullable()->constrained('worker_task_sessions')->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signature_type', 64);
            $table->string('signer_name');
            $table->string('signer_email')->nullable();
            $table->string('signed_payload_hash', 128)->nullable();
            $table->jsonb('signature_data')->nullable();
            $table->timestamp('signed_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->uuid('supersedes_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['customer_account_id', 'signature_type']);
            $table->index(['worker_task_session_id', 'signed_at'], 'signatures_session_signed_idx');
            $table->index('supersedes_id');
        });

        Schema::create('evidence_files', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('customer_account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->nullable()->constrained('business_entities')->nullOnDelete();
            $table->foreignUuid('workplace_id')->nullable()->constrained('workplaces')->nullOnDelete();
            $table->foreignUuid('worker_task_session_id')->nullable()->constrained('worker_task_sessions')->nullOnDelete();
            $table->foreignUuid('prestart_submission_id')->nullable()->constrained('prestart_submissions')->nullOnDelete();
            $table->foreignUuid('signature_id')->nullable()->constrained('signatures')->nullOnDelete();
            $table->foreignUuid('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evidence_type', 64);
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('file_hash', 128)->nullable();
            $table->string('file_hash_algorithm', 32)->default('sha256');
            $table->timestamp('captured_at')->nullable();
            $table->uuid('supersedes_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['customer_account_id', 'evidence_type']);
            $table->index(['worker_task_session_id', 'captured_at'], 'evidence_files_session_captured_idx');
            $table->index('file_hash');
            $table->index('supersedes_id');
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('customer_account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->nullable()->constrained('business_entities')->nullOnDelete();
            $table->foreignUuid('workplace_id')->nullable()->constrained('workplaces')->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type');
            $table->string('subject_id', 64);
            $table->string('event_type', 128);
            $table->string('anchor', 64)->nullable();
            $table->string('previous_hash', 128)->nullable();
            $table->string('event_hash', 128);
            $table->string('hash_algorithm', 32)->default('sha256');
            $table->unsignedBigInteger('hash_sequence');
            $table->jsonb('event_payload');
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index(['customer_account_id', 'subject_type', 'subject_id'], 'audit_events_subject_idx');
            $table->index(['customer_account_id', 'anchor'], 'audit_events_anchor_idx');
            $table->index('event_hash');
        });

        DB::statement(
            'CREATE UNIQUE INDEX audit_events_chain_sequence_unique '.
            'ON audit_events (customer_account_id, subject_type, subject_id, hash_sequence)',
        );

        Schema::create('alerts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('customer_account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->nullable()->constrained('business_entities')->nullOnDelete();
            $table->foreignUuid('workplace_id')->nullable()->constrained('workplaces')->nullOnDelete();
            $table->foreignUuid('worker_task_session_id')->nullable()->constrained('worker_task_sessions')->nullOnDelete();
            $table->foreignUuid('prestart_submission_id')->nullable()->constrained('prestart_submissions')->nullOnDelete();
            $table->foreignUuid('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('alert_type', 64);
            $table->string('severity', 16)->default('warning');
            $table->string('status', 32)->default('open');
            $table->unsignedSmallInteger('escalation_level')->default(0);
            $table->string('title');
            $table->text('message')->nullable();
            $table->jsonb('trigger_payload')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->uuid('supersedes_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_account_id', 'status']);
            $table->index(['workplace_id', 'severity']);
            $table->index(['assigned_to_user_id', 'status']);
            $table->index('supersedes_id');
        });

        foreach ([
            'signatures',
            'evidence_files',
            'alerts',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreign('supersedes_id')->references('id')->on($tableName)->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('evidence_files');
        Schema::dropIfExists('signatures');
    }
};
