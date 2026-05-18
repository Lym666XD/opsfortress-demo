<?php

declare(strict_types=1);

namespace App\Domain\Whs\Tasks\Models;

use App\Domain\OpsFortress\Occupations\Models\Occupation;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskOccupationAccess extends Model
{
    use SoftDeletes, UsesUuidPrimaryKey;

    protected $table = 'task_occupation_access';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'active_status' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class);
    }
}
