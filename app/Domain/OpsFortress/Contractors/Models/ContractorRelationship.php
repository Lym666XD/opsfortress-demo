<?php

declare(strict_types=1);

namespace App\Domain\OpsFortress\Contractors\Models;

use App\Domain\OpsFortress\BusinessEntities\Models\BusinessEntity;
use App\Domain\Shared\Context\BelongsToAccount;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractorRelationship extends Model
{
    use BelongsToAccount, SoftDeletes, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'access_scope' => 'array',
            'insurance_metadata' => 'array',
            'compliance_metadata' => 'array',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'approved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function hostBusinessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class, 'host_business_entity_id');
    }

    public function contractorBusinessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class, 'contractor_business_entity_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
