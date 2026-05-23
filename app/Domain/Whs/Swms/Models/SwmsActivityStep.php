<?php

declare(strict_types=1);

namespace App\Domain\Whs\Swms\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SwmsActivityStep extends Model
{
    use SoftDeletes, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'hazards' => 'array',
            'controls' => 'array',
            'required_ppe' => 'array',
            'stop_work_trigger' => 'boolean',
            'evidence_required' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function swmsVersion(): BelongsTo
    {
        return $this->belongsTo(SwmsVersion::class);
    }
}
