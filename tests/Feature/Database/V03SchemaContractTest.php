<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PostgreSQL schema contract checks for the v0.3 migration reset.
 *
 * This intentionally does not use RefreshDatabase. It validates the currently
 * migrated PostgreSQL database after `php artisan migrate` or `migrate:fresh`.
 */
final class V03SchemaContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('v0.3 schema contract tests require a PostgreSQL connection.');
        }
    }

    public function test_v03_tables_exist_and_obsolete_demo_tables_are_removed(): void
    {
        $this->assertTablesExist([
            'customer_accounts',
            'countries',
            'business_identifier_types',
            'business_entities',
            'account_businesses',
            'business_identifiers',
            'workplaces',
            'workplace_environments',
            'business_industries',
            'user_business_access',
            'user_workplace_access',
            'contractor_relationships',
            'tasks',
            'occupations',
            'industries',
            'task_occupation_access',
            'task_industry_access',
            'swms_versions',
            'swms_activity_steps',
            'prestart_questions',
            'workplace_task_settings',
            'import_batches',
            'import_source_files',
            'import_validation_results',
            'worker_task_sessions',
            'swms_step_events',
            'prestart_submissions',
            'prestart_responses',
            'signatures',
            'evidence_files',
            'audit_events',
            'alerts',
            'posttask_questions',
            'posttask_submissions',
            'posttask_responses',
            'training_questions',
            'training_attempts',
            'training_responses',
            'worker_training_completions',
        ]);

        $this->assertTablesMissing([
            'tenants',
            'businesses',
            'task_packs',
            'activities',
            'submissions',
            'file_uploads',
            'generated_documents',
        ]);
    }

    public function test_v03_domain_tables_use_database_generated_uuid_primary_keys(): void
    {
        foreach ([
            'users',
            'customer_accounts',
            'countries',
            'business_identifier_types',
            'business_entities',
            'account_businesses',
            'business_identifiers',
            'workplaces',
            'workplace_environments',
            'contractor_relationships',
            'tasks',
            'swms_versions',
            'worker_task_sessions',
            'audit_events',
            'worker_training_completions',
        ] as $table) {
            $this->assertGeneratedUuidPrimaryKey($table);
        }
    }

    public function test_key_foreign_keys_and_indexes_match_the_v03_contract(): void
    {
        $this->assertForeignKey('user_business_access', 'user_id', 'users');
        $this->assertForeignKey('user_workplace_access', 'user_id', 'users');
        $this->assertForeignKey('business_identifier_types', 'country_id', 'countries');
        $this->assertForeignKey('account_businesses', 'customer_account_id', 'customer_accounts');
        $this->assertForeignKey('account_businesses', 'business_entity_id', 'business_entities');
        $this->assertForeignKey('business_identifiers', 'identifier_type_id', 'business_identifier_types');
        $this->assertForeignKey('workplaces', 'business_entity_id', 'business_entities');
        $this->assertForeignKey('user_business_access', 'business_entity_id', 'business_entities');
        $this->assertForeignKey('contractor_relationships', 'host_business_entity_id', 'business_entities');
        $this->assertForeignKey('task_occupation_access', 'task_id', 'tasks');
        $this->assertForeignKey('swms_versions', 'task_id', 'tasks');
        $this->assertForeignKey('swms_activity_steps', 'swms_version_id', 'swms_versions');
        $this->assertForeignKey('prestart_submissions', 'worker_task_session_id', 'worker_task_sessions');
        $this->assertForeignKey('audit_events', 'customer_account_id', 'customer_accounts');

        $this->assertIndexExists('business_identifiers_active_unique', 'WHERE ((deleted_at IS NULL)');
        $this->assertIndexExists('account_businesses_active_unique', 'WHERE (deleted_at IS NULL)');
        $this->assertIndexExists('workplaces_business_code_unique', 'WHERE ((deleted_at IS NULL)');
        $this->assertIndexExists('task_occupation_access_active_unique', 'WHERE (deleted_at IS NULL)');
        $this->assertIndexExists('task_industry_access_active_unique', 'WHERE (deleted_at IS NULL)');
        $this->assertIndexExists('swms_activity_steps_number_unique', 'WHERE (deleted_at IS NULL)');
        $this->assertIndexExists('prestart_questions_number_unique', 'WHERE (deleted_at IS NULL)');
        $this->assertIndexExists('workplace_task_settings_active_unique', 'WHERE (deleted_at IS NULL)');
        $this->assertIndexExists('audit_events_chain_sequence_unique');
    }

    public function test_users_primary_key_is_uuid_and_legacy_public_uuid_column_is_removed(): void
    {
        $this->assertGeneratedUuidPrimaryKey('users');
        $this->assertColumnsExist('users', [
            'customer_account_id',
            'home_business_entity_id',
            'timezone',
            'locale',
            'metadata',
            'deleted_at',
        ]);
        $this->assertColumnsMissing('users', ['uuid']);
    }

    public function test_audit_events_have_hash_chain_columns(): void
    {
        $this->assertColumnsExist('audit_events', [
            'subject_type',
            'subject_id',
            'event_type',
            'anchor',
            'previous_hash',
            'event_hash',
            'hash_algorithm',
            'hash_sequence',
            'event_payload',
            'occurred_at',
        ]);
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

    /**
     * @param  list<string>  $tables
     */
    private function assertTablesMissing(array $tables): void
    {
        foreach ($tables as $table) {
            $this->assertNull(
                DB::selectOne(
                    'SELECT 1 FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = ?',
                    [$table],
                ),
                "Expected obsolete table [{$table}] to be absent.",
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

    /**
     * @param  list<string>  $columns
     */
    private function assertColumnsMissing(string $table, array $columns): void
    {
        foreach ($columns as $column) {
            $this->assertNull(
                DB::selectOne(
                    'SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?',
                    [$table, $column],
                ),
                "Expected column [{$table}.{$column}] to be absent.",
            );
        }
    }
}
