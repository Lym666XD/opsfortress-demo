<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Businesses\Models;

use App\Domain\OpsFortress\Workplaces\Models\Workplace;
use App\Domain\Shared\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use RuntimeException;

class Business extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'businesses';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'blockchain_id',
        'legal_name',
        'trading_name',
        'abn',
        'business_type',
        'logo_path',
        'primary_email',
        'primary_phone',
        'status',
        'metadata',
    ];

    /**
     * K3: blockchain_id is internal-only per Kevin's 2026-05-13 spec —
     * never serialised to the frontend, PDFs, exports, or APIs.
     */
    protected $hidden = [
        'blockchain_id',
    ];

    protected $casts = [
        // FK int casts — PG's PDO driver returns BIGINT as string.
        'tenant_id' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * K1 enforcement:
     *   - on `creating`: if blockchain_id wasn't set explicitly, auto-generate a ULID.
     *     ULID is time-ordered, 26 chars, and not derivable from customer data.
     *   - on `updating`: blockchain_id is immutable once set. Mutation throws.
     *
     * Per Kevin's spec: blockchain_id is system-generated, hidden, and
     * must not be derived from ABN / legal_name / tenant_id / email.
     */
    protected static function booted(): void
    {
        static::creating(function (self $business): void {
            if (empty($business->blockchain_id)) {
                $business->blockchain_id = (string) Str::ulid();
            }
        });

        static::updating(function (self $business): void {
            $original = $business->getOriginal('blockchain_id');
            if ($original !== null && $business->isDirty('blockchain_id')) {
                throw new RuntimeException(
                    'Business.blockchain_id is immutable once set. '.
                    'Per Kevin\'s spec (2026-05-13), it is an internal-only audit '.
                    'identifier that must never change.',
                );
            }
        });
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function workplaces(): HasMany
    {
        return $this->hasMany(Workplace::class);
    }
}
