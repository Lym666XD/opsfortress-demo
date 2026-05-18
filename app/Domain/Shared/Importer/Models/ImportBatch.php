<?php

declare(strict_types=1);

namespace App\Domain\Shared\Importer\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'summary' => 'array',
            'metadata' => 'array',
        ];
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }

    public function sourceFiles(): HasMany
    {
        return $this->hasMany(ImportSourceFile::class);
    }

    public function validationResults(): HasMany
    {
        return $this->hasMany(ImportValidationResult::class);
    }
}
