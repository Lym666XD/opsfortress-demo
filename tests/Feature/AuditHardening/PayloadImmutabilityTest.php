<?php

declare(strict_types=1);

namespace Tests\Feature\AuditHardening;

use App\Domain\OpsFortress\Businesses\Models\Business;
use App\Domain\OpsFortress\Tenancy\Models\Tenant;
use App\Domain\Shared\Tenancy\TenantContext;
use App\Domain\Whs\Activities\Models\Activity;
use App\Domain\Whs\Submissions\Models\Submission;
use App\Domain\Whs\TaskPacks\Models\TaskPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

/**
 * Audit hardening A1: payload JSON columns on Activity and Submission are
 * immutable after creation. Tests both models reject in-place payload edits.
 */
final class PayloadImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    private TenantContext $context;
    private Tenant $tenant;
    private Business $business;
    private User $user;
    private TaskPack $taskPack;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = app(TenantContext::class);
        $this->context->clear();

        $this->tenant = $this->context->runAs(null, fn () => Tenant::create([
            'slug' => 'payload-test',
            'name' => 'Payload Test Tenant',
        ]));

        $this->context->set($this->tenant->id);

        $this->business = Business::create([
            'legal_name' => 'PT Pty Ltd',
            'trading_name' => 'PT',
            'blockchain_id' => substr(md5('pt'), 0, 8),
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'business_id' => $this->business->id,
            'name' => 'PT User',
            'email' => 'pt@test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $this->taskPack = TaskPack::create([
            'tenant_id' => $this->tenant->id,
            'business_id' => $this->business->id,
            'code' => 'pt-pack',
            'title' => 'PT Pack',
            'category' => 'swms',
            'status' => 'active',
        ]);
    }

    public function test_activity_payload_cannot_be_updated_after_create(): void
    {
        $activity = Activity::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'activity_type' => 'prestart',
            'status' => 'completed',
            'payload' => ['question_1' => 'yes', 'question_2' => 'no'],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Activity.payload is immutable');

        $activity->payload = ['question_1' => 'tampered'];
        $activity->save();
    }

    public function test_submission_payload_cannot_be_updated_after_create(): void
    {
        $submission = Submission::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'task_pack_id' => $this->taskPack->id,
            'submission_type' => 'prestart',
            'status' => 'submitted',
            'payload' => ['answer_1' => true, 'answer_2' => false],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Submission.payload is immutable');

        $submission->update(['payload' => ['answer_1' => false]]);
    }

    public function test_other_fields_can_still_be_updated(): void
    {
        // Confirms we didn't accidentally freeze the whole row — only payload.
        $activity = Activity::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'activity_type' => 'prestart',
            'status' => 'pending',
            'payload' => ['x' => 1],
        ]);

        $activity->update(['status' => 'completed']);

        $this->assertSame('completed', $activity->fresh()->status);
    }
}
