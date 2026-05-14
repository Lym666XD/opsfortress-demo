<?php

declare(strict_types=1);

namespace App\Domain\Whs\Files\Models;

use App\Domain\OpsFortress\Businesses\Models\Business;
use App\Domain\Shared\Tenancy\BelongsToTenant;
use App\Domain\Whs\Submissions\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileUpload extends Model
{
    use BelongsToTenant;

    protected $table = 'file_uploads';

    protected $fillable = [
        'tenant_id',
        'business_id',
        'submission_id',
        'uploaded_by',
        'category',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'metadata',
    ];

    protected $casts = [
        // FK int casts — PG's PDO driver returns BIGINT as string.
        'tenant_id' => 'integer',
        'business_id' => 'integer',
        'submission_id' => 'integer',
        'uploaded_by' => 'integer',
        'size_bytes' => 'integer',
        'metadata' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
