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
            'user_occupations',
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
            'workplace_user_assignments',
            'roles',
            'user_roles',
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
            'user_occupations',
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
        $this->assertForeignKey('account_businesses', 'account_id', 'customer_accounts');
        $this->assertForeignKey('account_businesses', 'business_entity_id', 'business_entities');
        $this->assertForeignKey('business_identifiers', 'identifier_type_id', 'business_identifier_types');
        $this->assertForeignKey('workplaces', 'business_entity_id', 'business_entities');
        $this->assertForeignKey('workplaces', 'environment_id', 'workplace_environments');
        $this->assertForeignKey('user_business_access', 'business_entity_id', 'business_entities');
        $this->assertForeignKey('user_occupations', 'account_id', 'customer_accounts');
        $this->assertForeignKey('user_occupations', 'user_id', 'users');
        $this->assertForeignKey('user_occupations', 'occupation_id', 'occupations');
        $this->assertForeignKey('contractor_relationships', 'host_business_entity_id', 'business_entities');
        $this->assertForeignKey('task_occupation_access', 'task_id', 'tasks');
        $this->assertForeignKey('swms_versions', 'task_id', 'tasks');
        $this->assertForeignKey('swms_activity_steps', 'swms_version_id', 'swms_versions');
        $this->assertForeignKey('prestart_submissions', 'worker_task_session_id', 'worker_task_sessions');
        $this->assertForeignKey('audit_events', 'account_id', 'customer_accounts');
        $this->assertForeignKey('audit_events', 'worker_task_session_id', 'worker_task_sessions');

        $this->assertIndexExists('business_identifiers_active_unique', 'WHERE ((deleted_at IS NULL)');
        $this->assertIndexExists('account_businesses_active_unique', 'WHERE (deleted_at IS NULL)');
        $this->assertIndexExists('account_businesses_one_primary', 'WHERE ((is_primary = true)');
        $this->assertIndexExists('workplaces_business_code_unique', 'WHERE ((deleted_at IS NULL)');
        $this->assertIndexExists('business_industries_one_primary', 'WHERE ((is_primary = true)');
        $this->assertIndexExists('user_occupations_active_unique', 'WHERE (deleted_at IS NULL)');
        $this->assertIndexExists('task_occupation_access_active_unique', 'WHERE (deleted_at IS NULL)');
        $this->assertIndexExists('task_industry_access_active_unique', 'WHERE (deleted_at IS NULL)');
        $this->assertIndexExists('swms_activity_steps_number_unique', 'WHERE (deleted_at IS NULL)');
        $this->assertIndexExists('prestart_questions_number_unique', 'WHERE (deleted_at IS NULL)');
        $this->assertIndexExists('workplace_task_settings_active_unique', 'WHERE (deleted_at IS NULL)');
        $this->assertIndexExists('audit_events_chain_sequence_unique');

        $this->assertForeignKeyDeleteRule('audit_events', 'account_id', 'customer_accounts', 'RESTRICT');
        $this->assertForeignKeyDeleteRule('signatures', 'account_id', 'customer_accounts', 'RESTRICT');
        $this->assertForeignKeyDeleteRule('evidence_files', 'account_id', 'customer_accounts', 'RESTRICT');

        $this->assertCheckConstraintExists('user_business_access', 'uba_permission_role_check');
        $this->assertCheckConstraintExists('user_workplace_access', 'uwa_permission_role_check');
    }

    public function test_users_primary_key_is_uuid_and_legacy_public_uuid_column_is_removed(): void
    {
        $this->assertGeneratedUuidPrimaryKey('users');
        $this->assertColumnsExist('users', [
            'account_id',
            'home_business_entity_id',
            'timezone',
            'locale',
            'metadata',
            'deleted_at',
        ]);
        $this->assertColumnsMissing('users', ['uuid', 'blockchain_id']);
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
            'worker_task_session_id',
        ]);
    }

    public function test_2026_05_23_refactor_columns_match_confirmed_decisions(): void
    {
        $this->assertColumnsExist('business_entities', ['blockchain_id']);
        $this->assertColumnIsNotNullable('business_entities', 'blockchain_id');

        $this->assertColumnsExist('workplace_environments', [
            'environment_code',
            'environment_name',
            'active',
            'metadata',
            'deleted_at',
        ]);
        $this->assertColumnsMissing('workplace_environments', ['workplace_id', 'name', 'is_active']);
        $this->assertColumnsExist('workplaces', ['environment_id']);

        $this->assertColumnsExist('user_occupations', [
            'account_id',
            'user_id',
            'occupation_id',
            'is_primary',
            'starts_on',
            'ends_on',
            'granted_by_user_id',
            'metadata',
            'deleted_at',
        ]);

        $this->assertColumnsExist('business_industries', ['is_primary']);

        $this->assertColumnsExist('swms_activity_steps', [
            'initial_risk_level',
            'residual_risk_level',
            'residual_risk_reason',
            'stop_work_trigger',
            'evidence_required',
            'evidence_prompt',
            'quick_view_summary',
            'primary_task_performer',
            'supervisory_verification',
        ]);

        $this->assertColumnsMissing('tasks', ['trade_industry']);
    }

    public function test_refactor_constraints_and_append_only_triggers_exist(): void
    {
        $this->assertCheckConstraintExists('audit_events', 'audit_events_subject_link_check');
        $this->assertCheckConstraintExists('import_validation_results', 'import_validation_results_rule_code_prefix_check');

        $this->assertTriggerExists('audit_events', 'audit_events_no_update');
        $this->assertTriggerExists('signatures', 'signatures_no_update');
        $this->assertTriggerExists('evidence_files', 'evidence_files_no_update');
        $this->assertTriggerExists('audit_events', 'audit_events_account_consistency');
        $this->assertTriggerExists('signatures', 'signatures_account_consistency');
        $this->assertTriggerExists('evidence_files', 'evidence_files_account_consistency');
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

    private function assertForeignKeyDeleteRule(string $table, string $column, string $referencesTable, string $deleteRule): void
    {
        $constraint = DB::selectOne(
            'SELECT rc.delete_rule
             FROM information_schema.table_constraints tc
             JOIN information_schema.key_column_usage kcu
               ON tc.constraint_name = kcu.constraint_name
              AND tc.table_schema = kcu.table_schema
             JOIN information_schema.constraint_column_usage ccu
               ON ccu.constraint_name = tc.constraint_name
              AND ccu.constraint_schema = tc.table_schema
             JOIN information_schema.referential_constraints rc
               ON rc.constraint_name = tc.constraint_name
              AND rc.constraint_schema = tc.table_schema
             WHERE tc.constraint_type = ?
               AND tc.table_schema = current_schema()
               AND tc.table_name = ?
               AND kcu.column_name = ?
               AND ccu.table_name = ?',
            ['FOREIGN KEY', $table, $column, $referencesTable],
        );

        $this->assertNotNull($constraint, "Expected FK [{$table}.{$column}] to reference [{$referencesTable}].");
        $this->assertSame($deleteRule, $constraint->delete_rule);
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

    private function assertCheckConstraintExists(string $table, string $constraintName): void
    {
        $constraint = DB::selectOne(
            'SELECT 1
             FROM information_schema.table_constraints
             WHERE table_schema = current_schema()
               AND table_name = ?
               AND constraint_name = ?
               AND constraint_type = ?',
            [$table, $constraintName, 'CHECK'],
        );

        $this->assertNotNull($constraint, "Expected check constraint [{$constraintName}] on [{$table}].");
    }

    private function assertTriggerExists(string $table, string $triggerName): void
    {
        $trigger = DB::selectOne(
            'SELECT 1
             FROM pg_trigger
             WHERE tgrelid = ?::regclass
               AND tgname = ?
               AND NOT tgisinternal',
            [$table, $triggerName],
        );

        $this->assertNotNull($trigger, "Expected trigger [{$triggerName}] on [{$table}].");
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

    private function assertColumnIsNotNullable(string $table, string $column): void
    {
        $result = DB::selectOne(
            'SELECT is_nullable
             FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND table_name = ?
               AND column_name = ?',
            [$table, $column],
        );

        $this->assertNotNull($result, "Expected column [{$table}.{$column}] to exist.");
        $this->assertSame('NO', $result->is_nullable, "Expected column [{$table}.{$column}] to be NOT NULL.");
    }
}
