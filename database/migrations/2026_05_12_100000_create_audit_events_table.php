<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tamper-evident hash-chained audit events.
 *
 * Per WHS_Architecture_Record §3.16 and §3.20:
 *   - Two anchor types: HASH-001 (digital signature finalisation),
 *                       HASH-002 (post-task closeout approval)
 *   - Each new event references the previous event's hash via previous_hash
 *   - On read, recompute the live hash and compare to stored hash to detect tampering
 *
 * Append-only by convention (no UPDATE/DELETE in application code).
 * Indexed by (tenant_id, subject_type, subject_id) for fast trail lookups.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Polymorphic subject — what is this event about?
            // e.g. ('App\Domain\Whs\Submissions\Models\Submission', 1234)
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');

            // Anchor identifies the kind of event. Free-form for forward-compat,
            // but well-known anchors include 'HASH-001' and 'HASH-002'.
            $table->string('anchor', 32);
            $table->string('event_name');

            // The hash chain itself.
            $table->string('hash', 64);                  // SHA-256 of canonical payload + previous_hash
            $table->string('previous_hash', 64)->nullable(); // SHA-256 of previous event in chain (NULL only for first)

            // What was hashed — kept for verifiability.
            $table->jsonb('payload');

            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['tenant_id', 'subject_type', 'subject_id'], 'audit_events_subject_idx');
            $table->index(['tenant_id', 'anchor'], 'audit_events_anchor_idx');
            $table->index('hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
