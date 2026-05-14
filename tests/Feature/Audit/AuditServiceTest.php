<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Domain\OpsFortress\Tenancy\Models\Tenant;
use App\Domain\Shared\Audit\Models\AuditEvent;
use App\Domain\Shared\Audit\Services\AuditService;
use App\Domain\Shared\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the hash-chain semantics required by WHS_Architecture_Record §3.16
 * and §3.20:
 *   - first event has previous_hash = NULL
 *   - subsequent events thread previous_hash = prior.hash
 *   - tampering with payload or hash breaks verification
 */
final class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditService $service;
    private TenantContext $context;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AuditService;
        $this->context = app(TenantContext::class);
        $this->context->clear();

        $this->tenant = $this->context->runAs(null, fn () => Tenant::create([
            'slug' => 'audit-tenant',
            'name' => 'Audit Tenant',
        ]));

        $this->context->set($this->tenant->id);
    }

    public function test_first_event_has_null_previous_hash(): void
    {
        $event = $this->service->record(
            subject: $this->tenant,
            anchor: AuditService::ANCHOR_SIGNATURE,
            eventName: 'first_signed',
            payload: ['who' => 'alice', 'when' => '2026-05-12T00:00:00Z'],
        );

        $this->assertNull($event->previous_hash);
        $this->assertSame(64, strlen($event->hash));
    }

    public function test_chain_threads_previous_hash(): void
    {
        $a = $this->service->record($this->tenant, AuditService::ANCHOR_SIGNATURE, 'sign', ['n' => 1]);
        $b = $this->service->record($this->tenant, AuditService::ANCHOR_CLOSEOUT, 'close', ['n' => 2]);
        $c = $this->service->record($this->tenant, AuditService::ANCHOR_CLOSEOUT, 'close2', ['n' => 3]);

        $this->assertSame($a->hash, $b->previous_hash);
        $this->assertSame($b->hash, $c->previous_hash);

        $this->assertNull($this->service->detectTampering($this->tenant));
    }

    public function test_detects_payload_tampering(): void
    {
        $a = $this->service->record($this->tenant, AuditService::ANCHOR_SIGNATURE, 'sign', ['n' => 1]);
        $this->service->record($this->tenant, AuditService::ANCHOR_CLOSEOUT, 'close', ['n' => 2]);

        // Tamper with the first event's payload directly via DB.
        AuditEvent::withoutEvents(fn () => AuditEvent::query()
            ->where('id', $a->id)
            ->update(['payload' => json_encode(['n' => 999])]));

        $tampered = $this->service->detectTampering($this->tenant);

        $this->assertNotNull($tampered);
        $this->assertSame($a->id, $tampered->id);
    }

    public function test_canonical_hash_is_key_order_independent(): void
    {
        // Two payloads with same content, different key insertion order — must hash to same value.
        $a = $this->service->record($this->tenant, AuditService::ANCHOR_SIGNATURE, 'a', ['x' => 1, 'y' => 2]);

        // Reset chain by using a different subject so previous_hash = null on both.
        $tenant2 = $this->context->runAs(null, fn () => Tenant::create([
            'slug' => 'audit-tenant-2',
            'name' => 'Audit 2',
        ]));

        $b = $this->service->record($tenant2, AuditService::ANCHOR_SIGNATURE, 'a', ['y' => 2, 'x' => 1]);

        $this->assertSame($a->hash, $b->hash);
    }
}
