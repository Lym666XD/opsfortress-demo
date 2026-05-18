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
        $this->assertSame($account->id, $admin->customer_account_id);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertContains('admin', $admin->roleCodes());

        $this->assertDatabaseHas('user_business_access', [
            'customer_account_id' => $account->id,
            'user_id' => $admin->id,
            'permission_role' => 'admin',
            'access_status' => 'active',
        ]);

        app(AccountContext::class)->runAs(
            customerAccountId: $account->id,
            callback: function (): void {
                $this->assertSame(1, Workplace::query()->count());
            },
        );

        $this->assertDatabaseHas('tasks', [
            'task_code' => 'TASK-DEMO-001',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('swms_versions', [
            'external_swms_version_id' => 'demo-v1',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('prestart_questions', [
            'question_number' => 1,
            'is_critical_failure' => true,
        ]);
    }
}
