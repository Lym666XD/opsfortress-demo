<?php

declare(strict_types=1);

namespace App\Domain\Whs\Tasks\Models;

use App\Domain\OpsFortress\Industries\Models\Industry;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskIndustryAccess extends Model
{
    use SoftDeletes, UsesUuidPrimaryKey;

    protected $table = 'task_industry_access';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }
}
