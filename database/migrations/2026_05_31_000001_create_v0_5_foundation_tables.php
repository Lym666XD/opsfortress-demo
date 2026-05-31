<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestart_questions', function (Blueprint $table) {
            $table->foreignUuid('swms_version_id')
                ->nullable()
                ->after('task_id')
                ->constrained('swms_versions')
                ->nullOnDelete();

            $table->index(['task_id', 'swms_version_id'], 'prestart_questions_task_version_idx');
        });

        DB::statement('DROP INDEX IF EXISTS prestart_questions_number_unique');
        DB::statement(
            'CREATE UNIQUE INDEX prestart_questions_number_unique '.
            'ON prestart_questions (task_id, question_number) '.
            'WHERE deleted_at IS NULL AND swms_version_id IS NULL',
        );
        DB::statement(
            'CREATE UNIQUE INDEX prestart_questions_version_number_unique '.
            'ON prestart_questions (task_id, swms_version_id, question_number) '.
            'WHERE deleted_at IS NULL AND swms_version_id IS NOT NULL',
        );

        Schema::table('posttask_questions', function (Blueprint $table) {
            $table->foreignUuid('swms_version_id')
                ->nullable()
                ->after('task_id')
                ->constrained('swms_versions')
                ->nullOnDelete();

            $table->index(['task_id', 'swms_version_id'], 'posttask_questions_task_version_idx');
        });

        DB::statement('DROP INDEX IF EXISTS posttask_questions_number_unique');
        DB::statement(
            'CREATE UNIQUE INDEX posttask_questions_number_unique '.
            'ON posttask_questions (task_id, question_number) '.
            'WHERE deleted_at IS NULL AND swms_version_id IS NULL',
        );
        DB::statement(
            'CREATE UNIQUE INDEX posttask_questions_version_number_unique '.
            'ON posttask_questions (task_id, swms_version_id, question_number) '.
            'WHERE deleted_at IS NULL AND swms_version_id IS NOT NULL',
        );

        Schema::create('jurisdiction_regulatory_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('profile_code')->nullable();
            $table->string('country_code', 16)->nullable();
            $table->string('country_name')->nullable();
            $table->string('state_territory_province')->nullable();
            $table->string('workplace_type', 64)->nullable();
            $table->string('task_category')->nullable();
            $table->string('document_type_name')->nullable();
            $table->string('document_type_acronym', 32)->nullable();
            $table->string('safety_terminology_name')->nullable();
            $table->string('safety_terminology_acronym', 32)->nullable();
            $table->string('regulator_name')->nullable();
            $table->string('regulator_contact_name')->nullable();
            $table->string('regulator_phone', 64)->nullable();
            $table->string('regulator_email')->nullable();
            $table->string('regulator_website')->nullable();
            $table->string('emergency_reporting_contact')->nullable();
            $table->text('incident_reporting_instructions')->nullable();
            $table->jsonb('legislation_references')->nullable();
            $table->jsonb('codes_of_practice')->nullable();
            $table->text('source_reference')->nullable();
            $table->date('last_reviewed_at')->nullable();
            $table->boolean('active_status')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['country_code', 'state_territory_province'], 'jrp_country_state_idx');
            $table->index(['workplace_type', 'task_category'], 'jrp_workplace_task_idx');
            $table->index('active_status');
        });

        DB::statement(
            'CREATE UNIQUE INDEX jurisdiction_profiles_code_active_unique '.
            'ON jurisdiction_regulatory_profiles (profile_code) '.
            'WHERE deleted_at IS NULL AND profile_code IS NOT NULL',
        );

        Schema::create('workplace_external_parties', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->foreignUuid('workplace_id')->constrained('workplaces')->cascadeOnDelete();
            $table->foreignUuid('external_business_entity_id')->nullable()->constrained('business_entities')->nullOnDelete();
            $table->string('external_party_type', 64);
            $table->string('business_name')->nullable();
            $table->string('business_identifier')->nullable();
            $table->string('contact_given_name')->nullable();
            $table->string('contact_family_name')->nullable();
            $table->string('contact_role')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 64)->nullable();
            $table->text('reporting_responsibility')->nullable();
            $table->boolean('pdf_recipient')->default(false);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('active_status')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'active_status'], 'wep_account_active_idx');
            $table->index(['workplace_id', 'external_party_type'], 'wep_workplace_type_idx');
            $table->index('pdf_recipient');
        });

        Schema::create('asset_types', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('asset_type_code')->nullable();
            $table->string('name');
            $table->string('asset_category')->nullable();
            $table->boolean('inspection_required')->default(false);
            $table->boolean('prestart_required')->default(false);
            $table->boolean('posttask_required')->default(false);
            $table->boolean('maintenance_trigger_required')->default(false);
            $table->boolean('evidence_required')->default(false);
            $table->boolean('active_status')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('asset_category');
            $table->index('active_status');
        });

        DB::statement(
            'CREATE UNIQUE INDEX asset_types_code_active_unique '.
            'ON asset_types (asset_type_code) '.
            'WHERE deleted_at IS NULL AND asset_type_code IS NOT NULL',
        );

        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('business_entity_id')->constrained('business_entities')->cascadeOnDelete();
            $table->foreignUuid('asset_type_id')->nullable()->constrained('asset_types')->nullOnDelete();
            $table->string('asset_code')->nullable();
            $table->string('asset_name');
            $table->string('asset_category')->nullable();
            $table->string('make_model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('asset_status', 32)->default('active');
            $table->boolean('inspection_required')->default(false);
            $table->boolean('prestart_required')->default(false);
            $table->boolean('posttask_required')->default(false);
            $table->boolean('maintenance_trigger_required')->default(false);
            $table->boolean('evidence_required')->default(false);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'asset_status'], 'assets_account_status_idx');
            $table->index('asset_type_id');
            $table->index('asset_category');
        });

        DB::statement(
            'CREATE UNIQUE INDEX assets_account_code_active_unique '.
            'ON assets (account_id, asset_code) '.
            'WHERE deleted_at IS NULL AND asset_code IS NOT NULL',
        );

        Schema::create('workplace_asset_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignUuid('workplace_id')->constrained('workplaces')->cascadeOnDelete();
            $table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->date('assigned_from')->nullable();
            $table->date('assigned_to')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('active_status')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'active_status'], 'waa_account_active_idx');
            $table->index(['workplace_id', 'active_status'], 'waa_workplace_active_idx');
        });

        DB::statement(
            'CREATE UNIQUE INDEX workplace_asset_assignments_active_unique '.
            'ON workplace_asset_assignments (workplace_id, asset_id) '.
            'WHERE deleted_at IS NULL',
        );

        Schema::create('worker_task_session_assets', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('worker_task_session_id')->constrained('worker_task_sessions')->cascadeOnDelete();
            $table->foreignUuid('asset_id')->constrained('assets')->restrictOnDelete();
            $table->foreignUuid('selected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('selection_reason', 64)->nullable();
            $table->timestamp('selected_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('asset_id');
            $table->index('selected_by_user_id');
        });

        DB::statement(
            'CREATE UNIQUE INDEX worker_task_session_assets_unique '.
            'ON worker_task_session_assets (worker_task_session_id, asset_id)',
        );

        Schema::create('generated_documents', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('account_id')->constrained('customer_accounts')->restrictOnDelete();
            $table->foreignUuid('business_entity_id')->nullable()->constrained('business_entities')->nullOnDelete();
            $table->foreignUuid('workplace_id')->nullable()->constrained('workplaces')->nullOnDelete();
            $table->foreignUuid('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignUuid('swms_version_id')->nullable()->constrained('swms_versions')->nullOnDelete();
            $table->foreignUuid('jurisdiction_regulatory_profile_id')->nullable()->constrained('jurisdiction_regulatory_profiles')->nullOnDelete();
            $table->string('document_type', 64);
            $table->string('document_title')->nullable();
            $table->string('version_label', 64)->nullable();
            $table->string('generation_trigger', 64)->nullable();
            $table->string('bundle_key')->nullable();
            $table->string('status', 32)->default('draft');
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->string('file_hash', 128)->nullable();
            $table->string('file_hash_algorithm', 32)->default('sha256');
            $table->foreignUuid('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'status'], 'generated_documents_account_status_idx');
            $table->index(['workplace_id', 'document_type'], 'generated_documents_workplace_type_idx');
            $table->index(['task_id', 'swms_version_id'], 'generated_documents_task_version_idx');
            $table->index('bundle_key');
            $table->index('file_hash');
        });

        Schema::create('document_delivery_events', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('generated_document_id')->constrained('generated_documents')->cascadeOnDelete();
            $table->foreignUuid('workplace_external_party_id')->nullable()->constrained('workplace_external_parties')->nullOnDelete();
            $table->foreignUuid('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_type', 64)->nullable();
            $table->string('delivery_channel', 32)->default('email');
            $table->string('status', 32)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['generated_document_id', 'status'], 'document_delivery_document_status_idx');
            $table->index(['recipient_email', 'status'], 'document_delivery_email_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_delivery_events');
        Schema::dropIfExists('generated_documents');
        Schema::dropIfExists('worker_task_session_assets');
        Schema::dropIfExists('workplace_asset_assignments');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_types');
        Schema::dropIfExists('workplace_external_parties');
        Schema::dropIfExists('jurisdiction_regulatory_profiles');

        DB::statement('DROP INDEX IF EXISTS posttask_questions_version_number_unique');
        DB::statement('DROP INDEX IF EXISTS posttask_questions_number_unique');
        DB::statement(
            'CREATE UNIQUE INDEX posttask_questions_number_unique '.
            'ON posttask_questions (task_id, question_number) '.
            'WHERE deleted_at IS NULL',
        );

        Schema::table('posttask_questions', function (Blueprint $table) {
            $table->dropIndex('posttask_questions_task_version_idx');
            $table->dropConstrainedForeignId('swms_version_id');
        });

        DB::statement('DROP INDEX IF EXISTS prestart_questions_version_number_unique');
        DB::statement('DROP INDEX IF EXISTS prestart_questions_number_unique');
        DB::statement(
            'CREATE UNIQUE INDEX prestart_questions_number_unique '.
            'ON prestart_questions (task_id, question_number) '.
            'WHERE deleted_at IS NULL',
        );

        Schema::table('prestart_questions', function (Blueprint $table) {
            $table->dropIndex('prestart_questions_task_version_idx');
            $table->dropConstrainedForeignId('swms_version_id');
        });
    }
};
