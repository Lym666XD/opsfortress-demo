<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Workplaces\Models;

use App\Domain\OpsFortress\BusinessEntities\Models\BusinessEntity;
use App\Domain\Shared\Context\BelongsToAccount;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkplaceExternalParty extends Model
{
    use BelongsToAccount, SoftDeletes, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'pdf_recipient' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'active_status' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function workplace(): BelongsTo
    {
        return $this->belongsTo(Workplace::class);
    }

    public function externalBusinessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class, 'external_business_entity_id');
    }
}
