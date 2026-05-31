<?php

declare(strict_types=1);

namespace App\Domain\Shared\Documents\Models;

use App\Domain\OpsFortress\BusinessEntities\Models\BusinessEntity;
use App\Domain\OpsFortress\Jurisdictions\Models\JurisdictionRegulatoryProfile;
use App\Domain\OpsFortress\Workplaces\Models\Workplace;
use App\Domain\Shared\Context\BelongsToAccount;
use App\Domain\Whs\Swms\Models\SwmsVersion;
use App\Domain\Whs\Tasks\Models\Task;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneratedDocument extends Model
{
    use BelongsToAccount, SoftDeletes, UsesUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
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

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function swmsVersion(): BelongsTo
    {
        return $this->belongsTo(SwmsVersion::class);
    }

    public function jurisdictionRegulatoryProfile(): BelongsTo
    {
        return $this->belongsTo(JurisdictionRegulatoryProfile::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    public function deliveryEvents(): HasMany
    {
        return $this->hasMany(DocumentDeliveryEvent::class);
    }
}
