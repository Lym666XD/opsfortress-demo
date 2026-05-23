<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Database\Seeders\V03DemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AppendOnlyEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Append-only enforcement tests require PostgreSQL triggers.');
        }

        $this->seed(V03DemoSeeder::class);
    }

    public function test_audit_events_reject_updates(): void
    {
        $id = $this->insertAuditEvent();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('append-only');

        DB::table('audit_events')->where('id', $id)->update([
            'event_type' => 'tampered',
        ]);
    }

    public function test_audit_events_reject_deletes(): void
    {
        $id = $this->insertAuditEvent();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('append-only');

        DB::table('audit_events')->where('id', $id)->delete();
    }

    public function test_signatures_reject_updates(): void
    {
        $id = $this->insertSignature();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('append-only');

        DB::table('signatures')->where('id', $id)->update([
            'signer_name' => 'Tampered Signer',
        ]);
    }

    public function test_signatures_reject_deletes(): void
    {
        $id = $this->insertSignature();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('append-only');

        DB::table('signatures')->where('id', $id)->delete();
    }

    public function test_evidence_files_reject_updates(): void
    {
        $id = $this->insertEvidenceFile();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('append-only');

        DB::table('evidence_files')->where('id', $id)->update([
            'path' => 'tampered/path.png',
        ]);
    }

    public function test_evidence_files_reject_deletes(): void
    {
        $id = $this->insertEvidenceFile();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('append-only');

        DB::table('evidence_files')->where('id', $id)->delete();
    }

    public function test_audit_events_reject_cross_account_worker_task_sessions(): void
    {
        $sessionId = (string) DB::table('worker_task_sessions')->value('id');
        $accountId = $this->secondAccountId();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('account_id does not match');

        DB::table('audit_events')->insert([
            'id' => (string) Str::uuid(),
            'account_id' => $accountId,
            'worker_task_session_id' => $sessionId,
            'subject_type' => self::class,
            'subject_id' => $sessionId,
            'event_type' => 'append_only_test.cross_account',
            'event_hash' => hash('sha256', $sessionId),
            'hash_algorithm' => 'sha256',
            'hash_sequence' => 1,
            'event_payload' => json_encode(['session_id' => $sessionId], JSON_THROW_ON_ERROR),
            'occurred_at' => now(),
            'created_at' => now(),
        ]);
    }

    public function test_signatures_reject_cross_account_worker_task_sessions(): void
    {
        $sessionId = (string) DB::table('worker_task_sessions')->value('id');

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('account_id does not match');

        DB::table('signatures')->insert([
            'id' => (string) Str::uuid(),
            'account_id' => $this->secondAccountId(),
            'worker_task_session_id' => $sessionId,
            'signature_type' => 'append_only_test',
            'signer_name' => 'Cross Account Signer',
            'signed_at' => now(),
        ]);
    }

    public function test_evidence_files_reject_cross_account_prestart_submissions(): void
    {
        $prestartSubmissionId = (string) DB::table('prestart_submissions')->value('id');

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('account_id does not match');

        DB::table('evidence_files')->insert([
            'id' => (string) Str::uuid(),
            'account_id' => $this->secondAccountId(),
            'prestart_submission_id' => $prestartSubmissionId,
            'evidence_type' => 'append_only_test',
            'disk' => 'local',
            'path' => 'append-only/cross-account.png',
        ]);
    }

    private function insertAuditEvent(): string
    {
        $accountId = $this->accountId();
        $id = (string) Str::uuid();

        DB::table('audit_events')->insert([
            'id' => $id,
            'account_id' => $accountId,
            'subject_type' => self::class,
            'subject_id' => $id,
            'event_type' => 'append_only_test.created',
            'event_hash' => hash('sha256', $id),
            'hash_algorithm' => 'sha256',
            'hash_sequence' => 1,
            'event_payload' => json_encode(['id' => $id], JSON_THROW_ON_ERROR),
            'occurred_at' => now(),
            'created_at' => now(),
        ]);

        return $id;
    }

    private function insertSignature(): string
    {
        $id = (string) Str::uuid();

        DB::table('signatures')->insert([
            'id' => $id,
            'account_id' => $this->accountId(),
            'signature_type' => 'append_only_test',
            'signer_name' => 'Append Only Tester',
            'signed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function insertEvidenceFile(): string
    {
        $id = (string) Str::uuid();

        DB::table('evidence_files')->insert([
            'id' => $id,
            'account_id' => $this->accountId(),
            'evidence_type' => 'append_only_test',
            'disk' => 'local',
            'path' => "append-only/{$id}.png",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function accountId(): string
    {
        return (string) DB::table('customer_accounts')
            ->where('slug', 'acme-construction')
            ->value('id');
    }

    private function secondAccountId(): string
    {
        $id = (string) Str::uuid();

        DB::table('customer_accounts')->insert([
            'id' => $id,
            'slug' => 'cross-account-test',
            'name' => 'Cross Account Test',
            'status' => 'active',
            'timezone' => 'Australia/Melbourne',
            'locale' => 'en-AU',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
