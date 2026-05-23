<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Domain\OpsFortress\Workplaces\Models\Workplace;
use App\Domain\Shared\Context\AccountContext;
use App\Models\User;
use Database\Seeders\V03DemoSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class V03DevSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('v0.3 dev seeder test requires a PostgreSQL connection.');
        }
    }

    public function test_v03_demo_seed_creates_account_context_and_admin_access(): void
    {
        $this->seed(V03DemoSeeder::class);

        $account = DB::table('customer_accounts')->where('slug', 'acme-construction')->first();
        $this->assertNotNull($account);

        $admin = User::query()->where('email', 'admin@acme.test')->first();
        $this->assertNotNull($admin);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $admin->id,
        );
        $this->assertSame($account->id, $admin->account_id);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertContains('admin', $admin->roleCodes());

        $this->assertDatabaseHas('user_business_access', [
            'account_id' => $account->id,
            'user_id' => $admin->id,
            'permission_role' => 'admin',
            'access_status' => 'active',
        ]);

        app(AccountContext::class)->runAs(
            accountId: $account->id,
            callback: function (): void {
                $this->assertSame(1, Workplace::query()->count());
            },
        );

        $this->assertDatabaseHas('tasks', [
            'external_task_id' => 'TASK-DEMO-001',
            'active_status' => true,
        ]);
        $this->assertDatabaseHas('workplace_environments', [
            'environment_code' => 'construction',
            'environment_name' => 'Construction',
            'active' => true,
        ]);
        $this->assertDatabaseHas('swms_versions', [
            'external_swms_version_id' => 'demo-v1',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('swms_activity_steps', [
            'step_number' => 1,
            'initial_risk_level' => 'medium',
            'residual_risk_level' => 'low',
            'stop_work_trigger' => true,
        ]);
        $this->assertDatabaseHas('prestart_questions', [
            'question_number' => 1,
            'is_critical_failure' => true,
        ]);
        $this->assertDatabaseHas('user_occupations', [
            'account_id' => $account->id,
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('worker_task_sessions', [
            'account_id' => $account->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('swms_step_events', [
            'step_number' => 1,
            'event_type' => 'read_completed',
            'met_minimum_read_time' => true,
        ]);
        $this->assertDatabaseHas('signatures', [
            'account_id' => $account->id,
            'signature_type' => 'swms_acknowledgement',
        ]);
        $this->assertDatabaseHas('prestart_submissions', [
            'account_id' => $account->id,
            'status' => 'submitted',
            'has_critical_failure' => false,
        ]);
        $this->assertDatabaseHas('prestart_responses', [
            'answer_boolean' => true,
            'is_critical_failure' => false,
        ]);
        $this->assertDatabaseHas('evidence_files', [
            'account_id' => $account->id,
            'evidence_type' => 'prestart_photo',
        ]);
        $this->assertDatabaseHas('alerts', [
            'account_id' => $account->id,
            'alert_type' => 'prestart_review',
            'status' => 'open',
        ]);

        $this->assertSame(
            2,
            DB::table('audit_events')
                ->where('account_id', $account->id)
                ->whereNotNull('worker_task_session_id')
                ->count(),
        );
    }
}
