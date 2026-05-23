<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE user_business_access ADD CONSTRAINT uba_permission_role_check '.
            "CHECK (permission_role IN ('worker','supervisor','manager','admin','platform_admin'))",
        );

        DB::statement(
            'ALTER TABLE user_workplace_access ADD CONSTRAINT uwa_permission_role_check '.
            "CHECK (permission_role IN ('worker','supervisor','manager','admin','platform_admin'))",
        );

        DB::statement(
            'CREATE UNIQUE INDEX account_businesses_one_primary '.
            'ON account_businesses (account_id) '.
            'WHERE is_primary = true AND deleted_at IS NULL',
        );

        Schema::table('business_industries', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('industry_id');
        });

        DB::statement(
            'CREATE UNIQUE INDEX business_industries_one_primary '.
            'ON business_industries (business_entity_id) '.
            'WHERE is_primary = true AND deleted_at IS NULL',
        );

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION audit_events_account_consistency_check()
RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE
  linked_account_id uuid;
BEGIN
  IF NEW.worker_task_session_id IS NOT NULL THEN
    SELECT account_id INTO linked_account_id
    FROM worker_task_sessions
    WHERE id = NEW.worker_task_session_id;

    IF linked_account_id IS DISTINCT FROM NEW.account_id THEN
      RAISE EXCEPTION 'audit_events account_id does not match worker_task_sessions.account_id';
    END IF;
  END IF;

  RETURN NEW;
END;
$$;

CREATE OR REPLACE FUNCTION signatures_account_consistency_check()
RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE
  linked_account_id uuid;
BEGIN
  IF NEW.worker_task_session_id IS NOT NULL THEN
    SELECT account_id INTO linked_account_id
    FROM worker_task_sessions
    WHERE id = NEW.worker_task_session_id;

    IF linked_account_id IS DISTINCT FROM NEW.account_id THEN
      RAISE EXCEPTION 'signatures account_id does not match worker_task_sessions.account_id';
    END IF;
  END IF;

  RETURN NEW;
END;
$$;

CREATE OR REPLACE FUNCTION evidence_files_account_consistency_check()
RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE
  linked_account_id uuid;
BEGIN
  IF NEW.worker_task_session_id IS NOT NULL THEN
    SELECT account_id INTO linked_account_id
    FROM worker_task_sessions
    WHERE id = NEW.worker_task_session_id;

    IF linked_account_id IS DISTINCT FROM NEW.account_id THEN
      RAISE EXCEPTION 'evidence_files account_id does not match worker_task_sessions.account_id';
    END IF;
  END IF;

  IF NEW.prestart_submission_id IS NOT NULL THEN
    SELECT account_id INTO linked_account_id
    FROM prestart_submissions
    WHERE id = NEW.prestart_submission_id;

    IF linked_account_id IS DISTINCT FROM NEW.account_id THEN
      RAISE EXCEPTION 'evidence_files account_id does not match prestart_submissions.account_id';
    END IF;
  END IF;

  IF NEW.signature_id IS NOT NULL THEN
    SELECT account_id INTO linked_account_id
    FROM signatures
    WHERE id = NEW.signature_id;

    IF linked_account_id IS DISTINCT FROM NEW.account_id THEN
      RAISE EXCEPTION 'evidence_files account_id does not match signatures.account_id';
    END IF;
  END IF;

  RETURN NEW;
END;
$$;
SQL);

        DB::statement('DROP TRIGGER IF EXISTS audit_events_account_consistency ON audit_events');
        DB::statement(
            'CREATE TRIGGER audit_events_account_consistency '.
            'BEFORE INSERT OR UPDATE ON audit_events '.
            'FOR EACH ROW EXECUTE FUNCTION audit_events_account_consistency_check()',
        );

        DB::statement('DROP TRIGGER IF EXISTS signatures_account_consistency ON signatures');
        DB::statement(
            'CREATE TRIGGER signatures_account_consistency '.
            'BEFORE INSERT OR UPDATE ON signatures '.
            'FOR EACH ROW EXECUTE FUNCTION signatures_account_consistency_check()',
        );

        DB::statement('DROP TRIGGER IF EXISTS evidence_files_account_consistency ON evidence_files');
        DB::statement(
            'CREATE TRIGGER evidence_files_account_consistency '.
            'BEFORE INSERT OR UPDATE ON evidence_files '.
            'FOR EACH ROW EXECUTE FUNCTION evidence_files_account_consistency_check()',
        );
    }

    public function down(): void
    {
        foreach (['audit_events', 'signatures', 'evidence_files'] as $tableName) {
            DB::statement("DROP TRIGGER IF EXISTS {$tableName}_account_consistency ON {$tableName}");
        }

        DB::statement('DROP FUNCTION IF EXISTS evidence_files_account_consistency_check()');
        DB::statement('DROP FUNCTION IF EXISTS signatures_account_consistency_check()');
        DB::statement('DROP FUNCTION IF EXISTS audit_events_account_consistency_check()');

        DB::statement('DROP INDEX IF EXISTS business_industries_one_primary');

        Schema::table('business_industries', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });

        DB::statement('DROP INDEX IF EXISTS account_businesses_one_primary');
        DB::statement('ALTER TABLE user_workplace_access DROP CONSTRAINT IF EXISTS uwa_permission_role_check');
        DB::statement('ALTER TABLE user_business_access DROP CONSTRAINT IF EXISTS uba_permission_role_check');
    }
};
