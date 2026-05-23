<?php

declare(strict_types=1);

namespace App\Domain\Shared\Importer\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class ImportValidationResult extends Model
{
    use UsesUuidPrimaryKey;

    private const RULE_CODE_PATTERN = '/^(schema|structure|fk|business|dup)(:[A-Za-z0-9_.-]+)+$/';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (self $validationResult): void {
            if (! preg_match(self::RULE_CODE_PATTERN, (string) $validationResult->rule_code)) {
                throw new InvalidArgumentException('Import validation rule_code must use an approved namespace prefix.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function importSourceFile(): BelongsTo
    {
        return $this->belongsTo(ImportSourceFile::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
