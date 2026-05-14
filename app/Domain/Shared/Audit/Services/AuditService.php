<?php

declare(strict_types=1);

namespace App\Domain\Shared\Audit\Services;

use App\Domain\Shared\Audit\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Records hash-chained audit events.
 *
 * Hash construction (SHA-256):
 *   hash = sha256( canonical_json(payload) || '|' || (previous_hash ?? '') )
 *
 * Chain semantics:
 *   - Per (subject_type, subject_id), events form a strict linear chain ordered
 *     by id. Each event's previous_hash MUST equal the prior event's hash.
 *   - The very first event for a subject has previous_hash = NULL.
 *   - Verification recomputes the chain and compares.
 *
 * Concurrency: writes use a SELECT ... FOR UPDATE on the prior event's row to
 * prevent two concurrent writes from both reading the same previous_hash and
 * forking the chain. Postgres transaction isolation handles the rest.
 *
 * Well-known anchors (per WHS_Architecture_Record §3.20):
 *   HASH-001  digital signature finalisation       (worker SWMS workflow)
 *   HASH-002  post-task closeout approval          (worker SWMS workflow)
 *   ADMIN     admin configuration event            (e.g. workplace.created, business.updated)
 *
 * HASH-* anchors are for traceable worker-action events on workflow subjects;
 * ADMIN is for one-off configuration changes that still benefit from a
 * tamper-evident audit trail (legal & compliance review).
 */
final class AuditService
{
    public const ANCHOR_SIGNATURE = 'HASH-001';
    public const ANCHOR_CLOSEOUT = 'HASH-002';
    public const ANCHOR_ADMIN_CONFIG = 'ADMIN';

    /**
     * Record a new audit event for the given subject.
     *
     * @param  array<string, mixed>  $payload  what is being attested to
     */
    public function record(
        Model $subject,
        string $anchor,
        string $eventName,
        array $payload,
        ?int $userId = null,
        ?int $businessId = null,
    ): AuditEvent {
        if (! $subject->exists) {
            throw new RuntimeException('Cannot audit an unsaved subject.');
        }

        return DB::transaction(function () use ($subject, $anchor, $eventName, $payload, $userId, $businessId) {
            $previous = AuditEvent::query()
                ->where('subject_type', $subject::class)
                ->where('subject_id', $subject->getKey())
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $previousHash = $previous?->hash;
            $hash = $this->computeHash($payload, $previousHash);

            return AuditEvent::create([
                'business_id' => $businessId,
                'user_id' => $userId,
                'subject_type' => $subject::class,
                'subject_id' => $subject->getKey(),
                'anchor' => $anchor,
                'event_name' => $eventName,
                'hash' => $hash,
                'previous_hash' => $previousHash,
                'payload' => $payload,
                'occurred_at' => now(),
            ]);
        });
    }

    /**
     * Walk the chain for a subject and return the first tampered event,
     * or null if the chain is intact.
     */
    public function detectTampering(Model $subject): ?AuditEvent
    {
        $events = AuditEvent::query()
            ->where('subject_type', $subject::class)
            ->where('subject_id', $subject->getKey())
            ->orderBy('id')
            ->get();

        $expectedPrevious = null;

        foreach ($events as $event) {
            $expectedHash = $this->computeHash($event->payload, $expectedPrevious);

            if ($event->hash !== $expectedHash || $event->previous_hash !== $expectedPrevious) {
                return $event;
            }

            $expectedPrevious = $event->hash;
        }

        return null;
    }

    /**
     * Canonicalize and hash. JSON_UNESCAPED_SLASHES + sorted keys gives a
     * deterministic byte sequence regardless of array ordering.
     */
    private function computeHash(array $payload, ?string $previousHash): string
    {
        $canonical = $this->canonicalize($payload);
        $material = $canonical.'|'.($previousHash ?? '');

        return hash('sha256', $material);
    }

    private function canonicalize(array $payload): string
    {
        $sort = function (&$value) use (&$sort) {
            if (is_array($value)) {
                ksort($value);
                foreach ($value as &$inner) {
                    $sort($inner);
                }
            }
        };

        $sort($payload);

        return json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }
}
