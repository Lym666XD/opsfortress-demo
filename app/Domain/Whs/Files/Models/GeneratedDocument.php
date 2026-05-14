<?php

declare(strict_types=1);

namespace App\Domain\Whs\Files\Models;

use App\Domain\OpsFortress\Businesses\Models\Business;
use App\Domain\Shared\Tenancy\BelongsToTenant;
use App\Domain\Whs\Activities\Models\Activity;
use App\Domain\Whs\Submissions\Models\Submission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per architecture record §3.23: "PDF is a management output sent to
 * clients — workers never generate or receive it." Generation runs on the
 * queue (PdfGenerationJob); status transitions queued → generating → ready.
 */
class GeneratedDocument extends Model
{
    use BelongsToTenant;

    protected $table = 'generated_documents';

    protected $fillable = [
        'tenant_id',
        'business_id',
        'submission_id',
        'activity_id',
        'document_type',
        'status',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'generated_at',
    ];

    protected $casts = [
        // FK int casts — PG's PDO driver returns BIGINT as string.
        'tenant_id' => 'integer',
        'business_id' => 'integer',
        'submission_id' => 'integer',
        'activity_id' => 'integer',
        'size_bytes' => 'integer',
        'generated_at' => 'immutable_datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
