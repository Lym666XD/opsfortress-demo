<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceAccountForeignKey('audit_events', 'restrict');
        $this->replaceAccountForeignKey('signatures', 'restrict');
        $this->replaceAccountForeignKey('evidence_files', 'restrict');

        Schema::table('audit_events', function (Blueprint $table) {
            $table->foreignUuid('worker_task_session_id')
                ->nullable()
                ->after('workplace_id')
                ->constrained('worker_task_sessions')
                ->restrictOnDelete();

            $table->index('worker_task_session_id', 'audit_events_worker_task_session_id_idx');
        });

        DB::statement('ALTER TABLE audit_events ALTER COLUMN subject_type DROP NOT NULL');
        DB::statement('ALTER TABLE audit_events ALTER COLUMN subject_id DROP NOT NULL');
        DB::statement(
            'ALTER TABLE audit_events ADD CONSTRAINT audit_events_subject_link_check '.
            'CHECK (worker_task_session_id IS NOT NULL OR (subject_type IS NOT NULL AND subject_id IS NOT NULL))',
        );

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION audit_events_block_update_delete()
RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
  RAISE EXCEPTION '% is append-only', TG_TABLE_NAME;
END;
$$;
SQL);

        foreach (['audit_events', 'signatures', 'evidence_files'] as $tableName) {
            DB::statement("DROP TRIGGER IF EXISTS {$tableName}_no_update ON {$tableName}");
            DB::statement(
                "CREATE TRIGGER {$tableName}_no_update ".
                "BEFORE UPDATE OR DELETE ON {$tableName} ".
                'FOR EACH ROW EXECUTE FUNCTION audit_events_block_update_delete()',
            );
        }
    }

    public function down(): void
    {
        foreach (['audit_events', 'signatures', 'evidence_files'] as $tableName) {
            DB::statement("DROP TRIGGER IF EXISTS {$tableName}_no_update ON {$tableName}");
        }

        DB::statement('DROP FUNCTION IF EXISTS audit_events_block_update_delete()');
        DB::statement('ALTER TABLE audit_events DROP CONSTRAINT IF EXISTS audit_events_subject_link_check');

        DB::statement(
            "UPDATE audit_events SET subject_type = COALESCE(subject_type, 'worker_task_session'), ".
            'subject_id = COALESCE(subject_id, worker_task_session_id::text) '.
            'WHERE worker_task_session_id IS NOT NULL AND (subject_type IS NULL OR subject_id IS NULL)',
        );
        DB::statement('ALTER TABLE audit_events ALTER COLUMN subject_type SET NOT NULL');
        DB::statement('ALTER TABLE audit_events ALTER COLUMN subject_id SET NOT NULL');

        Schema::table('audit_events', function (Blueprint $table) {
            $table->dropIndex('audit_events_worker_task_session_id_idx');
            $table->dropConstrainedForeignId('worker_task_session_id');
        });

        $this->replaceAccountForeignKey('audit_events', 'cascade');
        $this->replaceAccountForeignKey('signatures', 'cascade');
        $this->replaceAccountForeignKey('evidence_files', 'cascade');
    }

    private function replaceAccountForeignKey(string $tableName, string $deleteAction): void
    {
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropForeign(['account_id']);
        });

        Schema::table($tableName, function (Blueprint $table) use ($deleteAction) {
            $foreign = $table->foreign('account_id')->references('id')->on('customer_accounts');

            if ($deleteAction === 'restrict') {
                $foreign->restrictOnDelete();

                return;
            }

            $foreign->cascadeOnDelete();
        });
    }
};
