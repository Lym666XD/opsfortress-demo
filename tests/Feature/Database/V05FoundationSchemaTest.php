<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PostgreSQL schema contract checks for the first v0.5 foundation migration.
 *
 * These tables reserve the DB shape for jurisdiction-driven PDF output,
 * workplace parties, assets, generated document records, and version-aware
 * pre/post question templates. They do not implement PDF generation or
 * offline sync behavior.
 */
final class V05FoundationSchemaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('v0.5 schema contract tests require a PostgreSQL connection.');
        }
    }

    public function test_v05_foundation_tables_exist(): void
    {
        $this->assertTablesExist([
            'jurisdiction_regulatory_profiles',
            'workplace_external_parties',
            'asset_types',
            'assets',
            'workplace_asset_assignments',
            'worker_task_session_assets',
            'generated_documents',
            'document_delivery_events',
        ]);
    }

    public function test_v05_tables_use_database_generated_uuid_primary_keys(): void
    {
        foreach ([
            'jurisdiction_regulatory_profiles',
            'workplace_external_parties',
            'asset_types',
            'assets',
            'workplace_asset_assignments',
            'worker_task_session_assets',
            'generated_documents',
            'document_delivery_events',
        ] as $table) {
            $this->assertGeneratedUuidPrimaryKey($table);
        }
    }

    public function test_version_aware_prestart_and_posttask_question_columns_exist(): void
    {
        $this->assertColumnsExist('prestart_questions', ['swms_version_id']);
        $this->assertColumnsExist('posttask_questions', ['swms_version_id']);

        $this->assertForeignKey('prestart_questions', 'swms_version_id', 'swms_versions');
        $this->assertForeignKey('posttask_questions', 'swms_version_id', 'swms_versions');

        $this->assertIndexExists('prestart_questions_number_unique', 'swms_version_id IS NULL');
        $this->assertIndexExists('prestart_questions_version_number_unique', 'swms_version_id IS NOT NULL');
        $this->assertIndexExists('posttask_questions_number_unique', 'swms_version_id IS NULL');
        $this->assertIndexExists('posttask_questions_version_number_unique', 'swms_version_id IS NOT NULL');
    }

    public function test_jurisdiction_profiles_support_location_workplace_type_task_and_regulatory_block_data(): void
    {
        $this->assertColumnsExist('jurisdiction_regulatory_profiles', [
            'country_id',
            'profile_code',
            'country_code',
            'country_name',
            'state_territory_province',
            'workplace_type',
            'task_category',
            'document_type_name',
            'document_type_acronym',
            'safety_terminology_name',
            'safety_terminology_acronym',
            'regulator_name',
            'regulator_contact_name',
            'regulator_phone',
            'regulator_email',
            'regulator_website',
            'emergency_reporting_contact',
            'incident_reporting_instructions',
            'legislation_references',
            'codes_of_practice',
            'source_reference',
            'last_reviewed_at',
            'active_status',
            'metadata',
            'deleted_at',
        ]);

        $this->assertForeignKey('jurisdiction_regulatory_profiles', 'country_id', 'countries');
        $this->assertIndexExists('jurisdiction_profiles_code_active_unique');
        $this->assertIndexExists('jrp_country_state_idx');
        $this->assertIndexExists('jrp_workplace_task_idx');
    }

    public function test_workplace_external_parties_support_pc_client_and_pdf_recipient_context(): void
    {
        $this->assertColumnsExist('workplace_external_parties', [
            'account_id',
            'business_entity_id',
            'workplace_id',
            'external_business_entity_id',
            'external_party_type',
            'business_name',
            'business_identifier',
            'contact_given_name',
            'contact_family_name',
            'contact_role',
            'contact_email',
            'contact_phone',
            'reporting_responsibility',
            'pdf_recipient',
            'effective_from',
            'effective_to',
            'active_status',
            'metadata',
            'deleted_at',
        ]);

        $this->assertForeignKey('workplace_external_parties', 'account_id', 'customer_accounts');
        $this->assertForeignKey('workplace_external_parties', 'business_entity_id', 'business_entities');
        $this->assertForeignKey('workplace_external_parties', 'workplace_id', 'workplaces');
        $this->assertForeignKey('workplace_external_parties', 'external_business_entity_id', 'business_entities');
        $this->assertIndexExists('wep_workplace_type_idx');
    }

    public function test_asset_foundation_tables_support_type_specific_asset_and_workplace_assignment(): void
    {
        $this->assertColumnsExist('asset_types', [
            'asset_type_code',
            'name',
            'asset_category',
            'inspection_required',
            'prestart_required',
            'posttask_required',
            'maintenance_trigger_required',
            'evidence_required',
            'active_status',
            'metadata',
            'deleted_at',
        ]);

        $this->assertColumnsExist('assets', [
            'account_id',
            'business_entity_id',
            'asset_type_id',
            'asset_code',
            'asset_name',
            'asset_category',
            'make_model',
            'serial_number',
            'registration_number',
            'asset_status',
            'inspection_required',
            'prestart_required',
            'posttask_required',
            'maintenance_trigger_required',
            'evidence_required',
            'metadata',
            'deleted_at',
        ]);

        $this->assertColumnsExist('workplace_asset_assignments', [
            'account_id',
            'workplace_id',
            'asset_id',
            'assigned_from',
            'assigned_to',
            'is_primary',
            'active_status',
            'metadata',
            'deleted_at',
        ]);

        $this->assertColumnsExist('worker_task_session_assets', [
            'worker_task_session_id',
            'asset_id',
            'selected_by_user_id',
            'selection_reason',
            'selected_at',
            'metadata',
        ]);

        $this->assertForeignKey('assets', 'account_id', 'customer_accounts');
        $this->assertForeignKey('assets', 'business_entity_id', 'business_entities');
        $this->assertForeignKey('assets', 'asset_type_id', 'asset_types');
        $this->assertForeignKey('workplace_asset_assignments', 'workplace_id', 'workplaces');
        $this->assertForeignKey('workplace_asset_assignments', 'asset_id', 'assets');
        $this->assertForeignKey('worker_task_session_assets', 'worker_task_session_id', 'worker_task_sessions');
        $this->assertForeignKey('worker_task_session_assets', 'asset_id', 'assets');

        $this->assertIndexExists('asset_types_code_active_unique');
        $this->assertIndexExists('assets_account_code_active_unique');
        $this->assertIndexExists('workplace_asset_assignments_active_unique');
        $this->assertIndexExists('worker_task_session_assets_unique');
    }

    public function test_generated_documents_record_version_jurisdiction_and_delivery_context(): void
    {
        $this->assertColumnsExist('generated_documents', [
            'account_id',
            'business_entity_id',
            'workplace_id',
            'task_id',
            'swms_version_id',
            'jurisdiction_regulatory_profile_id',
            'document_type',
            'document_title',
            'version_label',
            'generation_trigger',
            'bundle_key',
            'status',
            'disk',
            'path',
            'file_hash',
            'file_hash_algorithm',
            'generated_by_user_id',
            'generated_at',
            'metadata',
            'deleted_at',
        ]);

        $this->assertColumnsExist('document_delivery_events', [
            'generated_document_id',
            'workplace_external_party_id',
            'recipient_user_id',
            'recipient_name',
            'recipient_email',
            'recipient_type',
            'delivery_channel',
            'status',
            'sent_at',
            'delivered_at',
            'failed_at',
            'failure_message',
            'metadata',
        ]);

        $this->assertForeignKey('generated_documents', 'account_id', 'customer_accounts');
        $this->assertForeignKey('generated_documents', 'business_entity_id', 'business_entities');
        $this->assertForeignKey('generated_documents', 'workplace_id', 'workplaces');
        $this->assertForeignKey('generated_documents', 'task_id', 'tasks');
        $this->assertForeignKey('generated_documents', 'swms_version_id', 'swms_versions');
        $this->assertForeignKey('generated_documents', 'jurisdiction_regulatory_profile_id', 'jurisdiction_regulatory_profiles');
        $this->assertForeignKey('document_delivery_events', 'generated_document_id', 'generated_documents');
        $this->assertForeignKey('document_delivery_events', 'workplace_external_party_id', 'workplace_external_parties');

        $this->assertIndexExists('generated_documents_account_status_idx');
        $this->assertIndexExists('generated_documents_task_version_idx');
        $this->assertIndexExists('document_delivery_document_status_idx');
    }

    /**
     * @param  list<string>  $tables
     */
    private function assertTablesExist(array $tables): void
    {
        foreach ($tables as $table) {
            $this->assertNotNull(
                DB::selectOne(
                    'SELECT 1 FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = ?',
                    [$table],
                ),
                "Expected table [{$table}] to exist.",
            );
        }
    }

    private function assertGeneratedUuidPrimaryKey(string $table): void
    {
        $column = DB::selectOne(
            'SELECT data_type, column_default FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?',
            [$table, 'id'],
        );

        $this->assertNotNull($column, "Expected [{$table}.id] to exist.");
        $this->assertSame('uuid', $column->data_type, "Expected [{$table}.id] to be uuid.");
        $this->assertStringContainsString('gen_random_uuid()', $column->column_default ?? '', "Expected [{$table}.id] to default to gen_random_uuid().");

        $this->assertNotNull(
            DB::selectOne(
                'SELECT 1
                 FROM information_schema.table_constraints tc
                 JOIN information_schema.key_column_usage kcu
                   ON tc.constraint_name = kcu.constraint_name
                  AND tc.table_schema = kcu.table_schema
                 WHERE tc.constraint_type = ?
                   AND tc.table_schema = current_schema()
                   AND tc.table_name = ?
                   AND kcu.column_name = ?',
                ['PRIMARY KEY', $table, 'id'],
            ),
            "Expected [{$table}.id] to be the primary key.",
        );
    }

    private function assertForeignKey(string $table, string $column, string $referencesTable): void
    {
        $constraint = DB::selectOne(
            'SELECT 1
             FROM information_schema.table_constraints tc
             JOIN information_schema.key_column_usage kcu
               ON tc.constraint_name = kcu.constraint_name
              AND tc.table_schema = kcu.table_schema
             JOIN information_schema.constraint_column_usage ccu
               ON ccu.constraint_name = tc.constraint_name
              AND ccu.constraint_schema = tc.table_schema
             WHERE tc.constraint_type = ?
               AND tc.table_schema = current_schema()
               AND tc.table_name = ?
               AND kcu.column_name = ?
               AND ccu.table_name = ?',
            ['FOREIGN KEY', $table, $column, $referencesTable],
        );

        $this->assertNotNull($constraint, "Expected [{$table}.{$column}] to reference [{$referencesTable}].");
    }

    private function assertIndexExists(string $indexName, ?string $indexSqlFragment = null): void
    {
        $index = DB::selectOne(
            'SELECT indexdef FROM pg_indexes WHERE schemaname = current_schema() AND indexname = ?',
            [$indexName],
        );

        $this->assertNotNull($index, "Expected index [{$indexName}] to exist.");

        if ($indexSqlFragment !== null) {
            $this->assertStringContainsString($indexSqlFragment, $index->indexdef);
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function assertColumnsExist(string $table, array $columns): void
    {
        foreach ($columns as $column) {
            $this->assertNotNull(
                DB::selectOne(
                    'SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?',
                    [$table, $column],
                ),
                "Expected column [{$table}.{$column}] to exist.",
            );
        }
    }
}
